
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (
        Component,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paymee_Core/payment/transfer'
            },

            getCode: function () {
                return 'paymee_transfer';
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
                        'method': this.getCode(),
                        'transfer_bank': document.getElementById("transfer_bank").selectedOptions[0].value,
                        'transfer_branch': document.querySelector("input[name='payment[transfer_branch]']").value,
                        'transfer_account': document.querySelector("input[name='payment[transfer_account]']").value,
                    }
                };

                return dataObj;
            },
            getPaymeeBanks: function () {
                var banks = window.checkoutConfig.payment[this.getCode()]['banks'];
                return _.map(banks, function (value, key) {
                    return {
                        value: key,
                        bank: value,
                    };
                });
            },
            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                //this.getHash();
                return $form.validation() && $form.validation('isValid');
            },
        });
    }
);
