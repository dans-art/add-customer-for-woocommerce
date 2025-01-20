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
     * @param string $username - A username to start with or null
     * @return string A unique fake email
     */
    public function create_fake_email($username = null)
    {
        $domain_name = $this->get_domain_name();
        $number = '';
        $name = $this->create_fake_email_name($username);
        //Add a number if email already exists
        while ((get_user_by('email', $name . $number . '@' . $domain_name) !== false) and $number < 100) {
            if (empty($number)) {
                $number = 0;
            }
            $number++;
        }
        if ($number == 100) {
            return false;
        }
        $email = $name . $number . '@' . $domain_name;
        $email = str_replace(' ', '_', $email); //Replace the spaces with underlines
        $email = trim($email); //Removes all the other whitespaces, tabs, new-lines, etc.
        return $email;
    }

    /**
     * Receives the domain name without the scheme
     * If the option wac_fakemail_format is set, the domain name will be taken from the option. 
     *
     * @return string The Domain name.
     */
    public function get_domain_name()
    {
        $custom_format = $this->get_wac_option('wac_fakemail_format');
        $exp = ($custom_format !== null) ? explode('@', $custom_format) : null;
        //$exp[0] = email Name, $exp[1] = domain name
        if (isset($exp[1])) {
            return $exp[1];
        }
        $urlparts = parse_url(home_url());
        return ($urlparts['host'] !== 'localhost') ? $urlparts['host'] : 'local.host';
    }

    /**
     * Creates the name for the email
     * If the option wac_fakemail_format is set, the name will be created accordingly. 
     *
     * @param string $username
     * @return string The eMail name
     */
    public function create_fake_email_name($username = null)
    {
        $backend_class = new woo_add_customer_backend;
        $name = (!empty($username)) ? sanitize_user($username) : wp_generate_password(5, false);

        //Craft the email
        $custom_format = $this->get_wac_option('wac_fakemail_format');
        if (!empty($backend_class->check_option('wac_fakemail_format', $custom_format))) {
            //Email set in the options is not valid. Return the original name
            return $name;
        }
        if (!empty($custom_format)) {
            //Custom format
            $mail_split = explode('@', $custom_format);
            $custom_format_name = (isset($mail_split[0])) ? $mail_split[0] : $custom_format;
            $mail_parts = apply_filters('wac_supported_fake_email_parts', $this->supported_fake_email_parts);
            if (is_array($mail_parts)) {
                foreach ($mail_parts as $tag_name) {
                    //Add the _billing_ if field exists but only if no custom field exists with the same name
                    $request_name = $tag_name;
                    if (isset($_REQUEST['_billing_' . $tag_name]) and !isset($_REQUEST[$tag_name])) {
                        $request_name = '_billing_' . $tag_name;
                    }
                    if (isset($_REQUEST[$request_name]) and strpos($custom_format_name, $tag_name) !== false) {
                        //Placeholder found in the custom format and data found in request
                        $value = $_REQUEST[$request_name];
                        $custom_format_name = str_replace('[' . $tag_name . ']', $value, $custom_format_name);
                    }
                }
            }

            $name = str_replace(['[', ']'], '', $custom_format_name); //Remove tags if any left
        }
        //Make sure that the generated name is not empty
        return (empty($name)) ? wp_generate_password(5, false) : $name;
    }

    /**
     * Disables sending emails to the customer while creating a new user.
     *
     * @param integer $customer_id - Customer ID
     * @return bool true if emails got disabled, false if emails get send.
     */
    public function wac_disable_new_customer_mail($customer_id)
    {
        if (isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] === 'true') {
            add_filter('woocommerce_email_enabled_customer_new_account', function ($enabled, $user, $email) {
                return false; //$enabled = false;
            }, 10, 3);
            return true;
        }
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
        $deactivate_emails_for_user = apply_filters('wac_deactivate_user_emails', boolval($user_is_created_by_plugin), intval($user_id));

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
        apply_filters('simple_history_log', "Emails blocked for user - by Add Customer", array('emails' => implode(', ', $emails), 'user_id' => $user_id));
    }
    /**
     * Loads the translation of the plugin.
     * First it checks for downloaded translations by Wordpress, else it will search for the the translation in the plugin dir.
     * Located at: plugins/add-customer-for-woocommerce/languages/
     *
     * @return void
     */
    public function wac_load_textdomain()
    {
        //Search also in the wp-content/language folder
        load_textdomain('wac', $this->wac_get_home_path() . 'wp-content/languages/plugins/add-customer-for-woocommerce-' . determine_locale() . '.mo');
        //Try to load the file from the plugin-dir
        load_textdomain('wac', $this->wac_get_home_path() . 'wp-content/plugins/add-customer-for-woocommerce/languages/wac-' . determine_locale() . '.mo');
    }

    /**
     * Gets the Home Path. Workaround if WP is not completely loaded yet. 
     *
     * @return string  Full filesystem path to the root of the WordPress installation. (/var/www/htdocs/)
     */
    public function wac_get_home_path()
    {
        if (function_exists('get_home_path')) {
            return get_home_path();
        }
        $home    = set_url_scheme(get_option('home'), 'http');
        $siteurl = set_url_scheme(get_option('siteurl'), 'http');

        if (!empty($home) && 0 !== strcasecmp($home, $siteurl)) {
            $wp_path_rel_to_home = str_ireplace($home, '', $siteurl); /* $siteurl - $home */
            $pos                 = strripos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']), trailingslashit($wp_path_rel_to_home));
            $home_path           = substr($_SERVER['SCRIPT_FILENAME'], 0, $pos);
            $home_path           = trailingslashit($home_path);
        } else {
            $home_path = ABSPATH;
        }

        return str_replace('\\', '/', $home_path);
    }
    /**
     * Registers the custom settings Field
     *
     * @return void
     */
    public function wac_add_settings_section_init()
    {
        register_setting('general', 'wac_add_customer');
    }

    /**
     * Enqueue the styles of the plugin
     * Located at: plugins/add-customer-for-woocommerce/style/admin-style.css
     *
     * @return void
     */
    public function wac_enqueue_admin_style()
    {
        wp_enqueue_style('wac-admin-style', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/style/admin-style.min.css', array(), $this->version);
    }

    /**
     * Enqueue the scripts of the plugin
     * Located at: plugins/add-customer-for-woocommerce/include/js/wac-main-script.min.js
     *
     * @return void
     */
    public function wac_enqueue_admin_scripts()
    {
        if (WP_DEBUG) {
            wp_enqueue_script('wac-admin-script', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/include/js/wac-main-script.js', array('jquery', 'wp-i18n'), $this->version);
        } else {
            wp_enqueue_script('wac-admin-script', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/include/js/wac-main-script.min.js', array('jquery', 'wp-i18n'), $this->version);
        }
    }

    /**
     * Logs Events to the Simple History Plugin and to the PHP Error Log on error.
     * Some Errors get displayed to the user
     * 
     * @param string $log_type - The log type. Allowed types: added_user, failed_to_add_user
     * @param string $order_id - The order id
     * @param mixed $args - Args for the vspringf() Function. String or Int 
     * 
     * @return void
     */
    public function log_event($log_type, $order_id, ...$args)
    {
        $additional_log = array();
        $type = 'null';

        switch ($log_type) {
            case 'existing_account':
                $message = htmlspecialchars(__('Email "%s" already exists. New customer not created.', 'wac'));
                break;
            case 'no_user_id':
                $message = __('Could not update the customer, because the customer was not found.', 'wac');
                $type = 'error';
                break;
            case 'added_user':
                $message = htmlspecialchars(__('Added customer: %s <%s>', 'wac'));
                $type = 'success';
                break;
            case 'email_send':
                $message = __('Email sent to new customer: %s', 'wac');
                $type = 'success';
                break;
            case 'customer_updated':
                $message = __('Customer successfully updated.', 'wac');
                $type = 'success';
                $additional_log = array('changed_fields' => $args[0], 'changed_user_id' => $args[1]);
                break;
            case 'no_name':
                $message = __('Could not save customer. No name provided.', 'wac');
                $type = 'null';
                break;
            case 'failed_to_send_user_mail':
                $message = __('Failed to send email notification to customer.', 'wac');
                $type = 'error';
                break;
            case 'failed_to_send_user_mail_fakemail':
                $message = __('Email was not sent to user because no email was provided.', 'wac');
                $type = 'null';
                break;
            case 'invalid_email':
                $message = sprintf(__('The email "%s" is not valid.', 'wac'), $args[0]);
                $type = 'error';
                break;
            case 'failed_to_add_user':
                $message = __('New customer could not be added by Add Customer Plugin. Please contact the Plugin Author.', 'wac');
                $message .= (isset($args[0])) ? '<br/>' . json_encode($args[0]) : '';
                $type = 'error';
                $additional_log = array('wc_create_new_customer' => $args[0], 'user' => $args[1], 'email' => $args[2]);
                error_log($message . " - " . htmlspecialchars(json_encode($args))); //Prints the args with the error message from wc_create_new_customer to the error log
                break;
            case 'user_role_not_allowed':
                $message = __('The user role you provided is not allowed. Please select another one.', 'wac');
                $type = 'error';
                break;
            case 'order_linked_to_account':
                $message = __('Order got linked to the user', 'wac');
                $additional_log = array('order_id' => $order_id, 'email' => $args[0]);
                break;
            case 'order_linked_to_account_failed':
                $message = __('Failed to link the order to the customer', 'wac');
                $additional_log = array('order_id' => $order_id, 'email' => $args[0], 'message' => $args[1]);
                $type = 'error';
                break;
            default:
                $message = __('Log type not found!', 'wac');
                break;
        }
        if (!empty($args)) {
            $msg_trans = vsprintf($message, $args);
        } else {
            $msg_trans = $message;
        }
        apply_filters('simple_history_log', "{$msg_trans} - by Add Customer", $additional_log);
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
        apply_filters('simple_history_log', "{$log_message} - by Add Customer");
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
        return sprintf(__('Template "%s" not found! (%s)', 'plek'), $template_name, $path);
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
        return sprintf(__("New account created at %s", 'wac'), $blog_name);
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
        //Show success message on options save screen
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] && empty($id_to_get)) {
            add_settings_error('wac_settings_saved_message', 'wac_settings_saved_message', __('Settings saved', 'wac'), 'success');
            settings_errors('wac_settings_saved_message');
        }

        $user_id = get_current_user_id();
        if (isset($_GET['id'])) {
            $order_id = $_GET['id'];
        } else if (isset($_GET['post'])) {
            $order_id = $_GET['post'];
        } else if (!empty($id_to_get)) {
            $order_id = $id_to_get;
        } else {
            return;
        }
        $trans_id = "wac_admin_notice_{$user_id}_{$order_id}";
        $notices = get_transient($trans_id);

        if (is_array($notices)) {
            foreach ($notices as $notice) {
                echo $notice;
            }
        }
        delete_transient($trans_id);
    }

    /**
     * Generates the link to the reset password page
     *
     * @param string $user_email - The user email
     * @return string The url to the password reset page
     */
    public function get_user_reset_password_link($user_email)
    {
        //Create the Password reset link
        $user = get_user_by('email', $user_email);
        $user_login = $user->user_login;
        $reset_key = get_password_reset_key($user);
        return network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user_login), 'login');
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
     * Replaces all the email parts that are not valid
     * Converts cyrillic and other non latin characters to latin
     * Requires the INTL PHP extension
     * @return void
     */
    public function make_email_valid($email)
    {
        //Replaces the cyrillic letters
        $orig_email = $email;
        //Make sure that the Intl extension is installed
        if (function_exists('transliterator_transliterate')) {
            //Converts to latin characters
            $email = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $email);
        } elseif (!is_email($email)) {
            //Display notice if email is not valid and php intl extension is not installed
            $this->wac_set_notice(__('Intl PHP extension not installed. Please install it to validate the emails.', 'wac'), 'error', get_the_ID());
        }


        $email = sanitize_email($email, true); //Remove all un-allowed characters

        return apply_filters('wac_make_email_valid', $email, $orig_email);
    }

    /**
     * Checks if the given email is valid. Same as sanitize_email but allowing the special characters []
     *
     * @param string $email
     * @param string $fieldname
     * @return string The sanitized email
     */
    public function sanitize_placeholder_email($email, $fieldname)
    {
        // Test for the minimum length the email can be.
        if (strlen($email) < 6) {
            $this->wac_set_notice(esc_html__('Email is too short', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Test for an @ character after the first position.
        if (strpos($email, '@', 1) === false) {
            $this->wac_set_notice(esc_html__('Email must contain one @', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Split out the local and domain parts.
        list($local, $domain) = explode('@', $email, 2);

        /*
             * LOCAL PART
             * Test for invalid characters.
             */
        $local = preg_replace('/[^a-zA-Z0-9!#$%&\[\]\'*+\/=?^_`{|}~\.-]/', '', $local);
        if ('' === $local) {
            $this->wac_set_notice(esc_html__('Email contains forbidden characters or is empty', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        /*
             * DOMAIN PART
             * Test for sequences of periods.
             */
        $domain = preg_replace('/\.{2,}/', '', $domain);
        if ('' === $domain) {
            $this->wac_set_notice(esc_html__('Domain parts must contain at least one dot', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Test for leading and trailing periods and whitespace.
        $domain = trim($domain, " \t\n\r\0\x0B.");
        if ('' === $domain) {
            $this->wac_set_notice(esc_html__('Domain parts are invalid', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Split the domain into subs.
        $subs = explode('.', $domain);

        // Assume the domain will have at least two subs.
        if (2 > count($subs)) {
            $this->wac_set_notice(esc_html__('Domain parts are invalid', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Create an array that will contain valid subs.
        $new_subs = array();

        // Loop through each sub.
        foreach ($subs as $sub) {
            // Test for leading and trailing hyphens.
            $sub = trim($sub, " \t\n\r\0\x0B-");

            // Test for invalid characters.
            $sub = preg_replace('/[^a-z0-9-]+/i', '', $sub);

            // If there's anything left, add it to the valid subs.
            if ('' !== $sub) {
                $new_subs[] = $sub;
            }
        }

        // If there aren't 2 or more valid subs.
        if (2 > count($new_subs)) {
            $this->wac_set_notice(esc_html__('Domain parts are invalid', 'wac'), "error", $fieldname);
            return htmlspecialchars($email);
        }

        // Join valid subs into the new domain.
        $domain = implode('.', $new_subs);

        // Put the email back together.
        $sanitized_email = $local . '@' . $domain;

        // Congratulations, your email made it!
        return $sanitized_email;
    }

    /**
     * Replaces all the user name parts that are not valid
     * Converts cyrillic and other non latin characters to latin
     * Requires the INTL PHP extension
     * @return void
     */
    public function make_user_valid($user)
    {
        //Replaces the cyrillic letters
        $orig_user = $user;
        //Make sure that the Intl extension is installed
        if (function_exists('transliterator_transliterate')) {
            //Converts to latin characters
            $user = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $user);
        } elseif ($user !== sanitize_user($user, true)) {
            //Display notice if user is not valid and php intl extension is not installed
            $this->wac_set_notice(__('Intl PHP extension not installed. Please install it to validate username.', 'wac'), 'error', get_the_ID());
        }
        $user = sanitize_user($user, true); //Remove all un-allowed characters

        return apply_filters('wac_make_user_valid', $user, $orig_user);
    }

    /**
     * Returns a array with user roles. If the current user is not an admin, it will remove the admin role from the results
     * 
     * @return array The roles. ['slug' => 'Name']
     */
    public function get_user_role_array()
    {
        $current_user = wp_get_current_user();
        $user_is_admin = (array_search('administrator', $current_user->caps) !== false) ? true : false;
        $wp_roles = new WP_Roles();
        $roles = [];
        foreach ($wp_roles->role_names as $key => $value) {
            if (!$user_is_admin and $key === 'administrator') {
                continue;
            }
            $roles[$key] = $value;
        }
        return apply_filters('wac_get_user_roles', $roles);
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
        $users_created_in_meta = count($this -> get_users_created_by_plugin());
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
    public function get_users_created_by_plugin(){
        $args = [
            'meta_key'   => 'wac_created_by_plugin',
            'meta_value' => true,
            'fields' => 'ID'
        ];

        $user_query = new WP_User_Query($args);
        return $user_query->get_results();
    }
}
