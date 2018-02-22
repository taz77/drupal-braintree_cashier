INTRODUCTION
------------

Braintree Cashier enables recurring subscriptions by accepting a credit/debit
card or PayPal. It requires using the Braintree payment gateway.

With this module you can:
 * Accept payment by either Credit Card or PayPal.
 * Configure which Drupal Roles are granted to purchasers.
 * Configure which Drupal Roles are revoked when the subscription has expired.


REQUIREMENTS
------------

 * You must configure your Braintree account to accept PayPal in addition to
   cards.
 * You should make your website accessible only by HTTPS.


DEPENDENCIES
------------

 * Message - https://drupal.org/project/message
 * Token - https://drupal.org/project/token.
 * Braintree API - https://drupal.org/project/braintree_api
 * Money PHP - http://moneyphp.org/en/latest/
 * Dom PDF - https://github.com/dompdf/dompdf
 * PHP Dot Env is a developer dependency for running automated functional tests
   https://github.com/vlucas/phpdotenv


INSTALLATION
------------

Install this module using `composer require drupal/braintree_cashier` in order
to pick up dependencies, such as the Braintree PHP SDK, Money PHP, and Dom PDF.


CONFIGURATION
-------------

See the installation walkthrough at
https://www.drupal.org/docs/8/modules/braintree-cashier/installation-walkthrough


TROUBLESHOOTING
---------------

When entering your private key using the Key module, if you're using a file
provider to store your private key be sure to select the option to strip away
line endings, otherwise Webhooks received from Braintree will not validate.


MAINTAINERS
-----------

Current maintainers:
  * Shaun Dychko (ShaunDychko) - https://www.drupal.org/u/shaundychko

This project has been sponsored by:
  * College Physics Answers - Screencast solutions to physics problems in
    the OpenStax College Physics textbook.
    Visit https://collegephysicsanswers.com
