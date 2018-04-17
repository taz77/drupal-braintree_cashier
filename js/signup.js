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
    // Enabled the sign up button.
    var createNonceButton = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var finalSubmitButton = document.querySelector('#final-submit');

    createNonceButton.disabled = false;
    createNonceButton.addEventListener('click', function (event) {
      event.preventDefault();
      createNonceButton.disabled = true;
      dropinInstance.requestPaymentMethod().then(function (payload) {
        // Submit payload.nonce to the server
        nonceField.value = payload.nonce;
        finalSubmitButton.click();
      }).catch(function (error) {
        createNonceButton.disabled = false;
      });
    });
  }

})(Drupal);

