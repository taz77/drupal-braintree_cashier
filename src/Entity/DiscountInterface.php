<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Discount entities.
 *
 * @ingroup braintree_cashier
 */
interface DiscountInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Discount name.
   *
   * @return string
   *   Name of the Discount.
   */
  public function getName();

  /**
   * Gets the environment of the discount.
   *
   * @return string
   *   The environment.
   */
  public function getEnvironment();

  /**
   * Sets the Discount name.
   *
   * @param string $name
   *   The Discount name.
   *
   * @return \Drupal\braintree_cashier\Entity\DiscountInterface
   *   The called Discount entity.
   */
  public function setName($name);

  /**
   * Gets the Discount creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Discount.
   */
  public function getCreatedTime();

  /**
   * Sets the Discount creation timestamp.
   *
   * @param int $timestamp
   *   The Discount creation timestamp.
   *
   * @return \Drupal\braintree_cashier\Entity\DiscountInterface
   *   The called Discount entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Discount published status indicator.
   *
   * Unpublished Discount are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Discount is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Discount.
   *
   * @param bool $published
   *   TRUE to set this Discount to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\braintree_cashier\Entity\DiscountInterface
   *   The called Discount entity.
   */
  public function setPublished($published);

  /**
   * Gets the Braintree Discount ID.
   *
   * @return string
   *   The discount ID in the Braintree console.
   */
  public function getBraintreeDiscountId();

}
