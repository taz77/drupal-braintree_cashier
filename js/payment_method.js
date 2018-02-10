Drupal.behaviors.braintreeCashierPaymentMethod = {
  attach: function (context, settings) {
    var button = document.querySelector('#submit-button');
    var nonceField = document.querySelector('#nonce');
    var signupForm = document.querySelector('#payment-method-form');

    braintree.dropin.create({
      authorization: settings.braintree_cashier.authorizationKey,
      container: '#dropin-container',
      paypal: {
        flow: 'vault'
      }
    }, function (createErr, dropinInstance) {
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
    });
  }
};
