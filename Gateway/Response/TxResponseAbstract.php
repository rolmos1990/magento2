<?php
/**
 * Copyright © 2021 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Response;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
/*use Paguelofacil\Gateway\Services\OrderUpdaterService;*/

abstract class TxResponseAbstract implements HandlerInterface
{
    const TX_ID = 'TX_ID';
    const MESSAGE = 'MESSAGE';
    const STATUS = 'STATUS';

    const SUCCESS = 1;
    const FAILED = 0;

    /**
     * Transaction is Closed
     *
     * @return Boolean
     */

    abstract function isClosed();

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        if(!$response[self::TX_ID] or ($response[self::STATUS] != self::SUCCESS)){
            $message = $response[self::MESSAGE];
            throw new CouldNotSaveException(__($message));
        }

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::TX_ID]);
        $payment->setIsTransactionClosed($this->isClosed());
    }
}
