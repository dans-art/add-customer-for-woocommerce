<?php

/**
 * This Template render checkbox input field at the settings page.
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

$checked = ($options_val === 'yes') ? 'checked' : '';
$page = (isset($args['page'])) ? $args['page'] : 'null';
$class = (isset($args['class'])) ? $args['class'] : '';
$description = (isset($args['description'])) ? $args['description'] : '';
?>


<fieldset class="<?php echo $class; ?>">
    <label for="<?php echo $label_for; ?>">
        <input name="<?php echo $page.'['.$label_for.']'; ?>" type="checkbox" id="<?php echo $label_for; ?>" value="yes" <?php echo $checked; ?>>
        <?php echo __('Activated', 'wac'); ?></label>
    <p class="description">
        <?php echo $description; ?>
    </p>
</fieldset>