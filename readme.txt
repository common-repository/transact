=== Transact ===
Contributors: transact
Donate link: https://transact.io/
Tags: payments, micropayments, e-commerce, paywall, subscription, subscriptions, monetization, premium content, premium, paywall, pay-per-view, content monetization, donations, pay
Requires at least: 5.0
Requires PHP: 8.1
Tested up to: 6.4.2
Stable tag: 6.0.0
License: APACHE-2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

Micropayments from $0.01.   Receive payments for digital content on WordPress.

== Description ==

Transact.io brings A la Carte revenue model to digital media.  Charge for content within your posts.

Features:

* The publisher sets the price, which can be as low as $0.01
* Transact.io enables publishers to regain control of distribution.
* Single post, you can set the price from $0.01 to $50.00
* Optionally you can enable Subscriptions,  allow for unlimited content for a fixed monthly or annual rate
* For small amounts, transact has lower fees than credit cards or paypal.  For transactions less than $1 the commission is 10%, Over $1 it is 2%.
* Optionally use Google Tag Manager for conversion metrics.

Read more about us at https://transact.io/

This plugin is open source.   Source code is available at https://gitlab.com/transact/transact-wordpress

If you need help setting up your account please do not hesitate to contact us:
https://transact.io/about/contact

== Installation ==


1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Transact screen to configure the plugin
4. Sign up with a publisher account on https://transact.io/
5. Go to your Developer Settings->Keys   menu  to find your account ID and secret signing key.  Configure these key on WordPress plugin.
6. Create a new wordPress Post, set the price, and add a transact block.


Shortcode manual:
1. You can set up a shortcode directly on your post content, this shortcode will override the default button.
2. Is possible to set up new texts for purchase and subscribe buttons, also choose between the 3 model buttons (as in the post settings)
3. You can choose to show "Only Purchase Button", "Only Subscribe button", "Purchase and Subscribe button".
4. Shortcode is [transact_button]
5. If you do not set up any option, it will use default transact buttons.
6. Options are 'button_text' 'subscribe_text', 'button_type'
7. "Purchase and Subscribe button" = 1, "Only Purchase Button" = 2, "Only Subscribe button" = 3
8. Example: [transact_button button_text="purchase me" subscribe_text="subscribe to the site" button_type="1"]



== Frequently Asked Questions ==

= What is the lowest amount I can charge for content? =

$0.01

= What is the highest amount I can charge for content? =

$50.00

= What are the costs? =

10% of the first dollar of a purchase, after $1 USD 5%.
We are cheaper than any credit card service for small amounts.


= Is there support? =
Yes, please contact us at https://transact.io or  support"at"transact.io

== Screenshots ==

1.  Transact.io  Keys
2.  WordPress Settings
3.  Purchase button on your page, look can be customized on your theme

== Changelog ==

= 6.0.0 =
* Payment ouside of iframe

= 5.9.0 =
* Call to action text editor added.

= 5.8.1 =
* Fix currency selection

= 5.8.0 =
* Primary currency selection for USD, GBP, EUR, CAD

= 5.7.0 =
* Add feature to login with transact.

= 5.6.0 =
* Add option to allow search engine access.
* shortcode fixes for [transact_button]

= 5.5.1 =
* Update button styling

= 5.5.0 =
* Refactor loading of transact javascript.
* Detect if cookies are blocked and display message.

= 5.4.2 =
* Fix displaying error message of blocked 3rd party scripts on free posts.

= 5.4.1 =
* Fix validation of gift subscriptions

= 5.4.0 =
* Add tags for Google Tag Manager

= 5.3.2 =
* Fix for old editor. Don't show lead content after purchase

= 5.3.1 =
* Fix for old posts not using block editor
* Validate subscriptions improvements

= 5.3.0 =
* Set wordpress_logged_in to 15 days to stay signed in.

= 5.2.3 =
* Error check for blocked scripts
* Don't disply purchase button if price not set

= 5.2.2 =
* Add check for is_wp_error() in subscription

= 5.2.1 =
* Fix notice warning on WordPress 5.5

= 5.2.0 =
* Add oembed support
* fix for subscription validation

= 5.1.4 =
* Check for already exising email registration.

= 5.1.3 =
* improve zero price checks

= 5.1.2 =
* Fix if purchase cancled.

= 5.1.1 =
* Fix dontation button layout

= 5.1.0 =
* Configurable text fade on paid content.

= 5.0.0 =
* Create a user for each transact user.
* logged in viewers with subscription or paid access can.

= 4.4.0 =
* Make loading content syncronous to improve compatibility with other plugins.
  This will make content present when jQuery.ready() is triggered.

= 4.3.1 =
* JQuery loading fix

= 4.3.0 =
* new short code options:  call_to_action and display_promo
* admin options for default button type or disable transact button.
* alow admin user to see premium without paying

= 4.2.4 =
* Don't show purchase button if post has no premium blocks or premium content

= 4.2.3 =
* Fix with price of 0

= 4.2.2 =
* Multiple donations fix.

= 4.2.1 =
* Fix editing donation price. Pass donation flag to transact.io

= 4.2.0 =
* Display pricing as dollars and cents.

= 4.1.1 =
* Fix for donation pages.

= 4.1.0 =
* Google Tag manger integration

= 4.0.2 =
* Fix for wordpress jetpack tiled-gallery+carousel compatibility

= 4.0.1 =
* Styling and help text.

= 4.0.0 =
* Major update to use guttenberg block editor
* fix expiring subscriptions

= 3.7.0 =
* refactor to be more resilient to other bad plugins
* fix single post, purhcase only with no subscription

= 3.6.3 =
* fix reading settings validation
* detect if subscription not setup

= 3.6.2 =
* fix reading transients that expire on memcached hosting
* fix editing old post reading price settings.

= 3.6.1 =
* Fix subscribe only button

= 3.6.0 =
* Use Wordpress REST API instead of admin-ajax

= 3.5.0 =
* Please wait on loading spinner

= 3.4.0 =
* Delay enabling button until backend finishes.

= 3.3.0 =
* Load paid content via ajax rather than reload

= 3.2.1 =
* Comments bugfix

= 3.2.0 =
* Simlify configuration.

= 3.1.0 =
* Comments with caching fix

= 3.0.1 =
* JSON response fix

= 3.0.0 =
* Refactor code.  Fix phpcs WordPress-VIP-Go  errors

= 2.2.1 =
* Fix when used with JQuery 3.x

= 2.2.0 =
* Change CSS names to avoid theme conflicts

= 2.1.0 =
* Change colors in payment window

= 2.0.1 =
* scroll to payment form when purchasing

= 2.0.0 =
* Payment in modal iframe

= 1.9.5 =
* fix activate/deactivate session

= 1.9.5 =
* fix subscriptions

= 1.9.5 =
* deployment fix

= 1.9.3 =
* remove unused git file

= 1.9.2 =
* Update transact-io-php dependency

= 1.9.1 =
* Allow customizing call to action text

= 1.9.0 =
* Allow users to customize button text
* Pricing validation, to make sure the price is a numeric integer

= 1.8.0 =
* Changes to make other plugins that extend this plugin easier.

= 1.7.1 =
* Fix shortcodes bug and missing price.
* Details at https://gitlab.com/transact/transact-wordpress/merge_requests/9

= 1.7.0 =
* Fix for possible URL varable conflict. Test on latest WP

= 1.6.0 =
* promo text fetching,  button styling

= 1.5.2 =
* content stamp

= 1.5.1 =
* shortcode fix

= 1.5.0 =
* Comments closed without purhase

= 1.4.0 =
* Support for donations

= 1.3.0 =
* Support for affiliates. you can put aff=ID in the URL to share affiliate revenue.
Note, you must configure affiliate settings on transact publisher dashboard.

= 1.2.2 =
* Change Tokens to Cents.

= 1.2.1 =
* Fix purchase and subscription button. Prevent multiple buttons from occluding each other.

= 1.2.0 =
* Support for subscriptions

= 1.1.0 =
* Update styling. Button size and fade linear-gradient
* Update File headers

= 1.0 =
* Initial public release


= 0.5 =
* Fixed issues.

== Upgrade Notice ==

= 1.0 =
Initial public release, compatible with old releases for testers.
