<?php
/**
 * @category    Paguelofacil
 * @package     Paguelofacil_Gateway
 * @copyright   Paguelofacil (http://paguelofacil.com)
 */

namespace Paguelofacil\Gateway\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC');
    }
}
