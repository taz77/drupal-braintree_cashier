/**
 * @file
 * Presents Braintree's Drop-In UI on the signup form.
 */
(function (Drupal) {

  'use strict';

  /**
   * Initialize the Drop-In UI.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.braintreeCashierSignup = {
    attach: function (context, settings) {
      document.querySelector('#submit-button').disabled = true;
      braintree.dropin.create({
        authorization: settings.braintree_cashier.authorizationKey,
        container: '#dropin-container',
        paypal: {
          flow: 'vault'
        }
      }, dropinCreateCallback);
    }
  };

  /**
   * Callback when the Dropin instance has been initialized.
   *
   * @param error
   *   Notification about an error creating the Dropin instance.
   * @param {Dropin} dropinInstance
   *   The Braintree Dropin instance.
   *
   * @see https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html
   */
  function dropinCreateCallback(error, dropinInstance) {
    // Enabled the sign up button.
    var createNonceButton = document.querySelector('#submit-button');
    console.log('first', dropinInstance);

    createNonceButton.disabled = false;
    createNonceButton.addEventListener('click', function (event) {
      console.log('in click callback', dropinInstance);
      event.preventDefault();
      createNonceButton.disabled = true;
      dropinInstance.requestPaymentMethod(paymentMethodRequestCallback);
    });
  }

  function paymentMethodRequestCallback(error, payload) {
    var createNonceButton = document.querySelector('#submit-button');
    if (error != null) {
      createNonceButton.disabled = false;
      return;
    }
    var nonceField = document.querySelector('#nonce');
    var finalSubmitButton = document.querySelector('#final-submit');
    // Submit payload.nonce to the server
    nonceField.value = payload.nonce;
    finalSubmitButton.click();
  }

})(Drupal);

