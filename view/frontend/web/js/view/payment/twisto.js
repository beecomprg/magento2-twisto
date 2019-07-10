/*browser:true*/
/*global define*/
define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (Component,
            rendererList) {
    'use strict';

    var config = window.checkoutConfig.payment,
      twisto = 'twisto';

    if (config[twisto].isActive) {
      rendererList.push(
      {
        type: 'twisto',
        component: 'Beecom_Twisto/js/view/payment/method-renderer/twisto'
      });
    }

    return Component.extend({});
  }
);
