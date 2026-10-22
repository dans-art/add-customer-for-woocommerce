<?php

/**
 * Plugin Name:          Add Customer for WooCommerce
 * Plugin URI:           https://dev.dans-art.ch/blog/wordpress/add-customer-for-woocommerce/
 * Description:          Allows you to add a customer when creating a new order on the orders page
 * Version:              1.10.0
 * Requires at least:    5.4.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Dan's Art
 * Author URI:           https://dev.dans-art.ch
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          add-customer-for-woocommerce
 *
 * WC requires at least: 7.4.1
 * WC tested up to:      11.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Load the classes
 */
require_once('include/tools/helper.php');
require_once('include/classes/wac.php');
require_once('include/classes/wac-admin.php');
require_once('include/classes/wac-backend.php');
require_once('include/classes/wac-compatibility.php');

//Make sure all other Plugins are loaded, before running this.
add_action('plugins_loaded', function () {
    $wac = new woo_add_customer();
    $wac->wac_admin_init();
    //Add fixes to prevent plugin incompatibilities
});

add_action('admin_init', function () {
    wac_compatibility::add_fixes();
});
//Add fixes to prevent plugin incompatibilities. This will be executed right now
wac_compatibility::add_fixes_now();
