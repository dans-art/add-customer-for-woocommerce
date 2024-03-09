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

$checked = ($wac->get_wac_option('wac_preselect_update') === 'yes') ? 'checked' : '';

?>

<div id='wac_add_customer_con' class="edit_address">
<div class="_add_customer_fields">
    <label><?php echo __('Update customer', 'wac'); ?></label>
    <p class="wac_update_customer_field">
        <input type="checkbox" name="wac_update_customer" id="wac_update_customer" value="true" placeholder="" autocomplete="off">
        <label for="wac_update_customer"><?php echo __('Updates the existing customer', 'wac'); ?></label>
    </p>
</div>
</div>
<!-- Variables for the JS to use -->
<script type="text/javascript">
    window.sep_variables = {
        'default_options': {
            'update_customer': '<?php echo $checked; ?>',
        }
    }
</script>