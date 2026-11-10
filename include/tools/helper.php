<?php

/**
 * Various helper methods. 
 * 
 * @class       woo_add_customer_helper
 * @version     1.6.5
 * @package     WAC\classes
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class woo_add_customer_helper
{
    protected $version = '000'; //The current plugin version. This is used to make sure that on plugin update, the styles and scripts will be cleared from the cache.
    public $plugin_path = ''; //The path to the plugin folder
    public $supported_fake_email_parts = ['first_name', 'last_name', 'company', 'city', 'postcode', 'country', 'state', 'phone'];

    /**
     * Loads the current plugin version.
     */
    public function load_version()
    {
        if (!function_exists('get_file_data')) {
            $this->version = "000";
            return;
        }
        $plugin_meta = get_file_data($this->plugin_path . 'add-customer-for-woocommerce.php', array('Version'), 'plugin');
        $this->version = (!empty($plugin_meta[0])) ? $plugin_meta[0] : "001";
        return;
    }

    /**
     * Creates a fake email with the domain of the site.
     * It is recommended to setup a catch-all email
     *
     * The email is normalized before the uniqueness check, so the returned address is the final one.
     *
     * @param string $username - A username to start with or null
     * @param int|null $order_id - The order the notices belong to. Falls back to get_the_ID()
     * @return string|false A unique fake email, false if no valid or unique email could be created
     */
    public function create_fake_email($username = null, $order_id = null)
    {
        $name = $this->create_fake_email_name($username);
        $email = $this->normalize_fake_email($name, $order_id);

        if ($email === false) {
            //Nothing valid left of the name (e.g. only non latin characters without the Intl extension). Use a random name instead
            $email = $this->normalize_fake_email(wp_generate_password(10, false), $order_id);
        }
        if ($email === false) {
            return false;
        }

        //Add a number if email already exists
        list($name, $domain_name) = explode('@', $email, 2);
        $number = 0;
        while (email_exists($email) !== false) {
            $number++;
            if ($number >= 100) {
                return false;
            }
            $email = $name . $number . '@' . $domain_name;
        }

        return $email;
    }

    /**
     * Converts the name to a valid email with the fake email domain.
     * Uses the same sanitizing as for the real emails and makes sure that there is room for a two digit number.
     *
     * @param string $name - The name part of the email
     * @param int|null $order_id - The order the notices belong to
     * @return string|false The valid email, false if the email is not valid
     */
    private function normalize_fake_email($name, $order_id = null)
    {
        $domain_name = $this->get_domain_name();
        $email_total_characters = 100; //Maximum length of the email according to the user_email field in wp_users
        $number_characters = 2; //Room for the number added if the email already exists

        $name = str_replace(' ', '_', trim($name)); //Replace the spaces with underlines
        $email = $this->make_email_valid($name . '@' . $domain_name, $order_id);
        if (!is_email($email)) {
            return false;
        }

        //Make sure that the email is not too long
        list($name, $domain_name) = explode('@', $email, 2);
        $max_name_length = $email_total_characters - strlen($domain_name) - 1 - $number_characters;
        $name = rtrim(substr($name, 0, $max_name_length), '.');
        if ($name === '') {
            return false;
        }

        return $name . '@' . $domain_name;
    }

    /**
     * Receives the domain name without the scheme
     * If the option wac_fakemail_format is set and valid, the domain name will be taken from the option.
     *
     * @return string The Domain name.
     */
    public function get_domain_name()
    {
        $custom_format = $this->get_valid_fakemail_format();
        if ($custom_format !== null) {
            list(, $custom_domain) = explode('@', $custom_format, 2);
            if ($custom_domain !== '') {
                return $custom_domain;
            }
        }
        $urlparts = wp_parse_url(home_url());
        return ($urlparts['host'] !== 'localhost') ? $urlparts['host'] : 'local.host';
    }

    /**
     * Returns the custom fake email format from the options, but only if it is valid.
     *
     * @return string|null The format, null if not set or not valid
     */
    private function get_valid_fakemail_format()
    {
        $custom_format = $this->get_wac_option('wac_fakemail_format');
        if (empty($custom_format)) {
            return null;
        }

        $backend_class = new woo_add_customer_backend;
        $format_error = $backend_class->check_option('wac_fakemail_format', $custom_format);
        return (empty($format_error)) ? $custom_format : null;
    }

    /**
     * Creates the name for the email
     * If the option wac_fakemail_format is set and valid, the name will be created accordingly.
     *
     * @param string $username
     * @return string The eMail name
     */
    public function create_fake_email_name($username = null)
    {
        $name = (!empty($username)) ? sanitize_user($username) : wp_generate_password(5, false);

        //Craft the email
        $custom_format = $this->get_valid_fakemail_format();
        if ($custom_format !== null) {
            //Custom format
            $mail_split = explode('@', $custom_format);
            $custom_format_name = (isset($mail_split[0])) ? $mail_split[0] : $custom_format;
            $mail_parts = apply_filters('wac_supported_fake_email_parts', $this->supported_fake_email_parts); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook, renaming would break existing customizations
            if (is_array($mail_parts)) {
                // phpcs:disable WordPress.Security.NonceVerification -- Runs on the WooCommerce order save hook, WooCommerce verifies the nonce before.
                foreach ($mail_parts as $tag_name) {
                    //Add the _billing_ if field exists but only if no custom field exists with the same name
                    $request_name = $tag_name;
                    if (isset($_REQUEST['_billing_' . $tag_name]) and !isset($_REQUEST[$tag_name])) {
                        $request_name = '_billing_' . $tag_name;
                    }
                    if (isset($_REQUEST[$request_name]) and strpos($custom_format_name, $tag_name) !== false) {
                        //Placeholder found in the custom format and data found in request
                        $value = sanitize_text_field(wp_unslash($_REQUEST[$request_name]));
                        $custom_format_name = str_replace('[' . $tag_name . ']', $value, $custom_format_name);
                    }
                }
                // phpcs:enable WordPress.Security.NonceVerification
            }

            //Remove the placeholders that could not be filled, including their name. E.g. [last_name]
            $name = preg_replace('/\[[^\]]*\]/', '', $custom_format_name);
            $name = str_replace(['[', ']'], '', $name); //Remove unclosed brackets
            //Clean up the separators left by the removed placeholders. E.g. "john..doe" => "john.doe", "john." => "john"
            $name = preg_replace('/([._-])\1+/', '$1', $name);
            $name = trim($name, '._-');
        }

        //Make sure that the generated name is not empty
        return (empty($name)) ? wp_generate_password(50, false) : $name;
    }

    /**
     * Disables sending emails to the customer while creating a new user.
     *
     * @param integer $customer_id - Customer ID
     * @return bool true if emails got disabled, false if emails get send.
     */
    public function wac_disable_new_customer_mail($customer_id)
    {
        //Runs on the WooCommerce order save hook, WooCommerce verifies the nonce before
        if (isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] === 'true') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            add_filter('woocommerce_email_enabled_customer_new_account', function ($enabled, $user, $email) {
                return false; //$enabled = false;
            }, 10, 3);
            return true;
        }
        return false;
    }

    /**
     * Checks if the suppress_all_notifications option is activated and if the customer was created by the plugin. If so, the emails will be deactivated.
     *
     * @param int $user_id
     * @return false|void
     */
    public function wac_maybe_disable_all_emails($user_id)
    {
        if ($this->get_wac_option('wac_suppress_all_notification') !== 'yes') {
            return false; //The option to suppress all notifications is not activated, abort
        }
        if (!$user_id) {
            return false;
        }
        $user_id = intval($user_id);

        $user_is_created_by_plugin = get_user_meta($user_id, 'wac_created_by_plugin', true) ?: false;
        $deactivate_emails_for_user = apply_filters('wac_deactivate_user_emails', boolval($user_is_created_by_plugin), intval($user_id)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook, renaming would break existing customizations

        //Skip the deactivation if the user was not created by the plugin or if the filter was set to false
        if (!$deactivate_emails_for_user) {
            return false;
        }
        //Get all the emails templates
        $mailer = WC()->mailer();
        $email_templates = $mailer->get_emails();
        //Adds filters to suppress all the different emails
        $emails = [];
        foreach ($email_templates as $wc_email) {
            add_filter('woocommerce_email_enabled_' . $wc_email->id, function ($enabled) {
                return false;
            });
            $emails[] = $wc_email->id;
        }
        apply_filters('simple_history_log', "Emails blocked for user - by Add Customer", array('emails' => implode(', ', $emails), 'user_id' => $user_id)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of the Simple History plugin
    }
    /**
     * Enqueue the styles of the plugin
     * Located at: plugins/add-customer-for-woocommerce/style/admin-style.css
     *
     * @return void
     */
    public function wac_enqueue_admin_style()
    {
        wp_enqueue_style('wac-admin-style', $this->get_plugin_url('style/admin-style.min.css'), array(), $this->version);
    }

    /**
     * Returns the URL to a file inside the plugin folder
     *
     * @param string $relative_path - The path of the file, relative to the plugin folder
     * @return string The URL of the file
     */
    public function get_plugin_url($relative_path)
    {
        return plugins_url($relative_path, $this->plugin_path . 'add-customer-for-woocommerce.php');
    }

    /**
     * Enqueue the scripts of the plugin
     * Located at: plugins/add-customer-for-woocommerce/include/js/wac-main-script.min.js
     *
     * @return void
     */
    public function wac_enqueue_admin_scripts()
    {
        //Load the unminified script, if SCRIPT_DEBUG is enabled
        $use_unminified_script = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
        $script_file = ($use_unminified_script) ? 'include/js/wac-main-script.js' : 'include/js/wac-main-script.min.js';

        wp_enqueue_script('wac-admin-script', $this->get_plugin_url($script_file), array('jquery', 'wp-i18n'), $this->version, true);
        //The translations are loaded from translate.wordpress.org
        wp_set_script_translations('wac-admin-script', 'add-customer-for-woocommerce');
    }

    /**
     * Uses the translation file of the unminified script for the minified script.
     * The JSON translation files are named after the md5 hash of the unminified script path.
     *
     * @param string|false $relative_path - The relative path of the script
     * @param string $src - The full source URL of the script
     * @return string|false The relative path to use for the translation file
     */
    public function wac_script_translation_path($relative_path, $src)
    {
        $is_wac_script = is_string($relative_path) && strpos($relative_path, 'include/js/wac-main-script.min.js') !== false;
        if ($is_wac_script) {
            return str_replace('.min.js', '.js', $relative_path);
        }
        return $relative_path;
    }

    /**
     * Logs Events to the Simple History Plugin and to the PHP Error Log on error.
     * Some Errors get displayed to the user
     * 
     * @param string $log_type - The log type. Allowed types: added_user, failed_to_add_user, paid_without_login, ...
     * @param string $order_id - The order id
     * @param mixed $args - Args for the vspringf() Function. String or Int 
     * 
     * @return void
     */
    public function log_event($log_type, $order_id, ...$args)
    {
        $additional_log = array();
        $type = 'null';
        $message_suffix = ''; //Appended after the formatting. Must not go through vsprintf, because it can contain "%"

        switch ($log_type) {
            case 'existing_account':
                /* translators: %s: Email address */
                $message = htmlspecialchars(__('Email "%s" already exists. New customer not created.', 'add-customer-for-woocommerce'));
                break;
            case 'no_user_id':
                $message = __('Could not update the customer, because the customer was not found.', 'add-customer-for-woocommerce');
                $type = 'error';
                break;
            case 'added_user':
                /* translators: 1: Username of the customer, 2: Email address */
                $message = htmlspecialchars(__('Added customer: %1$s <%2$s>', 'add-customer-for-woocommerce'));
                $type = 'success';
                break;
            case 'email_send':
                /* translators: %s: Email address */
                $message = __('Email sent to new customer: %s', 'add-customer-for-woocommerce');
                $type = 'success';
                break;
            case 'customer_updated':
                $message = __('Customer successfully updated.', 'add-customer-for-woocommerce');
                $type = 'success';
                $additional_log = array('changed_fields' => $args[0], 'changed_user_id' => $args[1]);
                break;
            case 'no_name':
                $message = __('Could not save customer. No name provided.', 'add-customer-for-woocommerce');
                $type = 'null';
                break;
            case 'failed_to_send_user_mail':
                $message = __('Failed to send email notification to customer.', 'add-customer-for-woocommerce');
                $type = 'error';
                break;
            case 'failed_to_send_user_mail_fakemail':
                $message = __('Email was not sent to user because no email was provided.', 'add-customer-for-woocommerce');
                $type = 'null';
                break;
            case 'invalid_email':
                /* translators: %s: Email address */
                $message = __('The email "%s" is not valid.', 'add-customer-for-woocommerce');
                $type = 'error';
                break;
            case 'failed_to_add_user':
                $message = __('New customer could not be added by Add Customer Plugin. Please contact the Plugin Author.', 'add-customer-for-woocommerce');
                $message_suffix = (isset($args[0])) ? '<br/>' . esc_html(wp_json_encode($args[0])) : '';
                $type = 'error';
                $additional_log = array('wc_create_new_customer' => $args[0], 'user' => $args[1], 'email' => $args[2]);
                //Prints the args with the error message from wc_create_new_customer to the error log
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log($message . " - " . htmlspecialchars(wp_json_encode($args))); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
                $args = array(); //The message has no placeholders, the args are only used for the logs
                break;
            case 'user_role_not_allowed':
                $message = __('The user role you provided is not allowed. Please select another one.', 'add-customer-for-woocommerce');
                $type = 'error';
                break;
            case 'order_linked_to_account':
                $message = __('Order got linked to the user', 'add-customer-for-woocommerce');
                $additional_log = array('order_id' => $order_id, 'email' => $args[0]);
                break;
            case 'order_linked_to_account_failed':
                $message = __('Failed to link the order to the customer', 'add-customer-for-woocommerce');
                $additional_log = array('order_id' => $order_id, 'email' => $args[0], 'message' => $args[1]);
                $type = 'error';
                break;
            case 'paid_without_login':
                /* translators: 1: Order number, 2: Email address of the customer, 3: Payment method title */
                $message = __('Order #%1$s paid without login by customer %2$s with payment method: %3$s', 'add-customer-for-woocommerce');
                $type = 'success';
                $additional_log = array('order_id' => $order_id, 'email' => $args[1], 'payment_method' => $args[2], 'customer_id' => $args[3]);
                $args = array_slice($args, 0, 3);
                break;
            case 'paid_by_shop_manager':
                /* translators: 1: Order number, 2: Email address of the customer, 3: Display name of the shop manager, 4: Payment method title */
                $message = __('Order #%1$s of customer %2$s paid by %3$s with payment method: %4$s', 'add-customer-for-woocommerce');
                $type = 'success';
                $additional_log = array('order_id' => $order_id, 'email' => $args[1], 'paid_by' => $args[2], 'payment_method' => $args[3], 'customer_id' => $args[4], 'paid_by_user_id' => $args[5]);
                $args = array_slice($args, 0, 4);
                break;
            default:
                $message = __('Log type not found!', 'add-customer-for-woocommerce');
                break;
        }
        if (!empty($args)) {
            $msg_trans = vsprintf($message, $args);
        } else {
            $msg_trans = $message;
        }
        $msg_trans .= $message_suffix;
        apply_filters('simple_history_log', "{$msg_trans} - by Add Customer", $additional_log); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of the Simple History plugin
        $this->wac_set_notice($msg_trans, $type, $order_id);
        return;
    }

    /**
     * Logs a message to the simple history logger and prints it out as a notice
     *
     * @param string $log_message
     * @param int $order_id
     * @param string $type - null, error, or success
     * @return void
     */
    public function log_event_message($log_message, $order_id, $type = 'null')
    {
        apply_filters('simple_history_log', "{$log_message} - by Add Customer"); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of the Simple History plugin
        $this->wac_set_notice($log_message, $type, $order_id);
        return;
    }

    /**
     * Loads template to variable.
     * @param string $template_name - Name of the template without extension
     * @param string $subfolder - Name of the Subfolder(s). Base folder is Plugin_dir/templates/
     * @param string $template_args - Arguments to pass to the template
     * 
     * @return string Template content or error Message
     */
    public function load_template_to_var(string $template_name = '', string $subfolder = '', ...$template_args)
    {
        $args = get_defined_vars();
        $path = $this->get_template_location($template_name, $subfolder);

        if (file_exists($path)) {
            ob_start();
            include($path);
            $output_string = ob_get_contents();
            ob_end_clean();
            wp_reset_postdata();
            return $output_string;
        }
        /* translators: 1: Name of the template, 2: Path of the template */
        return esc_html(sprintf(__('Template "%1$s" not found! (%2$s)', 'add-customer-for-woocommerce'), $template_name, $path));
    }

    /**
     * Function to find the template file. First the Child-Theme will be checked. If not found, the file in the plugin will be returned.
     *
     * @param string $template_name - The name of the template.
     * @param string $subfolder - The subfolder the template is in. With tailing \
     * @return string The location of the file.
     */
    public function get_template_location($template_name, $subfolder)
    {
        //Checks if the file exists in the theme or child-theme folder
        $subfolder = (substr($subfolder, -1) !== '/') ? $subfolder . '/' : $subfolder;
        $locate = locate_template('woocommerce/add-customer/' . $subfolder . $template_name . '.php');
        if (empty($locate)) {
            return str_replace('\\', '/', $this->plugin_path . 'templates/' . $subfolder . $template_name . '.php');
        }
        return str_replace('\\', '/', $locate);
    }


    /**
     * Get the option value of the wac options
     * @param string $options_name - The name of the option to get
     * 
     * @return mixed Option value or Null, if option is not found
     */
    public function get_wac_option(string $options_name = '')
    {
        if (empty($options_name)) {
            return null;
        }
        $options = get_option('wac_general_options');

        if (empty($options[$options_name])) {
            return null;
        }
        return $options[$options_name];
    }

    /**
     * Gets the default user role. If no role is set, it will return "customer"
     *
     * @return string The user role
     */
    public function get_default_user_role()
    {
        $role = $this->get_wac_option('wac_default_user_role');
        return (empty($role)) ? 'customer' : $role;
    }

    /**
     * Sends a email with email and password reset link to the new customer
     *  @param string $email - The email address of the recipient
     *  @param string $name - The first name of the user
     * 
     *  @return bool true on success, false on error.
     * 
     */
    public function send_mail_to_new_customer(string $email = '', string $name = '')
    {
        $mailer = WC()->mailer();
        $blog_name = get_bloginfo('name');
        $blog_name = html_entity_decode($blog_name, ENT_QUOTES, 'UTF-8');
        $password_reset_link = $this->get_user_reset_password_link($email);
        if ($password_reset_link === false) {
            return false; //No reset link available, the email would be useless without it
        }
        $message = $this->load_template_to_var('new-account', 'email/', $email, $name, $password_reset_link, $blog_name);
        $from_email_option = $this->get_mail_from();

        $subject = $this->get_mail_subject('wac_template_subject_add_account');
        $headers = array("Content-Type: text/html", "From: " . $from_email_option);
        //Send email
        $send = $mailer->send($email, $subject, $message, $headers);
        return $send;
    }

    /**
     * Returns the email subject from the options.
     *
     * @param string $option_name - The option name to get the text from. Default: wac_template_subject_add_account
     * @return string The subject text
     */
    public function get_mail_subject(string $option_name = 'wac_template_subject_add_account')
    {
        $blog_name = get_bloginfo('name');
        $blog_name = html_entity_decode($blog_name, ENT_QUOTES, 'UTF-8');

        $subject = $this->get_wac_option($option_name);
        if (!empty($subject)) {
            return $subject;
        }
        //The default subject text
        /* translators: %s: Name of the website */
        return sprintf(__("New account created at %s", 'add-customer-for-woocommerce'), $blog_name);
    }

    /**
     * Loads the sender email from the options
     * If no email set, the default wordpress@sitename email will be used.
     *
     * @return string The email address set in the options, or the default email
     */
    public function get_mail_from()
    {
        $email_from = $this->get_wac_option('wac_email_from');
        if (!empty($email_from)) {
            return $email_from;
        }
        //Generate the default email
        $sitename = wp_parse_url(network_home_url(), PHP_URL_HOST);
        if (substr($sitename, 0, 4) === 'www.') {
            $sitename = substr($sitename, 4);
        }

        return 'wordpress@' . $sitename;
    }

    /**
     * Saves a message to be displayed as an admin_notice
     *
     * @param string $notice - The message to display
     * @param string $type - Type of message (success, error)
     * @param int|string $order_id - The order_id / post_id or a string as an identifier
     * @return bool True on success, false on error
     */
    public function wac_set_notice(string $notice, string $type, $order_id = 0)
    {
        $user_id = get_current_user_id();
        $trans_id = "wac_admin_notice_{$user_id}_{$order_id}";
        $notice = $this->wac_format_notice($notice, $type);
        $trans_notices = get_transient($trans_id);
        if (is_array($trans_notices)) {
            $trans_notices[] = $notice;
        } else {
            $trans_notices = array($notice);
        }
        return set_transient($trans_id, $trans_notices, 45);
    }

    /**
     * Formats the given notice. Wraps it around a div and <p></p>
     *
     * @param string $notice
     * @param string $type - Supported types: error, success, default: info
     * @return string The formatted message
     */
    public function wac_format_notice($notice = '', $type = 'info')
    {
        $classes = "";
        switch ($type) {
            case 'error':
                $classes = 'notice notice-error';
                break;
            case 'success':
                $classes = 'notice notice-success';
                break;
            case 'warning':
                $classes = 'notice notice-warning';
                break;

            default:
                $classes = 'notice notice-info';
                break;
        }

        return "<div class='{$classes} wac-notice'><p>{$notice}</p></div>";
    }

    /**
     * Displays the stored messages as admin_notices
     *
     * @param int|string $id_to_get The Id to get
     * @return void
     */
    public function wac_display_notices($id_to_get = null)
    {
        //Show success message on options save screen, but not after saving the settings of other plugins
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only used to select the notices to display
        $is_settings_updated = !empty($_GET['settings-updated']);
        $is_wac_options_page = isset($_GET['page']) && 'wac_general_options' === sanitize_key(wp_unslash($_GET['page']));
        $is_general_notice = empty($id_to_get);
        if ($is_settings_updated && $is_wac_options_page && $is_general_notice) {
            add_settings_error('wac_settings_saved_message', 'wac_settings_saved_message', __('Settings saved', 'add-customer-for-woocommerce'), 'success');
            settings_errors('wac_settings_saved_message');
        }

        $user_id = get_current_user_id();
        //An explicitly requested ID always wins over the ID of the current page
        if (!empty($id_to_get)) {
            $order_id = $id_to_get;
        } else if (isset($_GET['id'])) {
            $order_id = absint($_GET['id']);
        } else if (isset($_GET['post'])) {
            $order_id = absint($_GET['post']);
        } else {
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $trans_id = "wac_admin_notice_{$user_id}_{$order_id}";
        $notices = get_transient($trans_id);

        if (is_array($notices)) {
            foreach ($notices as $notice) {
                echo wp_kses_post($notice);
            }
        }
        delete_transient($trans_id);
    }

    /**
     * Generates the link to the reset password page
     *
     * @param string $user_email - The user email
     * @return string|false The url to the password reset page, false if the user was not found or no reset key could be created
     */
    public function get_user_reset_password_link($user_email)
    {
        //Create the Password reset link
        $user = get_user_by('email', $user_email);
        if (!$user) {
            return false;
        }

        //Can be a WP_Error, e.g. if password resets are disabled for this user or the key could not be saved
        $reset_key = get_password_reset_key($user);
        if (is_wp_error($reset_key)) {
            return false;
        }

        return network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    }

    /**
     * Increases the wooCommerce add customer counter by one.
     * 
     * @param string $type Type to increase. Accepts wac_add_customer_count and wac_edit_customer_count
     * @return bool True if the counter got increased, false otherwise.
     */
    public function increase_wac_counter($type)
    {
        $option_name = ($type === 'add') ? 'wac_add_customer_count' : 'wac_edit_customer_count';
        $value = (int) get_option($option_name);
        return update_option($option_name, $value + 1);
    }

    /**
     * Decreases the add customer counter, if the deleted user was created by the plugin.
     * This keeps the counter in sync with the users that have the wac_created_by_plugin meta (see is_customer_created_miss_match()).
     * Runs on the delete_user hook, before the user meta gets deleted.
     *
     * @param int $user_id - The ID of the deleted user
     * @return void
     */
    public function wac_decrease_counter_on_user_delete($user_id)
    {
        $is_created_by_plugin = (bool) get_user_meta($user_id, 'wac_created_by_plugin', true);
        if (!$is_created_by_plugin) {
            return;
        }

        $value = (int) get_option('wac_add_customer_count');
        if ($value > 0) {
            update_option('wac_add_customer_count', $value - 1);
        }
    }

    /**
     * Replaces all the email parts that are not valid
     * Converts cyrillic and other non latin characters to latin
     * Requires the INTL PHP extension
     * @param string $email - The email to make valid
     * @param int|null $order_id - The order the notice belongs to. Falls back to get_the_ID()
     * @return string The sanitized email
     */
    public function make_email_valid($email, $order_id = null)
    {
        $notice_id = ($order_id !== null) ? $order_id : get_the_ID();
        //Replaces the cyrillic letters
        $orig_email = $email;
        //Make sure that the Intl extension is installed
        if (function_exists('transliterator_transliterate')) {
            //Converts to latin characters
            $email = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $email);
        } elseif (!is_email($email)) {
            //Display notice if email is not valid and php intl extension is not installed
            $this->wac_set_notice(__('Intl PHP extension not installed. Please install it to validate the emails.', 'add-customer-for-woocommerce'), 'error', $notice_id);
        }


        $email = sanitize_email($email); //Remove all un-allowed characters

        return apply_filters('wac_make_email_valid', $email, $orig_email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook, renaming would break existing customizations
    }

    /**
     * Checks if the given email format is valid. Like is_email(), but allows the placeholder characters [] in the name part.
     * The value is not changed. If anything is invalid, an error notice is set and false is returned.
     *
     * @param string $email - The email format, e.g. [first_name].[last_name]@example.com
     * @param string $fieldname - The name of the field. Used as ID for the notice
     * @return string|false The unchanged email format, false if it is not valid
     */
    public function sanitize_placeholder_email($email, $fieldname)
    {
        $error = $this->get_placeholder_email_error($email);
        if ($error !== '') {
            $this->wac_set_notice(esc_html($error), "error", $fieldname);
            return false;
        }
        return $email;
    }

    /**
     * Validates the email format and returns the reason why it is not valid.
     *
     * @param string $email - The email format
     * @return string The error message, empty string if the format is valid
     */
    private function get_placeholder_email_error($email)
    {
        if (strlen($email) < 6) {
            return __('Email is too short', 'add-customer-for-woocommerce');
        }

        $has_one_at = substr_count($email, '@') === 1;
        $starts_with_at = strpos($email, '@') === 0;
        if (!$has_one_at || $starts_with_at) {
            return __('Email must contain one @', 'add-customer-for-woocommerce');
        }

        list($local, $domain) = explode('@', $email, 2);

        //Name part: Same characters as in is_email(), plus [] for the placeholders
        $local_is_valid = preg_match('/^[a-zA-Z0-9!#$%&\[\]\'*+\/=?^_`{|}~\.-]+$/', $local) === 1;
        if (!$local_is_valid) {
            return __('Email contains forbidden characters or is empty', 'add-customer-for-woocommerce');
        }

        //Domain part: At least two parts separated by a dot. Each part only contains letters, numbers and hyphens, but does not start or end with a hyphen
        $domain_parts = explode('.', $domain);
        $has_enough_parts = count($domain_parts) >= 2;
        $all_parts_valid = true;
        foreach ($domain_parts as $domain_part) {
            if (preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i', $domain_part) !== 1) {
                $all_parts_valid = false;
                break;
            }
        }
        if (!$has_enough_parts || !$all_parts_valid) {
            return __('Domain parts are invalid', 'add-customer-for-woocommerce');
        }

        return '';
    }

    /**
     * Replaces all the user name parts that are not valid
     * Converts cyrillic and other non latin characters to latin
     * Requires the INTL PHP extension
     * @param string $user - The username to make valid
     * @param int|null $order_id - The order the notice belongs to. Falls back to get_the_ID()
     * @return string The sanitized username
     */
    public function make_user_valid($user, $order_id = null)
    {
        $notice_id = ($order_id !== null) ? $order_id : get_the_ID();
        //Replaces the cyrillic letters
        $orig_user = $user;
        //Make sure that the Intl extension is installed
        if (function_exists('transliterator_transliterate')) {
            //Converts to latin characters
            $user = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $user);
        } elseif ($user !== sanitize_user($user, true)) {
            //Display notice if user is not valid and php intl extension is not installed
            $this->wac_set_notice(__('Intl PHP extension not installed. Please install it to validate username.', 'add-customer-for-woocommerce'), 'error', $notice_id);
        }
        $user = sanitize_user($user, true); //Remove all un-allowed characters

        //Make sure that the username is not longer than 60 characters
        if (strlen($user) > 60) {
            $user = substr($user, 0, 60);
        }
        return apply_filters('wac_make_user_valid', $user, $orig_user); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook, renaming would break existing customizations
    }

    /**
     * Returns a array with user roles. It only allows the roles that the current user can edit.
     * Users without the promote_users capability (e.g. shop managers) only get the default role set by the admin.
     * The administrator role is never included, not even for admins.
     * With the filter wac_get_user_roles, you can add a custom role, or one that got previously filtered out
     *
     * @return array The roles. ['slug' => 'Name']
     */
    public function get_user_role_array()
    {
        $can_promote_users = current_user_can('promote_users');

        if ($can_promote_users) {
            $roles = $this->get_editable_role_names();
        } else {
            $roles = $this->get_default_role_as_array();
        }

        //Customers should never be created as administrators
        unset($roles['administrator']);

        return apply_filters('wac_get_user_roles', $roles); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook, renaming would break existing customizations
    }

    /**
     * Returns the roles the current user is allowed to edit, with translated names.
     *
     * @return array The roles. ['slug' => 'Name']
     */
    private function get_editable_role_names()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $roles = [];
        foreach (get_editable_roles() as $role_slug => $role_details) {
            $roles[$role_slug] = translate_user_role($role_details['name']);
        }
        return $roles;
    }

    /**
     * Returns the default user role defined by the admin, with its translated name.
     *
     * @return array The role. ['slug' => 'Name'] or an empty array, if the role does not exist
     */
    private function get_default_role_as_array()
    {
        $default_role = $this->get_default_user_role();
        $role_names = wp_roles()->get_names();

        if (!isset($role_names[$default_role])) {
            return [];
        }
        return [$default_role => translate_user_role($role_names[$default_role])];
    }

    /**
     * Checks if the given role is registered in WordPress.
     *
     * @param string $role_slug - The slug of the role
     * @return bool True if the role exists, false otherwise
     */
    public function is_existing_user_role($role_slug)
    {
        return wp_roles()->is_role($role_slug);
    }

    /**
     * Checks if all the customers are created by the plugin.
     * This function is used to detect, if any users are created before Version 1.9
     *
     * @return bool true if there is a miss match, false if all users are created by the plugin
     */
    public function is_customer_created_miss_match()
    {
        $users_created_option = intval(get_option('wac_add_customer_count'));
        $users_created_in_meta = count($this->get_users_created_by_plugin());
        if ($users_created_in_meta !== $users_created_option) {
            return true; //Not all users are created by the plugin
        } else {
            return false; //No miss match, all users created by the plugin
        }
    }

    /**
     * Returns all the users created by the plugin
     *
     * @return array Empty array if no users are found, array with the user ids otherwise
     */
    public function get_users_created_by_plugin()
    {
        //Only used on the settings and users page
        $args = [
            'meta_key'   => 'wac_created_by_plugin', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => true, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'fields' => 'ID'
        ];

        $user_query = new WP_User_Query($args);
        return $user_query->get_results();
    }
}
