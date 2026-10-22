/**
 * Plugin Name: Add Customer for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
let wac_scripts = {

    __ : wp.i18n.__,

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
            //Show the add or update customer checkbox, depending on the selected customer
            this.wac_customer_checkbox_visibility();
            //Update the notify checkbox
            this.wac_checkbox_checker();
        });

        /**
         * Shows a warning if the user selects the admin as a user role
         */
        jQuery('#wac_default_user_role').change((e) => {
            if(e.currentTarget.value === 'administrator'){
                this.show_admin_user_warning();
            }
        });

        /**
         * Fires when the edit address button is clicked
         */
        jQuery('#order_data a.edit_address').on('click', () => {
            this.maybe_check_update_customer();
            this.maybe_check_add_customer();
            //WooCommerce shows all the div.edit_address on click, so the visibility has to be set again
            this.wac_customer_checkbox_visibility();
            this.wac_checkbox_checker();
        })

        //Run after document loaded to show the second checkbox.
        wac_scripts.wac_customer_checkbox_visibility();
        wac_scripts.wac_checkbox_checker();
        /*jQuery(document).ready(function () {
        });*/

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
     * Shows the update customer checkbox if a customer is selected, the add customer checkbox otherwise.
     * The hidden checkbox gets unchecked.
     */
    wac_customer_checkbox_visibility() {
        const customer_selected = this.is_customer_selected();
        if (customer_selected) {
            jQuery('#wac_add_customer').prop("checked", false);
        } else {
            jQuery('#wac_update_customer').prop("checked", false);
        }
        jQuery('#wac_add_customer_con').toggle(!customer_selected);
        jQuery('#wac_update_customer_con').toggle(customer_selected);
    },

    /**
     * Checks if a customer is selected in the customer list.
     * @returns {boolean}
     */
    is_customer_selected() {
        const customer_id = jQuery('#customer_user').val();
        return !this.empty(customer_id);
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
        //If the checkbox should be preselected, select it now. Not if a customer is already selected.
        const is_preselected = window.sep_variables.default_options.add_customer === 'checked';
        if (is_preselected && !this.is_customer_selected()) {
            jQuery('#wac_add_customer').prop("checked", true);
        }
    },

    /**
     * Displays an alert if the user selects the administrator as an default user.
     */
    show_admin_user_warning(){
        alert(this.__('You chose "Administrator" as the default role for a new customer. This is not recommended and can lead to an increased attack surface. "Customer" is the recommended role.','wac'));
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


