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

class Processingtx extends \Magento\Framework\App\Action\Action
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

    public function execute()
    {
        $id = $this->getRequest()->getParam("transactionId");
        $totalPagado = $this->getRequest()->getParam("TotalPagado") ? $this->getRequest()->getParam("TotalPagado") : $this->getRequest()->getParam("TotalPay");
        $amount = $this->getRequest()->getParam("CMTN");
        $estado = $this->getRequest()->getParam("Estado");
        $razon = $this->getRequest()->getParam("Razon");
        $operCode = $this->getRequest()->getParam("Oper") ? $this->getRequest()->getParam("Oper") : $this->getRequest()->getParam("CodOper");
        $order = $this->getRequest()->getParam("orderId");


        $isPagoCash = ($operCode && substr( $operCode, 0, 2 ) == "PP");

        if($isPagoCash && floatval($totalPagado) > 0){
            /** Se agrega funcion de PagoCash Aprobado */
            return $this->resultRedirectFactory->create()->setPath('paguelofacil/payment/updateorder', ['_current' => true, 'TotalPay' => $totalPagado, 'CodOper' => $operCode]);
        }


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order_model = $objectManager->get('Magento\Sales\Model\Order');
        $order = $order_model->load($order);

        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);

        if($estado == 'Pendiente'){
            /** Pending payments */
            $payment = $order->getPayment();
            $payment->setIsTransactionClosed(0);
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionApproved(false);
            $payment->setSkipOrderProcessing(true);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($operCode)
                ->build(Transaction::TYPE_AUTH);


            $payment->addTransactionCommentsToOrder($transaction, "Paguelofacil Payment Pending");

            $status = "pending";
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order->setState($state)->setStatus($status);

            $order->save();

            $checkoutSession = $this->_getCheckoutSession();
            $allItems = $checkoutSession->getQuote()->getAllVisibleItems();
            foreach ($allItems as $item)
            {
                $cartItemId = $item->getItemId();
                $itemObj=$this->getItemModel()->load($cartItemId);
                $itemObj->delete();
            }

            $this->_redirect('checkout/onepage/success');
            return;
        }
        else if($totalPagado <= 0 or $estado != "Aprobada"){
            $this->messageManager->addError("Pago declinado, motivo: " . $razon);
            $this->_getCheckoutSession()->restoreQuote();
            $this->_redirect('checkout/cart');
            return;
        } else {

            $payment = $order->getPayment();
            $payment->setIsTransactionClosed(1);

            $orderTransactionId = $payment->getTransactionId();
            $payment->setParentTransactionId($order->getId());
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionApproved(true);


            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($operCode)
                ->build(Transaction::TYPE_CAPTURE);
            $payment->addTransactionCommentsToOrder($transaction, "Paguelofacil Payment Received");

            $status = "processing";
            $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $order->setState($state)->setStatus($status);
            $payment->setSkipOrderProcessing(true);

            $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
            $invoice = $invoice->setTransactionId($payment->getTransactionId())
                ->addComment("Invoice created.")
                ->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);

            $invoice->setGrandTotal($totalPagado);
            $invoice->setBaseGrandTotal($totalPagado);

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

            $checkoutSession = $this->_getCheckoutSession();
            $allItems = $checkoutSession->getQuote()->getAllVisibleItems();
            foreach ($allItems as $item) {
                $cartItemId = $item->getItemId();
                $itemObj = $this->getItemModel()->load($cartItemId);
                $itemObj->delete();
            }

            $this->_redirect('checkout/onepage/success');
        }
    }
}
