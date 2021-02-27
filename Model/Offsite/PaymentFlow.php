<?php


namespace Paguelofacil\Gateway\Model\Offsite;
use Paguelofacil\Gateway\Gateway\Config;

class PaymentFlow extends \Magento\Payment\Model\Method\AbstractMethod
{

    const CODE = 'paguelofacil_offsite';

    const STORAGE_PATH = 'payment/'.self::CODE;

    protected $_code = self::CODE;

    protected $_isGateway = true;

    protected $_isOffline = true;

    protected $_canOrder = true;

    protected $_canRefund = false;

    protected $_canAuthorize = false;

    protected $_canCancelInvoice = false;

    protected $_canCapture = true;

    const BOOLEAN_TRUE = 1;
    const BOOLEAN_FALSE = 0;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param DirectoryHelper $directory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Directory\Helper\Data $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );

        $this->_scopeConfig =  $scopeConfig;
    }

    public function getStorageValue($field, $storeId){

        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        return $this->_scopeConfig->getValue(self::STORAGE_PATH .'/'. $field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Gets is enviroment is sandbox.
     *
     * Possible values: true or false
     *
     * @param int|null $storeId
     * @return boolean
     */
    public function isSandbox($storeId = null)
    {
        return $this->getStorageValue(Config::KEY_SANDBOX, $storeId) == self::BOOLEAN_TRUE;
    }

    /**
     * Gets active.
     *
     * @param int|null $storeId
     * @return boolean
     */
    public function isActive($storeId = NULL)
    {
        return $this->getStorageValue(Config::KEY_ACTIVE, $storeId) == self::BOOLEAN_TRUE;
    }

    /**
     * Gets cclw for sandbox.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantCclwSandbox($storeId = null)
    {
        return $this->getStorageValue(Config::KEY_MERCHANT_CCLW_SANDBOX, $storeId);
    }

    /**
     * Gets cclw for live.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantCclw($storeId = null)
    {
        return $this->getStorageValue(Config::KEY_MERCHANT_CCLW, $storeId);
    }

    /**
     * Gets real merchant token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRealMerchantCclw($storeId = null)
    {
        if($this->isSandbox($storeId)) {
            return $this->getMerchantCclwSandbox($storeId);
        }

        return $this->getMerchantCclw($storeId);
    }

    /**
     * Gets real merchant token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderStatus($storeId = null)
    {
        return $this->getStorageValue(Config::KEY_ORDER_STATUS, $storeId);
    }
}
