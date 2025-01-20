<?php

/**
 * Class for managing the Admin Backend. Add field, process data on order save. 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 * 
 * @class       woo_add_customer_admin
 * @version     1.7
 * @package     WAC\classes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class woo_add_customer_admin extends woo_add_customer_helper
{

    protected $adminnotice = null; //Variable for the WC_Admin_Notices
    public $custom_billing_fields = array(); //The custom billing fields to save.
    public $custom_shipping_fields = array(); //The custom shipping fields to save.
    public $default_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'email', 'phone'); //The default WooCommerce billing and shipping fields


    /**
     * Add the Actions and hooks, loads the classes and version
     */
    public function __construct()
    {

        $this->plugin_path = WP_PLUGIN_DIR . '/add-customer-for-woocommerce/';

        //Load the current version
        $this->load_version();

        //Remove all Admin Notices
        if (class_exists('WC_Admin_Notices')) {
            $this->adminnotice = new WC_Admin_Notices();
        }
        add_action('init', [$this, 'wac_load_textdomain']); //load language 
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'wac_add_checkbox'], 10, 1);
        add_action('woocommerce_created_customer', [$this, 'wac_disable_new_customer_mail'], 1, 1);
        add_action('woocommerce_process_shop_order_meta', [$this, 'wac_save_order'], 99, 2);

        add_action('woocommerce_checkout_order_created', [$this, 'wac_checkout_order_created_action'], 99, 2);

        add_action('woocommerce_before_save_order_items', [$this, 'wac_save_order_action'], 10, 2);

        //Get the custom billing and shipping fields. It catches all the previous defined custom fields.
        //If a custom field is not added, check if the priority of the add_filter(woocommerce_admin_*_fields) is lower than 9999
        add_filter("woocommerce_admin_billing_fields", [$this, 'wac_add_custom_billing_fields'], 9999, 1);
        add_filter("woocommerce_admin_shipping_fields", [$this, 'wac_add_custom_shipping_fields'], 9999, 1);

        add_filter('woocommerce_ajax_get_customer_details', [$this, 'wac_ajax_get_customer_details'], 10, 3);

        //Add Admin Menu
        $backend_class = new woo_add_customer_backend;
        add_action('admin_menu', [$backend_class, 'setup_options'], 101);
        add_action('admin_init', [$backend_class, 'wac_register_settings']);

        //Show Admin notices
        add_action('admin_notices', [$this, 'wac_display_notices'], 10000);

        //Mark the plugin as HPOS compatible
        add_action('before_woocommerce_init', function () {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', 'add-customer-for-woocommerce/add-customer-for-woocommerce.php', true);
            } else {
            }
        });
        //Adds a notice to the user profile
        add_action('edit_user_profile', [$this, 'wac_show_user_info'], 99, 1);

        //Adds a filter to get all the users created by the plugin
        add_filter('views_users', [$this, 'wac_add_user_views']);

        //Modify the users query to filter for add customer users
        add_filter('pre_get_users', [$this, 'wac_pre_get_users']);
    }

    /**
     * Loads the custom fields of the customer details.
     * Adds the values of the custom billing and shipping fields.
     *
     * @param array $data - Array with the data to populate. 
     * @param array $customer - WC_Customer object //Not used by now
     * @param int $user_id - Id uf the user
     * @return void
     */
    public function wac_ajax_get_customer_details($data, $customer, $user_id)
    {
        $user_meta = get_user_meta($user_id); //Get all the metadata of the user
        foreach ($user_meta as $index => $value) {
            //Billing Data
            if (strpos($index, 'billing_') !== false) {
                $name_clean = str_replace('billing_', '', $index);
                if (!array_search($name_clean, $this->default_fields)) {
                    //It is a non standard billing field. Set the custom field data 
                    $data['billing'][$name_clean] = $value;
                }
            }
            //Shipping Data
            if (strpos($index, 'shipping_') !== false) {
                $name_clean = str_replace('shipping_', '', $index);
                if (!array_search($name_clean, $this->default_fields)) {
                    //It is a non standard shipping field. Set the custom field data 
                    $data['shipping'][$name_clean] = $value;
                }
            }
        }
        return $data;
    }

    /**
     * Adds any custom billing fields to the $custom_billing_fields property
     *
     * @param array $fields - Fields to display in the bulling fields. 
     * @return array The custom billing fields.
     */
    public function wac_add_custom_billing_fields($fields)
    {
        $non_default_fields = $fields;
        foreach ($this->default_fields as $index) {
            if (isset($non_default_fields[$index])) {
                unset($non_default_fields[$index]); //Removes all the default fields
            }
        }
        $this->custom_billing_fields = $non_default_fields;
        return $fields;
    }

    /**
     * Adds any custom shipping fields to the $custom_shipping_fields property
     *
     * @param array $fields - Fields to display in the shipping fields. 
     * @return array The custom shipping fields.
     */
    public function wac_add_custom_shipping_fields($fields)
    {
        $non_default_fields = $fields;
        foreach ($this->default_fields as $index) {
            if (isset($non_default_fields[$index])) {
                unset($non_default_fields[$index]); //Removes all the default fields
            }
        }
        $this->custom_shipping_fields = $non_default_fields;
        return $fields;
    }

    /**
     * Hooks in after all fields are saved.
     * If "wac_add_customer" is checked, it will create a new user or link a existing one, if email exists in user db.
     *
     * @param integer $order_id - ID of Order
     * @param  object $posted 
     * @return bool false on error, true on success
     */
    public function wac_save_order($order_id, $posted)
    {
        //Load the order
        $order = wc_get_order($order_id);
        if (!$order) {
            $this->log_event_message(__('No order found', 'wac'), $order_id, 'error');
            return;
        }
        if (isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] == 'true') {
            //Add new customer
            $email = isset($_REQUEST['_billing_email']) ? sanitize_email($_REQUEST['_billing_email']) : false;
            $existing_user = get_user_by('email', $email);
            $user_id = null;
            if ($existing_user === false) {
                $new_customer_id = $this->wac_add_customer($email, $order_id);
                if ($new_customer_id) {
                    //Link the Order to the Customer
                    $_REQUEST['customer_user'] = $new_customer_id;
                    $order->set_customer_id($new_customer_id);
                    $user_id = $new_customer_id;
                }
            } else {
                //Link Order to Customer
                $this->log_event("existing_account", $order_id, $email);
                $_REQUEST['customer_user'] = $existing_user->ID;
                $user_id =  $existing_user->ID;
                $order->set_customer_id($user_id);
            }
            do_action('wac_after_insert_new_customer', $user_id, $order_id);
            $order->save();
            return true;
        }
        if (isset($_REQUEST['wac_update_customer']) and $_REQUEST['wac_update_customer'] == 'true') {
            //Update the customer Data
            $user_id = $order->get_customer_id();
            if ($user_id === false or empty($user_id)) {
                $this->log_event("no_user_id", $order_id);
                return false;
            }
            $update_customer = $this->wac_add_customer_meta($user_id, $order_id);
            if ($update_customer > 0) {
                $this->log_event("customer_updated", $order_id, $update_customer, $user_id);
                $this->increase_wac_counter('edit');
            }

            do_action('wac_after_insert_updated_customer', $user_id, $order_id);
        }
        return;
    }

    /**
     * Action that runs after checkout and when the order is created.
     * Links order to users if exists.
     *
     * @param WC_Order $order
     * @return void
     */
    public function wac_checkout_order_created_action($order)
    {
        if ($this->get_wac_option('wac_add_customer_order_to_user') !== 'yes') {
            return;
        }
        //Try to link order to user if user exists
        $email = $order->get_billing_email();
        $order_id = $order->get_id();

        if ($order->get_customer_id() > 0) {
            $this->log_event("order_linked_to_account_failed", $order_id, $email, __('This order is already linked to a user', 'wac'));
            return;
        }
        $user = get_user_by('email', $email);
        if ($user === false) {
            $this->log_event("order_linked_to_account_failed", $order_id, $email, __('No user found with the given email', 'wac'));
            return;
        }
        $order->set_customer_id($user->ID);
        if ($order->save()) {
            $this->log_event("order_linked_to_account", $order_id, $email);
        } else {
            $this->log_event("order_linked_to_account_failed", $order_id, $email, __('Failed to save the order', 'wac'));
        }
    }

    /**
     * Gets executed when a post is saved.
     *
     * @param int $post_id
     * @param object $post
     * @param bool $update
     * @return false|void False when no order found
     */
    public function wac_save_order_action($post_id, $post)
    {
        $order = wc_get_order($post_id);
        if (!$order) {
            return false;
        }
        $user_id = $order->get_user_id();
        $this->wac_maybe_disable_all_emails($user_id);
    }

    /**
     * Adds a new Customer and saves the billing and shipping address of the current order
     *
     * @param string $email - email of new user
     * @param integer $order_id - Order ID
     * @return mixed User ID on success, false on error
     */
    public function wac_add_customer($email, $order_id)
    {

        $user_first = (isset($_REQUEST['_billing_first_name']) and !empty($_REQUEST['_billing_first_name'])) ? sanitize_text_field($_REQUEST['_billing_first_name']) : '';
        $user_last = (isset($_REQUEST['_billing_last_name']) and !empty($_REQUEST['_billing_last_name'])) ? sanitize_text_field($_REQUEST['_billing_last_name']) : '';
        $user_company = (isset($_REQUEST['_billing_company']) and !empty($_REQUEST['_billing_company'])) ? sanitize_text_field($_REQUEST['_billing_company']) : '';
        $user = $this->wac_get_unique_user($user_first . '.' . $user_last . '.' . $user_company);
        $password = wp_generate_password();
        $email_is_fake = false;
        $user_role = (isset($_REQUEST['wac_add_customer_role'])) ? sanitize_key($_REQUEST['wac_add_customer_role']) : $this->get_default_user_role();

        $order = wc_get_order($order_id);
        if (!$order) {
            $this->log_event_message(__('No order found', 'wac'), $order_id, 'error');
            return;
        }

        if (empty($email)) {
            //create new 'fake' email
            $email = $this->create_fake_email($user);
            $email_is_fake = true;
        }

        //Add hook to allow to modify the email
        $email = $this->make_email_valid($email);
        $email = apply_filters('wac_add_customer_email', $email, $user);

        //Validate the email
        if (is_email($email) === false) {
            //User Exists already. This should never happen, but if it does, it does.
            $this->log_event("invalid_email", $order_id, $email);
            return false;
        }

        //Validate the user role. This should never return false, except if someone alters the select selection
        $valid_user_roles = $this->get_user_role_array();
        if (!isset($valid_user_roles[$user_role])) {
            //User role does not exist, or is not allowed
            $this->log_event("user_role_not_allowed", $order_id, $user_role);
            return false;
        } else {
            //Add the role
            add_filter('woocommerce_new_customer_data', function ($data) {
                $user_role = (isset($_REQUEST['wac_add_customer_role'])) ? sanitize_key($_REQUEST['wac_add_customer_role']) : $this->get_default_user_role();
                $data['role'] = $user_role;
                return $data;
            });
        }

        //Save the email to the order
        $order->update_meta_data('billing_email', $email);
        $order->save();

        if (get_user_by('email', $email) !== false) {
            //User Exists already. This should never happen, but if it does, it does.
            $this->log_event("existing_account", $order_id, $email);
            return false;
        }

        //Make the username valid
        $user = $this->make_user_valid($user);

        if ($user !== false) {
            $user_id = wc_create_new_customer($email, $user, $password);
            if (is_integer($user_id)) {
                $user_data = array('ID' => $user_id, 'first_name' => $user_first, 'last_name' => $user_last);
                wp_update_user($user_data);

                //Adds the billing and shipping infos
                $this->wac_add_customer_meta($user_id, $order_id);

                //Adds the meta for the customer in order to identify customers created by the plugin
                update_user_meta($user_id, 'wac_created_by_plugin', true);
                update_user_meta($user_id, 'wac_created_by_plugin_time', time());

                $this->log_event("added_user", $order_id, $user, $email);
                $this->increase_wac_counter('add');
                //Check if User notification should be send or not. If so, send email with login information.
                if (isset($_REQUEST['wac_add_customer_notify']) and $_REQUEST['wac_add_customer_notify'] == 'true') {
                    //Check if the address is a fake email. If so, no email will be sent.
                    if ($email_is_fake) {
                        $this->log_event("failed_to_send_user_mail_fakemail", $order_id, $user);
                        return (int) $user_id;
                    }
                    //No fake email, try to send the email
                    $send = $this->send_mail_to_new_customer($email, $user_first);
                    if (!$send) {
                        $this->log_event("failed_to_send_user_mail", $order_id);
                    } else {
                        $this->log_event("email_send", $order_id, $user);
                    }
                }
                return (int) $user_id;
            } else {
                $this->log_event("failed_to_add_user", $order_id, $user_id, $user, $email);
            }
        }
        return false;
    }

    /**
     * Adds all the customer meta to the user.
     *
     * @param integer $user_id - Id of a existing user
     * @param integer $order_id - Id the current order
     * @return int The number of changes saved
     */
    public function wac_add_customer_meta($user_id, $order_id)
    {
        $changes_count = 0;
        $fields = array();
        $fields = array(
            'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state',
            'billing_email', 'billing_phone', 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_postcode', 'shipping_country', 'shipping_state'
        );
        //Save all the default fields
        foreach ($fields as $f_name) {
            $f_value = (isset($_REQUEST['_' . $f_name]) and !empty($_REQUEST['_' . $f_name])) ? sanitize_text_field($_REQUEST['_' . $f_name]) : false;
            $old_value = get_user_meta($user_id, $f_name, true);

            //Only save if the value got updated
            if (($f_value !== $old_value) and $f_value !== false) {
                if (!update_user_meta($user_id, $f_name, $f_value)) {
                    $this->wac_set_notice(sprintf(__('Could not save the default field "%s"', 'wac'), $f_name), 'error', $order_id);
                } else {
                    $changes_count++;
                }
            }
        }
        //saves the custom billing fields
        if (!empty($this->custom_billing_fields) and is_array($this->custom_billing_fields)) {
            foreach ($this->custom_billing_fields as $field_id => $value) {
                $cf_value = (isset($_REQUEST['_billing_' . $field_id]) and !empty($_REQUEST['_billing_' . $field_id])) ? sanitize_text_field($_REQUEST['_billing_' . $field_id]) : false;
                $old_value = get_user_meta($user_id, "billing_{$field_id}", true);
                //Only update if value got changed
                if (($cf_value !== $old_value)) {
                    if (!update_user_meta($user_id, "billing_{$field_id}", $cf_value)) {
                        $this->wac_set_notice(sprintf(__('Could not save the "%s" billing field', 'wac'), $field_id), 'error', $order_id);
                    } else {
                        $changes_count++;
                    }
                }
            }
        }
        //saves the custom shipping fields
        if (!empty($this->custom_shipping_fields) and is_array($this->custom_shipping_fields)) {
            foreach ($this->custom_shipping_fields as $field_id => $value) {
                $sf_value = (isset($_REQUEST['_shipping_' . $field_id]) and !empty($_REQUEST['_shipping_' . $field_id])) ? sanitize_text_field($_REQUEST['_shipping_' . $field_id]) : false;
                $old_value = get_user_meta($user_id, "billing_{$field_id}", true);
                //Only update if value got changed
                if (($sf_value !== $old_value)) {
                    if (!update_user_meta($user_id, "shipping_{$field_id}", $sf_value)) {
                        $this->wac_set_notice(sprintf(__('Could not save the "%s" shipping field', 'wac'), $field_id), 'error', $order_id);
                    } else {
                        $changes_count++;
                    }
                }
            }
        }
        return $changes_count;
    }

    /**
     * Creates a unique username
     * Adds numbers at the end till the name is unique.
     *
     * @param string $user - The username you like to have
     * @return string The new username, or the existing one, if it is unique already
     */
    public function wac_get_unique_user($user)
    {
        $user = sanitize_user($user); //Remove everything that is not valid from the start
        if ($user === '..' or empty($user)) {
            //Try to get the username from the fake email name part
            $user = $this->create_fake_email_name();
        }
        //Replace dots
        $user = str_replace('..', '.', $user); //Replace the two dots with one
        $user = str_replace(' ', '_', $user); //Replace the spaces with underlines
        $user = ltrim($user, '.'); //Remove dots from the left
        $user = rtrim($user, '.'); //Remove dots from the right
        $user = trim($user); //Removes all the other whitespaces, tabs, new-lines, etc.

        $user = strtolower($user);
        $existing_user = get_user_by('login', $user);
        if ($existing_user === false) {
            return sanitize_user($user);
        } else {
            $user_add = 1;
            while (get_user_by('login', $user . '_' . $user_add) !== false) {
                $user_add++;
            }
            return sanitize_user($user . '_' . $user_add);
        }
    }

    /**
     * Adds the checkbox with a id of "wac_add_customer"
     * If the order is not assigned to a customer, the add customer button will show, otherwise the update customer button.
     * Includes the style for admin page
     *
     * @param object $order - The Order Object.
     * @return void Prints out the edit or update checkbox 
     */
    public function wac_add_checkbox($order)
    {
        if ($order->get_customer_id() !== 0) {
            //Show the update customer checkbox
            $html = $this->load_template_to_var('update-checkbox', 'order');
        } else {
            $html = $this->load_template_to_var('add-checkbox', 'order', $order);
        }

        $this->wac_enqueue_admin_style();
        $this->wac_enqueue_admin_scripts();

        echo $html;
        return;
    }

    /**
     * Shows when the user was created and if it is a user created by the plugin
     *
     * @param WP_User $user
     * @return string Message about the user or void, if the user is not created by the plugin
     */
    public function wac_show_user_info($user)
    {
        $user_id = (isset($user->ID)) ? $user->ID : false;
        $is_wac_created = get_user_meta($user_id, 'wac_created_by_plugin', true);
        if (!$is_wac_created) {
            return;
        }
        //Show the infos about the user creation
        $created_time = get_user_meta($user_id, 'wac_created_by_plugin_time', true);
        $time = date('d. F Y - H:i:s', intval($created_time));
        echo sprintf(__('User was created by the Add customer for WooCommerce Plugin on %s', 'wac'), $time);
    }


    /**
     * Adds a filter item in the role filter on the Users screen.
     *
     * @param array $views
     * @return array The views
     */
    public function wac_add_user_views($views){
        $url = add_query_arg( 'created_by', 'add_customer', 'users.php' );
        $user_count = count($this -> get_users_created_by_plugin());

        //Do not display the filter if there are no users to show
        if($user_count === 0){
            return $views;
        }
        $views['created_by_wac'] =sprintf(
            '<a href="%s">%s <span class="count">(%s)</span></a>',
            esc_url($url),
            __('Add Customer','wac'),
            $user_count
        );
        return $views;
    }

    /**
     * Filters the query before getting the users. Allows to select only the users created by the plugin
     *
     * @param object $query
     * @return object
     */
    public function wac_pre_get_users($query){
        if(!is_admin() OR !isset($_GET['created_by']) OR !$_GET['created_by'] === 'add_customer'){
            return $query;
        }
        if(empty($query -> meta_key)){
            $meta_query = array(
                array(
                    'key' => 'wac_created_by_plugin',
                    'value' => true,
                    'compare' => 'LIKE'
                )
            );
            $query -> set('meta_query',$meta_query);
            return $query;
        }
        return $query; //This should not happen, but just in case
    }
}
