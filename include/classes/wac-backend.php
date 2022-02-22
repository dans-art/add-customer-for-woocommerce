<?php

/**
 * Plugin Name: Add Customer for WooCommerce
 * Class description: Class for managing the Admin Menu.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class woo_add_customer_backend extends woo_add_customer_helper
{

    /**
     * Add the Actions
     */
    public function __construct()
    {
    }

    /**
     * Adds the Settings page in the Wordpress backend.
     *
     * @return void
     */
    public function setup_options()
    {
        $title = __('Add Customer Settings', 'wac');
        add_options_page($title, $title, 'manage_options', 'wac-options', [$this, 'render_options']);
    }

    /**
     * Loads the backend page template /template/backend/backend-options-page.php
     *
     * @return void
     */
    public function render_options()
    {
        $wac = new woo_add_customer();
        echo $wac->load_template_to_var('backend-options-page', 'backend/');
        return;
    }

    /**
     * Registers the settings for the Page. 
     *
     * @return void
     */
    public function wac_register_settings()
    {
        $wac = new woo_add_customer();

        $blog_name = get_bloginfo('name');
        $default_email_from = $wac->get_mail_from();

        register_setting('wac_general_options', 'wac_general_options', [$this, 'wac_options_validate']);

        add_settings_section('wac_main_settings', __('Main Settings', 'wac'), null, 'wac_general_options');
        add_settings_section('wac_template_settings', __('Template Settings', 'wac'), null, 'wac_general_options');

        add_settings_field(
            'wac_preselect',
            __('Selected by default', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_main_settings',
            array(
                'label_for' => 'wac_preselect',
                'type' => 'checkbox',
                'class' => 'wac_preselect',
                'description' => __('Select this box if you like to have the "Add Customer" Checkbox activated by default.', 'wac'),
                'page' => 'wac_general_options'

            )
        );
        add_settings_field(
            'wac_send_notification',
            __('Send Notifications to new user', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_main_settings',
            array(
                'label_for' => 'wac_send_notification',
                'type' => 'checkbox',
                'class' => 'wac_preselect',
                'description' => __('Check this to send a "Account created" email to the customer after account creation.', 'wac'),
                'page' => 'wac_general_options'
            )
        );

        //Template settings fields
        add_settings_field(
            'wac_template_subject_add_account',
            __('Email Subject for new accounts created', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_template_settings',
            array(
                'label_for' => 'wac_template_subject_add_account',
                'type' => 'text',
                'class' => 'wac_text_input',
                'description' => __('The subject / header for the email which are sended to the new customers', 'wac'),
                'page' => 'wac_general_options',
                'default_value' => sprintf(__("New account created at %s", 'wac'), $blog_name)
            )
        );
        add_settings_field(
            'wac_email_from',
            __('Sender email', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_template_settings',
            array(
                'label_for' => 'wac_email_from',
                'type' => 'text',
                'class' => 'wac_text_input',
                'description' => __('The sender email', 'wac'),
                'page' => 'wac_general_options',
                'default_value' => $default_email_from
            )
        );
    }

    /**
     * Validates the Input of the options page
     * @todo Validate the inputs
     *
     * @param [type] $input
     * @return void
     */
    public function wac_options_validate($input)
    {
        return $input;
    }

    /**
     * Loads the Options as html tags. 
     *
     * @param [array] $args
     * @return void
     */
    public function get_settings_option(array $args)
    {
        extract($args);
        $options = (array) get_option($args['page']);
        $default_value = (!empty($args['default_value']))?$args['default_value']:'';
        $options_val = (!empty($options[$label_for])) ? $options[$label_for] : $default_value;
        switch ($type) {
            case 'checkbox':
                $checked = ($options_val === 'yes') ? 'checked' : '';?>
                <tr class='<?php echo $class; ?>'>
                    <th><label><input name="<?php echo $args['page']; ?>[<?php echo $label_for; ?>]" id="<?php echo $label_for; ?>" type="checkbox" value="yes" <?php echo $checked; ?> />
                            <?php echo __('Activated','wac'); ?>
                        </label></th>
                    <td><?php echo $description; ?></td>
                </tr>
                <?php
                //echo "<input id='$label_for' name='wac_general_options[$label_for]' type='checkbox' value='yes' $checked />";
                break;

            case 'text':
                ?>
                <tr class='<?php echo $class; ?> text-input'>
                    <td>
                        <input name="wac_general_options[<?php echo $label_for; ?>]" id="<?php echo $label_for; ?>" type="<?php echo $type; ?>" value="<?php echo $options_val; ?>" />
                </td>
                </tr>
                <tr class='<?php echo $class; ?>-description'>
                    <td><?php echo $description; ?></td>
                </tr>
                <?php
                //echo "<input id='$label_for' name='wac_general_options[$label_for]' type='checkbox' value='yes' $checked />";
                break;

            default:
                # code...
                break;
        }
    }
}
