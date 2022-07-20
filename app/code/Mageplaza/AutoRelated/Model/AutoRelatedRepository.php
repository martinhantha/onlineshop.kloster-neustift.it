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

use Magento\Catalog\Model\Api\SearchCriteria\ProductCollectionProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mageplaza\AutoRelated\Api\AutoRelatedRepositoryInterface;
use Mageplaza\AutoRelated\Helper\Data;
use Mageplaza\AutoRelated\Helper\Rule as RuleHelper;
use Mageplaza\AutoRelated\Model\Config\Source\AddProductTypes;
use Mageplaza\AutoRelated\Model\Config\Source\Type;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule as ResourceModel;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule\Collection;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Class AutoRelatedRepository
 * @package Mageplaza\AutoRelated\Model
 */
class AutoRelatedRepository implements AutoRelatedRepositoryInterface
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $productCollectionProcessor;
    /**
     * @var ResourceModel
     */
    protected $resourceModel;
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;
    /**
     * @var RuleHelper
     */
    protected $ruleHelper;
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * AutoRelatedRepository constructor.
     *
     * @param Data $helperData
     * @param CollectionFactory $collectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param ResourceModel $resourceModel
     * @param RuleFactory $ruleFactory
     * @param DateTime $dateTime
     * @param RuleHelper $ruleHelper
     * @param ProductRepository $productRepository
     * @param CollectionProcessorInterface|null $productCollectionProcessor
     */
    public function __construct(
        Data $helperData,
        CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory,
        ResourceModel $resourceModel,
        RuleFactory $ruleFactory,
        DateTime $dateTime,
        RuleHelper $ruleHelper,
        ProductRepository $productRepository,
        CollectionProcessorInterface $productCollectionProcessor = null
    ) {
        $this->helperData                 = $helperData;
        $this->collectionFactory          = $collectionFactory;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->collectionProcessor        = $collectionProcessor;
        $this->searchResultsFactory       = $searchResultsFactory;
        $this->dateTime                   = $dateTime;
        $this->resourceModel              = $resourceModel;
        $this->ruleFactory                = $ruleFactory;
        $this->productCollectionProcessor = $productCollectionProcessor ?: $this->getProductCollectionProcessor();
        $this->ruleHelper                 = $ruleHelper;
        $this->productRepository          = $productRepository;
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getProductCollectionProcessor()
    {
        if (!$this->productCollectionProcessor) {
            $this->productCollectionProcessor = ObjectManager::getInstance()->get(
                ProductCollectionProcessor::class
            );
        }

        return $this->productCollectionProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleProductPage(
        SearchCriteriaInterface $searchCriteria = null,
        SearchCriteriaInterface $productSearchCriteria = null,
        $sku = null,
        $storeId = null,
        $customerGroup = null
    ) {
        return $this->initParam(
            ['type' => Type::TYPE_PAGE_PRODUCT, 'sku' => $sku],
            $searchCriteria,
            $productSearchCriteria,
            $storeId,
            $customerGroup
        );
    }

    /**
     * @param array $data
     * @param SearchCriteriaInterface|null $searchCriteria
     * @param SearchCriteriaInterface|null $productSearchCriteria
     * @param null $storeId
     * @param null $customerGroup
     *
     * @return SearchResultsInterface
     * @throws NoSuchEntityException
     */
    public function initParam(
        array $data,
        SearchCriteriaInterface $searchCriteria = null,
        SearchCriteriaInterface $productSearchCriteria = null,
        $storeId = null,
        $customerGroup = null
    ) {
        if (!$this->helperData->isEnabled($storeId)) {
            throw new NoSuchEntityException(__('The module is disabled'));
        }

        if ($searchCriteria === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }

        if ($productSearchCriteria === null) {
            $productSearchCriteria = $this->searchCriteriaBuilder->create();
        }
        $type = $data['type'];

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter($customerGroup, $storeId)
            ->addDateFilter($this->dateTime->date())
            ->addTypeFilter($type)
            ->addLocationFilter(['nin' => ['custom', 'cms-page']]);
        $this->collectionProcessor->process($searchCriteria, $collection);
        $result = [];
        foreach ($collection->getItems() as $rule) {
            /** @var Rule $rule */
            $productIds = $this->getApplyProductIds($rule, $data);
            if (empty($productIds)) {
                continue;
            }

            $productCollection = $rule->getProductCollection();
            $productCollection->addIdFilter($productIds);
            $this->productCollectionProcessor->process($productSearchCriteria, $productCollection);
            $productCollection = $this->ruleHelper->sortProduct($rule, $productCollection);
            if ($productCollection->getSize()) {
                $productCollection->setPageSize($rule->getLimitNumber());
                $rule->setMatchingProducts($productCollection->getItems());
            } else {
                $rule->setMatchingProducts([]);
            }
            $result[] = $rule;
        }

        /** @var SearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($result);
        $searchResult->setTotalCount(count($result));

        return $searchResult;
    }

    /**
     * @param Rule $rule
     * @param array $data
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getApplyProductIds(Rule $rule, array $data)
    {
        $productIds = [];
        switch ($data['type']) {
            case Type::TYPE_PAGE_PRODUCT:
                if ($data['sku']) {
                    $product = $this->productRepository->get($data['sku']);
                    if ($rule->getConditions()->validate($product)) {
                        $productIds         = $rule->getResource()->getProductListByRuleId(
                            $rule->getId(),
                            $product->getId()
                        );
                        $additionProductIds = $this->addAdditionProducts($rule, $product);

                        if ($rule->getAddRucProduct() && !empty($additionProductIds)) {
                            $productIds = array_unique(array_merge($productIds, $additionProductIds));
                        }
                    } else {
                        $productIds = [];
                    }
                } else {
                    $productIds = $rule->getResource()->getProductListByRuleId($rule->getId());
                }
                break;
            case Type::TYPE_PAGE_CATEGORY:
                if ($condition = $rule->getCategoryConditionsSerialized()) {
                    if ($data['category_id']) {
                        $category = $this->helperData->getCategoryById($data['category_id']);
                        if (!$category->getId()) {
                            throw new NoSuchEntityException(
                                __("The category that was requested doesn't exist. Verify the cagory and try again.")
                            );
                        }
                        $categoryIds = $this->helperData->unserialize($condition);
                        if (in_array($category->getId(), $categoryIds)) {
                            $productIds = $rule->getResource()->getProductListByRuleId($rule->getId());
                        } else {
                            $productIds = [];
                        }
                    } else {
                        $productIds = $rule->getResource()->getProductListByRuleId($rule->getId());
                    }
                }
                break;
            case Type::TYPE_PAGE_SHOPPING:
                $productIds = $rule->getResource()->getProductListByRuleId($rule->getId());
                break;
            case Type::TYPE_PAGE_OSC:
                $productIds = $rule->getResource()->getProductListByRuleId($rule->getId());
                break;
        }

        return $productIds;
    }

    /**
     * @param Rule $rule
     * @param Product $product
     *
     * @return array
     */
    protected function addAdditionProducts(Rule $rule, Product $product)
    {
        $productIds = [];
        if ($rule->getBlockType() !== 'product') {
            return $productIds;
        }

        $addProductTypes = explode(',', $rule->getAddRucProduct());
        if (in_array(AddProductTypes::RELATED_PRODUCT, $addProductTypes, true)) {
            $productIds = array_merge($productIds, $product->getRelatedProductIds());
        }
        if (in_array(AddProductTypes::UP_SELL_PRODUCT, $addProductTypes, true)) {
            $productIds = array_merge($productIds, $product->getUpSellProductIds());
        }
        if (in_array(AddProductTypes::CROSS_SELL_PRODUCT, $addProductTypes, true)) {
            $productIds = array_merge($productIds, $product->getCrossSellProductIds());
        }

        return $productIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleCategoryPage(
        SearchCriteriaInterface $searchCriteria = null,
        SearchCriteriaInterface $productSearchCriteria = null,
        $categoryId = null,
        $storeId = null,
        $customerGroup = null
    ) {
        return $this->initParam(
            ['type' => Type::TYPE_PAGE_CATEGORY, 'category_id' => $categoryId],
            $searchCriteria,
            $productSearchCriteria,
            $storeId,
            $customerGroup
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleCartPage(
        SearchCriteriaInterface $searchCriteria = null,
        SearchCriteriaInterface $productSearchCriteria = null,
        $storeId = null,
        $customerGroup = null
    ) {
        return $this->initParam(
            ['type' => Type::TYPE_PAGE_SHOPPING],
            $searchCriteria,
            $productSearchCriteria,
            $storeId,
            $customerGroup
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleOSCPage(
        SearchCriteriaInterface $searchCriteria = null,
        SearchCriteriaInterface $productSearchCriteria = null,
        $storeId = null,
        $customerGroup = null
    ) {
        return $this->initParam(
            ['type' => Type::TYPE_PAGE_OSC],
            $searchCriteria,
            $productSearchCriteria,
            $storeId,
            $customerGroup
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateTotal($ruleId, $options = null)
    {
        if (!$this->helperData->isEnabled()) {
            throw new NoSuchEntityException(__('The module is disabled'));
        }

        if (empty($ruleId)) {
            throw new InputException(__('Invalid rule id %1', $ruleId));
        }

        if ($ruleId) {
            /** @var Rule $model */
            $model = $this->ruleFactory->create();
            $this->resourceModel->load($model, $ruleId);
            if (!$model->getId()) {
                throw new InputException(__('Invalid rule id %1', $ruleId));
            }
            $model->getResource()->updateClick($ruleId);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnable($storeId = null)
    {
        return $this->helperData->isEnabled($storeId);
    }
}
