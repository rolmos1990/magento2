/**
 * Paguelofacil SA
 *
 * @copyright   Paguelofacil (http://paguelofacil.com)
 */
/*browser:true*/
/*global define*/
define(
    [
        'Paguelofacil_Gateway/js/view/payment/form/cc-form',
        'jquery',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paguelofacil_Gateway/payment/paguelofacil-form',
                timeoutMessage: 'Lo sentimos algo esta mal contacte proveedor.'
            },
            getCode: function() {
                return 'paguelofacil_gateway';
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
                            console.log("EJECUTADO");

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
