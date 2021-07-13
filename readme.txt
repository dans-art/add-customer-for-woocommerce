=== Add Customer for WooCommerce ===

Contributors: dans-art
Contributors URL: http://dev.dans-art.ch
Tags: woocommerce, customer
Donate link: https://paypal.me/dansart13
Requires at least: 5.5.3
Stable tag: 5.5.3
Tested up to: 5.7.2
Requires PHP: 7.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a new checkbox to the orders page to add a new customer / user.
It creates a new user based of the billing data. If no e-mail is provided, it will create a fake email ( [random]@your-site.com ).

== Description ==
This Plugin is designed to help you creating new users / customer with ease.
Especially useful if the client is ordering via phone or email and you like to take advantages of the stock- and order-management in WooCommerce.
It adds a simple checkbox at the end of the billing address. If checked it will create a new user with the role \"customer\".
If no e-mail is provided, it will create a unique one for you. It uses your sites domain as domain part and the name as name part. Like so; firstname.lastname]@your-site.com.
The newly created user will not get any emails while creating the account, but he will eventually on order change. 
Required Plugins: WooCommerce 4.7.0 or higher

If you like the Plugin, please leave some Stars or spend me a coffee. Thanks!

== Installation ==
1. Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the ‚Plugins‘ screen in WordPress.
3. Enjoy

== Screenshots ==
1. The billing part of a new order. With the checkbox selected, it will create a new user.
2. The order after saving. A new customer was created and assigned to the order.

= [1.1] 2021-07-13 =

* Added Error logging
* Error messages will be saved in the error_log
* Error and success messages will be added to the Log of the Simple History Plugin
* Fixed: Error when no first name and no last name was provided
* Fixed: Error when domainname is "localhost"
* Added 4 new Language strings
* Admin/Editor gets message if creation of the new user was not successful. Check error_log for details.
* Added Option Menu
* Added Option to send user notification
* Added Option to select the checkbox by default

= [1.0] 2020-11-24 =

* Feature complete Version
* Added comments and method description
* Sanitize input fileds
* First and Last name gets saved to the user as well

= [0.1] 2020-11-22 =

* Initial Version!