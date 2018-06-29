<?php

namespace Drupal\Tests\braintree_cashier\Functional;

use Drupal\braintree_cashier\Entity\Discount;
use Drupal\braintree_cashier\Entity\Subscription;
use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Drupal\Core\Url;
use Drupal\Tests\braintree_cashier\FunctionalJavascript\BraintreeCashierTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests braintree cashier admin pages.
 */
class AdminPagesTest extends BrowserTestBase {

  use BraintreeCashierTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['braintree_cashier'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $billing_plan = $this->createMonthlyBillingPlan();
    $account = $this->createUser([], NULL, TRUE);
    Subscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $account->id(),
      'status' => SubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'period_end_date' => time() + 10000,
      'braintree_subscription_id' => '123',
    ])->save();
    Subscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $account->id(),
      'status' => SubscriptionInterface::CANCELED,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'period_end_date' => time() + 10000,
      'braintree_subscription_id' => '123',
    ])->save();
    Discount::create([
      'billing_plan' => [$billing_plan->id()],
      'name' => 'CI Coupon',
      'discount_id' => 'CI_COUPON',
      'environment' => 'sandbox',
      'status' => TRUE,
    ])->save();
    $this->drupalLogin($account);
  }

  /**
   * Tests that the View for administering subscriptions exists.
   *
   * Tests that the View overrides the entity collection route.
   */
  public function testSubscriptionCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.subscription.collection'));
    $this->assertSession()->selectExists('Subscription Type');
    $headers = [
      'Subscribed user',
      'Name',
      'Subscription status',
      'Created',
      'Cancel at period end',
      'Subscription type',
      'Operations links',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

  /**
   * Tests that the View for administering Billing Plans exists.
   *
   * Checks that the View overrides the entity collection route.
   */
  public function testBillingPlanCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.billing_plan.collection'));
    $this->assertSession()->elementContains('css', 'caption:first-of-type', 'Sandbox');
    // Test table headers exist.
    $headers = [
      'Name',
      'Braintree Plan ID',
      'Is available for purchase',
      'Edit',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

  /**
   * Tests that the View for administering Discounts exists.
   *
   * Checks that the View overrides the entity collection route.
   */
  public function testDiscountCollectionExists() {
    $this->drupalGet(Url::fromRoute('entity.discount.collection'));
    $headers = [
      'The discount ID',
      'The billing plans for which this discount is valid',
      'Operations links',
    ];
    foreach ($headers as $header) {
      $this->assertSession()->pageTextContains($header);
    }
  }

}
