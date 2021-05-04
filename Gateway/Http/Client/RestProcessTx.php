<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Paguelofacil\Gateway\Utils\PaguelofacilServices;
use Paguelofacil\Gateway\Model\Ui\ConfigProvider;
use Paguelofacil\Gateway\Gateway\Config;

abstract class RestProcessTx implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    const TX_ID = "TX_ID";
    const STATUS = "STATUS";
    const DATE = "DATE";
    const TOTAL_PAY = "TOTAL_PAY";
    const REQUESTED_PAY = "REQUESTED_PAY";
    const BIN_INFO = "BIN_INFO";
    const DISPLAY_NUM = "DISPLAY_NUM";
    const MESSAGE = "MESSAGE";

    /**
     * @var Paguelofacil\Gateway\Utils\PaguelofacilServices
     *
     */
    private $httpRequest;

    /**
     * @var Config
     *
     */
    private $config;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param Config $config
     * @param Paguelofacil\Gateway\Utils\PaguelofacilServices $httpRequest
     */
    public function __construct(
        Logger $logger,
        Config $config,
        PaguelofacilServices $httpRequest
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = $this->generateResponseForHttpClient(
            $this->processTx(
                $transferObject
            )
        );

        $this->logger->debug(
            [
                'response' => $response
            ]
        );

        return $response;
    }

    /**
     * Generates response
     *
     * @return array
     */
    protected function generateResponseForHttpClient($resultCode)
    {
        $response = [];
        if($resultCode){

            $data = [
                "codOper" => "",
                "status" => self::FAILURE,
                "date" => null,
                "totalPay" => null,
                "requestPayAmount" => null,
                "displayNum" => null,
                "messageSys" => null,
                "bin_info" => null
            ];

            if($resultCode["data"]){
                $data = array_merge($data,$resultCode["data"]);
            }

            $description = $resultCode["headerStatus"]["description"] ? $resultCode["headerStatus"]["description"] : "Connection refused";

            $response[self::TX_ID] = $data["codOper"] ? $data["codOper"] : null;
            $response[self::STATUS] = $data["status"] == self::SUCCESS ? self::SUCCESS : self::FAILURE;
            $response[self::DATE] = $data["date"] ? $data["date"] : null;
            $response[self::TOTAL_PAY] = $data["totalPay"] ? $data["totalPay"] : null;
            $response[self::REQUESTED_PAY] = $data["requestPayAmount"] ? $data["requestPayAmount"] : null;
            $response[self::BIN_INFO] = $data["bin_info"] ? $data["bin_info"] : null;
            $response[self::DISPLAY_NUM] = $data["displayNum"] ? $data["displayNum"] : null;
            $response[self::MESSAGE] = $data["messageSys"] ? $data["messageSys"] : $description;

            return $response;
        }
    }

    /**
     * Returns result code
     *
     * @return Config
     */

    private function getCurrentConfig(){
        $config = $this->config;
        return $config;
    }

    private function getTokenApi(){
        $token = $this->getCurrentConfig()->getRealMerchantToken();
        return  $token;
    }

    private function getHttpClient(){
        return $this->httpRequest;
    }

    abstract function getCurrentService();

    /**
     * Returns result code
     *
     * @param TransferInterface $transfer
     * @return Object
     */
    function processTx(TransferInterface $transfer)
    {
        $body = $transfer->getBody();
        if($this->getHttpClient() == null){
            throw new \InvalidArgumentException('Curl is required for the petition');
        }
        if(!$this->getCurrentService()){
            throw new \InvalidArgumentException('The service for processTx is required');
        }

        try {
            $this->httpRequest->setService($this->getCurrentService());
            $this->httpRequest->setSandbox($this->getCurrentConfig()->isSandbox());
            $this->httpRequest->setCredentials($this->getTokenApi());

            if(isset($body["METHOD"])){
                $this->httpRequest->setService($body["METHOD"]);
            }

            $response = $this->httpRequest->processTx($transfer->getBody());

            return $response;

        } catch (\Exception $ex){

            $this->logger->debug([
                    'error' => $ex->getMessage()
            ]);

            throw new \InvalidArgumentException("PagueloFacil Request Exception - Service: ", $this->getCurrentService() , $ex->getMessage());
        }
    }
}
