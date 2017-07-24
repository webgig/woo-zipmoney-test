=== zipMoney Payment Gateway for WooCommerce ===
Contributors: zipmoney
Plugin Name: zipMoney WooCommerce Addon
Plugin URI: https://wordpress.org/plugins/zipmoney-woocommerce-addon/
Tags: zipmoney payments woocommerce, zipmoney woocommerce addon , zipmoney payment gateway for woocommerce, zipmoney for woocommerce, zipmoney payment gateway for wordpress, buy now pay later, zippay woocommerce plugin
Requires at least: WP 4.0 & WooCommerce 2.3+
Tested up to: 4.8 & & WooCommerce 3.1
Stable tag: 2.0.0
License: GPLv2 or later License http://www.gnu.org/licenses/gpl-2.0.html

zipMoney WooCommerce Plugin enables customer to checkout using zipMoney/zipPay as payment option.

== Description ==

Buy Now Pay Later.

zipMoney Payments allows realtime credit to customers in a seamless and user friendly way – all without the need to exchange any financial information, and leverage the power of promotional finance to increase sales for our business partners, risk free.


== Installation ==

= Automatic Installation =
*   Login to your WordPress Admin area
*   Go to "Plugins > Add New" from the left hand menu
*   In the search box type "zipMoney WooCommerce Addon"
* From the search result you will see "zipMoney WooCommerce Addon" click on "Install Now" to install the plugin
* A popup window will ask you to confirm your wish to install the Plugin.

= Note: =
If this is the first time you've installed a WordPress Plugin, you may need to enter the FTP login credential information. If you've installed a Plugin before, it will still have the login information. This information is available through your web server host.

* Click "Proceed" to continue the installation. The resulting installation screen will list the installation as successful or note any problems during the install.
* If successful, click "Activate Plugin" to activate it, or "Return to Plugin Installer" for further actions.

= Manual Installation =
1.  Download the plugin zip file
2.  Login to your WordPress Admin. Click on "Plugins > Add New" from the left hand menu.
3.  Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
4.  Activate the plugin.
5.  Open the Settings page for WooCommerce and click the "Checkout" tab.
6.  Click on the sub tab for "ZipMoney".
7.  Configure your "ZipMoney" settings. See below for details.

= Configure the plugin =
To configure the plugin, go to __WooCommerce > Settings__ from the left hand menu, then click "Checkout" from the top tab menu. You should see __"ZipMoney"__ as an option at the top of the screen. Click on it to configure the payment gateway.

* __Enable/Disable__ - check the box to enable zipMoney WooCommerce Addon.
* __Title - title of the payment option to be shown in the checkout page.
* __Sandbox__ - check the box to run the plugin in test mode. Unchecking this option will put It in production mode.You will need sandbox merchant id and api key to test it in sandbox mode.
* __Sandbox Merchant Public Key/Merchant Public Key__   - enter your zipMoney Merchant Public Key.
* __Sandbox Merchant Private Key/Merchant Private Key__   - enter your zipMoney Merchant Private Key.
* __Product__ - select the relevant product( zipMoney/zipPay) from the dropdown.
* __Charge Capture option__ - set whether to capture immediately or authorise now and capture later.
* __Log Message level__   - select the logging level.
* __Debug__   - check the box to enable logging all the api requests, response and any api errors.
* __Iframe Checkout__   - check the box to enable iframe checkout which will enable in-context checkout process in a popup window without leaving the store.
* __Minimum Order Total__  - set the minimum order amount to be used for zipMoney.
* __Marketing Widgets__   - check the box to enable marketing images and buttons below the Add To Cart button in product page and below Process To Checkout  button in cart pages.  
  * __Display on product page__ -Enables widget in the product page below Add to Cart button.
  * __Display on cart page__ -Enables widget in the cart page below  Process To Checkout.
* __Marketing Banners__   - check the box to enable marketing banners in different sections of the website.  
  * __Display Marketing Banners__ -Displays other options to render the banners in shop, product , category and cart pages.
  * __Display on Shop__ -Enables banner in the Shop/Store page.
  * __Display on Product Page__ -Enables banner in Product page.
  * __Display on Category page__ -Enables banner in the Category page.
  * __Display on Cart page__ -Enables banner in the Cart page.
* __Tagline__ -Option to display the tagline in product and cart pages
  * __Display on product page__ -Enables the tagline in the product page below the price.
  * __Display on cart page__ -Enables the tagline in the cart page below  the total.
* Click on __Save Changes__ for the changes you made to be effected.

== Changelog ==

= 2.0.0 =
* Changes: - Uses the new zipMoney Merchant Api. The plugin architecture has changed which introduces many breaking changes.

= 1.2.2 =
* Fixes:  - Missing Shipping addresss issue.

= 1.2.1 =
* Fixes:  - Email formatting issue.

= 1.2.0 =
* Fixes:  - Some bug fixes for express checkout flow.

= 1.1.0 =
* Feature: - Replaced the repayment calculator with tagline

= 1.0.3 =
* Hotfix: - Fixed an issue with partial refunds. 
* Hotfix: - Some bug fixes.

= 1.0.2.1 =
* Hotfix: - Fixed an issue where Place Order Button was disabled when using other payment option.

= 1.0.2 =
* Enabled the option to use express checkout (i.e no orders will be left on admin if user doesnot complete the checkout process) and fixed a bug in learn more link in checkout page

= 1.0.1 =
* Fixed a bug in iframe checkout flow where the Place Order button alwayes loaded the iframe popup.

= 1.0.0 =
* First release for Wordpress Plugin Directory.