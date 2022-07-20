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

use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\ProductFactory;
use Magento\Rule\Model\Condition\Context;

/**
 * Class SimilarityCombine
 * @package Mageplaza\AutoRelated\Model\CatalogRule\Condition
 */
class SimilarityCombine extends Combine
{
    /**
     * SimilarityCombine constructor.
     *
     * @param Context $context
     * @param ProductFactory $conditionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductFactory $conditionFactory,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $data);

        $this->setSimilarityActions([]);
    }

    /**
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $productAttributes = $this->_productFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($productAttributes as $code => $label) {
            $attributes[] = [
                'value' => 'Mageplaza\AutoRelated\Model\CatalogRule\Condition\CatalogRuleProduct|' . $code,
                'label' => $label,
            ];
        }
        $conditions = [['value' => '', 'label' => __('Please choose a condition to add.')]];
        $conditions = array_merge_recursive(
            $conditions,
            [
                ['label' => __('Product Attribute'), 'value' => $attributes]
            ]
        );

        return $conditions;
    }
}
