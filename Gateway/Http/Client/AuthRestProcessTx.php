<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Http\Client;

class AuthRestProcessTx extends RestProcessTx
{
    const AUTH = "AUTH";

    function getCurrentService(){
        return self::AUTH;
    }
}
