=== WooCommerce Multiple Payment Gateways (WCMPG) ===
Contributors: IRCF
Donate link: https://ircf.fr/
Tags: woo commerce, payment, gateway, bank, terminal, sips, paypal, paybox, system pay, monetico, mercanet, axepta
Requires at least: 3.0.1
Tested up to: 6.5
WC requires at least: 3.0.0
WC tested up to: 8.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WCMPG provides multiple payment gateways for WooCommerce.

== Description ==

WCMPG provides multiple payment gateways for WooCommerce to avoid installing multiple plugins.

WCMPG currently provides the following payment gateways :

= Free version : =
*   Paypal
*   Paybox (E-transaction)

= Pro version : =
*   SIPS 1.0 (Mercanet / BNP Paribas, Sherlocks / LCL, Scellius / Banque Postale, Sogenactif / Société Générale)
*   SIPS 2.0 Paypage POST (Mercanet / BNP Paribas, Sherlocks / LCL, Scellius / Banque Postale, Sogenactif / Société Générale)
*   System Pay (Cyberplus)
*   Monetico (Crédit mutuel CIC)
*   Axepta (BNP Paribas)

Some gateways require a Pro version, there are 2 types of Pro version :

- Mono site version : the license can be activated on a single site.
- Multi site version : the license can be activated up to 5 sites.

You can buy the plugin on our [WordPress plugin shop](https://ircf.fr/plugins-wordpress/)

== Installation ==

1. Upload `wcmpg` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to implement additional gateways ? =

Create a plugin or complete this one with a PHP class extending WC_Payment_Gateway, see other gateways for examples.
If you don't have the knowledge, you can also ask us at technique@ircf.fr

== Screenshots ==

1. Paybox (E-Transaction) payment gateway configuration
2. Mercanet (BNP Paribas) payment gateway configuration, Pro version only
3. SIPS (Mercanet, Sherlocks, Scellius, Citélis, Sogenactif) payment gateway configuration, Pro version only
4. Systempay (Cyberplus) payment gateway configuration, Pro version only
5. Monetico (Crédit mutuel CIC) payment gateway configuration, Pro version only

== Changelog ==

= 1.68 =
Axepta : Fixed API notification, fixed PHP8.2 deprecated notices.

= 1.67 =
Added support for WooCommerce Blocks.
Axepta : Added missing icon.
Fixed PHP8.2 deprecated notices.

= 1.66 =
Systempay : Added Scellius by La Banque Postale.

= 1.65 =
Paybox : Added 3DSv2 params.

= 1.64 =
Systempay : Fixed vads_trans_id max length error.

= 1.63 =
Paybox : Fixed automatic order success url.

= 1.62 =
Axepta : added payment method.

= 1.61 =
Paypage : fixed migration_mode parameter.

= 1.60 =
Paypage : added migration_mode parameter.

= 1.59 =
Systempay : added bank parameter (Sogenactif).

= 1.58 =
Monetico : ensure algorithm is always set.

= 1.57 =
Monetico : added algorithm sha1 (default) or md5.

= 1.56 =
Monetico : added order note when invalid mac.

= 1.55 =
Paypage : added send_transaction_reference option.

= 1.54 =
Systempay : limit vads_order_info to 255 cars (fixes signature error).

= 1.53 =
Systempay : removed CRLF from vads_order_info (fixes signature error).

= 1.52 =
Systempay : added htmlspecialchars to form fields (fixes errors with quotes).

= 1.51 =
Monetico : fixed MAC error (added missing cbmasquee).

= 1.50 =
Paypage + Sherlock's : removed transactionReference.

= 1.49 =
Paypage + Mercanet : restored transactionReference.

= 1.48 =
Paypage + Mercanet : Fixed test URL, restored transactionReference when keyVersion=1.

= 1.47 =
Added wcmpg prefix to woocommerce_support.

= 1.46 =
Sips Paypage : Fixed LCL testing URL.

= 1.45 =
Systempay : Fixed vads_product_amount error (2).

= 1.44 =
Systempay : Fixed vads_product_amount error.

= 1.43 =
Monetico : Fixed MAC on payment refused.

= 1.42 =
Paypage + Mercanet : Fixed normal/cancel url.

= 1.41 =
Systempay : Added algorithm setting.

= 1.40 =
Sips Paypage Post : added payment method.
misc : Removed dead code, added missing method descriptions.

= 1.39 =
Systempay : Added vads_order_id, vads_cust_*, vads_ship_*, vads_product_*.
Monetico : Fixed filter wcmpg_receipt_params.
misc : Fixed deprecation notices, clean up code.

= 1.38 =
Monetico : Fixed escape quotes.

= 1.37 =
Monetico : Fixed texte_libre -> texte-libre.

= 1.36 =
Mercanet : Better api response (403 instead of 500).

= 1.35 =
All : fixed order button FR localization.

= 1.34 =
Systempay : display autoresponse URL.

= 1.33 =
Monetico : text modification.
misc : reverse changelog, added missing icons.

= 1.32 =
Mercanet : Added key_version and debug logs.
misc : fixed locales and icons.

= 1.31 =
SIPS : Fixed response permissions.
misc : tags, locales, icons.

= 1.30 =
Fixed plugin name, fixed plugin URI.

= 1.29 =
SIPS : Fixed test.

= 1.28 =
Systempay : Fixed api response (2).

= 1.27 =
Systempay : Fixed api response.

= 1.26 =
SIPS : Fixed api response (empty).

= 1.25 =
SIPS : Update README, display autoresponse URL.

= 1.24 =
Monetico : Fixed response MAC computation.

= 1.23 =
Monetico : Fixed contexte_commande and lgue.

= 1.22 =
Monetico : Tidy up code.

= 1.21 =
Monetico : Fixed MAC computation, added debug logs.

= 1.20 =
Monetico : Fixed api response (version=2 cdr=0|1)

= 1.19 =
Monetico : display autoresponse url + return status 200.

= 1.18 =
SIPS : Fixed autoresponse (escapeshellcmd).

= 1.17 =
SIPS : Fixed autoresponse (return_context).

= 1.16 =
SIPS : Fixed autoresponse, added debug logs.

= 1.15 =
SIPS : Fixed detect language (override pathfile).

= 1.14 =
Added detect language from wpml or polylang.
Added wcmpg_receipt_params filter.

= 1.13 =
SIPS : removed sips_mode unused param.
SIPS : completed test message.

= 1.12 =
SIPS : moved upload outside plugin.
SIPS : set bin execution permission.
SIPS : use woocommerce success and cancel urls.
SIPS : added install kit help message.
SIPS : update pathfile parameters.

= 1.11 =
Fixed default path and URL settings.

= 1.10 =
Fixed SIPS upload and test.

= 1.9 =
Fixed Paypal test mode.

= 1.8 =
Fixed Mercanet production url, added simulation mode.
Fixed admin assets.
Added support link.

= 1.7 =
Fixed Monetico automatic url.
Fixed Mercanet normal url.
Fixed Paybox automatic url (read only).

= 1.6 =
Added Mercanet IPN.
Added SIPS IPN.
Added SystemPay IPN.
Added Monetico IPN.

= 1.5 =
Fixed Paybox IPN.

= 1.4 =
Fixed textdomain.
Added Paybox Server (Paybox or E-Transaction).
Fixed Paybox HMAC.

= 1.3 =
Added screenshots.

= 1.1 =
Added Monetico payment gateway.

= 1.0 =
Initial commit.
