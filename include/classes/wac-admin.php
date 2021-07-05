<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Class for managing the Admin Backend. Add field, process data on order save. 
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
class woo_add_customer_admin extends woo_add_customer_helper
{


    /**
     * Add the Actions
     */
    public function __construct()
    {
        add_action('init', [$this, 'wac_load_textdomain']); //load language 
        add_action('admin_init', [$this, 'wac_add_settings_section_init']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'wac_add_checkbox'], 10, 1);
        add_action('woocommerce_created_customer', [$this, 'wac_disable_new_customer_mail'], 1, 1);
        add_action('woocommerce_process_shop_order_meta', [$this, 'wac_save_order'], 99, 2);
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
            if(empty($_REQUEST['_billing_first_name']) AND empty($_REQUEST['_billing_last_name'])){
                $this -> log_event("no_name");
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
                $_REQUEST['customer_user'] = $existing_user->ID;
                update_post_meta($order_id, '_customer_user', $existing_user->ID);
            }
        }
    }

    /**
     * Adds a new Customer and saves the billing and shipping adress of the current order
     *
     * @param string $email - email of new user
     * @param integer $order_id - Order ID
     * @return mixec User ID on success, false on error
     * @todo Output error if customer could not be saved.
     */
    public function wac_add_customer($email, $order_id)
    {
        $user_first = (isset($_REQUEST['_billing_first_name']) and !empty($_REQUEST['_billing_first_name'])) ? sanitize_text_field($_REQUEST['_billing_first_name']) : '';
        $user_last = (isset($_REQUEST['_billing_last_name']) and !empty($_REQUEST['_billing_last_name'])) ? sanitize_text_field($_REQUEST['_billing_last_name']) : '';
        $user = $this->wac_get_unique_user($user_first . '.' . $user_last);
        
        if (empty($email)) {
            //create new 'fake' email
            $email = $this->create_fake_email($user);
            update_post_meta($order_id, '_billing_email', $email);
        }

        if ($user !== false) {
            $user_id = wc_create_new_customer($email, $user, wp_generate_password());
            if (is_integer($user_id)) {
                $user_data = array('ID' => $user_id,'first_name' => $user_first, 'last_name' => $user_last);
                wp_update_user($user_data);
                $this->wac_add_customer_meta($user_id);
                $this -> log_event("added_user", $user, $email);
                return (integer) $user_id;
            }else{
                $this -> log_event("failed_to_add_user", $user_id, $user, $email);
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
            'billing_email', 'billing_phone', 'Shipping', 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_postcode', 'shipping_country', 'shipping_state'
        );
        foreach ($fields as $f_name) {
            $f_value = (isset($_REQUEST['_' . $f_name]) and !empty($_REQUEST['_' . $f_name])) ? sanitize_text_field($_REQUEST['_' . $f_name]) : false;
            if ($f_value !== false) { 
                update_user_meta($user_id, $f_name, $f_value);
            }
        }
        return true;
    }

    /**
     * Creates a unique username
     * Adds nummers at the end till the name is unique.
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
     * Adds the chechbox with a id of "wac_add_customer"
     * Only displays if no customer is linked with the order
     * Includes the style for admin page
     *
     * @param object $order - The Order Object.
     * @return void
     */
    public function wac_add_checkbox($order)
    {
        if ($order->get_customer_id() !== 0) {
            return false;
        }

        $this -> wac_enqueue_admin_style();
?>
        <div id='wac_add_customer_con' class="edit_address">
            <p class="wac_add_customer_field ">
                <input type="checkbox" name="wac_add_customer" id="wac_add_customer" value="true" placeholder="">
                <label for="wac_add_customer"><?php echo __('Save as new customer', 'wac'); ?></label>
            </p>
        </div>
<?php
    }
}
