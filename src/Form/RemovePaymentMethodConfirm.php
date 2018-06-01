<?php

namespace Drupal\braintree_cashier\Form;


use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemovePaymentMethodConfirm extends ConfirmFormBase {

  /**
   * The account to operate on.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The Billable User service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_payment_method_confirm';
  }

  /**
   * Class constructor.
   */
  public function __construct(BillableUser $billableUser, BraintreeApiService $braintreeApi) {
    $this->billableUser = $billableUser;
    $this->braintreeApi = $braintreeApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_cashier.billable_user'),
      $container->get('braintree_api.braintree_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $customer = $this->billableUser->asBraintreeCustomer($this->user);
    foreach ($customer->paymentMethods as $paymentMethod) {
      $this->braintreeApi->getGateway()->paymentMethod()->delete($paymentMethod->token);
    }
    $form_state->setRedirect('braintree_cashier.payment_method', [
      'user' => $this->user->id(),
    ]);
    drupal_set_message($this->t('The payment method has been deleted.'));
  }

  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $this->user = $user;
    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove your payment method?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('braintree_cashier.payment_method', [
      'user' => $this->user->id(),
    ]);
  }


}
