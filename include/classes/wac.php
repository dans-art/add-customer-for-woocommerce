<?php

/**
 * Main Class. Includes the plugins functionalities to front- and backend. 
 * 
 * @class       woo_add_customer
 * @version     1.6.5
 * @package     WAC\classes
 */
if (!defined('ABSPATH')) {
    exit;
}
class woo_add_customer extends woo_add_customer_helper
{

    public function __construct()
    {
        $this->plugin_path = WP_PLUGIN_DIR . '/add-customer-for-woocommerce/';
    }
    /**
     * Loads the admin class
     *
     * @return void
     */
    public function wac_admin_init()
    {
        $adminclass = new woo_add_customer_admin;
    }
}