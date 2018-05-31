<?php
/**
 * Created by PhpStorm.
 * User: shaundychko
 * Date: 2018-05-31
 * Time: 9:03 AM
 */

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;


use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

class FreeTrialTest extends JavascriptTestBase {

  use BraintreeCashierTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['braintree_cashier'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The account created for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupBraintreeApi();
    $this->createMonthlyFreeTrialBillingPlan();

    $this->account = $this->drupalCreateUser();
    $this->drupalLogin($this->account);
  }

  /**
   * Tests signing up for a free trial.
   *
   * The invoices tab should show that no payments have been made, and that
   * there is an upcoming invoice.
   */
  public function testFreeTrialSignup() {
    $this->drupalGet(Url::fromUri('internal:/plans--sandbox'));
    $this->clickLink('Start Free Trial');
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);
    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 30000);
    $this->assertSession()->pageTextContains('You have been signed up for the Monthly Free Trial plan. Thank you, and enjoy your subscription!');

    // Confirm values on the Invoices tab.
    $this->drupalGet(Url::fromRoute('braintree_cashier.invoices', [
      'user' => $this->account->id(),
    ]));
    $this->assertSession()->elementTextContains('css', '.upcoming-invoice', '$9.00');
    $this->assertSession()->elementTextContains('css', '.payment-history', 'No payments have been made.');
  }

  /**
   * Tests that canceling a free trial makes the subscription status canceled.
   */
  public function testImmediateCancel() {
    $this->testFreeTrialSignup();
    $this->drupalGet(Url::fromRoute('braintree_cashier.cancel_confirm', [
      'user' => $this->account->id(),
    ]));
    $this->getSession()->getPage()->pressButton('Yes, I wish to cancel.');
    $this->assertSession()->elementTextContains('css', '.current-subscription-label', 'None');
  }
}
