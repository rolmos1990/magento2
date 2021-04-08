<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paguelofacil\Gateway\Observer\DataAssignObserver;
use Paguelofacil\Gateway\Gateway\Config;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Validate the enviroment for sandbox or production
     *
     * @return void
     */

    private function validateEnviromentConfig(){
        $validProduction = $this->config->getValue(Config::KEY_MERCHANT_CCLW) && ($this->config->getValue(Config::KEY_SANDBOX) != Config::BOOLEAN_TRUE);
        $validSandbox = $this->config->getValue(Config::KEY_MERCHANT_CCLW_SANDBOX) && ($this->config->getValue(Config::KEY_SANDBOX) == Config::BOOLEAN_TRUE);

        if (!$validProduction && !$validSandbox) {
            $message = "CCLW Prod: ". $this->config->getValue(Config::KEY_MERCHANT_CCLW). "CCLW Sandbox: ".$this->config->getValue(Config::KEY_MERCHANT_CCLW_SANDBOX). ", Mode Sandbox:" .$this->config->getValue(Config::KEY_SANDBOX);
            throw new \InvalidArgumentException('You must be an merchant cclw valid, Please verify your configuration. - '. $message);
        }
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
        $address = $order->getBillingAddress();

        /** Correct Enviroment validation configuration for Enviroment Production or Sandbox */
        $this->validateEnviromentConfig();

        #Credit card Owner
        $ccowner = explode(" ",$payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_OWNER),2);
        $name = isset($ccowner[0])?$ccowner[0]:'';
        $lastname = isset($ccowner[1])?$ccowner[1]:' ';

        $month = $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_MONTH_EXPIRY) > 9 ? $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_MONTH_EXPIRY) : "0" . $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_MONTH_EXPIRY);
        $year = substr($payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_YEAR_EXPIRY), -2);

        $card_type = $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_TYPE) == "VI" ? "VISA" : "MC";

        return [
            'cclw' => $this->config->getRealMerchantCclw($order->getStoreId()),
            'amount' => floatval($order->getGrandTotalAmount()),
            'taxAmount' => 0.00,
            'email' => $address->getEmail(),
            'phone' => $address->getTelephone(),
            'address' => $address->getStreetLine1(),
            'concept' => "MG-Order- " . $order->getOrderIncrementId(),
            "description" => "MG-Order- " . $order->getOrderIncrementId(),
            "ipCheck" => $order->getRemoteIp(),
            "cardInformation" => [
                "cardNumber" => $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CREDITCARD_NUMBER),
                "expMonth" => $month,
                "expYear" => $year,
                "cvv" => $payment->getPayment()->getAdditionalInformation(DataAssignObserver::CC_VERIFICATION),
                "firstName" => $name,
                "lastName" => $lastname,
                "cardType" => $card_type
            ]
        ];
    }
}
