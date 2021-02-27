<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Config
 *
 * @deprecated Starting from Magento 2.3.6 Braintree payment method core integration is deprecated
 * in favor of official payment integration available on the marketplace
 */
class Config extends \Magento\Payment\Gateway\Config\Config implements ConfigProviderInterface
{
    const CODE = 'paguelofacil_gateway';

    const KEY_SANDBOX = 'sandbox';
    const KEY_ACTIVE = 'active';
    const KEY_MERCHANT_CCLW_SANDBOX = 'merchant_cclw_sandbox';
    const KEY_MERCHANT_CCLW = 'merchant_cclw';
    const KEY_MERCHANT_TOKEN = 'merchant_token';
    const KEY_MERCHANT_TOKEN_SANDBOX = 'merchant_token_sandbox';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_ORDER_STATUS = 'order_status';

    const BOOLEAN_TRUE = 1;
    const BOOLEAN_FALSE = 0;

    /**
     * Braintree config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param null|string $methodCode
     * @param string $pathPattern
     * @param Json|null $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN,
        Json $serializer = null
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * Gets is enviroment is sandbox.
     *
     * Possible values: true or false
     *
     * @param int|null $storeId
     * @return boolean
     */
    public function isSandbox($storeId = null)
    {
        return $this->getValue(Config::KEY_SANDBOX, $storeId) == self::BOOLEAN_TRUE;
    }

    /**
     * Gets active.
     *
     * @param int|null $storeId
     * @return boolean
     */
    public function isActive($storeId = null)
    {
        return $this->getValue(Config::KEY_ACTIVE, $storeId) == self::BOOLEAN_TRUE;
    }

    /**
     * Gets cclw for sandbox.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantCclwSandbox($storeId = null)
    {
        return $this->getValue(Config::KEY_MERCHANT_CCLW_SANDBOX, $storeId);
    }

    /**
     * Gets cclw for live.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantCclw($storeId = null)
    {
        return $this->getValue(Config::KEY_MERCHANT_CCLW, $storeId);
    }

    /**
     * Gets real merchant token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRealMerchantCclw($storeId = null)
    {
        if($this->isSandbox($storeId)) {
            return $this->getMerchantCclwSandbox($storeId);
        }

            return $this->getMerchantCclw($storeId);
    }

    /**
     * Gets token for sandbox.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantTokenSandbox($storeId = null)
    {
        return $this->getValue(Config::KEY_MERCHANT_TOKEN_SANDBOX, $storeId);
    }

    /**
     * Gets token for live.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantToken($storeId = null)
    {
        return $this->getValue(Config::KEY_MERCHANT_TOKEN, $storeId);
    }

    /**
     * Gets real merchant token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRealMerchantToken($storeId = null)
    {
        if($this->isSandbox($storeId)) {
            return $this->getMerchantTokenSandbox($storeId);
        }
            return $this->getMerchantToken($storeId);
    }

    /**
     * Retrieve available credit card types
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCardTypes($storeId = null)
    {
        $ccTypes = $this->getValue(self::KEY_CC_TYPES, $storeId);

        return !empty($ccTypes) ? explode(',', $ccTypes) : [];
    }

    /**
     * Retrieve the order status by default
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderStatus($storeId = null)
    {
        return $this->getValue(Config::KEY_ORDER_STATUS, $storeId);
    }


    public function getConfig()
    {
        /*        $storeId = $this->session->getStoreId();
                $isActive = $this->config->isActive($storeId);*/

        return [
            'payment' => [
                self::CODE => [
                    /*                    'isActive' => $isActive,
                                        'merchantCclw' => $this->config->getRealMerchantCclw($storeId),
                                        'sandbox' => $this->config->isSandbox($storeId),*/
                ]
            ]
        ];
    }
}
