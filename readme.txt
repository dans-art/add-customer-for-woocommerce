=== Add Customer for WooCommerce ===

Contributors: dansart
Contributors URL: <http://dev.dans-art.ch>
Donate link: <https://paypal.me/dansart13>
Tags: woocommerce, customer, tools, helper
Requires at least: 5.5.3
Tested up to: 5.9
Stable tag: 1.3
License: GPLv3 or later
License URI: <http://www.gnu.org/licenses/gpl-2.0.html>
WC requires at least: 4.7.0
WC tested up to: 6.1.1
Requires PHP: 7.4

Adds a new checkbox to the orders page to add a new customer/user.
It creates a new user, based on the billing data. If no e-mail is provided, it will create a fake email (firstname.lastname@your-site.com).

== Description ==
This Plugin is designed to help you to create new users/customers with ease.
Especially useful if the client is ordering via phone or email and you like to take advantage of the stock- and order management in WooCommerce.
It adds a simple checkbox at the end of the billing address. If the box is checked, it will create a new user with the role "customer".
If no e-mail is provided, it will create a unique one for you. It uses your site’s domain as the domain part and the name as the name part. E.g. firstname.lastname@your-site.com.
By default, the newly created user will not get any emails while creating the account. But he will eventually on order change.
In the settings menu, there are options for checking the box by default and send login credentials to the new customer.

Settings Menu:
Settings -> Add Customer Settings

Required Plugins: WooCommerce 4.7.0 or higher

If you like the Plugin, please leave some Stars or donate me a coffee. Thanks!

== Installation ==
1. Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the ‚Plugins‘ screen in WordPress.
3. Enjoy

== Screenshots ==
1. The billing part of a new order. With the checkbox selected, it will create a new user.
2. The order after saving. A new customer was created and assigned to the order.
3. The settings menu.
4. Email received by the new customer.

== Upgrade Notice ==
Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or update the plugin through the WordPress plugins screen directly.

== Frequently Asked Questions ==
What role the new user will have?
- Customer

What happens, if an email already exists?
- There will be no new user created. The plugin will assign the order to the existing customer.

Does the new user get a notification of the created account?
- No, not per default. It can be enabled on the settings page. The Email will send the login credentials including a random password to the customer.

== Changelog ==
= [1.3] 2022-02-07 =

* Fixed: New customer gets created when a existing guest order is updated.
* Add customer checkbox is only pre-selected on new orders
* Updated: testet up to
* Updated: WC tested up to

= [1.2] 2021-10-10 =

* Added support for custom billing and shipping fields
* Added a second checkbox for customer notification
* Errors and notifications for the admin will now be displayed after saving the order

= [1.1] 2021-07-18 =

* Added Error logging
* Error messages will be saved in the error_log
* Error and success messages will be added to the Log of the Simple History Plugin
* Fixed: Error when no first name and no last name was provided
* Fixed: Error when the domain name is "localhost"
* Added new Language strings for de-DE and de-CH
* Admin/Editor gets a message if the creation of the new user was not successful. Check error_log for details.
* Added Option Menu
* Added Option to send user notification
* Added Option to select the checkbox by default
* Removed debugger

= [1.0] 2020-11-24 =

* Feature complete Version
* Added comments and method description
* Sanitize input fields
* First and Last name gets saved to the user as well

= [0.1] 2020-11-22 =

* Initial Version!