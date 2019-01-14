/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'paysto',
            component: 'Paysto_Paysto/js/view/payment/method-renderer/paysto'
        }
    );

    return Component.extend({});
});
