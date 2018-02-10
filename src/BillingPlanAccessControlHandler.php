<?php

namespace Drupal\braintree_cashier;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Billing plan entity.
 *
 * @see \Drupal\braintree_cashier\Entity\BillingPlan.
 */
class BillingPlanAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\braintree_cashier\Entity\BillingPlanInterface $entity */
    switch ($operation) {
      case 'view':
        if ($entity->isAvailableForPurchase()) {
          return AccessResult::allowedIfHasPermission($account, 'view billing plan entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'administer braintree cashier');

      default:
        return AccessResult::allowedIfHasPermission($account, 'administer braintree cashier');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer braintree cashier');
  }

}
