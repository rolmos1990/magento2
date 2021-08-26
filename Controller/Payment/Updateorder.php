<?php
/**
 * Copyright � 2021 Paguelofacil d.o.o.
 * created by Ramon Olmos(ramon.olmos90@gmail.com)
 */

namespace Paguelofacil\Gateway\Controller\Payment;

use Magento\Framework\App\ResponseInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;


class Updateorder extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;
    /**
     * @var Transaction\BuilderInterface
     */
    protected $transactionBuilder;


    protected $resultJsonFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;

    }

    /**
     * Loads a specified transaction by id
     *
     * @param int $transactionId
     * @return TransactionInterface|null
     */
    public function get($transactionId)
    {

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('txn_id')
                    ->setValue($transactionId)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $transaction = $this->transactionRepository->getList($searchCriteria)->getLastItem();

        try {
            return $transaction->getOrder();
        } catch (NoSuchEntityException $exception) {
            $this->logger->critical($exception->getMessage());
            return null;
        }
    }

    public function execute()
    {
        try {
            $totalPagado = $this->getRequest()->getParam("TotalPay") ? $this->getRequest()->getParam("TotalPay") : $this->getRequest()->getParam("TotalPagado");
            $operCode = $this->getRequest()->getParam("CodOper");

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $order = $this->get($operCode);

            if(!$order){
                echo "e:nfound";
                die();
            }
            $sumTotalPagado = $order->getTotalPaid() ? $order->getTotalPaid() : 0;
            $isFullPayment = ($sumTotalPagado + $totalPagado) >= $order->getBaseGrandTotal();

            /** Suma de total Pagado */
            if($isFullPayment) {

                /** FullPayment */
                $payment = $order->getPayment();
                $payment->setIsTransactionClosed(1);
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionApproved(true);
                $payment->setSkipOrderProcessing(true);

            } else {

                /** Partial Payment */
                $payment = $order->getPayment();
                $payment->setIsTransactionClosed(0);
                $payment->setIsTransactionPending(true);
                $payment->setIsTransactionApproved(true);
                $payment->setSkipOrderProcessing(false);

            }

            if($isFullPayment) {

                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($operCode)
                    ->build(Transaction::TYPE_CAPTURE);

                $payment->addTransactionCommentsToOrder($transaction, "Paguelofacil Payment completed");

                /** first payment applied */
                $status = "processing";
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($state)->setStatus($status);

                $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
                $invoice = $invoice->setTransactionId($payment->getTransactionId())
                    ->addComment("Invoice created.")
                    ->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);

                $invoice->setGrandTotal($sumTotalPagado + $totalPagado);
                $invoice->setBaseGrandTotal($sumTotalPagado + $totalPagado);

                $invoice->register()
                    ->pay();
                $invoice->save();

                // Save the invoice to the order
                $transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transaction->save();

                $order->addStatusHistoryComment(
                    __('Invoice #%1.', $invoice->getId())
                )
                    ->setIsCustomerNotified(true);

                $order->save();

                /** fix total payment */
                $order->setTotalPaid($sumTotalPagado + $totalPagado);
                $order->save();


            } else {

                $order->setTotalPaid($sumTotalPagado + $totalPagado);

                /** partial payment applied */
                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($operCode)
                    ->build(Transaction::TYPE_CAPTURE);

                $payment->addTransactionCommentsToOrder($transaction, "Paguelofacil Partial Payment");
                $transaction->save();
                $order->save();

            }


            echo "e:success";
            die();
        }catch(\Exception $ex){
            echo "e:failed";
            die();
        }

    }
}
