Drupal.behaviors.braintreeCashierPlanSelect = {
  attach: function (context, settings) {
    jQuery('#edit-plan-entity-id').on('change', function() {
      jQuery('#coupon-result').empty();
      jQuery('#coupon-code').val('');
    });
  }
};
