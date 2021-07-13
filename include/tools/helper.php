<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Various helper methods.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 *
 */

//Include a better debugger
include_once('kint.phar');

/**
 * Outputs the message as a Json to the error log
 * For easier debugging
 *
 * @param mixed $msg - Any Object, Array or String
 * @return void
 */
function l($msg)
{
    error_log(json_encode($msg));
}


class woo_add_customer_helper
{
    protected $version = '1.1';
    public $plugin_path = '';
    //public $admin_notices = array();

    public function __construct()
    {
    }

    /**
     * Creates a fake email with the domain of the site.
     * It is recomended to setup a catch-all email
     *
     * @param string $username - A username to start with or null
     * @return void
     */
    public function create_fake_email($username = null)
    {
        $urlparts = parse_url(home_url());
        $domain_name = ($urlparts['host'] !== 'localhost') ? $urlparts['host'] : 'local.host';
        $number = '';
        $name = (!empty($username)) ? sanitize_user($username) : wp_generate_password(5, false);
        while (get_user_by('email', $name . $number . '@' . $domain_name) !== false) {
            $number = (int)($number === '') ? 1 : $number++;
        }
        $email = $name . $number . '@' . $domain_name;
        return filter_var($email, FILTER_SANITIZE_EMAIL);
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
     * Located at: plugins/add-customer-for-woocommerce/languages/
     *
     * @return void
     */
    public function wac_load_textdomain()
    {
        load_textdomain('wac', $this->wac_get_home_path() . 'wp-content/plugins/add-customer-for-woocommerce/languages/wac-' . determine_locale() . '.mo');
    }

    /**
     * Gets the Home Path. Workaround if WP is not completly loaded yet. 
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
        wp_enqueue_style('wac-admin', get_option('siteurl') . '/wp-content/plugins/add-customer-for-woocommerce/style/admin-style.css');
    }

    /**
     * Logs Events to the Simple History Plugin and to the PHP Error Log on error.
     * Some Errors get displayed to the user
     * 
     * @param string $log_type - The log type. Allowed types: added_user, failed_to_add_user
     * @param mixed $args - Args for the vspringf() Function. String or Int 
     * 
     * @return void
     */
    public function log_event($log_type, ...$args)
    {
        $additional_log = array();
        $print_log = false;
        switch ($log_type) {
            case 'added_user':
                $message = __('Added User "%s <%s>" by Add Customer', 'wac');
                break;
            case 'no_name':
                $message = __('Could not save user. No Name provided - Add Customer.', 'wac');
                break;
            case 'failed_to_send_user_mail':
                $message = __('Failed to send email notification to user - Add Customer.', 'wac');
                break;
            case 'failed_to_add_user':
                $message = __('New User could not be added by Add Customer Plugin. Please contact the Plugin Author.', 'wac');
                $additional_log = array('wc_create_new_customer' => $args[0], 'user' => $args[1], 'email' => $args[2]);
                error_log($message . " - " . json_encode($args)); //Prints the args with the error message from wc_create_new_customer to the error log
                $print_log = $message;
                break;
            default:
                $message = __('Log Type not found!', 'wac');
                break;
        }
        $msg_trans = vsprintf($message, $args);
        if ($print_log) {
            $this->display_message($print_log);
        }
        apply_filters('simple_history_log', $msg_trans, $additional_log);
        return;
    }

    /**
     * Prints out a Woocommerce Admin notice to the user.
     * @param string $msg - Message to display
     * 
     * @return void
     */
    public function display_message($msg)
    {
        $this->adminnotice->add_custom_notice("wac_notice", $msg);
        $this->adminnotice->output_custom_notices();
        return;
    }

    /**
     * Loads template to variable.
     * @param string $template_name - Name of the template without extension
     * @param string $subfolder - Name of the Subfolder(s). Base folder is Plugin_dir/templates/
     * @param string $template_args - Arguments to pass to the template
     * 
     * @return string Template content or eror Message
     */
    public function load_template_to_var(string $template_name = '', string $subfolder = '', ...$template_args)
    {
        $args = get_defined_vars();
        $path = $this->plugin_path . 'templates/' . $subfolder . $template_name . '.php';
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
     * Get the option value of the wac options
     * @param string $template_args - Arguments to pass to the template
     * 
     * @return mixed Option value or Null, if option is not found
     */
    public function get_wac_option(string $options_name = '')
    {
        $options = get_option('wac_general_options');
        if (empty($options_name)) {
            return null;
        }
        if (empty($options[$options_name])) {
            return null;
        }
        return $options[$options_name];
    }


    public function send_mail_to_new_customer(string $email = '', string $name = '', string $password = '')
    {
        $mailer = WC()->mailer();
        $blog_name = get_bloginfo('name');
        $message = $this->load_template_to_var('new-account', 'email/', $email, $name, $password, $blog_name);
        $template = 'new-account.php';

        $subject = sprintf(__("New account created at %s", 'wac'), $blog_name);
        $headers = "Content-Type: text/html\r\n";
        //Send email
        $send = $mailer->send($email, $subject, $message, $headers);
        return $send;
    }
}
