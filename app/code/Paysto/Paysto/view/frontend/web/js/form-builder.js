/**
 * Copyright © Paysto, Inc. All rights reserved.
 *
 */

define([
    'jquery',
    'underscore',
    'mage/template'
], function ($, _, mageTemplate) {
    'use strict';

    return {
        /**
         * @param {Object} response
         * @return {*|jQuery}
         */
        build: function (response) {
            var formTmpl =
                '<form action="<%= data.action %>" method="POST" hidden enctype="application/x-www-form-urlencoded">' +
                    '<% _.each(data.fields, function(val, key){ %>' +
                    '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
                    '<% }); %>' +
                    '<% _.each(data.cartitems, function(val){ %>' +
                    '<input value="<%= val %>" name="x_line_item" type="hidden">' +
                    '<% }); %>' +
                    '</form>',
                inputs = {},
                cartitems = {},
                tmpl, index, hiddenFormTmpl;

            for (index in response.fields) { //eslint-disable-line guard-for-in
                if (response.fields[index] === 'x_line_item'){
                    cartitems = response.values[index]
                } else {
                    inputs[response.fields[index]] = response.values[index];
                }
            }

            hiddenFormTmpl = mageTemplate(formTmpl);
            tmpl = hiddenFormTmpl({
                data: {
                    action: response.action,
                    fields: inputs,
                    cartitems: cartitems
                }
            });

            return $(tmpl).appendTo($('[data-container="body"]'));
        }
    };
});
