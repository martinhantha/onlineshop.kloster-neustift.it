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
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\AutoRelated\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule as RuleResource;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Mageplaza\AutoRelated\Model\Rule;

/**
 * Class InstallData
 * @package Mageplaza\SeoAnalysis\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var RuleCollection
     */
    private $ruleCollection;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * UpgradeData constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param RuleCollection $ruleCollection
     * @param RuleResource $ruleResource
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        RuleCollection $ruleCollection,
        RuleResource $ruleResource
    ) {
        $this->eavSetupFactory   = $eavSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->ruleCollection    = $ruleCollection;
        $this->ruleResource      = $ruleResource;
    }

    /**
     * Upgrades data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(Product::ENTITY, 'mp_disable_auto_related', [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => __('Manually Setup Auto Related Products'),
                'note'                    => '',
                'input'                   => 'boolean',
                'class'                   => '',
                'source'                  => '',
                'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => 0,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'group'                   => 'Related',
                'sort_order'              => 10,
                'apply_to'                => ''
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            /** @var SalesSetup $salesInstaller */
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order_item',
                'arp_rule_token',
                ['type' => Table::TYPE_TEXT, 'visible' => false]
            );

            /** @var Rule $rule */
            foreach ($this->ruleCollection->create()->getItems() as $rule) {
                if (!$rule->getToken()) {
                    $rule->setToken($this->ruleResource->createToken());
                    $this->ruleResource->save($rule);
                }
            }
        }

        $setup->endSetup();
    }
}
