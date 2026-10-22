<?php

/**
 * This Template renders the backend page of the settings.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version     1.6.5
 * @package     WAC\Templates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included within woo_add_customer_helper::load_template_to_var(), the variables are not global.
$wac = new woo_add_customer;
$this->wac_enqueue_admin_scripts();
$this->wac_enqueue_admin_style();
?>

<h2><?php echo esc_html__('Add Customer for WooCommerce settings', 'add-customer-for-woocommerce'); ?></h2>

<?php
//Only used to select the tab to display
$current_tab = (isset($_GET['tab'])) ? sanitize_key(wp_unslash($_GET['tab'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<nav class="nav-tab-wrapper">
    <a href="?page=wac_general_options" class="nav-tab <?php if ($current_tab === null) {echo 'nav-tab-active';} ?>"><?php echo esc_html__('General', 'add-customer-for-woocommerce'); ?></a>
    <a href="?page=wac_general_options&tab=template" class="nav-tab <?php if ($current_tab === 'template') {echo 'nav-tab-active';} ?>"><?php echo esc_html__('Template', 'add-customer-for-woocommerce'); ?></a>
    <a href="?page=wac_general_options&tab=support" class="nav-tab <?php if ($current_tab === 'support') {echo 'nav-tab-active';} ?>"><?php echo esc_html__('Support', 'add-customer-for-woocommerce'); ?></a>
</nav>

<div class="wac-options-tab tab-content">
    <?php
    switch ($current_tab) {
        case 'support':
            echo $wac->load_template_to_var('backend-options-page-support', 'backend/'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output
            break;
        case 'template':
            echo $wac->load_template_to_var('backend-options-page-template', 'backend/'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output
            break;
        default:
            echo $wac->load_template_to_var('backend-options-page-main', 'backend/'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output
            break;
    }
    ?>
</div>
<div class="wac-statistics">
    <?php
    echo esc_html(sprintf(
        /* translators: 1: Number of customers added, 2: Number of customers edited */
        __('There have been %1$d customers added and %2$d customers edited by the Plugin', 'add-customer-for-woocommerce'),
        intval(get_option('wac_add_customer_count')),
        intval(get_option('wac_edit_customer_count'))
    ));
    ?>
</div>
<div class="wac-footer">
    <span><?php echo esc_html__('Made with ❤️ by Dan\'s Art', 'add-customer-for-woocommerce'); ?></span>
</div>