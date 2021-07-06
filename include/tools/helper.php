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
     * @return void
     */
    public function wac_disable_new_customer_mail($customer_id)
    {
        if (isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] === 'true') {
            add_filter('woocommerce_email_enabled_customer_new_account', function ($enabled, $user, $email) {
                return false; //$enabled = false;
            }, 10, 3);
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
    }

    /**
     * Prints out a Woocommerce Admin notice to the user.
     * @param string $msg - Message to display
     */
    public function display_message($msg)
    {
        $this -> adminnotice ->add_custom_notice("wac_notice",$msg);
        $this -> adminnotice->output_custom_notices();
        return;
    }
}
