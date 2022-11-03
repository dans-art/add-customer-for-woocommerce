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

        /**
         * Fires if the user selects an saved user form the customer list
         */
        jQuery('#customer_user').change((e) => {
            if (wac_scripts.empty(e.currentTarget.value)) {
                //No customer selected, change to the default state
                if (window.sep_variables.default_options.add_customer === 'checked') {
                    jQuery('#wac_add_customer').prop("checked", true);
                }
                //Uncheck the update customer
                jQuery('#wac_update_customer').prop("checked", false);
            } else {
                //Customer selected, remove the tick on the checkbox
                jQuery('#wac_add_customer').prop("checked", false);
            }
        });

        /**
         * Fires when the edit address button is clicked
         */
        jQuery('#order_data a.edit_address').on('click', () => {
            this.maybe_check_update_customer();
            this.maybe_check_add_customer();
        })

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

    /**
     * Checks if the edit customer checkbox should be checked and checks it.
     */
    maybe_check_update_customer() {
        //If the checkbox should be preselected, select it now.
        if (window.sep_variables.default_options.update_customer === 'checked') {
            jQuery('#wac_update_customer').prop("checked", true);
        }
    },
    /**
     * Checks if the add customer checkbox should be checked and checks it.
     */
    maybe_check_add_customer() {
        //If the checkbox should be preselected, select it now.
        if (window.sep_variables.default_options.add_customer === 'checked') {
            jQuery('#wac_add_customer').prop("checked", true);
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

jQuery(document).ready(() => {
    wac_scripts.construct();
});


