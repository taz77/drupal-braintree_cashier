<?php

namespace Drupal\braintree_cashier;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\BraintreeCustomerCreatedEvent;
use Drupal\braintree_cashier\Event\BraintreeErrorEvent;
use Drupal\braintree_cashier\Event\PaymentMethodUpdatedEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

/**
 * BillableUser class provides functions that apply to the user entity.
 *
 * @ingroup braintree_cashier
 */
class BillableUser {

  use StringTranslationTrait;

  /**
   * The Braintree Cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * Event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApiService;

  /**
   * BillableUser constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Braintree Cashier logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $bcService
   *   The braintree cashier service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   The container aware event dispatcher.
   * @param \Drupal\braintree_api\BraintreeApiService
   *   The Braintree API service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, BraintreeCashierService $bcService, ContainerAwareEventDispatcher $eventDispatcher, BraintreeApiService $braintreeApiService) {
    $this->logger = $logger;
    $this->subscriptionStorage = $entity_type_manager->getStorage('subscription');
    $this->bcService = $bcService;
    $this->eventDispatcher = $eventDispatcher;
    $this->braintreeApiService = $braintreeApiService;
  }

  /**
   * Updates the payment method for the provided user entity.
   *
   * Deletes the previous payment method so that only one is kept on file.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $nonce
   *   The payment method nonce from the Braintree Drop-in UI.
   *
   * @return bool
   *   A boolean indicating whether the update was successful.
   */
  public function updatePaymentMethod(User $user, $nonce) {

    $customer = $this->asBraintreeCustomer($user);

    // verifyCard is not an option here since the Drop-in UI is already
    // configured to verify the card, and we don't want users to see two
    // authorizations on their credit card accounts.
    // @see ::generateClientToken
    $payload = [
      'customerId' => $customer->id,
      'paymentMethodNonce' => $nonce,
      'options' => [
        'makeDefault' => TRUE,
      ],
    ];
    $result = $this->braintreeApiService->getGateway()->paymentMethod()->create($payload);

    if (!$result->success) {
      $this->logger->error('Error creating payment method: ' . $result->message);
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
      if (!empty($result->creditCardVerification)) {
        $credit_card_verification = $result->creditCardVerification;
        if ($credit_card_verification->status == 'processor_declined') {
          $this->bcService->handleProcessorDeclined($credit_card_verification->processorResponseCode, $credit_card_verification->processorResponseText);
        }
        if ($credit_card_verification->status == 'gateway_rejected') {
          $this->bcService->handleGatewayRejected($credit_card_verification->gatewayRejectionReason);
        }
      }
      else {
        drupal_set_message($this->t('Error: @message', ['@message' => $result->message]));
      }
      return FALSE;
    }

    $this->updateSubscriptionsToPaymentMethod($user, $result->paymentMethod->token);
    $this->removeNonDefaultPaymentMethods($user);

    $payment_method_type = get_class($result->paymentMethod);

    $event = new PaymentMethodUpdatedEvent($user, $payment_method_type);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::PAYMENT_METHOD_UPDATED, $event);

    return TRUE;
  }

  /**
   * Gets the Braintree customer.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return \Braintree\Customer
   *   The Braintree customer object.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function asBraintreeCustomer(User $user) {
    return $this->braintreeApiService->getGateway()->customer()->find($this->getBraintreeCustomerId($user));
  }

  /**
   * Gets the Braintree customer ID.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return string
   *   The Braintree customer ID.
   */
  public function getBraintreeCustomerId(User $user) {
    return $user->get('braintree_customer_id')->value;
  }

  /**
   * Updates all subscriptions to use the payment method with the given token.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $token
   *   The payment method token.
   */
  public function updateSubscriptionsToPaymentMethod(User $user, $token) {
    foreach ($this->getSubscriptions($user) as $subscription_entity) {
      /* @var $subscription_entity \Drupal\braintree_cashier\Entity\SubscriptionInterface */
      $this->braintreeApiService->getGateway()->subscription()->update(
        $subscription_entity->getBraintreeSubscriptionId(), [
        'paymentMethodToken' => $token,
      ]);
    }
  }

  /**
   * Gets the subscription entities for a user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param bool $active
   *   Whether to return only subscriptions that are currently active.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of subscription entities.
   */
  public function getSubscriptions(User $user, $active = TRUE) {
    $query = $this->subscriptionStorage->getQuery();
    $query->condition('subscribed_user.target_id', $user->id());
    if ($active) {
      $query->condition('status', SubscriptionInterface::ACTIVE);
    }
    $result = $query->execute();
    if (!empty($result)) {
      return $this->subscriptionStorage->loadMultiple($result);
    }
    return [];
  }

  /**
   * Remove non-default payment methods.
   *
   * This keeps the Drop-in UI simple since otherwise all payment methods are
   * always shown.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function removeNonDefaultPaymentMethods(User $user) {
    $customer = $this->asBraintreeCustomer($user);

    foreach ($customer->paymentMethods as $paymentMethod) {
      if (!$paymentMethod->isDefault()) {
        $this->braintreeApiService->getGateway()->paymentMethod()->delete($paymentMethod->token);
      }
    }
  }

  /**
   * Gets a Braintree payment method.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return \Braintree_PaymentMethod
   *   The Braintree payment method object.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function getPaymentMethod(User $user) {
    $customer = $this->asBraintreeCustomer($user);
    foreach ($customer->paymentMethods as $paymentMethod) {
      if ($paymentMethod->isDefault()) {
        return $paymentMethod;
      }
    }
  }

  /**
   * Creates a new Braintree customer.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $nonce
   *   The payment method nonce from the Drop-in UI.
   *
   * @return \Braintree_Customer|bool
   *   The Braintree customer object.
   */
  public function createAsBraintreeCustomer(User $user, $nonce) {
    $result = $this->braintreeApiService->getGateway()->customer()->create([
      'firstName' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'paymentMethodNonce' => $nonce,
      'creditCard' => [
        'options' => [
          'verifyCard' => TRUE,
        ],
      ],
    ]);

    if (!$result->success) {
      $this->logger->error('Error creating Braintree customer: ' . $result->message);
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
      if (!empty($result->creditCardVerification)) {
        $credit_card_verification = $result->creditCardVerification;
        if ($credit_card_verification->status == 'processor_declined') {
          $this->bcService->handleProcessorDeclined($credit_card_verification->processorResponseCode, $credit_card_verification->processorResponseText);
        }
        if ($credit_card_verification->status == 'gateway_rejected') {
          $this->bcService->handleGatewayRejected($credit_card_verification->gatewayRejectionReason);
        }
      }
      else {
        drupal_set_message($this->t('Card declined: @message', ['@message' => $result->message]));
      }
      return FALSE;
    }
    $user->set('braintree_customer_id', $result->customer->id);
    $user->save();

    $event = new BraintreeCustomerCreatedEvent($user);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_CUSTOMER_CREATED, $event);

    return $result->customer;
  }

  /**
   * Sets the user-provided invoice billing information.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity with the provided billing information.
   * @param string $billing_information
   *   The billing information.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function setInvoiceBillingInformation(User $user, $billing_information) {
    $user->set('invoice_billing_information', $billing_information);
    $user->save();
    return $user;
  }

  /**
   * Gets the user-provided invoice billing information for the user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return string
   *   The billing information markup.
   */
  public function getInvoiceBillingInformation(User $user) {
    return check_markup($user->get('invoice_billing_information')->value, $user->get('invoice_billing_information')->format);
  }

  /**
   * Gets the user's billing information as plain text.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return mixed
   *   The plain text billing information.
   */
  public function getRawInvoiceBillingInformation(User $user) {
    return $user->get('invoice_billing_information')->value;
  }

  /**
   * Generate client token for the Drop-in UI for the provided user entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity which might have a Braintree customer ID.
   * @param int $version
   *   The Braintree API version.
   *
   * @see https://developers.braintreepayments.com/reference/request/client-token/generate/php#version
   *   For documentation about the version.
   *
   * @return string
   *   The Braintree Client Token.
   */
  public function generateClientToken(User $user = NULL, $version = 3) {
    $version = $this->sanitizeVersion($version);
    try {
      if (!empty($user) && !empty($this->getBraintreeCustomerId($user))) {
        return $this->braintreeApiService->getGateway()->clientToken()->generate([
          'customerId' => $this->getBraintreeCustomerId($user),
          'version' => $version,
          'options' => [
            'verifyCard' => TRUE,
            'makeDefault' => TRUE,
          ],
        ]);
      }
      else {
        return $this->generateAnonymousClientToken($version);
      }
    }
    catch (\InvalidArgumentException $e) {
      // The customer id provided probably doesn't exist with Braintree.
      $this->logger->error('InvalidArgumentException occurred in generateClientToken: ' . $e->getMessage());
      drupal_set_message($this->t('Our payment processor reported the following error: %error. Please contact the site administrator.', [
        '%error' => $e->getMessage(),
      ]), 'error');
    }
    catch (\Exception $e) {
      // There was probably an API error of some kind. Either API credentials
      // are not configured properly, or there's an issue with Braintree.
      $this->logger->error('Exception in generateClientToken(): ' . $e->getMessage());
      drupal_set_message($this->t('Our payment processor reported the following error: %error. Please try reloading the page.', ['%error' => $e->getMessage()]), 'error');
    }
  }

  /**
   * Sanitizes the version number.
   *
   * @param int $version
   *   The Braintree API version.
   *
   * @return int
   *   A version which is guaranteed to be valid.
   */
  private function sanitizeVersion($version) {
    if (!in_array($version, [1, 2, 3])) {
      $version = 3;
    }
    return $version;
  }

  /**
   * Generates an anonymous Braintree client token for the Drop-in UI.
   *
   * @param int $version
   *   The Braintree API version.
   *
   * @see https://developers.braintreepayments.com/reference/request/client-token/generate/php#version
   *   For documentation about the version.
   *
   * @return string
   *   The Braintree Client Token.
   */
  public function generateAnonymousClientToken($version = 3) {
    $version = $this->sanitizeVersion($version);
    try {
      return $this->braintreeApiService->getGateway()->clientToken()->generate([
        'version' => $version,
      ]);
    }
    catch (\Exception $e) {
      // There was probably an API error of some kind. Either API credentials
      // are not configured properly, or there's an issue with Braintree.
      $this->logger->error('Exception in generateClientToken(): ' . $e->getMessage());
      drupal_set_message($this->t('Our payment processor reported the following error: %error. Please try reloading the page.', ['%error' => $e->getMessage()]), 'error');
    }
  }

}
