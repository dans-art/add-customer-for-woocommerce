<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Various helper methods.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
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
        if(!function_exists('get_file_data')){
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
        while (get_user_by('email', $name . $number . '@' . $domain_name) !== false) {
            $number = (int)($number === '') ? 1 : $number++;
        }
        $email = $name . $number . '@' . $domain_name;
        return filter_var($email, FILTER_SANITIZE_EMAIL);
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
        $exp = explode('@', $custom_format);
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

            foreach ($this->supported_fake_email_parts as $tag_name) {
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
            return str_replace(['[', ']'], '', $custom_format_name); //Remove tags if any left
        }
        return $name;
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
        wp_enqueue_style('wac-admin-style', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/style/admin-style.css', array(), $this->version);
    }

    /**
     * Enqueue the scripts of the plugin
     * Located at: plugins/add-customer-for-woocommerce/include/js/wac-main-script.min.js
     *
     * @return void
     */
    public function wac_enqueue_admin_scripts()
    {
        wp_enqueue_script('wac-admin-script', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/include/js/wac-main-script.min.js', array('jquery'), $this->version);
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
                $message = htmlspecialchars(__('Email "%s" already exists. No new customer got created.', 'wac'));
                break;
            case 'added_user':
                $message = htmlspecialchars(__('Added customer: %s <%s>', 'wac'));
                $type = 'success';
                break;
            case 'email_send':
                $message = __('Email send to new customer: %s', 'wac');
                $type = 'success';
                break;
            case 'no_name':
                $message = __('Could not save customer. No Name provided.', 'wac');
                $type = 'null';
                break;
            case 'failed_to_send_user_mail':
                $message = __('Failed to send email notification to customer.', 'wac');
                $type = 'error';
                break;
            case 'failed_to_send_user_mail_fakemail':
                $message = __('Email was not send to user because no email was provided.', 'wac');
                $type = 'null';
                break;
            case 'failed_to_add_user':
                $message = __('New customer could not be added by Add Customer Plugin. Please contact the Plugin Author.', 'wac');
                $type = 'error';
                $additional_log = array('wc_create_new_customer' => $args[0], 'user' => $args[1], 'email' => $args[2]);
                error_log($message . " - " . htmlspecialchars(json_encode($args))); //Prints the args with the error message from wc_create_new_customer to the error log
                break;
            default:
                $message = __('Log Type not found!', 'wac');
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
        $locate = locate_template('woocommerce/add-customer/'.$subfolder.$template_name.'.php');
        if(empty($locate)){
            return str_replace('\\','/', $this->plugin_path . 'templates/' . $subfolder . $template_name . '.php');
        }
        return str_replace('\\','/', $locate);
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
    public function get_mail_subject(string $option_name = 'wac_template_subject_add_account'){
        $blog_name = get_bloginfo('name');
        $blog_name = html_entity_decode($blog_name, ENT_QUOTES, 'UTF-8');

        $subject = $this->get_wac_option($option_name);
        if(!empty($subject)){
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
    public function get_mail_from(){
        $email_from = $this->get_wac_option('wac_email_from');
        if(!empty($email_from)){
            return $email_from;
        }
        //Generate the default email
        $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
        if ( substr( $sitename, 0, 4 ) === 'www.' ) {
            $sitename = substr( $sitename, 4 );
        }

        return 'wordpress@' . $sitename;
    }

    /**
     * Saves a message to be displayed as an admin_notice
     *
     * @param string $notice - The message to display
     * @param string $type - Type of message (success, error)
     * @param int $order_id - The order_id / post_id
     * @return bool True on success, false on error
     */
    public function wac_set_notice(string $notice, string $type, $order_id)
    {
        $user_id = get_current_user_id();
        $trans_id = "wac_admin_notice_{$user_id}_{$order_id}";
        $classes = "";
        switch ($type) {
            case 'error':
                $classes = 'notice notice-error';
                break;
            case 'success':
                $classes = 'notice notice-success';
                break;

            default:
                $classes = 'notice notice-info';
                break;
        }
        $notice = "<div class='{$classes}'><p>{$notice}</p></div>";
        $trans_notices = get_transient($trans_id);
        if (is_array($trans_notices)) {
            $trans_notices[] = $notice;
        } else {
            $trans_notices = array($notice);
        }
        return set_transient($trans_id, $trans_notices, 45);
    }

    /**
     * Displays the stored messages as admin_notices
     *
     * @return void
     */
    public function wac_display_notices()
    {
        add_action('admin_notices', function () {
            $user_id = get_current_user_id();
            $order_id = (!empty($_GET['post'])) ? $_GET['post'] : 0;
            $trans_id = "wac_admin_notice_{$user_id}_{$order_id}";

            $notices = get_transient($trans_id);
            if (is_array($notices)) {
                foreach ($notices as $notice) {
                    echo $notice;
                }
            }
            delete_transient($trans_id);
        });
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
}
