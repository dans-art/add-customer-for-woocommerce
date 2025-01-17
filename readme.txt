=== Add Customer for WooCommerce ===

Contributors: dansart
Contributors URL: <http://dev.dans-art.ch>
Donate link: <https://paypal.me/dansart13>
Tags: woocommerce, customer, tools, helper
Requires at least: 5.4.0
Tested up to: 6.7.1
Stable tag: 1.9.4
License: GPLv3 or later
License URI: <http://www.gnu.org/licenses/gpl-2.0.html>
WC requires at least: 7.4.1
WC tested up to: 9.5.2
Requires PHP: 7.4

Adds a new checkbox to the orders page to add a new customer/user and links orders to existing accounts

== Description ==
This plugin is designed to simplify the process of creating new users/customers. It's particularly useful if you receive client orders via phone or email and want to integrate these orders into WooCommerce for stock and order management.
The plugin adds a checkbox at the end of the billing address form. When this box is checked, it creates a new user with the role "customer." If an email is not provided, the plugin generates a unique one using your site’s domain as the email domain and the customer's name as the local part (e.g., firstname.lastname@your-site.com). You have the option to customize this auto-generated email format.

Try it out for free with TasteWP:
<a href="https://tastewp.com/new?pre-installed-plugin-slug=woocommerce%2Cadd-customer-for-woocommerce&redirect=admin.php%3Fpage%3Dwac_general_options&ni=true" target="_blank">https://tastewp.com/new?pre-installed-plugin-slug=woocommerce%2Cadd-customer-for-woocommerce</a>

By default, the newly created user does not receive any emails during account creation, though they will probably on order updates.

In the settings menu, there are options to:
- Check the box by default
- Send login credentials to the new customer

There are also additional options:
- Update new customer information by default
- Link customer orders
- Define the user role individually
- Set the user role for new customers
- Customize the auto-generated email format
- Edit the email subject for accounts created
- Change the sender's email address

Settings Menu:
WooCommerce -> Add customer settings

Required Plugins: WooCommerce 4.7.0 or higher
Required PHP extension: Intl

If you like the plugin, please leave some stars or buy me a coffee. Thank you!

== Installation ==
1. Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the ‚Plugins‘ screen in WordPress.
3. Enjoy

== Screenshots ==
1. The billing section of a new order. When the checkboxes are selected, it creates a new user who will receive a notification.
2. Selection of the role when "Define user role individually" is activated
2. The order after saving. A new customer was created and assigned to the order.
3. The main settings menu.
4. The template preview menu and the design of the email that the new customer receives.

== Upgrade Notice ==
Upload the plugin files to the /wp-content/plugins/add-customer-for-woocommerce directory, or update the plugin through the WordPress plugins screen directly.

== Frequently Asked Questions ==
What role will the new user have?
- By default, the new user will have the "Customer" role. You can select a different default role from any available roles in the options menu.

What happens if an email already exists?
- A new user will not be created. The plugin will assign the order to the existing user with that email.

How can I link guest orders to users?
- Activate the option "Link customer orders" to associate guest orders with existing users.

Does the new user receive a notification about the created account?
- No, not by default. This can be enabled on the settings page. An email will then be sent to the customer with login credentials, including a random password.

Can I change the email template for the new customer?
- Yes, you can. Copy "new-account.php" from "wp-content/plugins/add-customer-for-woocommerce/templates/email" to "wp-content/themes/[your-theme/child-theme]/woocommerce/add-customer/email" and make your changes there.

Is it possible to change the email format?
- Yes. You can select the preferred format in the Settings menu of the plugin.

Are there any hooks I can use?
- Certainly! There are currently four filters:
 wac_add_customer_email - To modify the email saved by the plugin.
 wac_supported_fake_email_parts - To add custom fields support.
 wac_get_user_roles - An array with the user roles a user is allowed to set.
 wac_deactivate_user_emails - Allows you to override the deactivation of user emails
- And two actions:
 wac_after_insert_new_customer - To execute after a new customer has been created.
 wac_after_insert_updated_customer - To execute after an existing customer has been updated.
 Consult the example file examples/hooks.php for more details how to use them.

 I created a users before the update 1.9.0 and would like use the feature to suppress the emails from being sent.
 - You can use the new hook "wac_deactivate_user_emails" for that. It has two parameters: $is_deactivated and $user_id. You can use this filter and return "true" for the users you like to suppress the messages. 

== Changelog ==

= [1.9.4] 2025-01-17 =
* Added compatibility for Enhanced Cloudflare Turnstile
* Updated WC Tested up to version

= [1.9.3] 2024-12-01 =
* Fixed: Fatal error when suppressing all emails
* Added TasteWP link
* Updated Support page

= [1.9.2] 2024-09-13 =
* Fixed: Options could not be saved (Error: The wac_general_options options page is not in the allowed options list.)

= [1.9.1] 2024-08-29 =
* Fixes: Added compatibility with reCAPTCHA for WooCommerce
* Improved: Code for the menu gets loaded on demand

= [1.9.0] 2024-08-04 =
* Added new option to suppress all the notification for customers added by the plugin
* Added new filter "wac_deactivate_user_emails"
* Moved settings to be a WooCommerce submenu
* Increased tested up version
* Added filter in users page to get all the users created by the plugin

= [1.8.0] 2024-03-09 =
* Updated style of the options menu
* Added option to select the default user role
* Added option to allow the user role to be set before user creation
* Added option to link orders to existing user
* Fixed spelling and wording in English language (thanks Naakai)
* French language added (thanks patou)

= [1.7.1] 2024-01-07 =
* Fixed: XSS Security issue
* Fixed: Fatal error when creating a fake email
* Updated tested up version

= [1.7] 2023-09-22 =
* Added support for WooCommerce HPOS.
* Increased minimum WooCommerce version to 7.4.1 

= [1.6.5] 2023-01-17 =
* Fixed: Better error messages if creation of customer fails

= [1.6.4] 2022-12-28 =
* Fixed: Compatibility issues with the plugin https://wordpress.org/plugins/simple-cloudflare-turnstile
* Added: Support page
* Added 12 new language strings, 1 updated

= [1.6.3] 2022-11-12 =

* Fixed: Errors when non-Latin characters were used.
* Added filter: wac_make_user_valid - Use this if you like to modify the username before saving it to the database
* Added filter: wac_make_email_valid - Use this if you like to modify the email before saving it to the database
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