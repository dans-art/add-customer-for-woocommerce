<?php

/**
 * This Template render text input field at the settings page.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version 1.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $backend_class;
$backend_class = (!is_object($backend_class)) ? new woo_add_customer_backend() : $backend_class;

extract(get_defined_vars());
$label_for = (isset($template_args[0])) ? $template_args[0] : ''; //The ID/name of the field
$options_val = (isset($template_args[1])) ? $template_args[1] : ''; //Value of the option
$default_value = (isset($template_args[2])) ? $template_args[2] : ''; //The default value / placeholder
$args = (isset($template_args[3])) ? $template_args[3] : []; //The arguments form the add_settings_field function

$description = (isset($args['description'])) ? $args['description'] : '';
$class = (isset($args['class'])) ? $args['class'] : '';
?>

<tr class='<?php echo $class; ?> text-input'>
    <td>
        <input name="wac_general_options[<?php echo $label_for; ?>]" id="<?php echo $label_for; ?>" type="text" value="<?php echo $options_val; ?>" placeholder="<?php echo $default_value; ?>" />
    </td>
</tr>
<tr class='<?php echo $class; ?>-field-errors wac-field-errors'>
    <td><?php echo $backend_class->check_option($label_for, $options_val); ?></td>
</tr>
<tr class='<?php echo $class; ?>-description'>
    <td><?php echo $description; ?></td>
</tr>