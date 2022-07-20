<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AutoRelated
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\AutoRelated\Model\CatalogRule\Condition;

use Magento\CatalogRule\Model\Rule\Condition\Product;
use Mageplaza\AutoRelated\Block\Adminhtml\Rule\Editable;

/**
 * Class CatalogRuleProduct
 * @package Mageplaza\AutoRelated\Model\CatalogRule\Condition
 */
class CatalogRuleProduct extends Product
{
    /**
     * Load operator options
     *
     * @return $this
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            [
                '==' => __('is'),
                '!=' => __('is not'),
            ]
        );

        return $this;
    }

    /**
     * Get value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Get this condition as html.
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() .
            $this->getAttributeElementHtml() .
            $this->getOperatorElementHtml() .
            $this->getValueElementHtml() .
            $this->getAdditionalHtml() .
            $this->getRemoveLinkHtml();
    }

    /**
     * @return string
     */
    public function getAdditionalHtml()
    {
        return __('same as Product ') . $this->getAttributeName() . __(' on Product Page');
    }

    /**
     * @return array|mixed|string|null
     */
    public function getValueName()
    {
        $value = $this->getValue();

        if ($value === null || '' === $value) {
            return $value;
        }

        return parent::getValueName();
    }

    /**
     * Get value element renderer.
     *
     * @return Editable
     */
    public function getValueElementRenderer()
    {
        if (strpos($this->getValueElementType(), '/') !== false) {
            return $this->_layout->getBlockSingleton($this->getValueElementType());
        }

        return $this->_layout->getBlockSingleton(Editable::class);
    }
}
