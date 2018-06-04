<?php

namespace Drupal\braintree_cashier\Plugin\QueueWorker;

use Drupal\braintree_cashier\Entity\Subscription;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notify users about upcoming free trial expiration.
 *
 * @QueueWorker(
 *   id = "free_trial_expiring_notifier",
 *   title = @Translation("Free trial expiring notifier"),
 *   cron = {"time" = 60}
 * )
 */
class FreeTrialExpiringNotifier extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * FreeTrialExpiringNotifier constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MessageNotifier $messageNotifier) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('message_notify.sender')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $subscription_entity = Subscription::load($data['subscription_entity_id']);
    $message = Message::create([
      'template' => 'free_trial_expiring_notification',
      'uid' => $subscription_entity->getSubscribedUserId(),
      'arguments' => [
        '@free_trial_notification_period' => $this->configFactory->get('braintree_cashier.settings')->get('free_trial_notification_period'),
        '@amount' => $data['amount'],
      ],
      'field_subscription' => $subscription_entity->id(),
    ]);
    $message->save();
    $this->messageNotifier->send($message);
  }

}
