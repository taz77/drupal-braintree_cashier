<?php

namespace Drupal\braintree_cashier\Form;


use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateExpiryForm extends FormBase {

  /**
   * The user account to operate on.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_credit_card_expiry';
  }

  /**
   * Class constructor.
   */
  public function __construct(BraintreeApiService $braintreeApi, BillableUser $billableUser) {
    $this->braintreeApi = $braintreeApi;
    $this->billableUser = $billableUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('braintree_cashier.billable_user')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $this->user = $user;
    $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    $month_options = [];
    foreach ($months as $month) {
      $month_options[$month] = $month;
    }
    $form['month'] = [
      '#type' => 'select',
      '#options' => $month_options,
      '#required' => TRUE,
      '#title' => $this->t('Expiration month')
    ];

    $current_date = new \DateTime();
    $current_year = (int) $current_date->format('Y');
    $years = [];
    for ($i = 0; $i < 15; $i++) {
      $years[] = $current_year + $i;
    }
    $year_options = [];
    foreach ($years as $year) {
      $year_options[$year] = $year;
    }
    $form['year'] = [
      '#type' => 'select',
      '#options' => $year_options,
      '#title' => $this->t('Expiration year'),
      '#required' => TRUE,
    ];

    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('braintree_cashier.payment_method', [
        'user' => $user->id(),
      ]),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-danger',
        ],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $has_credit_card = $this->billableUser->getBraintreeCustomerId($this->user) && ($this->billableUser->getPaymentMethod($this->user) instanceof \Braintree_CreditCard);
    if (!$has_credit_card) {
      $form_state->setErrorByName('form_token', 'A valid credit card is missing');
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $credit_card = $this->billableUser->getPaymentMethod($this->user);
    $result = $this->braintreeApi->getGateway()->paymentMethod()->update($credit_card->token, [
      'expirationMonth' => $form_state->getValue('month'),
      'expirationYear' => $form_state->getValue('year'),
      'billingAddress' => [
        'postalCode' => $form_state->getValue('postal_code'),
      ],
    ]);
    if (!empty($result)) {
      $form_state->setRedirect('braintree_cashier.payment_method', [
        'user' => $this->user->id(),
      ]);
      drupal_set_message($this->t('The expiry date has been updated. Thank you!'));
    }
  }


}
