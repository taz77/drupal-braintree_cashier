/**
 * @file
 * Presents Braintree's Drop-In UI on the signup form.
 */
(function (Drupal) {

  'use strict';

  /**
   * Callback after the Drop-In UI has been initialized and is visible.
   *
   * @param createErr
   * @param {Dropin} dropinInstance
   *
   * @see https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html
   */
  function braintreeCashierDropinInitialized(createErr, dropinInstance) {

    var createNonceButton = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var finalSubmitButton = document.querySelector('#final-submit');

    // Enabled the sign up button.
    createNonceButton.disabled = false;

    createNonceButton.addEventListener('click', function (event) {
      event.preventDefault();
      createNonceButton.disabled = true;
      dropinInstance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
        if (requestPaymentMethodErr) {
          createNonceButton.disabled = false;
        }
        // Submit payload.nonce to the server
        nonceField.value = payload.nonce;
        finalSubmitButton.click();
      });
    });
  }

  /**
   * Initialize the Drop-In UI.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.braintreeCashierSignup = {
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

