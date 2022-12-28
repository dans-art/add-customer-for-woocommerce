<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Class for managing the compatibility issues
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
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

    }
}
