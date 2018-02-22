/**
 * @file
 * Presents Braintree's Drop-In UI on the update payment method form.
 */
(function (Drupal) {

  'use strict';

  /**
   * Callback after the Drop-In UI has been initialized and is visible.
   *
   * @param createErr
   * @param {Dropin} dropinInstance
   *   The Braintree Dropin instance.
   *
   * @see https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html
   */
  function braintreeCashierDropinInitialized(createErr, dropinInstance) {

    var button = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var signupForm = document.querySelector('#payment-method-form');

    // Enable the update payment method button.
    button.disabled = false;

    button.addEventListener('click', function (event) {
      event.preventDefault();
      button.disabled = true;
      dropinInstance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
        if (requestPaymentMethodErr) {
          button.disabled = false;
        }
        // Submit payload.nonce to the server
        nonceField.value = payload.nonce;
        signupForm.submit();
      });
    });
    if (typeof dropinInstance !== 'undefined') {
      dropinInstance.on('paymentMethodRequestable', function (event) {
        if (event.type === 'PayPalAccount') {
          button.click();
        }
      });
    }
  }

  /**
   * Initialize the Drop-In UI and process payment method submission.
   *
   * Used by the payment method update form.
   */
  Drupal.behaviors.braintreeCashierPaymentMethod = {
    attach: function (context, settings) {
      braintree.dropin.create({
        authorization: settings.braintree_cashier.authorizationKey,
        container: '#dropin-container',
        paypal: {
          flow: 'vault'
        }
      }, braintreeCashierDropinInitialized(createErr, dropinInstance));
    }
  };

})(Drupal);
