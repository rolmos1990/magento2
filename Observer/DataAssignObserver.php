<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paguelofacil\Gateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const CC_TYPE = "cc_type";
    const CREDITCARD_NUMBER = "cc_number";
    const CC_VERIFICATION = "cc_cid";
    const CC_OWNER = "cc_ownerx";
    const CC_MONTH_EXPIRY = "cc_exp_month";
    const CC_YEAR_EXPIRY = "cc_exp_year";


    protected $additionalInformationList = [
        self::CC_TYPE,
        self::CREDITCARD_NUMBER,
        self::CC_VERIFICATION,
        self::CC_OWNER,
        self::CC_MONTH_EXPIRY,
        self::CC_YEAR_EXPIRY
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);


        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }

    }
}
