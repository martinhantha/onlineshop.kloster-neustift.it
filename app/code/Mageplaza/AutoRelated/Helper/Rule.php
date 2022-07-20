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

namespace Mageplaza\AutoRelated\Helper;

use Magento\Bundle\Model\ResourceModel\Selection;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\AutoRelated\Model\Config\Source\Direction;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule\Collection;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule\CollectionFactory;
use Mageplaza\AutoRelated\Model\Rule as AutoRelatedRule;
use Zend_Db_Expr;
use Mageplaza\AutoRelated\Model\Config\Source\Type;

/**
 * Class Rule
 * @package Mageplaza\AutoRelated\Helper
 */
class Rule extends Data
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Configurable
     */
    protected $configurableType;

    /**
     * @var Grouped
     */
    protected $groupedType;

    /**
     * @var Selection
     */
    protected $bundleSelection;

    /**
     * Rule constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param Session $customerSession
     * @param DateTime $dateTime
     * @param CollectionFactory $collectionFactory
     * @param Configurable $configurableType
     * @param Grouped $groupedType
     * @param Selection $bundleSelection
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Registry $registry,
        Session $customerSession,
        DateTime $dateTime,
        CollectionFactory $collectionFactory,
        Configurable $configurableType,
        Grouped $groupedType,
        Selection $bundleSelection
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dateTime          = $dateTime;
        $this->configurableType  = $configurableType;
        $this->groupedType       = $groupedType;
        $this->bundleSelection   = $bundleSelection;

        parent::__construct($context, $objectManager, $storeManager, $registry, $customerSession);
    }

    /**
     * @return bool
     */
    public function isEnableArpBlock()
    {
        if (!$this->getData('arp_enable')) {
            $enable = false;
            if ($this->isEnabled()) {
                switch ($this->_request->getFullActionName()) {
                    case 'catalog_product_view':
                        $this->setData('type', Type::TYPE_PAGE_PRODUCT);
                        $product = $this->registry->registry('current_product');
                        $this->setData('entity_id', $product ? $product->getId() : '');
                        $enable = $product ? !$product->getMpDisableAutoRelated() : false;
                        break;
                    case 'catalog_category_view':
                        $this->setData('type', Type::TYPE_PAGE_CATEGORY);
                        $category = $this->registry->registry('current_category');
                        $this->setData('entity_id', $category ? $category->getId() : '');
                        $enable = true;
                        break;
                    case 'checkout_cart_index':
                        $this->setData('type', Type::TYPE_PAGE_SHOPPING);
                        $enable = true;
                        break;
                    case 'onestepcheckout_index_index':
                        $this->setData('type', Type::TYPE_PAGE_OSC);
                        $enable = true;
                        break;
                    case 'checkout_onepage_success':
                        $this->setData('type', Type::TYPE_PAGE_CHECKOUT_SUCCESS);
                        $enable = true;
                        break;
                    case 'cms_page_view':
                    case 'cms_index_index':
                        $this->setData('type', Type::CMS_PAGE);
                        $enable = true;
                        break;
                }
            }

            $this->setData('arp_enable', $enable);
        }

        return $this->getData('arp_enable');
    }

    /**
     * @param string $mode
     *
     * @return array|null
     */
    public function getActiveRulesByMode($mode)
    {
        if (!$this->getData('rule_mode_' . $mode)) {
            $rules = [];
            foreach ($this->getActiveRules() as $rule) {
                if ($rule->getDisplayMode() === $mode) {
                    $rules[] = $rule;
                }
            }

            $this->setData('rule_mode_' . $mode, $rules);
        }

        return $this->getData('rule_mode_' . $mode);
    }

    /**
     * @return Collection
     */
    public function getActiveRules()
    {
        if (!$this->getData('active_rules')) {
            /** @var Collection $ruleCollections */
            $ruleCollections = $this->collectionFactory->create();
            $ruleCollections->addActiveFilter($this->getCustomerGroup(), $this->getCurrentStore())
                ->addDateFilter($this->dateTime->date('Y-m-d'))
                ->addTypeFilter($this->getData('type'))
                ->addLocationFilter(['nin' => ['custom', 'cms-page']]);

            $this->setData('active_rules', $ruleCollections);
        }

        return $this->getData('active_rules');
    }

    /**
     * Retrieve custom rules
     *
     * @return Collection
     */
    public function getCustomRules()
    {
        if (!$this->getData('custom_rules')) {
            /** @var Collection $ruleCollections */
            $ruleCollections = $this->collectionFactory->create();
            $ruleCollections->addActiveFilter($this->getCustomerGroup(), $this->getCurrentStore())
                ->addDateFilter($this->dateTime->date())
                ->addTypeFilter($this->getData('type'))
                ->addLocationFilter(['in' => ['custom', 'cms-page']]);

            $this->setData('custom_rules', $ruleCollections);
        }

        return $this->getData('custom_rules');
    }

    /**
     * @param AutoRelatedRule $rule
     * @param CatalogCollection $collection
     *
     * @return CatalogCollection
     */
    public function sortProduct(AutoRelatedRule $rule, CatalogCollection $collection)
    {
        switch ($rule->getSortOrderDirection()) {
            case Direction::BESTSELLER:
                $productIds = [];
                $collection->getSelect()->joinLeft(
                    ['soi' => $collection->getTable('sales_bestsellers_aggregated_yearly')],
                    'e.entity_id = soi.product_id',
                    ['qty_ordered' => 'SUM(soi.qty_ordered)']
                )
                    ->group('e.entity_id')
                    ->order('qty_ordered DESC');
                /** @var Product $product */
                foreach ($collection->getItems() as $product) {
                    if (in_array($product->getId(), $productIds, true)) {
                        continue;
                    }
                    $parentId = $this->getFirstParentId($product);
                    if ($parentId) {
                        $productIds[] = $parentId;
                    } elseif ($product->getData('visibility') != 1) {
                        $productIds[] = $product->getId();
                    }
                }

                $collection = $rule->getProductCollectionVisibility();
                $collection->getSelect()->where('e.entity_id IN (?)', $productIds);
                $collection->getSelect()->reset(Select::ORDER);
                $collection->getSelect()
                    ->order(new Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $productIds) . ')'));

                break;
            case Direction::PRICE_LOW:
                $collection->setVisibility([
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH
                ]);
                $collection->getSelect()->order('final_price ASC');
                break;
            case Direction::PRICE_HIGH:
                $collection->setVisibility([
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH
                ]);
                $collection->getSelect()->order('final_price DESC');
                break;
            case Direction::NEWEST:
                $collection->setVisibility([
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH
                ]);
                $collection->getSelect()->order('e.created_at DESC');
                break;
            default:
                $collection->setVisibility([
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH
                ]);
                $collection->getSelect()->order('rand()');
                break;
        }

        return $collection;
    }

    /**
     * @param Product $product
     *
     * @return string|null
     */
    private function getFirstParentId($product)
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (!empty($configurableProducts)) {
            return array_shift($configurableProducts);
        }

        $groupedProducts = $this->groupedType->getParentIdsByChild($product->getId());
        if (!empty($groupedProducts)) {
            return array_shift($groupedProducts);
        }

        $bundleProducts = $this->bundleSelection->getParentIdsByChild($product->getId());
        if (!empty($bundleProducts)) {
            return array_shift($bundleProducts);
        }

        return null;
    }
}
