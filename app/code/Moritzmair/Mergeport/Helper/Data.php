<?php

namespace Moritzmair\Mergeport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use GuzzleHttp\Client;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;
    protected $productRepository;
    private $state;
    private $dateTime;


    const XML_PATH = 'mergeport/options/';

    protected $productCollectionFactory;

    private $orderCollectionFatory;

    // API URL
    const API_URL = 'https://ordering.mergeport.com/v4/hooks/main/';

    private $logger;


    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CollectionFactory  $productCollectionFactory,
        State $state,
        DateTime $dateTime,
        OrderCollectionFactory $orderCollectionFatory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager  = $storeManager;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dateTime = $dateTime;
        $this->state = $state;
        $this->orderCollectionFatory = $orderCollectionFatory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . $code, $storeId);
    }

    public function getProductBySynchId($synch_id)
    {

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('id')
            ->addAttributeToFilter('pos_id', $synch_id);
        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return null;
    }

    public function getProductBySku($sku)
    {

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('id')
            ->addAttributeToFilter('sku', $sku);
        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return null;
    }

    public function updateStock($product_id, $quantity)
    {
        $product = $this->productRepository->getById($product_id);
//        $product->setStatus(($quantity > 10 ? 1 : 0));
        $product->setStockData(array(
            'qty' => ($quantity > 10 ? $quantity : 0),
            'is_in_stock' => ($quantity > 10 ? 1 : 0)
        ));

        $product->setData('last_synch', $this->dateTime->formatDate(time()));
        $product->save();

        $this->productRepository->save($product);
    }

    public function synchProducts()
    {
        $client = new Client([
            'base_uri' => self::API_URL,
        ]);

        $url = $this->getGeneralConfig('provider_id') . '/' . $this->getGeneralConfig('restaurant_id') . '/items';

        $response = $client->request('GET', $url);

        $items = json_decode($response->getBody()->getContents());

        if (!$this->state->getAreaCode()) {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }


        foreach ($items as $item) {
            $product = $this->getProductBySku($item->posItemId);

            if ($product) {
                $this->updateStock($product->getId(), $item->stock ? $item->stock : 0);
            }
        }
    }

    public function synchOrders()
    {
        if (!$this->state->getAreaCode()) {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }

        $orders = $this->orderCollectionFatory->create();
        $orders->addAttributeToSelect('*');
        $orders->addAttributeToFilter('status', 'processing');
        $orders->addAttributeToFilter('pos_id', array('null' => true));

        $client = new Client([
            'base_uri' => self::API_URL,
        ]);

        $url = $this->getGeneralConfig('provider_id') . '/order/' . $this->getGeneralConfig('restaurant_id');

        foreach ($orders as $order) {
            $products = array_map(function ($item) {
                return [
                    'id' => $item->getPosId() ? $item->getPosId() : $item->getSku(),
                    'name' => $item->getName(),
                    'unitPrice' => $item->getPrice() * 100,
                    'paidPrice' => $item->getRowTotalInclTax() * 100,
                    'quantity' => $item->getQtyInvoiced() * 1
                ];
            }, $order->getAllItems());
            array_push($products, [
                'id' => '900000',
                'name' => 'Transportspesen',
                'unitPrice' => $order->getShippingAmount() * 100,
                'paidPrice' => $order->getShippingAmount() * 100,
                'quantity' => 1
            ]);
            $value = [
                'token' => $order->getId(),
                'createdAt' => (new \DateTime($order->getCreatedAt()))->format('Y-m-d\TH:i:s\Z'),
                'code' => 'WebShop',
                'platformInfo' => [
                    'restaurantId' => '0',
                    'countryCode' => 'IT',
                    'name' => 'WebShop',
                    'currency' => 'EUR'
                ],
                'customer' => [
                    'firstName' => $order->getBillingAddress()->getFirstName(),
                    'lastName' => $order->getBillingAddress()->getLastName()
                ],
                'payment' => [
                    'type' => 'CARD',
                    'payOnDelivery' => false,
                    'amount' => $order->getGrandTotal() * 100
                ],
                'expeditionType' => 'delivery',
                'products' => [],
                'price' => [
                    'grandTotal' => $order->getGrandTotal() * 100
                ],
                // 'additionalCosts' => [
                //     [
                //         'name' => 'Transportspesen',
                //         'amount' => $order->getShippingAmount() * 100,
                //     ]
                // ],
                'products' => $products
            ];

            $response = $client->request('POST', $url, [
                'json' => $value
            ]);

            $response = $response->getBody()->getContents();

            $this->logger->info($response);

            $response = json_decode($response);

            $order->setData('pos_id', $response->remoteResponse->remoteOrderId);
            $order->setData('synched_at', $this->dateTime->formatDate(time()));
            $order->save();
        }
    }
}
