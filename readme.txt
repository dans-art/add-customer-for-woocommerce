=== Add Customer for WooCommerce ===

Contributors: dansart
Contributors URL: <http://dev.dans-art.ch>
Donate link: <https://paypal.me/dansart13>
Tags: woocommerce, customer, tools, helper
Requires at least: 5.4.0
Tested up to: 6.1
Stable tag: 1.6.3
License: GPLv3 or later
License URI: <http://www.gnu.org/licenses/gpl-2.0.html>
WC requires at least: 4.7.0
WC tested up to: 7.0.1
Requires PHP: 7.4

Adds a new checkbox to the orders page to add a new customer/user.
It creates a new user, based on the billing data. If no e-mail is provided, it will create a fake email (firstname.lastname@your-site.com).

== Description ==
This Plugin is designed to help you to create new users/customers with ease.
Especially useful if the client is ordering via phone or email and you like to take advantage of the stock- and order management in WooCommerce.
It adds a simple checkbox at the end of the billing address. If the box is checked, it will create a new user with the role "customer".
If no e-mail is provided, it will create a unique one for you. It uses your site’s domain as the domain part and the name as the name part. E.g. firstname.lastname@your-site.com.
You can customize the auto-generated email to your liking.
By default, the newly created user will not get any emails while creating the account. But he will eventually on order change.
In the settings menu, there are options for checking the box by default and send login credentials to the new customer.

Settings Menu:
Settings -> Add Customer Settings

Required Plugins: WooCommerce 4.7.0 or higher
Required PHP extension: Intl

If you like the Plugin, please leave some Stars or donate me a coffee. Thanks!

== Installation ==
1. Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the ‚Plugins‘ screen in WordPress.
3. Enjoy

== Screenshots ==
1. The billing part of a new order. With the checkbox selected, it will create a new user.
2. The order after saving. A new customer was created and assigned to the order.
3. The settings menu.
4. The template preview menu and look of the Email received by the new customer.

== Upgrade Notice ==
Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or update the plugin through the WordPress plugins screen directly.

== Frequently Asked Questions ==
What role the new user will have?
- Customer

What happens, if an email already exists?
- There will be no new user created. The plugin will assign the order to the existing customer.

Does the new user get a notification of the created account?
- No, not per default. It can be enabled on the settings page. The Email will send the login credentials including a random password to the customer.

Can I change how the email template for the new customer?
- Yes, you can. Just copy the "new-account.php" from "wp-content\plugins\add-customer-for-woocommerce\templates\email" to "\wp-content\themes\[theme/child-theme]\woocommerce\add-customer\email" and do your changes.

Is it possible to change the email format?
- Yes. You can set the desired format in the Settings menu of the plugin.

Are there any hooks I can use?
- Sure thing! There are currently two filters:
    wac_add_customer_email - To modify the email saved by the plugin
    wac_supported_fake_email_parts - To add support for custom fields
- And two actions:
   wac_after_insert_new_customer to hook in after new customer got created
   wac_after_insert_updated_customer to hook in after new customer got updated



== Changelog ==
= [1.6.3] 2022-11-12 =

* Fixed: Errors when non-latin characters where used.
* Added filter: wac_make_user_valid - Use this if you like to modify the username before saving to the database
* Added filter: wac_make_email_valid - Use this if you like to modify the email before saving to the database
* Added two new language strings

= [1.6.2] 2022-11-07 =

* Fixed: Notify customer checkbox did not get displayed

= [1.6.1] 2022-11-03 =

* Fixed: Removed extra dots if not all three fields (billing_first_name, billing_last_name and billing_company) are filled out
* Added action "wac_after_insert_new_customer" to hook in after new customer got created
* Added action "wac_after_insert_updated_customer" to hook in after new customer got updated

= [1.6.0] 2022-11-03 =

* Fixed: Invalid email when the value of the tags, defined in the custom fake email where empty
* Changed: Default fake-email structure to [billing_first_name].[billing_last_name].[billing_company]
* Added: The add_customer checkbox gets unchecked if a customer gets loaded
* Added hook to filter "wac_add_customer_email" the email before checking and saving a new user 
* Fixed 'wac_supported_fake_email_parts' hook
* Fixed various issues
* Removed the need to have billing name/surname or company at all. It will just generate a random name for the email
* Added 1 new language string

= [1.5.1] 2022-09-14 =

* Fixed: Error on add customer settings page when no eMail was provided.

= [1.5.0] 2022-09-14 =

* Added settings field to customize the generated email
* Added filter: wac_supported_fake_email_parts
* Fixed Language strings, 15 new strings added. 2 obsolete strings removed
* Added "Update customer" function
* Added counter to keep track of updated and created customers
* Optimized code. Moved HTML code to dedicated template files
* Added new settings field. Allowing to preselect the update customer checkbox
* Backend style fixes

= [1.4.2] 2022-05-15 =

* Fixed: Fatal error on template preview if WooCommerce is not installed
* Tested up to WP 6.0

= [1.4.1] 2022-02-24 =

* Fixed: Fatal error on loading the version

= [1.4] 2022-02-22 =

* Added: Option to set the sender email
* Added: Option to set the subject of the new customer email
* Added: New tab at the settings page to preview the loaded template (new-account)
* The plain password got replaced by a password reset link to improve security.
* Fixed: Email to the new customer will only be send, if the email is not generated by the plugin.
* Added 18 new language strings. 4 obsolete strings removed.

= [1.3.1] 2022-02-08 =

* Fixed: Wordpress.org translations did not get loaded.

= [1.3] 2022-02-07 =

* Fixed: New customer gets created when an existing guest order is updated.
* Add customer checkbox is only pre-selected on new orders
* Updated: tested up to
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
* Added Option to send a notification to the new user/customer 
* Added Option to select the checkbox by default
* Removed debugger

= [1.0] 2020-11-24 =

* Feature complete Version
* Added comments and method description
* Sanitize input fields
* First and Last name gets saved to the user as well

= [0.1] 2020-11-22 =

* Initial Version!