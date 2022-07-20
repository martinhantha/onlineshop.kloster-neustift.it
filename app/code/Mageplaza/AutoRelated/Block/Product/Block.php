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

namespace Mageplaza\AutoRelated\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\SessionFactory as QuoteSessionFactory;
use Magento\Customer\Model\SessionFactory as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url\Helper\Data as UrlData;
use Magento\Framework\View\LayoutFactory;
use Magento\Widget\Block\BlockInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Helper\Data as WishlistData;
use Mageplaza\AutoRelated\Helper\Data;
use Mageplaza\AutoRelated\Helper\Rule;
use Mageplaza\AutoRelated\Model\Rule as ModelRule;
use Mageplaza\AutoRelated\Model\Config\Source\AddProductTypes;
use Mageplaza\AutoRelated\Model\Config\Source\ProductNotDisplayed;
use Mageplaza\AutoRelated\Model\RuleFactory;

/**
 * Class Block
 * @package Mageplaza\AutoRelated\Block\Product
 */
class Block extends AbstractProduct implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Mageplaza_AutoRelated::product/block.phtml';

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ModelRule
     */
    protected $rule;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var array
     */
    protected $displayTypes;

    /**
     * @var UrlData
     */
    protected $urlHelper;

    /**
     * @var
     */
    protected $rendererListBlock;

    /**
     * @var Stock
     */
    protected $stockHelper;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var QuoteSessionFactory
     */
    protected $quoteSessionFactory;

    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var WishlistData
     */
    protected $wishlistHelperData;

    /**
     * @var Compare
     */
    protected $catalogHelperCompare;

    /**
     * Block constructor.
     *
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Wishlist $wishlist
     * @param QuoteSessionFactory $quoteSessionFactory
     * @param Rule $helper
     * @param UrlData $urlHelper
     * @param WishlistData $wishlistHelperData
     * @param Compare $catalogHelperCompare
     * @param Stock $stockHelper
     * @param RuleFactory $ruleFactory
     * @param CustomerSession $customerSession
     * @param LayoutFactory|null $layoutFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Wishlist $wishlist,
        QuoteSessionFactory $quoteSessionFactory,
        Rule $helper,
        UrlData $urlHelper,
        WishlistData $wishlistHelperData,
        Compare $catalogHelperCompare,
        Stock $stockHelper,
        RuleFactory $ruleFactory,
        CustomerSession $customerSession,
        LayoutFactory $layoutFactory = null,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helper                   = $helper;
        $this->urlHelper                = $urlHelper;
        $this->stockHelper              = $stockHelper;
        $this->layoutFactory            = $layoutFactory ?: ObjectManager::getInstance()->get(LayoutFactory::class);
        $this->ruleFactory              = $ruleFactory;
        $this->customerSession          = $customerSession;
        $this->quoteSessionFactory      = $quoteSessionFactory;
        $this->wishlist                 = $wishlist;
        $this->wishlistHelperData       = $wishlistHelperData;
        $this->catalogHelperCompare     = $catalogHelperCompare;

        parent::__construct($context, $data);
    }

    /**
     * @param ModelRule $rule
     *
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        $location = $rule->getLocation();
        if ($location === 'left-popup-content' || $location === 'right-popup-content') {
            $this->setTemplate('Mageplaza_AutoRelated::product/block-floating.phtml');
        }

        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getLocationBlock()
    {
        return $this->rule->getLocation();
    }

    /**
     * Get heading label
     *
     * @return string
     */
    public function getTitleBlock()
    {
        return $this->rule->getBlockName();
    }

    /**
     * @return mixed
     */
    public function getRuleId()
    {
        return $this->rule->getId();
    }

    /**
     * @return string
     */
    public function getJsData()
    {
        return Rule::jsonEncode([
            'type'                    => $this->isSliderType() ? 'slider' : 'grid',
            'rule_id'                 => $this->rule->getId(),
            'parent_id'               => $this->rule->getData('parent_id'),
            'location'                => $this->rule->getData('location'),
            'number_product_slider'   => $this->rule->getData('number_product_slider') ?: 5,
            'number_product_scrolled' => $this->rule->getData('number_product_scrolled') ?: 2,
            'mode'                    => $this->rule->getData('display_mode')
        ]);
    }

    /**
     * Get layout config
     *
     * @return int
     */
    public function isSliderType()
    {
        return !$this->rule->getProductLayout();
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function canShow($type)
    {
        if ($this->displayTypes === null) {
            $this->displayTypes = $this->rule->getDisplayAdditional() ? explode(
                ',',
                $this->rule->getDisplayAdditional()
            ) : [];
        }

        return in_array($type, $this->displayTypes);
    }

    /**
     * @return array|Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductCollection()
    {
        $rule = $this->rule;
        if (!$rule || !$rule->getId()) {
            return [];
        }

        $productIds = $rule->getApplyProductIds();
        if (empty($productIds)) {
            return [];
        }
        if ($rule->getAddRucProduct() && !empty($this->addAdditionProducts())) {
            $productIds = array_unique(array_merge($productIds, $this->addAdditionProducts()));
        }
        if ($this->rule->getProductNotDisplayed() && !empty($this->removeProducts())) {
            $productIds = array_diff($productIds, $this->removeProducts());
        }
        if (empty($productIds)) {
            return [];
        }

        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($productIds)
            ->setVisibility([
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH
            ])
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1);

        $collection = $this->_addProductAttributesAndPrices($collection);

        if ($rule->getDisplayOutOfStock()) {
            $collection->setFlag('has_stock_status_filter', true);
            $this->stockHelper->addStockStatusToProducts($collection);
        } else {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }

        $newCollection = $this->productCollectionFactory->create()->addIdFilter($collection->getAllIds());
        $newCollection = $this->_addProductAttributesAndPrices($newCollection);

        if ($limit = $rule->getLimitNumber()) {
            $newCollection->getSelect()->limit($limit);
        }

        $newCollection = $this->helper->sortProduct($rule, $newCollection);

        return $newCollection;
    }

    /**
     * @return array|string
     */
    protected function addAdditionProducts()
    {
        $productIds = [];
        if ($this->rule->getBlockType() !== 'product') {
            return $productIds;
        }

        $product = $this->helper->getCurrentProduct();

        $addProductTypes = explode(',', $this->rule['add_ruc_product']);
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
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function removeProducts()
    {
        $productIds          = [];
        $productNotDisplayed = explode(',', $this->rule['product_not_displayed']);
        $customer            = $this->customerSession->create();
        $customerId          = $customer->getCustomer()->getId();

        if (in_array(ProductNotDisplayed::IN_CART, $productNotDisplayed)) {
            /** @var  Session $quoteSession */
            $quoteSession    = $this->quoteSessionFactory->create();
            $cartProductList = $quoteSession->getQuote()->getAllItems();
            foreach ($cartProductList as $item) {
                $productIds[] = $item->getProductId();
            }
        }

        if (in_array(ProductNotDisplayed::IN_WISHLIST, $productNotDisplayed) && $customerId) {
            $wishListItems = $this->wishlist->loadByCustomerId($customerId)->getItemCollection();

            if (count($wishListItems)) {
                foreach ($wishListItems as $item) {
                    $productIds[] = $item->getProduct()->getId();
                }
            }
        }

        return $productIds;
    }

    /**
     * Get post parameters
     *
     * @param Product $product
     *
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product, ['_escape' => false]);

        return [
            'action' => $url,
            'data'   => [
                'product'                               => (int) $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getDetailsRendererList()
    {
        if (empty($this->rendererListBlock)) {
            $layout = $this->layoutFactory->create(['cacheable' => false]);
            $layout->getUpdate()->addHandle('catalog_widget_product_list')->load();
            $layout->generateXml();
            $layout->generateElements();

            $this->rendererListBlock = $layout->getBlock('category.product.type.widget.details.renderers');
        }

        return $this->rendererListBlock;
    }

    /**
     * @return int
     */
    public function getPageColumnLayout()
    {
        return $this->rule->getPageColumnLayout() ?: 4;
    }

    /**
     * @return mixed
     */
    public function getRuleToken()
    {
        if ($parentId = $this->rule->getParentId()) {
            return $this->ruleFactory->create()->load($parentId)->getToken();
        }

        return $this->rule->getToken();
    }

    /**
     * @return WishlistData
     */
    public function getWishlistHelperData()
    {
        return $this->wishlistHelperData;
    }

    /**
     * @return Compare
     */
    public function getCatalogHelperCompare()
    {
        return $this->catalogHelperCompare;
    }
}
