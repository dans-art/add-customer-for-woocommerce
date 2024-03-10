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
    echo __('Options must be formatted as an array', 'wac');
    return;
}
?>

<div class='<?php echo $class; ?> select-input'>
        <select name="wac_general_options[<?php echo $label_for; ?>]" id="<?php echo $label_for; ?>">
            <?php foreach ($options as $id => $name) : ?>
                <?php
                $selected = (($default_value === $id AND empty($options_val))|| $options_val === $id) ? "selected" : "";
                ?>
                <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
            <?php endforeach; ?>
        </select>
</div>
<div class='<?php echo $class; ?>-field-errors wac-field-errors'>
        <?php
        //get the error messages
        echo $this->wac_display_notices($label_for); ?>
</div>
<p class='<?php echo $class; ?>-description description'>
    <?php echo $description; ?>
</p>