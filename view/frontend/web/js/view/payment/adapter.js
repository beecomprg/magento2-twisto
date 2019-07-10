
/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function ($, globalMessageList, $t) {
    'use strict';

    return {
        client: null,
        config: {},
        checkout: null,

        /**
         * Get Braintree api client
         * @returns {Object}
         */
        getClient: function () {
            if (!this.client) {
                this.client = window.Twisto;
            }

            return this.client;
        },

        /**
         * Setup Braintree SDK
         */
        setup: function () {
           return this.getClient();
        },

        /**
         * Get payment name
         * @returns {String}
         */
        getCode: function () {
            return 'twisto';
        },

        /**
         * Get client token
         * @returns {String|*}
         */
        getPublicKey: function () {
            return window.checkoutConfig.payment[this.getCode()].publicKey;
        },

        /**
         * Show error message
         *
         * @param {String} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage
            });
        },
    };
});
