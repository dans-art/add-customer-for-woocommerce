<?php

/**
 * This Template renders the backend page for the main settings.
 * Wordpress Backend -> Settings -> Add Customer Settings
 * 
 * @version     1.6.5
 * @package     WAC\Templates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<form id='wac_options_page' action="options.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="page" value="wac_general_options"/>
    <?php
    settings_fields('wac_general_options');
    do_settings_sections('wac_general_options');

    submit_button();
    ?>
</form>