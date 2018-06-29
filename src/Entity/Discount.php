<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Discount entity.
 *
 * @ingroup braintree_cashier
 *
 * @ContentEntityType(
 *   id = "discount",
 *   label = @Translation("Discount"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\braintree_cashier\DiscountListBuilder",
 *     "views_data" = "Drupal\braintree_cashier\Entity\DiscountViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\braintree_cashier\Form\DiscountForm",
 *       "add" = "Drupal\braintree_cashier\Form\DiscountForm",
 *       "edit" = "Drupal\braintree_cashier\Form\DiscountForm",
 *       "delete" = "Drupal\braintree_cashier\Form\DiscountDeleteForm",
 *     },
 *     "access" = "Drupal\braintree_cashier\DiscountAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\braintree_cashier\DiscountHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "discount",
 *   admin_permission = "administer discount entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/braintree-cashier/discount/discount/{discount}",
 *     "add-form" = "/braintree-cashier/discount/discount/add",
 *     "edit-form" = "/braintree-cashier/discount/discount/{discount}/edit",
 *     "delete-form" = "/braintree-cashier/discount/discount/{discount}/delete",
 *     "collection" = "/braintree-cashier/discount-list",
 *   },
 *   field_ui_base_route = "discount.settings"
 * )
 */
class Discount extends ContentEntityBase implements DiscountInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getEnvironment() {
    return $this->get('environment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBraintreeDiscountId() {
    return $this->get('discount_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Discount entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_plan'] = BaseFieldDefinition::create('entity_reference')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setLabel(t('The billing plans for which this discount is valid.'))
      ->setDescription(t('Be sure to select billing plans in the same environment'))
      ->setSetting('target_type', 'billing_plan')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'weight' => 0,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The human name of the Discount entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('The discount ID.'))
      ->addConstraint('UniqueField', [])
      ->setDescription(t('This ID must match that in the Braintree control panel.'))
      ->setDisplayOptions('form', [
        'weight' => -1,
      ]);

    $fields['environment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Environment'))
      ->setDescription(t('The Braintree environment for this billing plan.'))
      ->setSetting('allowed_values', [
        BraintreeApiService::ENVIRONMENT_PRODUCTION => t('Production'),
        BraintreeApiService::ENVIRONMENT_SANDBOX => t('Sandbox'),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'weight' => -3,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Discount is published. Only published discounts may be applied to new subscriptions. The setting here has no affect on existing subscriptions.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 1,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
