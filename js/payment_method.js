/**
 * @file
 * Presents Braintree's Drop-In UI on the update payment method form.
 */
(function (Drupal) {

  'use strict';

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
  }

  /**
   * Initialize the Drop-In UI and process payment method submission.
   *
   * Used by the payment method update form.
   */
  Drupal.behaviors.braintreeCashierPaymentMethod = {
    attach: function (context, settings) {
      document.querySelector('#submit-button').disabled = true;
      braintree.dropin.create({
        authorization: settings.braintree_cashier.authorizationKey,
        container: '#dropin-container',
        paypal: {
          flow: 'vault'
        }
      }).then(dropinCreateCallback);
    }
  };

  /**
   * Callback when the Dropin instance has been initialized.
   *
   * @param {Dropin} dropinInstance
   *   The Braintree Dropin instance.
   *
   * @see https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html
   */
  function dropinCreateCallback(dropinInstance) {
    var button = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var signupForm = document.querySelector('#payment-method-form');

    button.disabled = false;
    button.addEventListener('click', function (event) {
      event.preventDefault();
      button.disabled = true;
      dropinInstance.requestPaymentMethod().then(function (payload) {
        nonceField.value = payload.nonce;
        signupForm.submit();
      }).catch(function (error) {
        button.disabled = false;
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

})(Drupal);
