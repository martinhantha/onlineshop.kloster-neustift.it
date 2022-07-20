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

namespace Mageplaza\AutoRelated\Model;

use DateTime;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\CatalogRule\Model\Data\Condition\Converter;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Rule\Model\Action\Collection as RuleActionCollection;
use Magento\Framework\App\ObjectManager;
use Magento\SalesRule\Model\Rule\Condition\Combine as SalesRuleCombine;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory as CatalogRuleCombineFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;
use Magento\SalesRule\Model\Rule\Condition\CombineFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\AutoRelated\Api\Data\AutoRelatedInterface;
use Mageplaza\AutoRelated\Model\Config\Source\Type;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPageRule as CmsPageRuleResource;
use Mageplaza\AutoRelated\Model\CatalogRule\Condition\SimilarityCombineFactory;
use Mageplaza\AutoRelated\Helper\Data;
use Mageplaza\AutoRelated\Helper\Rule as HelperRule;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttributeRepository;
use Mageplaza\AutoRelated\Model\ResourceModel\RuleFactory;

/**
 * Class Rule
 * @package Mageplaza\AutoRelated\Model
 * @method getToken()
 * @method setToken(string $createToken)
 * @method getApplySimilarity()
 * @method getSimilarityActionsSerialized()
 * @method hasSimilarityActionsSerialized()
 * @method setSimilarityActionsSerialized(bool|string $serialize)
 */
class Rule extends AbstractModel implements AutoRelatedInterface
{
    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $productIds;

    /**
     * Store matched product Ids in condition tab
     *
     * @var array
     */
    protected $productConditionsIds;

    /**
     * Store matched product Ids with rule id
     *
     * @var array
     */
    protected $dataProductIds;

    /**
     * @var Iterator
     */
    protected $resourceIterator;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var Status
     */
    protected $productStatus;

    /**
     * @var CatalogRuleCombineFactory
     */
    protected $_productCombineFactory;

    /**
     * @var CombineFactory
     */
    protected $_salesCombineFactory;

    /**
     * @var HelperRule
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Converter
     */
    protected $ruleConditionConverter;

    /**
     * @var ProductInterface[]
     */
    protected $matchProducts;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CmsPageRuleResource
     */
    protected $cmsPageRuleResource;

    /**
     * Store rule actions model
     *
     * @var RuleActionCollection
     */
    protected $_similarityActions;

    /**
     * @var SimilarityCombineFactory
     */
    protected $similarityCombineFactory;

    /**
     * @var ProductAttributeRepository
     */
    protected $productAttributeRepository;

    /**
     * @var RuleFactory
     */
    protected $resourceModelRuleFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Rule constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param Status $productStatus
     * @param Visibility $productVisibility
     * @param ProductFactory $productFactory
     * @param CatalogRuleCombineFactory $catalogCombineFactory
     * @param CombineFactory $salesCombineFactory
     * @param Iterator $resourceIterator
     * @param HelperRule $helper
     * @param Session $checkoutSession
     * @param CmsPageRuleResource $cmsPageRuleResource
     * @param SimilarityCombineFactory $similarityCombineFactory
     * @param ProductAttributeRepository $productAttributeRepository
     * @param RuleFactory $resourceModelRuleFactory
     * @param Cart $cart
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        Status $productStatus,
        Visibility $productVisibility,
        ProductFactory $productFactory,
        CatalogRuleCombineFactory $catalogCombineFactory,
        CombineFactory $salesCombineFactory,
        Iterator $resourceIterator,
        HelperRule $helper,
        Session $checkoutSession,
        CmsPageRuleResource $cmsPageRuleResource,
        SimilarityCombineFactory $similarityCombineFactory,
        ProductAttributeRepository $productAttributeRepository,
        RuleFactory $resourceModelRuleFactory,
        Cart $cart,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry
    ) {
        $this->_productCombineFactory     = $catalogCombineFactory;
        $this->_salesCombineFactory       = $salesCombineFactory;
        $this->resourceIterator           = $resourceIterator;
        $this->productFactory             = $productFactory;
        $this->productVisibility          = $productVisibility;
        $this->productStatus              = $productStatus;
        $this->helper                     = $helper;
        $this->checkoutSession            = $checkoutSession;
        $this->cart                       = $cart;
        $this->cmsPageRuleResource        = $cmsPageRuleResource;
        $this->similarityCombineFactory   = $similarityCombineFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->resourceModelRuleFactory   = $resourceModelRuleFactory;
        $this->categoryFactory            = $categoryFactory;
        $this->storeManager               = $storeManager;
        $this->stockRegistry              = $stockRegistry;

        parent::__construct($context, $registry, $formFactory, $localeDate);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Rule::class);
        $this->setIdFieldName('rule_id');
    }

    /**
     * Get rule condition combine model instance
     *
     * @return Combine|SalesRuleCombine
     */
    public function getConditionsInstance()
    {
        $type = $this->_registry->registry('autorelated_type');
        if (in_array(
            $type,
            [Type::TYPE_PAGE_SHOPPING, Type::TYPE_PAGE_OSC, Type::TYPE_PAGE_CHECKOUT_SUCCESS],
            true
        )
        ) {
            return $this->_salesCombineFactory->create();
        }

        return $this->_productCombineFactory->create();
    }

    /**
     * Get rule condition product combine model instance
     *
     * @return Combine
     */
    public function getActionsInstance()
    {
        return $this->_productCombineFactory->create();
    }

    /**
     * @param string $formName
     *
     * @return string
     */
    public function getConditionsFieldSetId($formName = '')
    {
        return $formName . 'rule_conditions_fieldset_' . $this->getId();
    }

    /**
     * @param string $formName
     *
     * @return string
     */
    public function getActionsFieldSetId($formName = '')
    {
        return $formName . 'rule_actions_fieldset_' . $this->getId();
    }

    /**
     * @param string $formName
     *
     * @return string
     */
    public function getSimilarityActionsFieldSetId($formName = '')
    {
        return $formName . 'rule_similarity_actions_fieldset_' . $this->getId();
    }

    /**
     * @return bool
     */
    public function hasChild()
    {
        $ruleChild = $this->getChild();
        if (!empty($ruleChild)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getChild()
    {
        return $this->getResource()->getRuleData($this->getId(), 'parent_id');
    }

    /**
     * @return bool
     */
    public function hasChildActive()
    {
        $ruleChild = $this->getChild();

        return !empty($ruleChild) && $ruleChild['is_active'];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        $resource = $this->getResource();
        if ($this->getCustomerGroupIds() || $this->getStoreIds()) {
            $this->getResource()->deleteOldData($this->getId());
            if ($storeIds = $this->getStoreIds()) {
                $resource->updateStore($storeIds, $this->getId());
            }
            if ($groupIds = $this->getCustomerGroupIds()) {
                $resource->updateCustomerGroup($groupIds, $this->getId());
            }
        }

        if ($this->getBlockType() === Type::CMS_PAGE) {
            $ruleId              = $this->getId();
            $arpCmsPageRuleTable = $resource->getTable('mageplaza_autorelated_cms_page_rule');
            $resource->deleteMultipleData(
                $arpCmsPageRuleTable,
                ['rule_id = ?' => $ruleId]
            );

            if ($this->getLocation() === 'cms-page') {
                $data            = $this->getData();
                $dataArrayKeys   = array_keys($data);
                $cmsPagePosition = [];
                foreach ($dataArrayKeys as $key) {
                    if (strncmp($key, 'page_id_checkbox_', 17) === 0) {
                        $start             = strlen('page_id_checkbox_');
                        $end               = strlen($key) - $start;
                        $pageId            = substr($key, $start, $end);
                        $cmsPagePosition[] = [
                            'rule_id'  => $ruleId,
                            'page_id'  => $pageId,
                            'position' => $data['position_' . $pageId]
                        ];
                    }
                }

                if (!empty($cmsPagePosition)) {
                    $this->cmsPageRuleResource->getConnection()->insertMultiple(
                        $arpCmsPageRuleTable,
                        $cmsPagePosition
                    );
                }
            }
        }

        $this->reindex();

        return parent::afterSave();
    }

    /**
     * @return $this
     */
    public function reindex()
    {
        $this->getMatchingProductIds();
        $this->getResource()->deleteActionIndex($this->getId());
        if (!empty($this->dataProductIds) && is_array($this->dataProductIds)) {
            $this->getResource()->insertActionIndex($this->dataProductIds);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingProductIds()
    {
        if ($this->productIds === null) {
            $this->productIds = [];
            $this->setCollectedAttributes([]);

            $productCollection = $this->getProductCollection();
            $productCollection = $this->filterProductByStores($productCollection);
            $this->getActions()->collectValidatedAttributes($productCollection);

            $this->resourceIterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackValidateProduct']],
                [
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => $this->productFactory->create()
                ]
            );
        }

        return $this->productIds;
    }

    /**
     * @return Collection
     */
    public function getProductCollection()
    {
        /** @var $productCollection Collection */
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1);

        return $productCollection;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApplyProductIds()
    {
        $productIds = [];
        switch ($this->getData('block_type')) {
            case Type::TYPE_PAGE_PRODUCT:
                $product = $this->helper->getCurrentProduct();

                /* Compatible with mp_is_in_stock attribute of Mageplaza Product Feed */
                $stockItem = $this->stockRegistry->getStockItem($product->getId());
                $product->setData('mp_is_in_stock', (int) $stockItem->getIsInStock());

                if ($this->getConditions()->validate($product)) {
                    $similarityActions = Data::jsonDecode($this->getSimilarityActionsSerialized());
                    if (!$this->getApplySimilarity() || !isset($similarityActions['conditions'])) {
                        $productIds = $this->getResource()->getProductListByRuleId(
                            $this->getId(),
                            $product->getId()
                        );
                    } else {
                        /** @var Select $productListSelect */
                        $productListSelect = $this->getResource()->getProductListByRuleIdSelect(
                            $this->getId(),
                            $product->getId()
                        );
                        $productCollection = $this->getProductCollection();
                        $connection        = $productCollection->getConnection();
                        $productSelect     = $productCollection->getSelect();
                        $productSelect->joinLeft(
                            ['cat' => $productCollection->getTable('catalog_category_product')],
                            'cat.product_id = e.entity_id',
                            ['category_id']
                        );
                        $categoryInfoSql = null;
                        $filterAny       = [];
                        $actionValue     = (int) $similarityActions['value'];
                        foreach ($similarityActions['conditions'] as $condition) {
                            $attributeCode          = $condition['attribute'];
                            $attribute              = $this->productAttributeRepository->get($attributeCode);
                            $attributeData          = $product->getData($attributeCode);
                            $productAttributeValues = is_array($attributeData) ? $attributeData : [$attributeData];
                            if ($attribute->getFrontendInput() === 'multiselect' && !is_array($attributeData)) {
                                $productAttributeValues = explode(',', $attributeData);
                            }

                            $filterAll = [];
                            if ($actionValue === 1) {
                                $operatorSql = $condition['operator'] === '==' ? 'finset' : 'nfinset';
                            } else {
                                $operatorSql = $condition['operator'] === '!=' ? 'finset' : 'nfinset';
                            }

                            $categoryOperator = $operatorSql === 'finset' ? 'FIND_IN_SET' : 'NOT FIND_IN_SET';
                            if ($attributeCode !== 'category_ids') {
                                foreach ($productAttributeValues as $attributeValue) {
                                    $filter      = [
                                        'attribute'  => $attributeCode,
                                        $operatorSql => $attributeValue
                                    ];
                                    $filterAny[] = $filter;
                                    $filterAll[] = $filter;
                                }
                            } else {
                                $categoryInfoSql = $connection->quoteInto(
                                    $categoryOperator . '(cat.category_id, ?)',
                                    implode(',', $attributeData)
                                );
                            }
                            if ($similarityActions['aggregator'] === 'all') {
                                if ($attributeCode !== 'category_ids') {
                                    $productCollection->addFieldToFilter($filterAll);
                                }
                                if ($categoryInfoSql) {
                                    $productSelect->where($categoryInfoSql);
                                }
                            }
                        }
                        if ($similarityActions['aggregator'] === 'any') {
                            $productCollection->addFieldToFilter($filterAny);
                            if ($categoryInfoSql) {
                                $productSelect->orWhere($categoryInfoSql);
                            }
                        }

                        $productListSelect->joinInner(
                            ['product_collection' => $productCollection->getSelect()],
                            'indexTable.product_id = product_collection.entity_id',
                            []
                        );

                        $productListSelect->group('product_id');
                        $productIds = $this->getResource()->getProductIdsBySelect($productListSelect);
                    }
                }

                break;
            case Type::TYPE_PAGE_CATEGORY:
                if ($condition = $this->getCategoryConditionsSerialized()) {
                    try {
                        $categoryIds = $this->helper->unserialize($condition);
                        $category    = $this->helper->getCurrentCategory();
                        if (in_array($category->getId(), $categoryIds)) {
                            $productIds = $this->getResource()->getProductListByRuleId($this->getId());
                        }
                    } catch (Exception $e) {
                        $this->_logger->critical($e->getMessage());
                    }
                }
                break;
            case Type::TYPE_PAGE_SHOPPING:
            case Type::TYPE_PAGE_OSC:
            case Type::TYPE_PAGE_CHECKOUT_SUCCESS:
                $quote   = $this->checkoutSession->getQuote();
                $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
                $address->setData('total_qty', $this->cart->getSummaryCount());

                if ($this->getConditions()->validate($address)) {
                    $productIds = $this->getResource()->getProductListByRuleId($this->getId());
                }
                break;
            case Type::CMS_PAGE:
                $productIds = $this->getResource()->getProductListByRuleId($this->getId());
                break;
        }

        return $productIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingProducts()
    {
        if (!$this->loaded && !$this->getData(self::MATCH_PRODUCTS)) {
            $productIds = $this->getResource()->getProductListByRuleId($this->getId());
            if (empty($productIds)) {
                return [];
            }
            $productCollection = $this->getProductCollection();
            $productCollection = $this->filterProductByStores($productCollection);
            $productCollection->addIdFilter($productIds);
            $this->setData(self::MATCH_PRODUCTS, $productCollection->getItems());
        }

        return $this->getData(self::MATCH_PRODUCTS);
    }

    /**
     * {@inheritdoc}
     */
    public function setMatchingProducts($products)
    {
        $this->setData(self::MATCH_PRODUCTS, $products);
        $this->loaded = true;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getMatchingProductIdsByCondition()
    {
        if ($this->productConditionsIds === null) {
            $this->productConditionsIds = [];
            $this->setCollectedAttributes([]);

            $productCollection = $this->getProductCollection();
            $productCollection = $this->filterProductByStores($productCollection);
            $this->getConditions()->collectValidatedAttributes($productCollection);

            $this->resourceIterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackValidateProductConditions']],
                [
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => $this->productFactory->create()
                ]
            );
        }

        return $this->productConditionsIds;
    }

    /**
     * @param Collection $productCollection
     *
     * @return mixed
     */
    public function filterProductByStores($productCollection)
    {
        $storeIds    = $this->resourceModelRuleFactory->create()->getStoresByRuleId($this->getId());
        $categoryIds = [];
        foreach ($storeIds as $storeId) {
            $categoryIds[] = $this->getCategoryIds($storeId);
        }

        if (!in_array('0', $storeIds, true) && $categoryIds) {
            $productCollection->addCategoriesFilter(['in' => $categoryIds]);
        }

        return $productCollection;
    }

    /**
     * @param int $storeId
     *
     * @return array|string|null
     */
    public function getCategoryIds($storeId)
    {
        try {
            $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
            $rootCategory   = $this->categoryFactory->create()->load($rootCategoryId);

            return $rootCategory->getAllChildren(true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return Collection
     */
    public function getProductCollectionVisibility()
    {
        /** @var $productCollection Collection */
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addAttributeToSelect('*')
            ->setVisibility([
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH
            ])
            ->addAttributeToFilter('status', 1);

        return $productCollection;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     *
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        $ruleId = $this->getRuleId();
        if ($ruleId && $this->getActions()->validate($product)) {
            $this->productIds[]     = $product->getId();
            $this->dataProductIds[] = ['rule_id' => $ruleId, 'product_id' => $product->getId()];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleId()
    {
        return $this->getData(self::RULE_ID);
    }

    /**
     * Callback function for product matching (conditions)
     *
     * @param array $args
     *
     * @return void
     */
    public function callbackValidateProductConditions($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        $ruleId = $this->getRuleId();
        if ($ruleId && $this->getConditions()->validate($product)) {
            $this->productConditionsIds[] = $product->getId();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRuleId($value)
    {
        return $this->setData(self::RULE_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($value)
    {
        return $this->setData(self::NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType()
    {
        return $this->getData(self::BLOCK_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBlockType($value)
    {
        return $this->setData(self::BLOCK_TYPE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getFromDate()
    {
        return $this->getData(self::FROM_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setFromDate($value)
    {
        return $this->setData(self::FROM_DATE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getToDate()
    {
        return $this->getData(self::TO_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setToDate($value)
    {
        return $this->setData(self::TO_DATE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsActive($value)
    {
        return $this->setData(self::IS_ACTIVE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleConditions()
    {
        return $this->getRuleConditionConverter()->arrayToDataModel($this->getConditions()->asArray());
    }

    /**
     * Getter for the rule condition converter
     *
     * @return Converter
     */
    private function getRuleConditionConverter()
    {
        if ($this->ruleConditionConverter === null) {
            $this->ruleConditionConverter = ObjectManager::getInstance()
                ->get(Converter::class);
        }

        return $this->ruleConditionConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function setRuleConditions($condition)
    {
        $this->getConditions()
            ->setConditions([])
            ->loadArray($this->getRuleConditionConverter()->dataModelToArray($condition));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleActions()
    {
        return $this->getRuleConditionConverter()->arrayToDataModel($this->getActions()->asArray());
    }

    /**
     * {@inheritdoc}
     */
    public function setRuleActions($condition)
    {
        $this->getActions()
            ->setActions([])
            ->loadArray($this->getRuleConditionConverter()->dataModelToArray($condition));

        return $this;
    }

    /**
     * @param array $arrAttributes
     *
     * @return ConditionInterface
     */
    public function arrayToDataModel(array $arrAttributes = [])
    {
        return $this->getRuleConditionConverter()->arrayToDataModel($arrAttributes);
    }

    /**
     * @return RuleActionCollection
     */
    public function getSimilarityActions()
    {
        if (!$this->_similarityActions) {
            $this->_resetSimilarityActions();
        }

        // Load rule actions if it is applicable
        if ($this->hasSimilarityActionsSerialized()) {
            $actions = $this->getSimilarityActionsSerialized();
            if (!empty($actions)) {
                $actions = $this->serializer->unserialize($actions);
                if (is_array($actions) && !empty($actions)) {
                    $this->_similarityActions->loadArray($actions);
                }
            }
            $this->unsSimilarityActionsSerialized();
        }

        return $this->_similarityActions;
    }

    /**
     * Reset rule actions
     *
     * @param null|RuleActionCollection $actions
     *
     * @return $this
     */
    protected function _resetSimilarityActions($actions = null)
    {
        if (null === $actions) {
            $actions = $this->getSimilarityActionsInstance();
        }
        $actions->setRule($this)->setId('1')->setPrefix('similarity_actions');
        $this->setSimilarityActions($actions);

        return $this;
    }

    /**
     * Get rule condition product combine model instance
     *
     * @return Combine
     */
    public function getSimilarityActionsInstance()
    {
        return $this->similarityCombineFactory->create();
    }

    /**
     * Set rule actions model
     *
     * @param RuleActionCollection $actions
     *
     * @return $this
     */
    public function setSimilarityActions($actions)
    {
        $this->_similarityActions = $actions;

        return $this;
    }

    public function beforeSave()
    {
        // Serialize sinmilarity actions
        if ($this->getSimilarityActions()) {
            $this->setSimilarityActionsSerialized(
                $this->serializer->serialize($this->getSimilarityActions()->asArray())
            );
            $this->_similarityActions = null;
        }
        parent::beforeSave();

        return $this;
    }

    public function loadPost(array $data)
    {
        $arr = $this->_convertFlatToRecursive($data);

        if (isset($arr['similarity_actions'])) {
            $this->getSimilarityActions()->setSimilarityActions([])->loadArray(
                $arr['similarity_actions'][1],
                'similarity_actions'
            );
        }

        return parent::loadPost($data); // TODO: Change the autogenerated stub
    }

    /**
     * @param array $data
     *
     * @return array|mixed
     * @throws Exception
     */
    protected function _convertFlatToRecursive(array $data)
    {
        $arr = [];
        foreach ($data as $key => $value) {
            if (($key === 'conditions' || $key === 'actions' || $key === 'similarity_actions') && is_array($value)) {
                foreach ($value as $id => $data) {
                    $path = explode('--', $id);
                    $node = &$arr;
                    for ($i = 0, $l = count($path); $i < $l; $i++) {
                        if (!isset($node[$key][$path[$i]])) {
                            $node[$key][$path[$i]] = [];
                        }
                        $node = &$node[$key][$path[$i]];
                    }
                    foreach ($data as $k => $v) {
                        $node[$k] = $v;
                    }
                }
            } else {
                /**
                 * Convert dates into \DateTime
                 */
                if (in_array($key, ['from_date', 'to_date'], true) && $value) {
                    $value = new DateTime($value);
                }
                $this->setData($key, $value);
            }
        }

        return $arr;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryConditions()
    {
        return $this->getData(self::CATEGORY_CONDITIONS_SERIALIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function setCategoryConditions($value)
    {
        return $this->setData(self::CATEGORY_CONDITIONS_SERIALIZED, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder($value)
    {
        return $this->setData(self::SORT_ORDER, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setParentId($value)
    {
        return $this->setData(self::PARENT_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getImpression()
    {
        return $this->getData(self::IMPRESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setImpression($value)
    {
        return $this->setData(self::IMPRESSION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getClick()
    {
        return $this->getData(self::CLICK);
    }

    /**
     * {@inheritdoc}
     */
    public function setClick($value)
    {
        return $this->setData(self::CLICK, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation()
    {
        return $this->getData(self::LOCATION);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocation($value)
    {
        return $this->setData(self::LOCATION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockName()
    {
        return $this->getData(self::BLOCK_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setBlockName($value)
    {
        return $this->setData(self::BLOCK_NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getLimitNumber()
    {
        return $this->getData(self::LIMIT_NUMBER);
    }

    /**
     * {@inheritdoc}
     */
    public function setLimitNumber($value)
    {
        return $this->setData(self::LIMIT_NUMBER, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayOutOfStock()
    {
        return $this->getData(self::DISPLAY_OUT_OF_STOCK);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayOutOfStock($value)
    {
        return $this->setData(self::DISPLAY_OUT_OF_STOCK, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductLayout()
    {
        return $this->getData(self::PRODUCT_LAYOUT);
    }

    /**
     * {@inheritdoc}
     */
    public function setProductLayout($value)
    {
        return $this->setData(self::PRODUCT_LAYOUT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrderDirection()
    {
        return $this->getData(self::SORT_ORDER_DIRECTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrderDirection($value)
    {
        return $this->setData(self::SORT_ORDER_DIRECTION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayAdditional()
    {
        return $this->getData(self::DISPLAY_ADDITIONAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayAdditional($value)
    {
        return $this->setData(self::DISPLAY_ADDITIONAL, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddRucProduct()
    {
        return $this->getData(self::ADD_RUC_PRODUCT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddRucProduct($value)
    {
        return $this->setData(self::ADD_RUC_PRODUCT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductNotDisplayed()
    {
        return $this->getData(self::PRODUCT_NOT_DISPLAYED);
    }

    /**
     * {@inheritdoc}
     */
    public function setProductNotDisplayed($value)
    {
        return $this->setData(self::PRODUCT_NOT_DISPLAYED, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalImpression()
    {
        return $this->getData(self::TOTAL_IMPRESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalImpression($value)
    {
        return $this->setData(self::TOTAL_IMPRESSION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalClick()
    {
        return $this->getData(self::TOTAL_CLICK);
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalClick($value)
    {
        return $this->setData(self::TOTAL_CLICK, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($value)
    {
        return $this->setData(self::CREATED_AT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($value)
    {
        return $this->setData(self::UPDATED_AT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayMode()
    {
        return $this->getData(self::DISPLAY_MODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayMode($value)
    {
        return $this->setData(self::DISPLAY_MODE, $value);
    }
}
