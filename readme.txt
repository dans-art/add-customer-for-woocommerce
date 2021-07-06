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

Required Plugins: WooCommerce 4.7.0 or higher

= [1.1] 2021-07-06 =

* Added Error logging
* Error messages will be saved in the error_log
* Error and success messages will be added to the Log of the Simple History Plugin
* Fixed: Error when no first name and no last name was provided
* Fixed: Error when domainname is "localhost"
* Added 4 new Language strings
* Admin/Editor gets message if creation of the new user was not successful. Check error_log for details.

= [1.0] 2020-11-24 =

* Feature complete Version
* Added comments and method description
* Sanitize input fileds
* First and Last name gets saved to the user as well

= [0.1] 2020-11-22 =

* Initial Version!