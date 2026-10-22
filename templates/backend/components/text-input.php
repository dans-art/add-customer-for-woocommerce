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
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included within woo_add_customer_helper::load_template_to_var(), the variables are not global.
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

<div class='<?php echo esc_attr($class); ?> text-input'>
    <input name="wac_general_options[<?php echo esc_attr($label_for); ?>]" id="<?php echo esc_attr($label_for); ?>" type="text" value="<?php echo esc_attr($options_val); ?>" placeholder="<?php echo esc_attr($default_value); ?>" />
</div>
<div class='<?php echo esc_attr($class); ?>-field-errors wac-field-errors'>
    <?php
    //get the error messages
    $this->wac_display_notices($label_for); ?>
</div>
<p class='<?php echo esc_attr($class); ?>-description description'>
    <?php echo wp_kses_post($description); ?>
</p>