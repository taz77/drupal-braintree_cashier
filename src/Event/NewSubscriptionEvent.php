<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\braintree_cashier\Entity\BillingPlanInterface;
use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Creates an Event when a new subscription is created.
 */
class NewSubscriptionEvent extends Event {

  /**
   * The Braintree Subscription.
   *
   * @var \Braintree_Subscription
   */
  protected $braintreeSubscription;

  /**
   * The Billing Plan entity.
   *
   * @var \Drupal\braintree_cashier\Entity\BillingPlan
   *   The billing plan entity.
   */
  protected $billingPlan;

  /**
   * The subscription entity created.
   *
   * @var \Drupal\braintree_cashier\Entity\SubscriptionInterface
   *   The subscription entity created.
   */
  protected $subscriptionEntity;

  /**
   * NewSubscriptionEvent constructor.
   *
   * @param \Braintree_Subscription $braintree_subscription
   *   The Braintree subscription just created.
   * @param \Drupal\braintree_cashier\Entity\BillingPlanInterface $billing_plan
   *   The billing plan entity used to crete the subscription.
   * @param \Drupal\braintree_cashier\Entity\SubscriptionInterface $subscription_entity
   *   The subscription entity created.
   */
  public function __construct(\Braintree_Subscription $braintree_subscription, BillingPlanInterface $billing_plan, SubscriptionInterface $subscription_entity) {
    $this->braintreeSubscription = $braintree_subscription;
    $this->billingPlan = $billing_plan;
    $this->subscriptionEntity = $subscription_entity;
  }

  /**
   * Gets the Billing Plan entity.
   *
   * @return \Drupal\braintree_cashier\Entity\BillingPlan
   *   The billing plan entity.
   */
  public function getBillingPlan() {
    return $this->billingPlan;
  }

  /**
   * Gets the Braintree subscription.
   *
   * @return \Braintree_Subscription
   *   The Braintree subscription.
   */
  public function getBraintreeSubscription() {
    return $this->braintreeSubscription;
  }

  /**
   * Gets the subscription entity.
   *
   * @return \Drupal\braintree_cashier\Entity\SubscriptionInterface
   *   The subscription entity.
   */
  public function getSubscriptionEntity() {
    return $this->subscriptionEntity;
  }

}
