/**
 * @file
 * Supports payment forms created with Braintree's Drop-in UI.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.signupForm = {
    attach: function (context, settings) {
      var buttonInitial = $('#submit-button');
      var buttonFinal = $('#final-submit');
      var nonceField = $('#payment-method-nonce');

      var createParams = {
        authorization: drupalSettings.braintree_cashier.authorization,
        container: '#dropin-container'
      };

      if (drupalSettings.braintree_cashier.acceptPaypal) {
        createParams.paypal = {
          flow: 'vault'
        };
      }

      braintree.dropin.create(createParams, function (createErr, instance) {
        buttonInitial.click(function (event) {
          event.preventDefault();

          instance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
            console.log(payload);
            nonceField.val(payload.nonce);
            buttonFinal.click();
          });
        });
      });
    }
  };


})(jQuery, Drupal, drupalSettings);
