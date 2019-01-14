/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */

define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Paysto_Paysto/js/form-builder',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/place-order',
    'Magento_Customer/js/customer-data'
], function (
    $,
    Component,
    quote,
    customer,
    urlBuilder,
    storage,
    formBuilder,
    errorProcessor,
    fullScreenLoader,
    placeOrderService,
    customerData
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Paysto_Paysto/payment/paysto-form',
            redirectAfterPlaceOrder: false
        },

        /**
         * After place order action
         */
        afterPlaceOrder: function () {
            var self = this;

            $.get(window.checkoutConfig.payment[this.getCode()].transactionDataUrl)
                .done(function (response) {
                    customerData.invalidate(['cart', 'checkout-data']);
                    formBuilder.build(response).submit();
                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                    fullScreenLoader.stopLoader();
                });
        },

        /**
         * Rewrites place order deferred object with guest cart service handler
         * as a workaround for issue with customer sections flush on regular place order action
         * @returns {*}
         */
        getPlaceOrderDeferredObject: function () {
            var self = this;

            if (customer.isLoggedIn()) {
                return this._super();
            }

            return $.when(
                placeOrderService(
                    urlBuilder.createUrl('/paysto-guest-carts/:quoteId/payment-information', {
                        quoteId: quote.getQuoteId()
                    }),
                    {
                        cartId: quote.getQuoteId(),
                        billingAddress: quote.billingAddress(),
                        paymentMethod: this.getData(),
                        email: quote.guestEmail
                    },
                    self.messageContainer
                )
            );
        },

        /**
         * Payment method code getter
         * @returns {String}
         */
        getCode: function () {
            return 'paysto';
        }
    });
});
