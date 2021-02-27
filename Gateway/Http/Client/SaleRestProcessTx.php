<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

class SaleRestProcessTx extends RestProcessTx
{
    const AUTH_CAPTURE = "AUTH_CAPTURE";

    function getCurrentService(){
        return self::AUTH_CAPTURE;
    }
}
