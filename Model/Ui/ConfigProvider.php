<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE_ONSITE = 'paguelofacil_gateway';

    const CODE_OFFSITE = 'paguelofacil_offsite';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
/*        $storeId = $this->session->getStoreId();
        $isActive = $this->config->isActive($storeId);*/

        return [
            'payment' => [
                self::CODE_ONSITE => [
                  'isActive' => true,
                ],
                self::CODE_OFFSITE => [
                    'isActive' => true
                ]
            ]
        ];
    }

}
