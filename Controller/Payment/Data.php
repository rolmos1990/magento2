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
use Paguelofacil\Gateway\Utils\PaguelofacilServices;

class Data extends \Magento\Framework\App\Action\Action
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
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
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

    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function getConfig()
    {
        return $this->scopeConfig;
    }

    public function execute()
    {
        try {
            $order = $this->_getCheckoutSession()->getLastRealOrder();
            if (!isset($order)) {
                throw new \InvalidArgumentException('Order data object should be provided');
            }

            /** @var PaymentDataObjectInterface $payment */
            $payment = $order->getPayment();
            $amountToPay = $order->getBaseGrandTotal();

            if (!isset($payment)) {
                throw new \InvalidArgumentException('Payment data object should be provided');
            }

            $order = $payment->getOrder();
            //check enviroments
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->_paymentHelper->getMethodInstance($method);

            $client = new PaguelofacilServices();
            $client->setSandbox($methodInstance->isSandbox($order->getStoreId()));

            $payment->setTransactionId(uniqid("pf_"))->setIsTransactionClosed(0);

            $orderTransactionId = $payment->getTransactionId();

            $payment->setParentTransactionId($order->getId());
            $payment->setIsTransactionPending(true);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->build(Transaction::TYPE_ORDER);

            $payment->addTransactionCommentsToOrder($transaction, "Pending for Payment...");

            $status = $methodInstance->getOrderStatus($order->getStoreId());
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order->setState($state)->setStatus($status);
            $payment->setSkipOrderProcessing(true);
            $url = $client->getLinkPayment([
                "CCLW" => $methodInstance->getRealMerchantCCLW($order->getStoreId()),
                "CMTN" => number_format($amountToPay,2),
                "CDSC" => "MG2 - LP - " . $order->getId(),
                "orderId" => $order->getId(),
                "transactionId" => $orderTransactionId,
                "RETURN_URL" => bin2hex($this->_url->getUrl("paguelofacil/payment/processingtx"))
            ]);

            $this->getResponse()->setRedirect($url);

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Payment Data invalid' . $e->getMessage());
        }
    }
}
