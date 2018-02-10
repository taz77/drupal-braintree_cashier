Braintree Cashier enables recurring subscriptions for Drupal websites. It is inspired by, and borrows some of the software architecture from, Laravel's Cashier Braintree project.
With this module you can

    Configure which Drupal Roles are granted to purchasers.
    Configure which Drupal Roles are revoked when the subscription is canceled.
    Accept payment by either Credit Card or PayPal.

Notes

    Uses the Drop-in UI to collect payment. It is a tokenized payment form provided by Braintree and it ensures that credit card numbers never reach your server. Payment details are sent by the user's browser via JS directly to Braintree, which responds to the user's browser with a unique token representing the payment method that may be used only by your server. The user's browser submits this token to your server, and not the raw payment details.

