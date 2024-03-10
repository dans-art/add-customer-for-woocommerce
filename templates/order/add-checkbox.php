<?php

/**
 * The update customer data checkbox
 * 
 * @version     1.6.5
 * @package     WAC\BackendTemplate
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $wac;
$wac = (!is_object($wac)) ? new woo_add_customer() : $wac;

extract(get_defined_vars());
$order = (isset($template_args[0])) ? $template_args[0] : null; //The current order object
$order_status = (is_object($order)) ? $order->get_status() : null;

//Only pre-check the checkbox if the option is selected by the user and the order is a new order. 
$checked = ($wac->get_wac_option('wac_preselect') === 'yes' and $order_status === 'auto-draft') ? 'checked' : '';
$checked_notify = ($wac->get_wac_option('wac_send_notification') === 'yes') ? 'checked' : '';

$set_role = $wac->get_wac_option('wac_define_user_role');
$user_role_to_set = $wac -> get_default_user_role();

?>

<div id='wac_add_customer_con' class="edit_address">
    <div class="_add_customer_fields">
        <label><?php echo __('Add new customer', 'wac'); ?></label>
        <div class="wac-checkbox-container">
            <input type="checkbox" name="wac_add_customer" id="wac_add_customer" value="true" placeholder="" autocomplete="off">
            <label for="wac_add_customer"><?php echo ($set_role === 'yes') ? __('Save as new customer with role: ', 'string') : __('Save as new customer', 'wac'); ?></label>
        </div>
        <div class="wac-customer-role-container">
            <?php
            //Add the options field to choose a user role
            if ($set_role === 'yes') : ?>
                <select name="wac_add_customer_role" id="wac_add_customer_role">
                    <?php foreach ($wac->get_user_role_array(false) as $role_slug => $name) : ?>
                        <?php $selected = ($role_slug === $user_role_to_set) ? 'selected' : ''; ?>
                        <option value="<?php echo $role_slug ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <p class="wac_add_customer_notify_field" style="display: none;">
            <input type="checkbox" name="wac_add_customer_notify" id="wac_add_customer_notify" value="true" placeholder="" autocomplete="off" <?php echo $checked_notify; ?>>
            <label for="wac_add_customer_notify"><?php echo __('Send email to new customer', 'wac'); ?></label>
        </p>
    </div>
</div>
<!-- Variables for the JS to use -->
<script type="text/javascript">
    window.sep_variables = {
        'default_options': {
            'add_customer': '<?php echo $checked; ?>',
            'notify_customer': '<?php echo $checked_notify; ?>'
        }
    }
</script>