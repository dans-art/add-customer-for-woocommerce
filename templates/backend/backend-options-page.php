<?php

/**
 * This Template renders the backend page of the settings.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version 1.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$wac = new woo_add_customer;

$this->wac_enqueue_admin_style();
?>

<h2><?php echo __('Add Customer for Woocommerce Settings', 'wac'); ?></h2>

<?php
$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : null;
?>

<nav class="nav-tab-wrapper">
    <a href="?page=wac-options" class="nav-tab <?php if ($current_tab === null) {echo 'nav-tab-active';} ?>"><?php echo __('General', 'wac'); ?></a>
    <a href="?page=wac-options&tab=template" class="nav-tab <?php if ($current_tab === 'template') {echo 'nav-tab-active';} ?>"><?php echo __('Template', 'wac'); ?></a>
</nav>

<div class="wac-options-tab tab-content">
    <?php
    switch ($current_tab) {
        case 'template':
            echo $wac->load_template_to_var('backend-options-page-template', 'backend/');
            break;
        default:
            echo $wac->load_template_to_var('backend-options-page-main', 'backend/');
            break;
    }
    ?>
</div>