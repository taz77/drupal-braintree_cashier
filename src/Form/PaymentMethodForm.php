<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class PaymentMethodForm.
 */
class PaymentMethodForm extends FormBase {

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApiBraintreeApi;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new PaymentMethodForm object.
   */
  public function __construct(BraintreeApiService $braintree_api_braintree_api, LoggerChannel $logger_channel_braintree_cashier, EntityTypeManagerInterface $entity_type_manager, BillableUser $billable_user) {
    $this->braintreeApiBraintreeApi = $braintree_api_braintree_api;
    $this->logger = $logger_channel_braintree_cashier;
    $this->billableUser = $billable_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('entity_type.manager'),
      $container->get('braintree_cashier.billable_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_method_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {

    $form['#attributes']['id'] = 'payment-method-form';

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['dropin_ui'] = [
      '#markup' => '<div id="dropin-container"></div>',
      '#allowed_tags' => ['div'],
      '#suffix' => '<p>' . t('To update an existing card, please select "Choose another way to pay" and enter the card details again.') . '</p>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'id' => 'submit-button',
        'disabled' => TRUE,
      ],
      '#value' => $this->t('Update payment method'),
    ];

    $form['nonce'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'nonce',
      ],
    ];

    $form['#attached'] = [
      'library' => [
        'braintree_cashier/payment_method',
      ],
      'drupalSettings' => [
        'braintree_cashier' => [
          'authorizationKey' => $this->billableUser->generateClientToken($user),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    if (empty($values['nonce'])) {
      $message = t('The payment method could not be updated.');
      $form_state->setErrorByName('nonce', $message);
      $this->logger->error($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->userStorage->load($values['uid']);
    if (empty($this->billableUser->getBraintreeCustomerId($user))) {
      $result = $this->billableUser->createAsBraintreeCustomer($user, $values['nonce']);
    }
    else {
      $result = $this->billableUser->updatePaymentMethod($user, $values['nonce']);
    }
    if ($result) {
      $message = t('Your payment method has been updated successfully!');

    }
    else {
      $message = t('There was an error updating your payment method. Please try again.');
    }
    drupal_set_message($message);
  }

  /**
   * Access control handler for this route.
   */
  public function accessRoute(AccountInterface $browsing_account, User $user = NULL) {
    $is_allowed = $browsing_account->isAuthenticated() && !empty($user) && ($browsing_account->id() == $user->id() || $browsing_account->hasPermission('administer braintree cashier'));
    return AccessResultAllowed::allowedIf($is_allowed);
  }

}
