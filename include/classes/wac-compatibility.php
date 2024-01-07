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
    }
}
