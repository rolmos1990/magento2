<?php
/**
 * Copyright © 2021 PagueloFacil. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Gateway\Validator;

use http\Exception\InvalidArgumentException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Paguelofacil\Gateway\Gateway\Http\Client\RestProcessTx;

class ResponseCodeValidator extends AbstractValidator
{
    const STATUS = 'STATUS';

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException(__("Payment response not received"));
        }

        $response = $validationSubject['response'];
        $message = $response[RestProcessTx::MESSAGE];

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                [__($message)]
            );
        }
    }

    /**
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        return isset($response[self::STATUS])  && $response[self::STATUS] == RestProcessTx::SUCCESS;
    }
}
