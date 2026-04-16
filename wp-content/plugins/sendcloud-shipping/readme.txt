=== Sendcloud | Shipping & Returns Automation for WooCommerce ===
Version: 2.4.5
Developer: SendCloud Global B.V.
Developer URI: http://sendcloud.com
Tags: shipping, carriers, service point, delivery, webshop, woocommerce, dpd, dhl, ups, postnl, bpost, colissimo, fadello
Requires at least: 4.5.0
Tested up to: 6.6.1
Stable tag: 2.4.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: sendcloudbv

SendCloud helps to grow your online store by optimizing the shipping process.
Shipping packages has never been that easy!

== Description ==

[youtube https://www.youtube.com/watch?v=0GUV5W0bNi0 ]

= Sendcloud, the all-in-one shipping platform that accelerates your growth =

“Shipping Sucks.”

We feel you.

Orders are trickling in, before having to be picked, labeled and shipped in time. Meanwhile your customers receive boring tracking emails and you’re kept busy handling returns. It takes a ton of time (and money) to do all of this properly.

Shipping should be easy, no matter what, where and how much you ship, so you can focus on growing your ecommerce business!

That’s why we built Sendcloud. A ridiculously easy to use shipping platform _that scales with you_.

* Increase checkout conversion by offering a wide choice of delivery options
* Let customers choose if they want to receive their parcel today, tomorrow, or on another specific date
* Easily activate and ship with multiple carriers through a single platform
* Save a lot of time with automated shipping label generation process
* Provide the best post-purchase experience with branded tracking emails and pages that drive customer loyalty
* Avoid negative reviews by keeping customers in the loop, even during shipping delays
* Create a smooth and efficient returns process for both you and your customers with your own branded returns portal
* Get actionable insights from your shipping dashboard to drive business growth

The time is now to turn shipping into your competitive advantage. Join 23,000+ online businesses that grow with Sendcloud.

= Integrate your WooCommerce with +100 international carriers =
Your business is unique. That’s why we’ve built integrations with the world’s leading shipping carriers. Connect up to +100 international carriers (and counting!) and switch in one click whenever needed. Upload your own carrier contract or ship directly via Sendcloud’s pre-negotiated shipping rates.

= Supported carriers =
DHL, DHL Express, DPD, UPS, FedEx, Hermes, Budbee, GLS, Royal Mail, PostNL, bpost, SEUR, Correos, Correos Express, Colissimo, Mondial Relay, Colis Prive, Lettre Suivie, Chronopost, Austrian Post, Deutsche Post, Red Je Pakketje, Trunkrs, Post Italiane, MRW, Homerr, BRT, ViaTim, Hurby, Fietskoeriers, StoreShippers, Delivengo, Quicargo,...Stay tuned! We’re integrating new carriers at a fast pace, so don’t miss out on any [developments](http://releaselog.sendcloud.com).

= How does Sendcloud work? =
Pretty darn smoothly! Connect your WooCommerce to Sendcloud in under 2 minutes (yep, literally).
Step 1: Choose the carriers that fit your store best.
Step 2: Optimise your checkout with your customers’ preferred shipping methods
Step 3: Start saving minutes on every order with picking lists, packing slips and shipping labels that can be printed simultaneously or separately.
Step 4: Build out your brand. The last mile is no longer just in the hands of carriers. Send branded track & trace notifications and use them to increase customer retention.
Step 5: Tackle returns and drive growth. Offer a seamless return process and handle returns without hassle. It will make both you and your clients happy!

= 3rd Party Libraries =
Our plugin is relying on 3rd party libraries from [JSDELIVR](https://www.jsdelivr.com) that are used on the checkout page in order to render a widget for the nominated day delivery shipping method. These Libraries are included regardless of the shipping method is enabled or not and are loaded from a remote service.

These are the links to used plugins.
[https://cdn.jsdelivr.net/npm/@sendcloud/checkout-plugin-ui@1/dist/checkout-plugin-ui.js](https://cdn.jsdelivr.net/npm/@sendcloud/checkout-plugin-ui@1/dist/checkout-plugin-ui.js)
[https://cdn.jsdelivr.net/npm/@sendcloud/checkout-plugin-ui@1/dist/checkout-plugin-ui.css](https://cdn.jsdelivr.net/npm/@sendcloud/checkout-plugin-ui@1/dist/checkout-plugin-ui.css)

Please find the links to Terms of service and privacy policy for JSDelivr on following websites:
* Terms of service - [https://www.jsdelivr.com/terms/acceptable-use-policy-jsdelivr-net](https://www.jsdelivr.com/terms/acceptable-use-policy-jsdelivr-net)
* Privacy Policy - [https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net](https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net)

== Installation ==

= General instructions =

1. Upload the plugin files to the `/wp-content/plugins/sendcloud` directory,
2. Activate the plugin through the 'Plugins' screen in WordPress or install the plugin through the WordPress plugins screen directly (recommended).
3. Activate your WooCommerce integration _with service point support_ in the Sendcloud Panel. Please, refer to _Integrations_ section in the [support page](https://support.sendcloud.com) for more information about this.

= WooCommerce 2.5.x =

1. Go to WooCommerce->Settings->Shipping->Service Point Delivery
2. Check the _Enable this shipping method_
3. You may change your shipping costs by setting a new value for the _Cost_ field.
4. Click _Save changes_

= WooCommerce 2.6.x, 3.x.x =

1. Go to WooCommerce->Settings->Shipping
2. Use the _Add shipping method_ button in the _Shipping Zones_ listing and select _Service Point Delivery_ from the drop down menu.
3. You should see _Service Point Delivery_ in the _Shipping Method(s)_ column of the _Shipping Zones_ list.
4. You may change your shipping costs by clicking in the _Service Point Delivery_ and setting a new value for the _Cost_ field.
5. Click _Save changes_

= Checking the installation =

Your customers should be able to select _Service Point Delivery_ in the checkout page, alongside with a button labeled _Select Service Point_.


== Frequently Asked Questions ==

= How can I get started with Sendcloud? =

Learn how Sendcloud works and how to set it up in our [help center](https://support.sendcloud.com/hc/en-us/articles/360024833452-Getting-started-with-Sendcloud-).

= Do I need a Sendcloud account to use this plugin? =

Yes. In order to connect, you must register for an account and then, follow the installation instructions.

= What is a service point? =

Service Points are places that accept packages to be retrieved later by the customer.
e.g. A grocery store near your house or work may accept those packages.

== Screenshots ==

1. Easy shipping automation | Sendcloud
2. Try multi-carrier shipping | Sendcloud
3. Offer checkout options | Sendcloud
4. Put work on autopilot | Sendcloud
5. Automatically print shipping labels | Sendcloud
6. Provide branded tracking | Sendcloud
7. Automate returns | Sendcloud
8. More than 2k 5-star reviews | Sendcloud

== Changelog ==

= 2.4.5 =
* Add missing carrier list on the checkout blocks

= 2.4.4 =
* Add compatibility with Mollie payments for WooCommerce blocks in the checkout

= 2.4.3 =
* Fix issue with plugin significantly affects site efficiency

= 2.4.2 =
* Fix issue with service point carrier list

= 2.4.1 =
* Changed: removed errors in console on checkout

= 2.4.0 =
* Added: Compatibility with WooCommerce 8.3.0

= 2.3.0 =
* Feature: Enable message logging based on the log level

= 2.2.22 =
* Changed: readme.txt file
* Changed: compatible versions of WooCommerce (8.2.1) and WordPress (6.3.2)

= 2.2.21 =
* Changed: Adjustment of order synchronization flow

= 2.2.20 =
* Feature: Deprecate DynamicCheckout. Remove dynamic checkout configuration, mark dynamic checkout shippping methods as
deprecated and legacy service point as the only available option for new Sendcloud users.

= 2.2.19 =
* Changed: Replace get_woocommerce_currency() with get_option('woocommerce_currency')

= 2.2.18 =
* Fix issue with accessing undefined array key

= 2.2.17 =
* Added: Enable shop manager role to access plugin page

= 2.2.16 =
* Added: New optional public description field for dynamic checkout delivery methods
* Added: Custom CSS styling classes to allow the customization of the delivery method title and new public description
* Changed: Remove saving access token on order payload

= 2.2.14 =
 * Support weight rate logic for Service Point Delivery method

= 2.2.13 =
 * Fix nonce verification

= 2.2.12 =
 * Fix shipping rate links

= 2.2.11 =
 * Add notice when connect button is disabled

= 2.2.10 =
 * Fix: Fix issue with nominated date delivery shipping method
 * Fix: Fix issue with postal code not being sent to service point picker

= 2.2.9 =
 * Fix: Add missing translations for checkout shipping methods

= 2.2.8 =
 * Add compatibility with malware scanner.

= 2.2.7 =
 * Add compatibility with WooFunnels plugin.

= 2.2.6 =
 * Fix: Add compatibility with WooCommerce sniffs.

= 2.2.5 =
 * Fix: Fix issue with service points on checkout.

= 2.2.4 =
 * Fix: Fix issue with invalid weight.

= 2.2.3 =
 * Fix: Remove extra array from error response.

= 2.2.2 =
 * Fix: Fix wp_register_script called incorrectly.

= 2.2.1 =
 * Fix: Fixed small issue with free shipping method.

= 2.2.0 =
 * New delivery method in Dynamic Checkout - Service point delivery (available for all users). The service point map is embedded on the page and possible to style with CSS.
 * Legacy Service points method still supported.
 * Setup weight based rates directly in the Dynamic Checkout Delivery methods (standard, same day, nominated day methods).
 * New Coupon support for Dynamic Checkout delivery methods.

= 2.1.3 =
 * Fix: Replace deprecated function and fix escaping javascript.

= 2.1.2 =
 * Added: Added more security validations.

= 2.1.1 =
 * Added: Support for configuring nominated day methods internationally.

= 2.1.0 =
* Added: Support for 2 new delivery methods in the Checkout feature for subscription users.
* New delivery method for small shop and above: standard delivery (international and domestic).
* New delivery method for large shop and above: same day delivery (domestic, dependent on carrier).
* Added: Holiday support (for same day/nominated day methods, configurable in Sendcloud platform).
* Added: Translations for all Sendcloud supported languages (English, Dutch, German, Spanish, French, Italian).
* WordPress compatibility check for 5.8.

= 2.0.3 =
* Fix - Updated translations.
* Fix - Service points delivery (with same free shipping threshold) are now all visible at checkout.

= 2.0.2 =
* Fix - Fixed rendering Nominated day.

= 2.0.1 =
* Fix - Enabled compatibility with Woo Funnels plugin to fix Service points Sendcloud.

= 2.0.0 =
* Add - Introduced compatibility with the Sendcloud Checkout module giving users the best delivery options.
  Sendcloud Checkout module allows for the following:
  Set up delivery options and customize them.
  Set the pricing for the delivery options based on weight classes.
  Offer time slots for delivery which are sensitive to cut-off times.
* Fix - Bug fixes related to compatibility Sendcloud Servicepoints for Woocommerce version 3.

= 1.1.2 =
* Added support for Wordpress 5.3

= 1.1.1 =
* Added support for Wordpress 5.2.3

= 1.1.0 =
* Rebranding

= 1.0.17 =
* Add support to WooCommerce 3.5.5

= 1.0.16 =
* Fix translations

= 1.0.15 =
* Add carrier selection for service point shipping method
* Fix translations

= 1.0.14 =
* Improve WooCommerce 3.x.x compatibility

= 1.0.13 =
* Made permalink checks less strict

= 1.0.12 =
* Add support for custom site url's

= 1.0.11 =
* Made this plugin compatible with CloudFlare Rocket

= 1.0.10 =
* This plugin is now compatible with WooCommerce 3.0.x

= 1.0.9 =
* Added support for preventing the customer to ship to a service point when one of the items is too big.

= 1.0.8 =
* Service Point Shipping method can be set as free after certain amount of the order
* Add translation to select service point button

= 1.0.7 =
* Compatible with PHP 5.3
* Connect with SendCloud on new tab

= 1.0.6 =
* Added mo translation files

= 1.0.5 =
* Fix redirection to SendCloud Panel

= 1.0.4 =
* Fix headers on autoconnect

= 1.0.3 =
* Improved readme, documentation and screenshots

= 1.0.2 =
* Add auto connect button

= 1.0.1 =
* Add service point address on email

= 1.0.0 =
* Integrate WooCommerce with an existing SendCloud account enabling service point delivery
  locations to be selected at the checkout.
