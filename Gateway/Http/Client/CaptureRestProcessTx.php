<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

class CaptureRestProcessTx extends RestProcessTx
{
    const CAPTURE = "CAPTURE";

    function getCurrentService(){
        return self::CAPTURE;
    }
}
