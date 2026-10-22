<?php

/**
 * The update customer data checkbox
 * 
 * @version     1.10.0
 * @package     WAC\BackendTemplate
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included within woo_add_customer_helper::load_template_to_var(), the variables are not global.
global $wac;
$wac = (!is_object($wac)) ? new woo_add_customer() : $wac;

$checked = ($wac->get_wac_option('wac_preselect_update') === 'yes') ? 'checked' : '';

?>

<div id='wac_update_customer_con' class="edit_address">
<div class="_add_customer_fields">
    <label><?php echo esc_html__('Update customer', 'add-customer-for-woocommerce'); ?></label>
    <p class="wac_update_customer_field">
        <input type="checkbox" name="wac_update_customer" id="wac_update_customer" value="true" placeholder="" autocomplete="off">
        <label for="wac_update_customer"><?php echo esc_html__('Updates the existing customer', 'add-customer-for-woocommerce'); ?></label>
    </p>
</div>
</div>
<!-- Variables for the JS to use. Extends the variables of the add-checkbox template -->
<script type="text/javascript">
    window.sep_variables = window.sep_variables || {'default_options': {}};
    window.sep_variables.default_options.update_customer = '<?php echo esc_js($checked); ?>';
</script>