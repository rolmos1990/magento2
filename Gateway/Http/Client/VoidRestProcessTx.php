<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

class VoidRestProcessTx extends RestProcessTx
{
    const REVERSE_AUTH = "REVERSE_AUTH";

    function getCurrentService(){
        return self::REVERSE_AUTH;
    }
}
