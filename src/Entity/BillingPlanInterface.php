<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Billing plan entities.
 *
 * @ingroup braintree_cashier
 */
interface BillingPlanInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Billing plan name.
   *
   * @return string
   *   Name of the Billing plan.
   */
  public function getName();

  /**
   * Sets the Billing plan name.
   *
   * @param string $name
   *   The Billing plan name.
   *
   * @return \Drupal\braintree_cashier\Entity\BillingPlanInterface
   *   The called Billing plan entity.
   */
  public function setName($name);

  /**
   * Gets the environment of the billing plan.
   *
   * @return string
   *   The environment of the billing plan.
   */
  public function getEnvironment();

  /**
   * Gets the Billing plan creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Billing plan.
   */
  public function getCreatedTime();

  /**
   * Sets the Billing plan creation timestamp.
   *
   * @param int $timestamp
   *   The Billing plan creation timestamp.
   *
   * @return \Drupal\braintree_cashier\Entity\BillingPlanInterface
   *   The called Billing plan entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get whether this billing plan is available for purchase.
   *
   * @return bool
   *   A boolean indicating whether the billing plan is available for purchase.
   */
  public function isAvailableForPurchase();

  /**
   * Set whether this billing plan is available for purchase.
   *
   * @param bool $is_available_for_purchase
   *   A boolean indicating whether the billing plan is available for purchase.
   */
  public function setIsAvailableForPurchase($is_available_for_purchase);

  /**
   * Gets the Billing plan description.
   *
   * @return string
   *   Description of the Billing plan.
   */
  public function getDescription();

  /**
   * Gets the billing plan ID.
   *
   * This should match what is configured in the Braintree control panel.
   *
   * @return string
   *   Returns a billing plan machine name.
   */
  public function getBraintreePlanId();

  /**
   * Gets the subscription type to create.
   *
   * @return string
   *   The value of the subscription type.
   */
  public function getSubscriptionType();

  /**
   * Gets the roles to assign to the subscribed user.
   *
   * @return array
   *   A list of role ID's.
   */
  public function getRolesToAssign();

  /**
   * Gets the roles to revoke from the subscribed user.
   *
   * @return array
   *   A list of role ID's.
   */
  public function getRolesToRevoke();

  /**
   * Gets the call to action text.
   *
   * @return string
   *   The call to action text.
   */
  public function getCallToAction();

  /**
   * Get whether this billing plan has a free trial.
   */
  public function hasFreeTrial();

}
