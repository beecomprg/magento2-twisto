/* @api */
define([
  'ko',
  'jquery',
  'uiComponent',
  'Magento_Checkout/js/action/place-order',
  'Magento_Checkout/js/action/select-payment-method',
  'Magento_Checkout/js/model/quote',
  'Magento_Customer/js/model/customer',
  'Magento_Checkout/js/model/payment-service',
  'Magento_Checkout/js/checkout-data',
  'Magento_Checkout/js/model/checkout-data-resolver',
  'uiRegistry',
  'Magento_Checkout/js/model/payment/additional-validators',
  'Magento_Ui/js/model/messages',
  'uiLayout',
  'Magento_Checkout/js/action/redirect-on-success',
  'Beecom_Twisto/js/view/payment/adapter',
  'Magento_Checkout/js/action/set-payment-information',
  'Magento_Checkout/js/model/full-screen-loader'
], function (
  ko,
  $,
  Component,
  placeOrderAction,
  selectPaymentMethodAction,
  quote,
  customer,
  paymentService,
  checkoutData,
  checkoutDataResolver,
  registry,
  additionalValidators,
  Messages,
  layout,
  redirectOnSuccessAction,
  twisto,
  setPaymentInformationAction,
  fullScreenLoader,
) {
  'use strict';

  return Component.extend({
    isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
    defaults: {
      redirectAfterPlaceOrder: true,
      template: 'Beecom_Twisto/payment/twisto',
      active: false,
      twistoClient: null,
      txnId: null,

      /**
       * Additional payment data
       *
       * {Object}
       */
      additionalData: {},

      imports: {
        onActiveChange: 'active'
      }
    },

    /**
     * Initialize view.
     *
     * @return {exports}
     */
    initialize: function () {
      var billingAddressCode,
        billingAddressData,
        defaultAddressData;

      this._super().initChildren();
      quote.billingAddress.subscribe(function (address) {
        this.isPlaceOrderActionAllowed(address !== null);
      }, this);
      checkoutDataResolver.resolveBillingAddress();

      billingAddressCode = 'billingAddress' + this.getCode();
      registry.async('checkoutProvider')(function (checkoutProvider) {
        defaultAddressData = checkoutProvider.get(billingAddressCode);

        if (defaultAddressData === undefined) {
          // Skip if payment does not have a billing address form
          return;
        }
        billingAddressData = checkoutData.getBillingAddressFromData();

        if (billingAddressData) {
          checkoutProvider.set(
            billingAddressCode,
            $.extend(true, {}, defaultAddressData, billingAddressData)
          );
        }
        checkoutProvider.on(billingAddressCode, function (providerBillingAddressData) {
          checkoutData.setBillingAddressFromData(providerBillingAddressData);
        }, billingAddressCode);
      });

      return this;
    },

    /**
     * Create child message renderer component
     *
     * @returns {Component} Chainable.
     */
    createMessagesComponent: function () {

      var messagesComponent = {
        parent: this.name,
        name: this.name + '.messages',
        displayArea: 'messages',
        component: 'Magento_Ui/js/view/messages',
        config: {
          messageContainer: this.messageContainer
        }
      };

      layout([messagesComponent]);

      return this;
    },

    /**
     * @return {Boolean}
     */
    selectPaymentMethod: function () {
      selectPaymentMethodAction(this.getData());
      checkoutData.setSelectedPaymentMethod(this.item.method);

      return true;
    },

    isRadioButtonVisible: ko.computed(function () {
      return paymentService.getAvailablePaymentMethods().length !== 1;
    }),

    /**
     * Get payment method data
     */
    getData: function () {
      return {
        'method': this.item.method,
        'additional_data': {
          'txn_id': this.txnId
        }
      };
    },

    getCode: function () {
      return 'twisto';
    },

    /**
     * Get payment method type.
     */
    getTitle: function () {
      return this.item.title;
    },

    /**
     * Initialize child elements
     *
     * @returns {Component} Chainable.
     */
    initChildren: function () {
      this.messageContainer = new Messages();
      this.createMessagesComponent();

      return this;
    },

    /**
     * Set list of observable attributes
     *
     * @returns {exports.initObservable}
     */
    initObservable: function () {
      console.log('ola');
      // validator.setConfig(window.checkoutConfig.payment[this.getCode()]);
      this._super()
        .observe(['active']);
      // this.validatorManager.initialize();
      // this.initClientConfig();

      return this;
    },

    isChecked: ko.computed(function () {
      return quote.paymentMethod() ? quote.paymentMethod().method : null;
    }),

    /**
     * Check if payment is active
     *
     * @returns {Boolean}
     */
    isActive: function () {
      var active = this.getCode() === this.isChecked();

      this.active(active);

      return active;
    },

    /**
     * @return {Boolean}
     */
    validate: function () {
      return true;
    },

    /**
     * Get full selector name
     *
     * @param {String} field
     * @returns {String}
     */
    getSelector: function (field) {
      console.log('ola');
      return '#' + this.getCode() + '_' + field;
    },

    /**
     * Set payment nonce
     * @param {String} paymentMethodNonce
     */
    setTransactionId: function (id) {
      this.txnId = id;
    },

    /**
     * Prepare data to place order
     * @param {Object} data
     */
    beforePlaceOrder: function (data) {
      // this.setPaymentMethodNonce(data.nonce);

      this.placeOrder();
    },

    afterPlaceOrder: function (data) {
      console.log('afterPlaceOrderData');
    },

    placeOrder: function (data, event) {
      var self = this;

      if (event) {
        event.preventDefault();
      }

      if (this.validate() && additionalValidators.validate()) {
        this.isPlaceOrderActionAllowed(false);
        fullScreenLoader.startLoader();
        $.when(
          setPaymentInformationAction(this.messageContainer, self.getData())
        ).done(
          function () {
            var Twisto = twisto.setup();
            $.ajax({
              url: '/'+ self.getCode() +'/checkout/callback',
              type: 'POST',
            }).success(function( result ) {
              console.log(result);
              Twisto.check(result.payload, function(response) { //success
                  if (response.status === 'accepted') {
                    console.log(response.transaction_id);
                    self.setTransactionId(response.transaction_id);
                    $.when( //stupid way of adding txn id to order object
                      setPaymentInformationAction(self.messageContainer, self.getData())
                    ).done(function () {
                      //FIXME pass the transaction id to additional data here
                      self.getPlaceOrderDeferredObject()
                        .fail(
                          function () {
                            self.isPlaceOrderActionAllowed(true);
                          }
                        ).done(
                        function () {
                          self.afterPlaceOrder();

                          if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                          }
                        }
                      );

                      return true;
                    });

                  } else {
                    // platba byla zamítnuta
                    console.log(response.reason);
                    self.messageContainer.addErrorMessage(response.reason);
                  }
                }, function(response) { //error
                  console.log('General error.', response);
                  fullScreenLoader.stopLoader();
                  self.isPlaceOrderActionAllowed(true);
                  self.showErrorMessageFromResponse(response);
                }
              );
            }).error(function( result ) {
              console.log(result);
              fullScreenLoader.stopLoader();
              self.showErrorMessageFromResponse(response);
            });
          }
        ).always(
          function () {
            // self.isPlaceOrderActionAllowed(true);
            // fullScreenLoader.stopLoader();
          }
        );
        return true;
      }
      return false;
    },
    /**
     * @return {String}
     */
    getBillingAddressFormName: function () {
      return 'billing-address-form-' + this.item.method;
    },

    /**
     * Dispose billing address subscriptions
     */
    disposeSubscriptions: function () {
      // dispose all active subscriptions
      var billingAddressCode = 'billingAddress' + this.getCode();

      registry.async('checkoutProvider')(function (checkoutProvider) {
        checkoutProvider.off(billingAddressCode);
      });
    },

    /**
     * @return {*}
     */
    getPlaceOrderDeferredObject: function () {
      return $.when(
        placeOrderAction(this.getData(), this.messageContainer)
      );
    },

    showErrorMessageFromResponse: function (response) {
      var self = this;
      $.each(response.order, function(key,valueObj){
        $.each(valueObj, function(keyR,valueObjR){
          $.each(valueObjR, function(k, message){
            alert(key + ': ' + message);
          });
        });
      });
    }

  });
});
