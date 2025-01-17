<?php

/**
 * Class for managing the compatibility issues
 * 
 * @class       wac_compatibility
 * @version     1.6.5
 * @package     WAC\classes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class wac_compatibility
{

    /**
     * Removes actions from other plugins to fix issues
     * This function is called with the init_admin action
     *
     */
    public static function add_fixes()
    {
        //Removes actions from other plugins to fix issues

        //Simple Cloudflare Turnstile — The new user-friendly alternative to CAPTCHA
        //https://wordpress.org/plugins/simple-cloudflare-turnstile/
        //Tested with Version: 1.17.1
        if (is_admin() and is_plugin_active('simple-cloudflare-turnstile/simple-cloudflare-turnstile.php')) {
            remove_action('woocommerce_register_post', 'cfturnstile_woo_register_check');
        }

        //Workaround for Dokan – Best WooCommerce Multivendor Marketplace Solution – Build Your Own Amazon, eBay, Etsy
        //https://wordpress.org/plugins/dokan-lite/
        //Tested with Version: 3.9.5
        //Removes the nonce check on wc-orders page
        if (is_admin() and isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] == 'true') {
            add_filter('dokan_register_nonce_check', function () {
                return false;
            });
        }

        //Workaround for Enhanced Cloudflare Turnstile
        //https://woocommerce.com/products/enhanced-cloudflare-turnstile/
        //Tested with Version: 1.0.2
        //Removed the register hook if new customer gets added
        if (is_admin() and isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] == 'true') {
            remove_action( 'woocommerce_register_post', 'ecft_validate_woo_register_form', 10 );
        }
    }

    
    /**
     * Fixes some issues on the fly
     *
     * @return void
     */
    public static function add_fixes_now()
    {
        //Workaround for reCAPTCHA for WooCommerce
        //https://wordpress.org/plugins/recaptcha-woo/
        //Tested with Version: 1.3.3
        //Removes the check when a customer is created by the add customer for woocommerce plugin
        if (is_admin() and isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] == 'true') {
            add_filter('option_rcfwc_woo_register', function ($e) {
                return false;
            });
        }
    }
}
