<?php

namespace Drupal\braintree_cashier\Event;

use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A subscription was canceled by a user.
 */
class SubscriptionCanceledByUserEvent extends Event {

  /**
   * The subscription entity canceled.
   *
   * @var \Drupal\braintree_cashier\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * SubscriptionCanceledByUserEvent constructor.
   *
   * @param \Drupal\braintree_cashier\Entity\SubscriptionInterface $subscription
   *   The subscription entity canceled.
   */
  public function __construct(SubscriptionInterface $subscription) {
    $this->subscription = $subscription;
  }

  /**
   * Gets the subscription entity canceled.
   *
   * @return \Drupal\braintree_cashier\Entity\SubscriptionInterface
   *   The subscription entity canceled.
   */
  public function getSubscription() {
    return $this->subscription;
  }

}
