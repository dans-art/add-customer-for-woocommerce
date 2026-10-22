<?php

/**
 * This Template render select input field at the settings page.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version     1.8.0
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
$default_value = (isset($template_args[2])) ? $template_args[2] : []; //The default select options
$args = (isset($template_args[3])) ? $template_args[3] : []; //The arguments form the add_settings_field function

$description = (isset($args['description'])) ? $args['description'] : '';
$class = (isset($args['class'])) ? $args['class'] : '';
$options = (isset($args['values_array'])) ? $args['values_array'] : '';

if (!is_array($options)) {
    echo esc_html__('Options must be formatted as an array', 'add-customer-for-woocommerce');
    return;
}
?>

<div class='<?php echo esc_attr($class); ?> select-input'>
        <select name="wac_general_options[<?php echo esc_attr($label_for); ?>]" id="<?php echo esc_attr($label_for); ?>">
            <?php foreach ($options as $id => $name) : ?>
                <?php
                $selected = (($default_value === $id AND empty($options_val))|| $options_val === $id) ? "selected" : "";
                ?>
                <option value="<?php echo esc_attr($id); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($name); ?></option>
            <?php endforeach; ?>
        </select>
</div>
<div class='<?php echo esc_attr($class); ?>-field-errors wac-field-errors'>
        <?php
        //get the error messages
        $this->wac_display_notices($label_for); ?>
</div>
<p class='<?php echo esc_attr($class); ?>-description description'>
    <?php echo wp_kses_post($description); ?>
</p>