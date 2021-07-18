<?php
/**
 * This Template renders the backend page of the settings.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version 1.1
 */
?>
<h2><?php echo __('Add Customer for Woocommerce Settings','wac');?></h2>


<form id='wac_options_page' action="options.php" method="post"  enctype="multipart/form-data">
    <?php

    settings_fields('wac_general_options'); 
    do_settings_sections('wac_general_options');
    
    submit_button();
    ?>
</form>