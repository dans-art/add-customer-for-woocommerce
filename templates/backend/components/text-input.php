<?php

/**
 * This Template render text input field at the settings page.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version     1.6.5
 * @package     WAC\Templates
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

<div class='<?php echo $class; ?> text-input'>
    <input name="wac_general_options[<?php echo $label_for; ?>]" id="<?php echo $label_for; ?>" type="text" value="<?php echo $options_val; ?>" placeholder="<?php echo $default_value; ?>" />
</div>
<div class='<?php echo $class; ?>-field-errors wac-field-errors'>
    <?php
    //get the error messages
    echo $this->wac_display_notices($label_for); ?>
</div>
<p class='<?php echo $class; ?>-description description'>
    <?php echo $description; ?>
</p>