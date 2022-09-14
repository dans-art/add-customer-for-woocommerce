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

    public function __construct()
    {
        $this->plugin_path = WP_PLUGIN_DIR . '/add-customer-for-woocommerce/';
    }

    /**
     * Adds the Settings menu item in the Wordpress backend.
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
            __('Add new customer by default', 'wac'),
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
            'wac_preselect_update',
            __('Update new customer by default', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_main_settings',
            array(
                'label_for' => 'wac_preselect_update',
                'type' => 'checkbox',
                'class' => 'wac_preselect_update',
                'description' => __('If this is checked, the existing customer data will be updated when the order is updated.', 'wac'),
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
        add_settings_field(
            'wac_fakemail_format',
            __('Auto-Generated eMail Format', 'wac'),
            [$this, 'get_settings_option'],
            'wac_general_options',
            'wac_main_settings',
            array(
                'label_for' => 'wac_fakemail_format',
                'type' => 'text',
                'class' => 'wac_text_input',
                'description' =>
                __('The Format you like to have for the automatic generated eMail.', 'wac') . ' ' .
                    __('You can use the supported tags below wrapping them in [ ]', 'wac') . '<br/>' .
                    __('Supported tags:', 'wac') . ' ' . implode(', ', $wac->supported_fake_email_parts),
                'page' => 'wac_general_options',
                'default_value' => '[first_name].[last_name]@' . $wac->get_domain_name()
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
     * Checks if the option field contains some errors.
     *
     * @param string $field_name
     * @param string $value
     * @return string Error message or empty string
     */
    public function check_option($field_name, $value)
    {
        switch ($field_name) {
            case 'wac_fakemail_format':
                //Check if multiple @ exists
                if (substr_count($value, '@') !== 1) {
                    return "<div class='notice notice-error'><p>" . __('Invalid eMail address', 'wac') . "</p></div>";
                }
                break;

            default:
                # code...
                break;
        }
        return '';
    }

    /**
     * Loads the Options as html input elements, wrapped in a table structure
     *
     * @param array $args - The arguments form the add_settings_field function
     * @return void
     */
    public function get_settings_option(array $args)
    {
        extract($args); //$label_for, $type, $class, $description, $page, $default_value
        $options = (array) get_option($args['page']);
        $default_value = (!empty($args['default_value'])) ? $args['default_value'] : '';
        $options_val = (!empty($options[$label_for])) ? $options[$label_for] : '';
        switch ($type) {
            case 'checkbox':
                echo $this->load_template_to_var('checkbox', 'backend/components', $label_for, $options_val, $default_value, $args);
                break;
            case 'text':
                echo $this->load_template_to_var('text-input', 'backend/components', $label_for, $options_val, $default_value, $args);
                break;

            default:
                # code...
                break;
        }
    }
}
