<?php

/**
 * This Template renders the backend page of the template preview.
 * Wordpress Backend -> Settings -> Add Customer Settings -> [Tab]Template
 * 
 * @version     1.6.5
 * @package     WAC\Templates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<h2><?php echo esc_html__('Support', 'add-customer-for-woocommerce') ?></h2>
<div>
    <?php echo esc_html__('Thanks for using this Plugin. It was crafted with a lot of care and love, but errors and incompatibilities with other plugins can occur.', 'add-customer-for-woocommerce'); ?><br />
    <?php echo esc_html__('If you\'re having trouble, please let me know by writing to:', 'add-customer-for-woocommerce'); ?> <a href='mailto:info@dans-art.ch' target="_blank">info@dans-art.ch</a>

    <h3><?php echo esc_html__('Development', 'add-customer-for-woocommerce') ?></h3>
    <p>
        <a href='https://wordpress.org/plugins/add-customer-for-woocommerce/' target="_blank"><?php echo esc_html__('Wordpress.org', 'add-customer-for-woocommerce') ?></a><br />
        <a href='https://github.com/dans-art/wac-woocommerce-add-customer' target="_blank"><?php echo esc_html__('Github.com', 'add-customer-for-woocommerce') ?></a><br />
    </p>
    <h3><?php echo esc_html__('Support me', 'add-customer-for-woocommerce') ?></h3>
    <p>
        <?php echo esc_html__('If you like this plugin and want to give me something back, here are some ways to do so:', 'add-customer-for-woocommerce') ?><br />
        <a href='https://wordpress.org/support/plugin/add-customer-for-woocommerce/reviews/#new-post' target="_blank"><?php echo esc_html__('Write a review and give me 5 stars on Wordpress.com', 'add-customer-for-woocommerce') ?></a><br />
        <a href='https://www.paypal.com/paypalme/dansart13' target="_blank"><?php echo esc_html__('Make a donation on PayPal', 'add-customer-for-woocommerce') ?></a><br />
        <?php echo esc_html__('Thanks!', 'add-customer-for-woocommerce') ?>
    </p>
    <p>
    <h3><?php echo esc_html__('Try the plugin on TasteWP:', 'add-customer-for-woocommerce') ?></h3>
    <a href='https://tastewp.com/new?pre-installed-plugin-slug=woocommerce%2Cadd-customer-for-woocommerce&redirect=admin.php%3Fpage%3Dwac_general_options&ni=true' target="_blank"><?php echo esc_html__('Try it for free on tastewp.com', 'add-customer-for-woocommerce') ?></a><br />
    <a href='https://tastewp.com/a/5p4giXsZ' target="_blank"><?php echo esc_html__('Get your non-expiring site [Affiliate link]', 'add-customer-for-woocommerce') ?></a><br />
    </p>

</div>
<?php
//Testing
//This section is intended to test internal functions
/*
$helper = new woo_add_customer_helper();
s($helper -> create_fake_email('test'));
*/
?>