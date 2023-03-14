
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paymee_Core/payment/pix'
            },

            getCode: function () {
                return 'paymee_pix';
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            /** Returns instructions */
            getInstructions: function() {
                return window.checkoutConfig.payment[this.getCode()]['instructions'];
            },

            getData: function () {
                // data to Post in backend
                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode()
                    }
                };

                if (this.displayCpf()) {
                    dataObj['additional_data']['pix_cpf'] = document.querySelector("input[name='payment[pix_cpf]']").value
                }

                return dataObj;
            },

            /* Display Paymee field cpf on checkout */
            displayCpf: function() {
                return window.checkoutConfig.payment[this.getCode()]['fieldCpf'];
            }
        });
    }
);
