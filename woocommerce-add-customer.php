<?php

/**
 * Plugin Name: Woocommerce Add Customer
 * Description: Allows you to add a customer when a new order is created via backend.
 * Version: 0.1
 * Author: DansArt.
 * Author URI: http://dans-art.ch
 * Text Domain: wac
 * License: GPLv2 or later
 *
 */


/**
 * Load the classes and tools
 */
require_once('include/tools/helper.php');
require_once('include/classes/wac.php');
require_once('include/classes/wac-admin.php');


$wac = new woo_add_customer();
$wac->wac_admin_init();
