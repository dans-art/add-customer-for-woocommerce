<?php 
/**
 * Example file for the use of the various hooks for Add customer for WooCommerce
 * Add this snippets to your functions.php and change them accordingly.
 * 
 */

/**
 * Adds a custom billing field to the WooCommerce order page
 * This will be saved in the usermeta as well. So if you load a customer in a new order, this data will be loaded as well.
 * 
 * @param array $fields - The billing fields
 */
add_filter('woocommerce_admin_billing_fields', function ($fields) {
    $fields['custom_field_1'] = ['label' => 'My Fancy Field', 'show' => true];
    return $fields;
}, 20, 1);

/**
 * This hook lets you add custom fields the email part. Add them first with "woocommerce_admin_billing_fields".
 * 
 * @param array $fields - The email part fields
 */
add_filter('wac_supported_fake_email_parts', function (array $fields) {
    $fields[] = 'custom_field_1'; //Name of the custom field to add
    return $fields;
}, 10, 1);


/**
 * Action that will happen after a new customer got saved.
 * 
 * @param string $user_id - The user id
 * @param string $order_id - The id of the order
 */
add_action('wac_after_insert_new_customer', function ($user_id, $order_id) {
    //Do action
}, 10, 2);

/**
 * Action that will happen after a new customer got saved.
 * 
 * @param string $user_id - The user id
 * @param string $order_id - The id of the order
 */
add_action('wac_after_insert_updated_customer', function ($user_id, $order_id) {
    //Do action
}, 10, 2);


/**
 * Edit user Page
 * This actions will modify the edit user page.
 */

/**
 * Displays the custom field in the edit user page
 * @param object $user - WP_User object
 */
add_action('edit_user_profile', function ($user) {
    //Note that if we use the filter "woocommerce_admin_billing_fields" to add the field, the fieldname will be changed to billing_custom_field_1
    $custom_field_value = get_user_meta($user->ID, 'billing_custom_field_1', true);
    echo '<p class="form-field">';
    echo '<label for="billing_custom_field_1">My Fancy Field</label>';
    echo '<input type="text" class="short" style="" name="billing_custom_field_1" id="billing_custom_field_1" value="' . $custom_field_value . '" placeholder=""> </p>';
});

/**
 * Updates the custom meta field
 * @param string $user_id - The user id
 */
add_action('edit_user_profile_update', function ($user_id) {
    $field_value = isset($_REQUEST['billing_custom_field_1']) ? $_REQUEST['billing_custom_field_1'] : false;
    if ($field_value) {
        //So some validation here! Check for allowed characters etc.
        $field_value = htmlspecialchars($field_value);
        update_user_meta($user_id, 'billing_custom_field_1', $field_value);
    }
});


/**
 * Deactivates the WooCommerce for the user with the ID 13. You can also use this hook to reactivate the sending again
 * @param bool $is_deactivated - If the emails are deactivated for the given user
 * @param int $user_id - The user id
 */
add_filter( 'wac_deactivate_user_emails', function($is_deactivated, $user_id){

    if($user_id === 13){
        return true; //Deactivates all the emails to the user
    }
    return false; //Emails wil be sent to the user as normal

}, 10, 2 );


?>