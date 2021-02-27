<?php

/**
 * Copyright © PagueloFacil 2021, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Paguelofacil\Gateway\Gateway\Command;

use http\Exception\InvalidArgumentException;
use Magento\Braintree\Model\Adapter\BraintreeSearchAdapter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Payment\Gateway\Helper;

/**
 * Class CaptureStrategyCommand
 */
class CaptureStrategyCommand implements CommandInterface
{
    /**
     * PagueloFacil authorize and capture command
     */
    const SALE = 'sale';

    /**
     * PagueloFacil capture command
     */
    const CAPTURE = 'settlement';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param CommandPoolInterface $commandPool
     * @param TransactionRepositoryInterface $repository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        TransactionRepositoryInterface $repository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->commandPool = $commandPool;
        $this->transactionRepository = $repository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->readPayment($commandSubject);

        $command = $this->getCommand($paymentDO);

        $this->commandPool->get($command)->execute($commandSubject);
    }

    /**
     * Get execution command name.
     *
     * @param PaymentDataObjectInterface $paymentDO
     * @return string
     */
    private function getCommand(PaymentDataObjectInterface $paymentDO)
    {
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        // if auth transaction does not exist then execute authorize&capture command
        $existsCapture = $this->isExistsCaptureTransaction($payment);
        if (!$payment->getAuthorizationTransaction() && !$existsCapture) {
            return self::SALE;
        }

        return self::CAPTURE;
    }

    /**
     * Check if capture transaction already exists
     *
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    private function isExistsCaptureTransaction(OrderPaymentInterface $payment)
    {
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('payment_id')
                    ->setValue($payment->getId())
                    ->create(),
            ]
        );

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('txn_type')
                    ->setValue(TransactionInterface::TYPE_CAPTURE)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();
        return (boolean)$count;
    }
}
