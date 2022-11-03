/**
 * Plugin Name: Add Customer for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
let wac_scripts = {

    construct() {
        /**
         * Fires on change of the wac checkbox. If selected, the "Notify customer" checkbox will be shown.
         */
        jQuery('#wac_add_customer').change(() => {
            this.wac_checkbox_checker();
        });

        jQuery('#customer_user').change((e) => {
            debugger;
            console.log('Changed');
            if(wac_scripts.empty(e.currentTarget.value)){
                //No customer selected, change to the default state
                if(window.sep_variables.default_options.add_customer === 'checked'){
                    jQuery('#wac_add_customer').prop("checked", true);
                }
            }else{
                //Customer selected, remove the tick on the checkbox
                jQuery('#wac_add_customer').prop("checked", false);
            }
        });

        //Run after document loaded to show the second checkbox.
        jQuery(document).ready(function () {
            wac_scripts.wac_checkbox_checker();
        });
    },

    /**
     * Checks if the secondary checkbox for the notification has to be shown or not.
     */
    wac_checkbox_checker() {
        if (jQuery('#wac_add_customer').is(":checked")) {
            jQuery('p.wac_add_customer_notify_field').show();
        } else {
            jQuery('p.wac_add_customer_notify_field').hide();
        }
    },

    //Helper functions
    /**
     * Checks if the given value is empty or null
     * @param {mixed} value 
     * @returns 
     */
    empty(value) {
        if (value === null) {
            return true;
        }
        if (typeof value === 'undefined') {
            return true;
        }
        if (value.length === 0) {
            return true;
        }
        return false;
    }

};

wac_scripts.construct();


