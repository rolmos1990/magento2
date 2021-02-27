/**
 * Paguelofacil SA
 *
 * @copyright   Paguelofacil (http://paguelofacil.com)
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'jquery',
        'mage/url',
    ],
    function (Component, placeOrderAction, $, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paguelofacil_Gateway/payment/paguelofacil-offsite'
            },
            getCode: function() {
                return 'paguelofacil_offsite';
            },
            isActive: function() {
                return true;
            },
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();
                        }
                    );

                    return true;
                }

                return false;
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('paguelofacil/payment/data'));
            }
        });
    }
);
