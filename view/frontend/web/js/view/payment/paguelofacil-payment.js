define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'paguelofacil_gateway',
                component: 'Paguelofacil_Gateway/js/view/payment/method-renderer/paguelofacil-method'
            },
            {
                type: 'paguelofacil_offsite',
                component: 'Paguelofacil_Gateway/js/view/payment/method-renderer/offsite-method'
            },
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
