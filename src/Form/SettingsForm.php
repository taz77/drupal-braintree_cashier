<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Money\Currencies\ISOCurrencies;
use Money\Currency;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'braintree_cashier.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'braintree_cashier_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('braintree_cashier.settings');

    $form['currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('The <a href="@currency_link">currency code</a> of your default Braintree merchant account. You can see it by clicking "Test Connection" on the <a href="@api_link">Braintree API settings</a>. Defaults to <em>USD</em>. Other examples are <em>EUR</em> for Euros, <em>GBP</em> for British Pounds, <em>CAD</em> for Canadian Dollars.', [
        '@currency_link' => 'https://developers.braintreepayments.com/reference/general/currencies',
        '@api_link' => Url::fromRoute('braintree_api.braintree_api_admin_form')->toString(),
      ]),
      '#default_value' => $config->get('currency_code'),
    ];

    $form['force_locale_en'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force using the <em>en</em> locale'),
      '#description' => $this->t('This is necessary only if your web host does not have the PHP <a href="@url_intl">intl</a> extension and you have therefore run <code>composer require symfony/intl</code>. <a href="@url_symfony_intl">Symfony intl</a> requires the <em>en</em> locale and PHP 7.1.3+. Visit <a href="@php_info">PHP info</a> and search for <em>intl</em> to see if your host already has this extension.', [
        '@url_intl' => 'http://php.net/manual/en/intl.installation.php',
        '@url_symfony_intl' => 'http://symfony.com/doc/current/components/intl.html',
        '@php_info' => Url::fromRoute('system.php')->toString(),
      ]),
      '#default_value' => $config->get('force_locale_en'),
    ];

    $form['generic_declined_message'] = [
      '#type' => 'text_format',
      '#format' => empty($config->get('generic_declined_message')['format']) ? NULL : $config->get('generic_declined_message')['format'],
      '#title' => $this->t('Generic declined message'),
      '#description' => $this->t('The message to display to a user when their payment method is declined while attempting to check out.'),
      '#default_value' => $config->get('generic_declined_message')['value'],
    ];

    $form['invoice_business_information'] = [
      '#type' => 'text_format',
      '#format' => empty($config->get('invoice_business_information')['format']) ? NULL : $config->get('invoice_business_information')['format'],
      '#title' => $this->t('Invoice business information'),
      '#description' => $this->t('Business information to display on invoices, such as the business address.'),
      '#default_value' => $config->get('invoice_business_information')['value'],
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $currencies = new ISOCurrencies();
    $currency = new Currency(strtoupper($values['currency_code']));
    if (!$currency->isAvailableWithin($currencies)) {
      $message = $this->t('Not a valid currency code.');
      $form_state->setErrorByName('currency_code', $message);
      $this->logger->error($message);
    }
    $form_state->setValue('currency_code', strtoupper($values['currency_code']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('braintree_cashier.settings');
    $values = $form_state->getValues();
    $keys = [
      'generic_declined_message',
      'currency_code',
      'invoice_business_information',
      'force_locale_en',
    ];
    foreach ($keys as $key) {
      if (isset($values[$key])) {
        $config->set($key, $values[$key]);
      }
    }
    $config->save();
  }

}
