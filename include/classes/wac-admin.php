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

        //Allow paying for orders without login.
        add_filter('user_has_cap', [$this, 'wac_allow_pay_for_order'], 20, 3);
        add_action('template_redirect', [$this, 'wac_remember_email_verification']);
        add_filter('woocommerce_order_email_verification_required', [$this, 'wac_skip_verified_email'], 10, 2);
        add_filter('woocommerce_order_received_verify_known_shoppers', [$this, 'wac_order_received_verify_known_shoppers']);
        add_filter('woocommerce_payment_successful_result', [$this, 'wac_log_payment_by_other_user'], 10, 2);
    }

    /**
     * Logs to Simple History and adds an order note, if the order was paid on the payment page by:
     * - A logged out user
     * - A shop manager or administrator, on behalf of the customer
     * For redirect based gateways (e.g. PayPal), this is logged when the user gets redirected to the payment provider.
     *
     * @param array $result - The result of the payment gateway
     * @param int $order_id - The order id
     * @return array The unchanged result
     */
    public function wac_log_payment_by_other_user($result, $order_id)
    {
        $order = wc_get_order($order_id);
        //Only on the payment page, the order key is not in the URL on the regular checkout
        if (!$order || !$this->is_pay_without_login_active() || !$this->is_valid_order_key_in_request($order)) {
            return $result;
        }

        $current_user = wp_get_current_user();
        $is_logged_out = !$current_user->exists();
        $user_can_manage_shop = current_user_can('manage_woocommerce');
        $is_other_customer = $order->get_customer_id() !== $current_user->ID;
        $payment_method = $order->get_payment_method_title();

        if ($is_logged_out && $this->is_payable_without_login($order)) {
            $this->log_event('paid_without_login', $order_id, $order->get_order_number(), $order->get_billing_email(), $payment_method, $order->get_customer_id());
            /* translators: %s: Payment method title */
            $order->add_order_note(sprintf(__('Order paid without login by an anonymous user via the customer payment page. Payment method: %s', 'wac'), $payment_method));
        } elseif ($user_can_manage_shop && $is_other_customer) {
            $this->log_event('paid_by_shop_manager', $order_id, $order->get_order_number(), $order->get_billing_email(), $current_user->display_name, $payment_method, $order->get_customer_id(), $current_user->ID);
            /* translators: 1: Display name of the shop manager, 2: Payment method title */
            $order->add_order_note(sprintf(__('Order paid by %1$s on behalf of the customer via the customer payment page. Payment method: %2$s', 'wac'), $current_user->display_name, $payment_method));
        }
        return $result;
    }

    /**
     * Grants the "pay_for_order" capability, so the payment page of an order can be opened by:
     * - Logged out users with a valid order key, and only if the customer of the order was created by the plugin
     * - Shop managers and administrators, for any order
     * Only active if the option "wac_allow_pay_for_order" is set.
     *
     * @param array $allcaps - All the capabilities of the user
     * @param array $caps - The required capabilities
     * @param array $args - [0] => Requested capability, [1] => User ID, [2] => Order ID
     * @return array The capabilities of the user
     */
    public function wac_allow_pay_for_order($allcaps, $caps, $args)
    {
        $is_pay_for_order_check = isset($args[0]) && $args[0] === 'pay_for_order';
        $has_order_id = !empty($args[2]);
        if (!$is_pay_for_order_check || !$this->is_pay_without_login_active() || !$has_order_id) {
            return $allcaps;
        }

        //do not use current_user_can() here, it would call this filter again
        //This allows admins and shop manages to pay
        $user_can_manage_shop = !empty($allcaps['manage_woocommerce']);
        if ($user_can_manage_shop) {
            $allcaps['pay_for_order'] = true;
            return $allcaps;
        }

        //For logged out users
        $is_logged_out = empty($args[1]);
        $order = wc_get_order($args[2]);
        if ($is_logged_out && $order && $this->is_payable_without_login($order)) {
            $allcaps['pay_for_order'] = true;
        }
        return $allcaps;
    }

    /**
     * Remembers a successful email verification of a logged out user in the WooCommerce session.
     * Without this, WooCommerce asks for the email again on every request, e.g. after a failed payment submission.
     * Runs on template_redirect, so the session cookie can still be set.
     *
     * @return void
     */
    public function wac_remember_email_verification()
    {
        $is_logged_out = !is_user_logged_in();
        $is_valid_nonce = (bool) wp_verify_nonce(filter_input(INPUT_POST, 'check_submission'), 'wc_verify_email');
        $session = $this->get_wc_session();
        if (!$is_logged_out || !$is_valid_nonce || !$session) {
            return;
        }

        $order = $this->get_order_from_endpoint();
        if (!$order || !$this->is_payable_without_login($order)) {
            return;
        }

        $supplied_email = sanitize_email(wp_unslash(filter_input(INPUT_POST, 'email')));
        $is_matching_email = !empty($supplied_email) && 0 === strcasecmp($supplied_email, $order->get_billing_email());
        if (!$is_matching_email) {
            return;
        }

        $verified_orders = (array) $session->get('wac_verified_orders', []);
        if (!in_array($order->get_id(), $verified_orders, true)) {
            $verified_orders[] = $order->get_id();
        }
        if (!$session->has_session()) {
            $session->set_customer_session_cookie(true);
        }
        $session->set('wac_verified_orders', $verified_orders);
    }

    /**
     * Skips the email verification, if the logged out user already verified the email for this order.
     *
     * @param bool $verification_required - If WooCommerce requires the email verification
     * @param WC_Order $order - The order to verify
     * @return bool If the email verification is required
     */
    public function wac_skip_verified_email($verification_required, $order)
    {
        $is_logged_out = !is_user_logged_in();
        if (!$verification_required || !$is_logged_out) {
            return $verification_required;
        }

        if ($this->is_email_verified_in_session($order) && $this->is_payable_without_login($order)) {
            return false;
        }
        return $verification_required;
    }

    /**
     * Allows logged out users to see the "order received" page of orders, which they were allowed to pay for without login.
     * WooCommerce would ask them to log in. Only if the email was verified on the payment page in the same session,
     * because the order confirmation block shows the order details without any further verification.
     *
     * @param bool $verify_known_shoppers - If the user has to log in to see the order
     * @return bool
     */
    public function wac_order_received_verify_known_shoppers($verify_known_shoppers)
    {
        $is_logged_out = !is_user_logged_in();
        $order = $this->get_order_from_endpoint();
        if (!$is_logged_out || !$order) {
            return $verify_known_shoppers;
        }

        if ($this->is_email_verified_in_session($order) && $this->is_payable_without_login($order)) {
            return false;
        }
        return $verify_known_shoppers;
    }

    /**
     * Checks if the option "wac_allow_pay_for_order" is set
     *
     * @return bool
     */
    public function is_pay_without_login_active()
    {
        return $this->get_wac_option('wac_allow_pay_for_order') === 'yes';
    }

    /**
     * Checks if the order can be paid without login:
     * The option is active, the customer was created by the plugin and the order key in the URL is valid.
     *
     * @param WC_Order $order
     * @return bool
     */
    public function is_payable_without_login($order)
    {
        $is_option_active = $this->is_pay_without_login_active();
        $is_customer_created_by_plugin = $this->is_order_customer_created_by_plugin($order);
        $is_valid_order_key = $this->is_valid_order_key_in_request($order);

        return $is_option_active && $is_customer_created_by_plugin && $is_valid_order_key;
    }

    /**
     * Checks if the order key in the URL matches the order
     *
     * @param WC_Order $order
     * @return bool
     */
    public function is_valid_order_key_in_request($order)
    {
        $order_key = (isset($_GET['key']) && is_string($_GET['key'])) ? wc_clean(wp_unslash($_GET['key'])) : '';
        return !empty($order_key) && hash_equals($order->get_order_key(), $order_key);
    }

    /**
     * Checks if the email of the order was verified before in the WooCommerce session
     *
     * @param WC_Order $order
     * @return bool
     */
    public function is_email_verified_in_session($order)
    {
        $session = $this->get_wc_session();
        if (!$session) {
            return false;
        }
        $verified_orders = (array) $session->get('wac_verified_orders', []);
        return in_array($order->get_id(), $verified_orders, true);
    }

    /**
     * Gets the WooCommerce session
     *
     * @return WC_Session|null Null if no session is available
     */
    public function get_wc_session()
    {
        $session = WC()->session;
        return is_a($session, 'WC_Session') ? $session : null;
    }

    /**
     * Gets the order of the current "order-pay" or "order-received" page
     *
     * @return WC_Order|false False if not on one of those pages or if the order is not found
     */
    public function get_order_from_endpoint()
    {
        global $wp;
        $order_id = 0;
        if (!empty($wp->query_vars['order-pay'])) {
            $order_id = absint($wp->query_vars['order-pay']);
        } elseif (!empty($wp->query_vars['order-received'])) {
            $order_id = absint($wp->query_vars['order-received']);
        }
        return ($order_id) ? wc_get_order($order_id) : false;
    }

    /**
     * Checks if the customer of the order was created by the plugin
     *
     * @param WC_Order $order
     * @return bool
     */
    public function is_order_customer_created_by_plugin($order)
    {
        $customer_id = $order->get_user_id();
        if (empty($customer_id)) {
            return false;
        }
        return (bool) get_user_meta($customer_id, 'wac_created_by_plugin', true);
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
                update_user_meta($user_id, 'wac_created_by_author_id', get_current_user_id());

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
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_email',
            'billing_phone',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_postcode',
            'shipping_country',
            'shipping_state'
        );
        //Save all the default fields
        foreach ($fields as $f_name) {
            $f_value = (isset($_REQUEST['_' . $f_name]) and !empty($_REQUEST['_' . $f_name])) ? sanitize_text_field($_REQUEST['_' . $f_name]) : false;
            $old_value = get_user_meta($user_id, $f_name, true);

            //Only save if the value got updated
            if (($f_value !== $old_value) and $f_value !== false) {
                if (!update_user_meta($user_id, $f_name, $f_value)) {
                    /* translators: %s: Name of the field */
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
                        /* translators: %s: ID of the billing field */
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
                        /* translators: %s: ID of the shipping field */
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

        //Make sure that the email name is not longer than 55 characters (Max allowed are 60)
        if (strlen($user) > 55) {
            $user = substr($user, 0, 55);
        }

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
        //Load both checkboxes, the JS shows the one matching the selected customer
        $html = $this->load_template_to_var('add-checkbox', 'order', $order);
        $html .= $this->load_template_to_var('update-checkbox', 'order');

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
        if (!$user_id) {
            return false;
        }
        $is_wac_created = get_user_meta($user_id, 'wac_created_by_plugin', true);
        if (!$is_wac_created) {
            return false;
        }
        //Show the infos about the user creation
        $created_time = get_user_meta($user_id, 'wac_created_by_plugin_time', true);
        $created_by = get_user_meta($user_id, 'wac_created_by_author_id', true);
        $user_name = $created_by ? get_user_by('ID', $created_by) : false;
        $time = $created_time
            ? wp_date('d. F Y - H:i:s', intval($created_time))
            : __('unknown date', 'wac');
        if ($user_name instanceof WP_User) {
            echo sprintf(
                /* translators: 1: Display name of the user who created the customer, 2: Date and time of creation */
                __('User was created by %s with the Add customer for WooCommerce Plugin on %s', 'wac'),
                esc_html($user_name->display_name),
                esc_html($time)
            );
        } else {
            echo sprintf(
                /* translators: %s: Date and time of creation */
                __('User was created by the Add customer for WooCommerce Plugin on %s', 'wac'),
                esc_html($time)
            );
        }
    }


    /**
     * Adds a filter item in the role filter on the Users screen.
     *
     * @param array $views
     * @return array The views
     */
    public function wac_add_user_views($views)
    {
        $url = add_query_arg('created_by', 'add_customer', 'users.php');
        $user_count = count($this->get_users_created_by_plugin());

        //Do not display the filter if there are no users to show
        if ($user_count === 0) {
            return $views;
        }
        $views['created_by_wac'] = sprintf(
            '<a href="%s">%s <span class="count">(%s)</span></a>',
            esc_url($url),
            __('Add Customer', 'wac'),
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
    public function wac_pre_get_users($query)
    {
        if (!is_admin() or !isset($_GET['created_by']) or !$_GET['created_by'] === 'add_customer') {
            return $query;
        }
        if (empty($query->meta_key)) {
            $meta_query = array(
                array(
                    'key' => 'wac_created_by_plugin',
                    'value' => true,
                    'compare' => 'LIKE'
                )
            );
            $query->set('meta_query', $meta_query);
            return $query;
        }
        return $query; //This should not happen, but just in case
    }
}
