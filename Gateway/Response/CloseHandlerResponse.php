<?php
/**
 * Copyright © 2021 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Response;

class CloseHandlerResponse extends TxResponseAbstract
{
    /* is the transaction is final status */
    const IS_CLOSED = true;

    function isClosed()
    {
        return self::IS_CLOSED;
    }
}
