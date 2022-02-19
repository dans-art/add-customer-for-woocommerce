<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Class for managing the Admin Backend. Add field, process data on order save. 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class woo_add_customer_admin extends woo_add_customer_helper
{

    protected $adminnotice = null; //Variable for the WC_Admin_Notices
    public $custom_billing_fields = array(); //The custom billing fields to save.
    public $custom_shipping_fields = array(); //The custom shipping fields to save.
    public $default_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'email', 'phone'); //The default Woocommerce billing and shipping fields


    /**
     * Add the Actions
     */
    public function __construct()
    {
        $this->plugin_path = WP_PLUGIN_DIR . '/add-customer-for-woocommerce/';
        //Remove all Admin Notices
        if (class_exists('WC_Admin_Notices')) {
            $this->adminnotice = new WC_Admin_Notices();
        }
        add_action('init', [$this, 'wac_load_textdomain']); //load language 
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'wac_add_checkbox'], 10, 1);
        add_action('woocommerce_created_customer', [$this, 'wac_disable_new_customer_mail'], 1, 1);
        add_action('woocommerce_process_shop_order_meta', [$this, 'wac_save_order'], 99, 2);

        //Get the custom billing and shipping fields. It catches all the previous defined custom fields.
        //If a custom field is not added, check if the priority of the add_filter(woocommerce_admin_*_fields) is lower than 9999
        add_filter("woocommerce_admin_billing_fields", [$this, 'wac_add_custom_billing_fields'], 9999, 1);
        add_filter("woocommerce_admin_shipping_fields", [$this, 'wac_add_custom_shipping_fields'], 9999, 1);

        add_filter('woocommerce_ajax_get_customer_details', [$this, 'wac_ajax_get_customer_details'], 10, 3);

        //Add Admin Menu
        $backend_class = new woo_add_customer_backend;
        add_action('admin_menu', [$backend_class, 'setup_options']);
        add_action('admin_init', [$backend_class, 'wac_register_settings']);
        $this->wac_display_notices();
    }

    /**
     * Loads the custom fields of the customer details.
     * Adds the values of the custom billing and shipping fields.
     *
     * @param array $data - Array with the data to populate. 
     * @param array $customer - WC_Customer object
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
     * @return array The original fields.
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
     * @return array The original fields.
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
     * Hoocks in after all fields are saved.
     * If "wac_add_customer" is checked, it will create a new user or link a existing one, if email exists in user db.
     *
     * @param integer $order_id - ID of Order
     * @param  object $posted 
     * @return void
     */
    public function wac_save_order($order_id, $posted)
    {
        if (isset($_REQUEST['wac_add_customer']) and $_REQUEST['wac_add_customer'] == 'true') {
            $email = isset($_REQUEST['_billing_email']) ? sanitize_email($_REQUEST['_billing_email']) : false;
            $existing_user = get_user_by('email', $email);
            if (empty($_REQUEST['_billing_first_name']) and empty($_REQUEST['_billing_last_name'])) {
                $this->log_event("no_name", $order_id);
                return false;
            }
            if ($existing_user === false) {
                $new_customer_id = $this->wac_add_customer($email, $order_id);
                if ($new_customer_id) {
                    //Link the Order to the Customer
                    $_REQUEST['customer_user'] = $new_customer_id;
                    update_post_meta($order_id, '_customer_user', $new_customer_id);
                }
            } else {
                //Link Order to Customer
                $this -> log_event("existing_account",$order_id, $email);
                $_REQUEST['customer_user'] = $existing_user->ID;
                update_post_meta($order_id, '_customer_user', $existing_user->ID);
            }
        }
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
        $user = $this->wac_get_unique_user($user_first . '.' . $user_last);
        $password = wp_generate_password();
        $email_is_fake = false;

        if (empty($email)) {
            //create new 'fake' email
            $email = $this->create_fake_email($user);
            update_post_meta($order_id, '_billing_email', $email);
            $email_is_fake = true;
        }
        if ($user !== false) {
            $user_id = wc_create_new_customer($email, $user, $password);
            //$this->log_event("failed_to_add_user", $user_id, $user, $email);
            if (is_integer($user_id)) {
                $user_data = array('ID' => $user_id, 'first_name' => $user_first, 'last_name' => $user_last);
                wp_update_user($user_data);
                $this->wac_add_customer_meta($user_id);
                $this->log_event("added_user", $order_id, $user, $email);
                //Check if User notification should be send or not. If so, send email with login information.
                if (isset($_REQUEST['wac_add_customer_notify']) and $_REQUEST['wac_add_customer_notify'] == 'true') {
                    //Check if user email is a fake email. If so, no email will be sent.
                    if($email_is_fake){
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
     * @return bool true
     * @todo Output error if meta could not be saved.
     */
    public function wac_add_customer_meta($user_id)
    {
        $fields = array();
        $fields = array(
            'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state',
            'billing_email', 'billing_phone', 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_postcode', 'shipping_country', 'shipping_state'
        );
        //Save all the default fields
        foreach ($fields as $f_name) {
            $f_value = (isset($_REQUEST['_' . $f_name]) and !empty($_REQUEST['_' . $f_name])) ? sanitize_text_field($_REQUEST['_' . $f_name]) : false;
            if ($f_value !== false) {
                update_user_meta($user_id, $f_name, $f_value);
            }
        }
        //saves the custom billing fields
        if (!empty($this->custom_billing_fields) and is_array($this->custom_billing_fields)) {
            foreach ($this->custom_billing_fields as $field_id => $value) {
                $cf_value = (isset($_REQUEST['_billing_' . $field_id]) and !empty($_REQUEST['_billing_' . $field_id])) ? sanitize_text_field($_REQUEST['_billing_' . $field_id]) : false;
                update_user_meta($user_id, "billing_{$field_id}", $cf_value);
            }
        }
        //saves the custom shipping fields
        if (!empty($this->custom_shipping_fields) and is_array($this->custom_shipping_fields)) {
            foreach ($this->custom_shipping_fields as $field_id => $value) {
                $cf_value = (isset($_REQUEST['_shipping_' . $field_id]) and !empty($_REQUEST['_shipping_' . $field_id])) ? sanitize_text_field($_REQUEST['_shipping_' . $field_id]) : false;
                update_user_meta($user_id, "shipping_{$field_id}", $cf_value);
            }
        }
        return true;
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
        if ($user === '.') {
            return false;
        }
        $user = strtolower($user);
        $existing_user = get_user_by('login', $user);
        if ($existing_user === false) {
            return sanitize_user($user, true);
        } else {
            $user_add = 1;
            while (get_user_by('login', $user . '_' . $user_add) !== false) {
                $user_add++;
            }
            return sanitize_user($user . '_' . $user_add, true);
        }
    }

    /**
     * Adds the checkbox with a id of "wac_add_customer"
     * Only displays if no customer is linked with the order
     * Includes the style for admin page
     *
     * @param object $order - The Order Object.
     * @return false|void False if no customer ID found. Prints out the checkbox 
     */
    public function wac_add_checkbox($order)
    {
        if ($order->get_customer_id() !== 0) {
            return false;
        }
        $order_status = $order->get_status();

        $this->wac_enqueue_admin_style();
        $this->wac_enqueue_admin_scripts();

        //Only pre-check the checkbox if the option is selected by the user and the order is a new order. 
        $checked = ($this->get_wac_option('wac_preselect') === 'yes' AND $order_status === 'auto-draft') ? 'checked' : '';
        $checked_notify = ($this->get_wac_option('wac_send_notification') === 'yes') ? 'checked' : '';
?>
        <div id='wac_add_customer_con' class="edit_address">
            <div class="_add_customer_fields">
                <label><?php echo __('Add new Customer', 'wac'); ?></label>
                <p class="wac_add_customer_field">
                    <input type="checkbox" name="wac_add_customer" id="wac_add_customer" value="true" placeholder="" <?php echo $checked; ?>>
                    <label for="wac_add_customer"><?php echo __('Save as new customer', 'wac'); ?></label>
                </p>
                <p class="wac_add_customer_notify_field" style="display: none;">
                    <input type="checkbox" name="wac_add_customer_notify" id="wac_add_customer_notify" value="true" placeholder="" <?php echo $checked_notify; ?>>
                    <label for="wac_add_customer_notify"><?php echo __('Send email to new customer', 'wac'); ?></label>
                </p>
            </div>
        </div>
<?php
    }
}
