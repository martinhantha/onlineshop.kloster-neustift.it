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

namespace Mageplaza\AutoRelated\Plugin\Model\Condition;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Product
 * @package Mageplaza\AutoRelated\Plugin\Model\Condition
 */
class Product
{
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Product constructor.
     *
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param ProductCondition $subject
     * @param AbstractModel $model
     *
     * @return array
     */
    public function beforeValidate(ProductCondition $subject, AbstractModel $model)
    {
        /* Compatible with mp_is_in_stock attribute of Mageplaza Product Feed */
        $attrCode = $subject->getAttribute();
        if ($attrCode === 'mp_is_in_stock') {
            $stockItem = $this->stockRegistry->getStockItem($model->getId());
            $model->setData($attrCode, (int) $stockItem->getIsInStock());
        }

        return [$model];
    }
}
