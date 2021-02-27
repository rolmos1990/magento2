<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

class RefundRestProcessTx extends RestProcessTx
{
    const REVERSE_CAPTURE = "REVERSE_CAPTURE";

    function getCurrentService(){
        return self::REVERSE_CAPTURE;
    }
}
