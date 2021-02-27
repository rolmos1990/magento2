<?php

namespace Paguelofacil\Gateway\Utils;

class PaguelofacilServices {

    //Enviroment
    const PRODUCTION_ENVIROMENT         = "https://secure.paguelofacil.com";
    const SANDBOX_ENVIROMENT            = "https://sandbox.paguelofacil.com";
    const PROCESS_TX                    = "/rest/processTx/";
    const LINK_PAYMENT                  = "/LinkDeamon.cfm";
    const AUTH_SERVICE                  = "AUTH";
    const CAPTURE_SERVICE               = "CAPTURE";
    const AUTH_CAPTURE_SERVICE          = "AUTH_CAPTURE";
    const REVERSE_AUTH                  = "REVERSE_AUTH";
    const REVERSE_CAPTURE               = "REVERSE_CAPTURE";
    const OFFSITE                       = "OFFSITE";

    //Service availables
    const SERVICE_AVAILABLES = [ self::AUTH_SERVICE, self::CAPTURE_SERVICE, self::AUTH_CAPTURE_SERVICE, self::REVERSE_AUTH, self::REVERSE_CAPTURE];

    private $service = self::AUTH_SERVICE;

    private $headers = [
        "Content-type:application/json"
    ];

    private $sandbox = false;

    /**
     * Set Current Service
     * @param String $service
     * @return void
     */

    function setService($service){
        if(!in_array($service,self::SERVICE_AVAILABLES)){
         $this->service = null;
         return new \InvalidArgumentException("service - PagueloFacil Services not found");
        }
        $this->service = $service;
    }

    /**
     * Get Current Service
     * @return String
     */
    function getService(){
        return $this->service;
    }

    /**
     * Modify sandbox enviroment
     * @param Boolean $sandbox
     * @return void
     */

    function setSandbox($sandbox){
        $this->sandbox = $sandbox;
    }

    /**
     * Check is sandbox enviroment
     * @return Boolean
     */
    function isSandbox(){
        return $this->sandbox;
    }

    /**
     * Add credentials
     * @param String $apiToken
     * @return void
     */
    function setCredentials($apiToken){
        $this->headers[] = "Authorization:".$apiToken;
    }

    /**
     * Returns credentials
     *
     * @return String
     */
    function getCredentials(){
        return $this->headers[1];
    }

    /**
     * Returns result code
     *
     * @param array $params
     * @return Boolean
     */
    function hasCCLW($params){
        if(is_array($params)) {
            $params = array_change_key_case($params, CASE_UPPER);
            if ($params["CCLW"] == null or $params["CCLW"] == "") {
                return false;
            }
            else{
                return true;
            }
        }
        return false;
    }
    /**
     * Returns the full url enviroment for the service
     *
     * @return String
     */
    function getFullUri(){
        return self::PRODUCTION_ENVIROMENT . self::PROCESS_TX . $this->service;
    }

    /**
     * Returns result code
     *
     * @param array $params
     * @return Object
     */
    function processTx($params, $forceSandbox = null){
        //validate CCLW
        if(!$this->hasCCLW($params)){
            throw new \InvalidArgumentException("Paguelofacil ProcessTx - CCLW is Required");
        }

        $jsonData = (is_array($params) ? json_encode($params) : $params);
        $isSandbox = $forceSandbox !== null ? $forceSandbox : $this->sandbox;
        $serviceURL = ($isSandbox) ? self::SANDBOX_ENVIROMENT . self::PROCESS_TX . $this->getService() : $this->getFullUri();

        if(!$this->getCredentials()){
            throw new \InvalidArgumentException("Paguelofacil ProcessTx - Authorization Token is Required");
        }

        $ch = curl_init($serviceURL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        try {
            $arresult = json_decode($response, true);
            return $arresult;
        }catch(Exception $ex){
            throw new \InvalidArgumentException("Connection error: " , $ex->getMessage());
        } finally{
            curl_close($ch);
        }
    }

    /**
     * Returns result code
     *
     * @param url $params
     * @return String
     */
    public function getLinkPayment($params, $forceSandbox = null) {

        if(!$this->hasCCLW($params)){
            throw new \InvalidArgumentException("Paguelofacil ProcessTx - CCLW is Required");
        }

        $isSandbox = $forceSandbox !== null ? $forceSandbox : $this->sandbox;
        $params = http_build_query($params);
        $serviceURL = ($isSandbox) ? self::SANDBOX_ENVIROMENT . self::LINK_PAYMENT : self::PRODUCTION_ENVIROMENT . self::LINK_PAYMENT;
        $serviceURL = $serviceURL . "?". $params;
        return $serviceURL;
    }
}
