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
$wac = new woo_add_customer;
$this->wac_enqueue_admin_scripts();
$this->wac_enqueue_admin_style();
?>

<h2><?php echo __('Add Customer for WooCommerce settings', 'wac'); ?></h2>

<?php
$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : null;
?>

<nav class="nav-tab-wrapper">
    <a href="?page=wac_general_options" class="nav-tab <?php if ($current_tab === null) {echo 'nav-tab-active';} ?>"><?php echo __('General', 'wac'); ?></a>
    <a href="?page=wac_general_options&tab=template" class="nav-tab <?php if ($current_tab === 'template') {echo 'nav-tab-active';} ?>"><?php echo __('Template', 'wac'); ?></a>
    <a href="?page=wac_general_options&tab=support" class="nav-tab <?php if ($current_tab === 'support') {echo 'nav-tab-active';} ?>"><?php echo __('Support', 'wac'); ?></a>
</nav>

<div class="wac-options-tab tab-content">
    <?php
    switch ($current_tab) {
        case 'support':
            echo $wac->load_template_to_var('backend-options-page-support', 'backend/');
            break;
        case 'template':
            echo $wac->load_template_to_var('backend-options-page-template', 'backend/');
            break;
        default:
            echo $wac->load_template_to_var('backend-options-page-main', 'backend/');
            break;
    }
    ?>
</div>
<div class="wac-statistics">
    <?php
    echo sprintf(
        __('There have been %d customers added and %d customers edited by the Plugin', 'wac'),
        intval(get_option('wac_add_customer_count')),
        intval(get_option('wac_edit_customer_count'))
    );
    ?>
</div>
<div class="wac-footer">
    <span><?php echo __('Made with ❤️ by Dan\'s Art', 'wac'); ?></span>
</div>