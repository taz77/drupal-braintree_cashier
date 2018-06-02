<?php

namespace Drupal\braintree_cashier\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class UpdateExpiryForm extends FormBase {

  /**
   * The user account to operate on.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_credit_card_expiry';
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }


}
