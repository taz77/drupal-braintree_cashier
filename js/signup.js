Drupal.behaviors.braintreeCashierSignup = {
  attach: function (context, settings) {
    var createNonceButton = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var finalSubmitButton = document.querySelector('#final-submit');

    braintree.dropin.create({
      authorization: settings.braintree_cashier.authorizationKey,
      container: '#dropin-container',
      paypal: {
        flow: 'vault'
      }
    }, function (createErr, dropinInstance) {
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
    });
  }
};

