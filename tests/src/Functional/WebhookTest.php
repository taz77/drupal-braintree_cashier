<?php

namespace Drupal\Tests\braintree_cashier\Functional;

use Drupal\braintree_cashier\Entity\Subscription;
use Drupal\braintree_cashier\Entity\SubscriptionInterface;
use Drupal\Core\Url;
use Drupal\Tests\braintree_cashier\FunctionalJavascript\BraintreeCashierTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests receiving webhooks from Braintree.
 */
class WebhookTest extends BrowserTestBase {

  use BraintreeCashierTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['braintree_cashier', 'braintree_api_test'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The user id created by this test.
   *
   * @var string
   */
  protected $uid;

  /**
   * The subscription entity id.
   *
   * @var string
   */
  protected $subscriptionEntityId;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $billing_plan = $this->createMonthlyBillingPlan();
    $this->createRole([], 'premium', 'Premium');

    $billing_plan->set('roles_to_assign', ['premium']);
    $billing_plan->set('roles_to_revoke', ['premium']);
    $billing_plan->save();

    $this->uid = $this->drupalCreateUser()->id();

    $subscription_entity = Subscription::create([
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $this->uid,
      'status' => SubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'period_end_date' => time() + 10000,
      'braintree_subscription_id' => '123',
    ]);
    $subscription_entity->save();
    $this->subscriptionEntityId = $subscription_entity->id();
  }

  /**
   * Test subscription canceled webhook.
   *
   * Tests that a webhook received from Braintree that notifies that a
   * subscription has been canceled results in the subscription entity
   * having it's status changed to cancel, along with associated effects such
   * as the user having the appropriate role revoked.
   */
  public function testSubscriptionCanceledWebhook() {
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Confirm that the subscribed user has the premium role.
    /** @var \Drupal\user\Entity\User $account */
    $account = $user_storage->load($this->uid);
    $this->assertTrue($account->hasRole('premium'), 'User has the premium role before webhook received.');

    // Create a sample webhook and submit it. The form will POST to the webhook
    // url /braintree/webhooks, simulating the same POST request from Braintree.
    $sample_notification = \Braintree_WebhookTesting::sampleNotification(\Braintree_WebhookNotification::SUBSCRIPTION_CANCELED, '123');
    $this->drupalPostForm(Url::fromRoute('braintree_api_test.webhook_notification_test_form'), [
      'bt_signature' => $sample_notification['bt_signature'],
      'bt_payload' => $sample_notification['bt_payload'],
    ], 'Submit');
    $this->assertSession()->pageTextContains('Thanks!');

    // Reset the cache and check that the premium role was removed.
    $user_storage->resetCache([$this->uid]);
    $account = $user_storage->load($this->uid);
    $this->assertFalse($account->hasRole('premium'), 'The user does not have the premium role after the "subscription_canceled" webhook was received');
  }

  /**
   * Tests the subscription_expired webhook from Braintree.
   *
   * Tests that a 'subscription_expired' webhook notification from Braintree
   * will result in the corresponding subscription entity having it's status
   * set to 'canceled' and the subscribed user will have the premium role
   * revoked.
   */
  public function testSubscriptionExpiredWebhook() {
    $subscription_storage = $this->container->get('entity_type.manager')->getStorage('subscription');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Confirm that the subscribed user has the premium role.
    /** @var \Drupal\user\Entity\User $account */
    $account = $user_storage->load($this->uid);
    $this->assertTrue($account->hasRole('premium'), 'User has the premium role before webhook received.');

    // Confirm that the subscription is active.
    /** @var \Drupal\braintree_cashier\Entity\SubscriptionInterface $subscription */
    $subscription = $subscription_storage->load($this->subscriptionEntityId);
    $this->assertTrue($subscription->getStatus() == SubscriptionInterface::ACTIVE, 'The subscription is active before expired webhook');

    // Create a sample webhook and submit it. The form will POST to the webhook
    // url /braintree/webhooks, simulating the same POST request from Braintree.
    $sample_notification = \Braintree_WebhookTesting::sampleNotification(\Braintree_WebhookNotification::SUBSCRIPTION_EXPIRED, '123');
    $this->drupalPostForm(Url::fromRoute('braintree_api_test.webhook_notification_test_form'), [
      'bt_signature' => $sample_notification['bt_signature'],
      'bt_payload' => $sample_notification['bt_payload'],
    ], 'Submit');
    $this->assertSession()->pageTextContains('Thanks!');

    // Reset the cache and check that the subscription is canceled.
    $subscription_storage->resetCache([$this->subscriptionEntityId]);
    $subscription = $subscription_storage->load($this->subscriptionEntityId);
    $this->assertTrue($subscription->getStatus() == SubscriptionInterface::CANCELED, 'The subscription is canceled after the expired webhook');

    // Reset the cache and check that the premium role was removed.
    $user_storage->resetCache([$this->uid]);
    $account = $user_storage->load($this->uid);
    $this->assertFalse($account->hasRole('premium'), 'The user does not have the premium role after the "subscription_expired" webhook was received');
  }

}
