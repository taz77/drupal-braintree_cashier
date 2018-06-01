<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves free trials that will be expiring soon.
 *
 * @QueueWorker(
 *   id = "retrieve_expiring_free_trials",
 *   title = @Translation("Retrieve expiring free trials"),
 *   cron = {"time" = 60}
 * )
 */
class RetrieveExpiringFreeTrials extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * Braintree Cashier configuration.
   *
   * \Drupal\Core\Config\ImmutableConfig
   */
  protected $bcConfig;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The decimal money parser.
   *
   * @var \Money\Parser\DecimalMoneyParser
   */
  protected $moneyParser;

  /**
   * The international money formatter.
   *
   * @var \Money\Formatter\IntlMoneyFormatter
   */
  protected $moneyFormatter;

  /**
   * The Braintree Cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BraintreeApiService $braintreeApi, ConfigFactoryInterface $configFactory, QueueFactory $queueFactory, SubscriptionService $subscriptionService, BraintreeCashierService $bcService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->braintreeApi = $braintreeApi;
    $this->bcConfig = $configFactory->get('braintree_cashier.settings');
    $this->queueFactory = $queueFactory;
    $this->subscriptionService = $subscriptionService;
    $this->bcService = $bcService;

    // Setup Money.
    $currencies = new ISOCurrencies();
    $this->moneyParser = new DecimalMoneyParser($currencies);
    $numberFormatter = new \NumberFormatter($this->bcService->getLocale(), \NumberFormatter::CURRENCY);
    $this->moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('braintree_api.braintree_api'),
      $container->get('config.factory'),
      $container->get('queue'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('braintree_cashier.braintree_cashier_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $lookaheadPeriod = new \DateTime();
    $lookaheadString = '+' . $this->bcConfig->get('free_trial_notification_period') . ' days';
    $lookaheadPeriod->modify($lookaheadString);

    // Find upcoming active subscription on a free trial.
    $braintree_subscriptions = $this->braintreeApi->getGateway()->subscription()->search([
      \Braintree_SubscriptionSearch::status()->in(
        [\Braintree_Subscription::ACTIVE]
      ),
      \Braintree_SubscriptionSearch::inTrialPeriod()->is(true),
      \Braintree_SubscriptionSearch::nextBillingDate()
      ->lessThanOrEqualTo($lookaheadPeriod),
    ]);

    $items = [];
    foreach ($braintree_subscriptions as $braintree_subscription) {
      $subscription_entity = $this->subscriptionService->findSubscriptionEntity($braintree_subscription->id);
//      if (!$subscription_entity->sentFreeTrialExpiringNotification()) {
      if (TRUE) {
        $currency_code = $this->bcConfig->get('currency_code');
        $amount = $this->moneyParser->parse($braintree_subscription->nextBillingPeriodAmount, $currency_code);
        $items[] = [
          'subscription_entity_id' => $subscription_entity->id(),
          'amount' => $this->moneyFormatter->format($amount),
          'currency_code' => $currency_code,
          'next_billing_date' => $braintree_subscription->nextBillingDate->getTimestamp(),
        ];

        // Record sending the notification here since we don't want this
        // subscription to accidentally be added to the queue again before
        // the notification is actually sent.
        $subscription_entity->setSentFreeTrialExpiringNotification(TRUE);
        $subscription_entity->save();
      }
    }

    // Sort by next billing date.
    $next_billing_date_column = [];
    foreach ($items as $key => $item) {
      $next_billing_date_column[$key] = $item['next_billing_date'];
    }
    array_multisort($next_billing_date_column, SORT_DESC, $items);

    $notificationQueue = $this->queueFactory->get('free_trial_expiring_notifier', TRUE);
    foreach ($items as $item) {
      $notificationQueue->createItem($item);
    }
  }

}
