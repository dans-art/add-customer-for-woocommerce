<?php

/**
 * This Template renders the backend page of the template preview.
 * Wordpress Backend -> Settings -> Add Customer Settings -> [Tab]Template
 * 
 * @version 1.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<h2><?php echo __('Support','wac') ?></h2>
<div>
    <?php echo __('Thanks for using this Plugin. It was crafted with a lot of care and love, but errors and incompatibilities with other plugins can occur.','wac'); ?><br/>
    <?php echo __('If you find yourself having trouble, please let me know by writing to:','wac'); ?> <a href='mailto:info@dans-art.ch' target="_blank">info@dans-art.ch</a>
    
<h3><?php echo __('Development','wac') ?></h3>
<p>
<a href='https://wordpress.org/plugins/add-customer-for-woocommerce/' target="_blank"><?php echo __('Wordpress.org','wac') ?></a><br/>
<a href='https://github.com/dans-art/wac-woocommerce-add-customer' target="_blank"><?php echo __('Github.com','wac') ?></a><br/>
</p>
<h3><?php echo __('Support me','wac') ?></h3>
<p>
    <?php echo __('If you like this plugin and want to give me something back, here are some ways to do so:','wac') ?><br/>
<a href='https://wordpress.org/support/plugin/add-customer-for-woocommerce/reviews/#new-post' target="_blank"><?php echo __('Write a review and give me 5 stars on Wordpress.com','wac') ?></a><br/>
<a href='https://www.paypal.com/paypalme/dansart13' target="_blank"><?php echo __('Donate something on PayPal','wac') ?></a><br/>
<?php echo __('Thanks!','wac') ?>
</p>

</div>