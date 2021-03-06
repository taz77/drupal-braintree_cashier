<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BillingPlanTypeForm.
 */
class BillingPlanTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $billing_plan_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $billing_plan_type->label(),
      '#description' => $this->t("Label for the Billing plan type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $billing_plan_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\braintree_cashier\Entity\BillingPlanType::load',
      ],
      '#disabled' => !$billing_plan_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $billing_plan_type = $this->entity;
    $status = $billing_plan_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Billing plan type.', [
          '%label' => $billing_plan_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Billing plan type.', [
          '%label' => $billing_plan_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($billing_plan_type->toUrl('collection'));
  }

}
