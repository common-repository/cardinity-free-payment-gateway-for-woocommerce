=== Cardinity Payment Gateway for WooCommerce ===
Contributors: cardinity, ignasr
Tags: cardinity, cardinity checkout, payment gateway, payments, woocommerce, e-commerce
Requires at least: 4.0.2
Tested up to: 6.7
Stable tag: 3.3.3
Requires PHP: 7.2.5
License: GPLv2 or later

Add Cardinity checkout form to your WooCommerce site and start accepting payments.

== Description ==

= Online Card Payment Processing Provider. =
Accept the most popular international and local credit and debit cards on your e-commerce website with our WooCommerce extension:

* Visa, Visa Debit, VPay
* Mastercard, Mastercard Debit
* Maestro
* Diners Club International
* Dankort
* Nexi (former CartaSi)
* Carte Bleue

Cardinity is a licensed payment institution registered on VISA Europe and MasterCard International associations. No matter whether you have an individual activity or a company, feel free to apply for the Cardinity merchant account. Our secure services encompass the whole cycle including a payment gateway, transaction processing, an acquiring bank, and a merchant account. Increase your sales volume and profit with Cardinity – the friendliest online payment system in the European marketplace.

Cardinity charges less per transaction than most payment service providers. No setup fees. No monthly fees. You will get all the useful features for free:

* Global processing
* Different currencies
* Recurring billing for subscriptions
* One-click payments
* In-app/mobile payments
* 3D Secure authentication tool
* Integration support

Apply For Your Merchant Account Now – [cardinity.com](https://cardinity.com) . It’s FREE!

== Installation ==

The integration process with Cardinity is smooth and easy. You can integrate by choosing one of the two ways.

= Install from WordPress Plugin Store: =

1. Navigate to your e-shop’s admin area.
2. Navigate to Plugins → Add New.
3. Type "Cardinity Payment Gateway for WooCommerce" into the search bar.
4. Cardinity Plugin for WooCommerce should appear as a search result. Click "Install Now".
5. If the installation was successful, Activate button should appear. Click the button.
6. Once the plugin is activated, you can see it in the Plugins list.
7. The next step is plugin configuration. Navigate to WooCommerce → Settings. Click on the Checkout tab, select Cardinity Payment Gateway, then check Enable Cardinity gateway checkbox, enter the Consumer key and Consumer secret you’ve received from Cardinity and click Save.
8. That’s it. You can start receiving Credit/Debit Card payments using Cardinity services!

= Install manually: =

1. Navigate to the WordPress Plugin Website and download the latest version of plugin by clicking the Download button.
2. Sign in into your e-shop member’s area.
3. Then navigate to Plugins→ Add New.
4. Click on the Upload Plugin link located at the top of the page.
5. On the next window click Choose File (there you will need to choose the Cardinity Woocommerce module which you’ve downloaded from the Cardinity website) and press Install Now.
6. If everything goes well, you will see the Activate Plugin link on the next page. This means that the Cardinity payment module was successfully installed.
7. Click the Activate Plugin link on the page you were redirected to after module’s installation.
8. Once the plugin is activated, you can see it in the Plugins list
9. The next step is plugin configuration. Navigate to WooCommerce → Settings.
10. Click on the Checkout tab, select Cardinity Payment Gateway, then check Enable Cardinity gateway checkbox, enter Consumer key and Consumer secret you’ve received from Cardinity, and click Save.
11. That’s it. You can start receiving Credit/Debit Card payments using Cardinity services!

Note: If you have disabled billing address, or made it optional on checkout you need to make sure you have a default country set on the wcommerce settings.
Go to Wcommerce Settings > General > Default customer location and set it to either "Shop base address" or use "Geolocate"

SSL note: Cardinity requires that any page hosting a live checkout form be SSL (they should start with `https://`).

== Frequently Asked Questions ==

= Does this module support recurring billing? =

Yes, it is fully compatible with WooCommerce Subscriptions. Cardinity can enable recurring payments for subscriptions or memberships. Cardinity uses the tokenization method. The system encrypts the data and creates a payment reference ID which is later used for recurring billing on a periodic basis.

= What are the fraud prevention methods used by Cardinity? =

Cardinity conducts regular checks to identify potentially fraudulent activities. We advise every merchant to activate the 3D Secure authentication tool. We enable it for free. Most importantly, Cardinity is fully compliant with PCI DSS standards.

= Do you require an SSL certificate? =

An SSL certificate is a must for every merchant.

= Where is the integration documentation available? =

You can find the integration information [here](https://cardinity.com/developers/module/woocommerce)

= Does this module work with both LIVE and TEST keys? =

Yes, the module can work in both modes depending on the keys you use.

== Screenshots ==

1. Cardinity Payment Gateway for WooCommerce Settings
2. Cardinity Payment Gateway for WooCommerce Checkout
3. Cardinity Payment Gateway for WooCommerce External Hosted Checkout
4. Cardinity Payment Gateway for WooCommerce External Hosted Payment Page

== Changelog ==

= 3.3.3

 * Updated compatibility versions

= 3.3.2

 * Bugfix for mobile number with leading '+' causing bad request on hosted payment.

= 3.3.1

 * Bugfix for thousand seperator getting added on hosted checkout.

= 3.3.0

 * Updated hosted payment to implement notification endpoint.

= 3.2.6

 * Added additional card holder info on 3DS Authentication Requests

= 3.2.5

 * Added payment gateway block to support block checkout through hosted payment.

= 3.2.4

 * Bugfix hosted response attempt

= 3.2.3

 * Removed checkout extra texts before redirect

= 3.2.2

 * Updated validation on card holder

= 3.2.1

 * Added option to enable disable Card Holder Input

= 3.2.0

 * Update to support woocommerce HPOS system

= 3.1.5

 * Checkout UI margin fix for when using external checkout

= 3.1.4

  * Removed currency restriction to USD, EUR, GBP

= 3.1.3

  * Added new log to Recurring transaction processing
  * Fallback to use wc_get_customer_default_location when no country found

= 3.1.2

  * Bugfix on recurring subscription payments. Update Cardinity SDK to 3.0.6.

= 3.1.1

  * Tested upto WP 6.1.1 and WCommerce 7.3.0. Updated compatibility version on marketplace

= 3.1.0

  * Updated card holder name to accept unicode names

= 3.0.9

  * Added card holder name input on checkout
  * Added review notice

= 3.0.8

  * Added fallback to use orderid from callback url in case response missing

= 3.0.7

  * Updated function prefixes to avoid conflict

= 3.0.6

  * Security fix on external request

= 3.0.5

  * bugfix admin visibility on currency switcher
  * bugfix front end visibility of Description

= 3.0.4.1

  * site url fix for compatibility with multi language module

= 3.0.4

  * Encoded ThreeDSSessionData with base64 url

= 3.0.3.2

  * Bugfix on external has fields reversed logic

= 3.0.3.1

  * Bugfix on shop manager permissions.

= 3.0.3

  * Bugfix and vendor updated to latest sdk

= 3.0.2

  * Added transaction history, viewable from admin

= 3.0.1 =

  * Fallback into 3D secure v1 added in case of failed response in 3D secure v2.

= 3.0.0 =

  * Cardinity SDK updated to v3
  * 3ds v2 Implemented
  * Fixed a bug about shipping cost getting excluded sometime from total transaction

= 2.1.2 =

  * Bug fix of orders 'payment pending' after external checkout payment.

= 2.1.1 =

  * Fix a bug of symfony translate

= 2.1.0 =

  * Fix a bug where External Checkout Redirect Page is not found

= 2.0.0 =

  * Requires PHP 7.1.3 or greater
  * Added functionality for external checkout, more information about it [here](https://developers.cardinity.com/api/v1/#hosted-payment-page)

= 1.3.0 =

  * Requires PHP 5.5.9 or greater

= 1.2.1 =

  * Add support for multiple subscriptions

= 1.2.0 =

 * Add support for WooCommerce Subscriptions

= 1.1.1 =

 * Fix error with empty() in PHP versions below 5.5

= 1.1.0 =

 * Replaced deprecated methods
 * Other Woocommerce 3 fixes

= 1.0.2 =

* Fixed post validation.

= 1.0.1 =

* Updated project's file structure.

= 1.0.0 =

* Initial release.
