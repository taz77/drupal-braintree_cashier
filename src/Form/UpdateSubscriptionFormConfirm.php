<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\Entity\BillingPlanInterface;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\NewSubscriptionEvent;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller to confirm a change to an existing subscription.
 *
 * @ingroup braintree_cashier
 */
class UpdateSubscriptionFormConfirm extends ConfirmFormBase {

  /**
   * Drupal\braintree_cashier\BillableUser definition.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;
  /**
   * Drupal\braintree_cashier\SubscriptionService definition.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;
  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;
  /**
   * Drupal\braintree_cashier\BraintreeCashierService definition.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The user entity for which a subscription is being changed.
   *
   * @var \Drupal\user\Entity|User
   */
  protected $account;

  /**
   * The Billing Plan entity to which the user's subscription will be updated.
   *
   * @var \Drupal\braintree_cashier\Entity\BillingPlanInterface
   */
  protected $billingPlan;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * UpdateSubscriptionFormConfirm constructor.
   *
   * @param \Drupal\braintree_cashier\BillableUser $braintree_cashier_billable_user
   *   The billable user service.
   * @param \Drupal\braintree_cashier\SubscriptionService $braintree_cashier_subscription_service
   *   The subscription service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The braintree_cashier logger channel.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $braintree_cashier_braintree_cashier_service
   *   The generic braintree_cashier service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_api\BraintreeApiService $braintree_api
   *   The braintree API service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(BillableUser $braintree_cashier_billable_user, SubscriptionService $braintree_cashier_subscription_service, LoggerChannelInterface $logger, BraintreeCashierService $braintree_cashier_braintree_cashier_service, RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager, BraintreeApiService $braintree_api, EventDispatcherInterface $eventDispatcher) {
    $this->billableUser = $braintree_cashier_billable_user;
    $this->subscriptionService = $braintree_cashier_subscription_service;
    $this->logger = $logger;
    $this->bcService = $braintree_cashier_braintree_cashier_service;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_cashier.billable_user'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('braintree_api.braintree_api'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_subscription_form_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to switch your subscription plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Your new plan will be: %plan_description', [
      '%plan_description' => $this->billingPlan->getDescription(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL, BillingPlanInterface $billing_plan = NULL, $coupon_code = NULL) {

    $this->account = $user;
    $this->billingPlan = $billing_plan;
    $form['coupon_code'] = [
      '#type' => 'value',
      '#value' => $coupon_code,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!empty($form_state->getValue('coupon_code')) && !$this->bcService->discountExists($this->billingPlan, $form_state->getValue('coupon_code'))) {
      $form_state->setErrorByName('coupon_code', t('The coupon code %coupon_code is invalid', [
        '%coupon_code' => $form_state->getValue('coupon_code'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]);
    $success_message = t('Your subscription has been updated!');
    if (!empty($subscriptions = $this->billableUser->getSubscriptions($this->account))) {
      // An active subscription exists, so swap it.
      if (count($subscriptions) > 1) {
        $message = 'An error has occurred. You have multiple active subscriptions. Please contact a site administrator.';
        drupal_set_message($message);
        $this->logger->emergency($message);
        $this->bcService->sendAdminErrorEmail($message);
        return;
      }
      $subscription = array_shift($subscriptions);
      if ($this->subscriptionService->isBraintreeManaged($subscription)) {
        $result = $this->subscriptionService->swap($subscription, $this->billingPlan, $this->account);
        if (!empty($result)) {
          drupal_set_message($success_message);
          return;
        }
        else {
          drupal_set_message(t('There was an error updating your subscription.'), 'error');
          $this->logger->error(t('Error updating subscription with entity ID: %subscription_id, for target billing plan: %billing_plan_id, for user ID: %uid', [
            '%subscription_id' => $subscription->id(),
            '%billing_plan_id' => $this->billingPlan->id(),
            '%uid' => $this->account->id(),
          ]));
          return;
        }
      }
      else {
        $this->subscriptionService->cancelNow($subscription);
      }
    }

    $payment_method = $this->billableUser->getPaymentMethod($this->account);
    $coupon_code = $form_state->getValue('coupon_code');
    if (empty($braintree_subscription = $this->subscriptionService->createBraintreeSubscription($this->account, $payment_method->token, $this->billingPlan, [], $coupon_code))) {
      drupal_set_message(t('You have not been charged.'), 'error');
      return;
    }

    $subscription_entity = $this->subscriptionService->createSubscriptionEntity($this->billingPlan, $this->account, $braintree_subscription);
    if (!$subscription_entity) {
      // A major constraint violation occurred while creating the
      // subscription.
      $message = t('An error occurred while creating the subscription. Unfortunately your payment method has already been charged. The site administrator has been notified, but you might wish to contact him or her yourself to troubleshoot the issue.');
      drupal_set_message($message, 'error');
      $this->logger->emergency($message);
      $this->bcService->sendAdminErrorEmail($message);
      return;
    }

    $new_subscription_event = new NewSubscriptionEvent($braintree_subscription, $this->billingPlan, $subscription_entity);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::NEW_SUBSCRIPTION, $new_subscription_event);

    drupal_set_message($success_message);
  }

}
