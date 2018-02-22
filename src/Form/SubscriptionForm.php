<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Subscription edit forms.
 *
 * @ingroup braintree_cashier
 */
class SubscriptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\braintree_cashier\Entity\Subscription */
    $form = parent::buildForm($form, $form_state);

    $form['period_end_date']['widget']['#after_build'][] = [get_class($this), 'setPeriodEndDateDescription'];

    $form['braintree_subscription_id']['#states'] = [
      'enabled' => [
        ':input[name=subscription_type]' => [
          ['value' => SubscriptionInterface::PAID_INDIVIDUAL],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Subscription.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Subscription.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.subscription.canonical', ['subscription' => $entity->id()]);
  }

  /**
   * Sets the description for period end date field.
   *
   * This is an #after_build callback, which is needed since the #description
   * doesn't work for this field type.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return array
   *   The element array.
   */
  public static function setPeriodEndDateDescription(array $element, FormStateInterface $form_state) {
    $element[0]['value']['#description'] = t('The end date of the current subscription period. Subscriptions can still legitimately be active past this date depending on your Braintree payment retry logic in case a payment has failed.');
    return $element;
  }

}
