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
 * @package     Mageplaza_Blog
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\AutoRelated\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Mageplaza\AutoRelated\Model\Config\Source\DisplayMode;

/**
 * Class UpgradeSchema
 * @package Mageplaza\AutoRelated\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            if ($installer->tableExists('mageplaza_autorelated_block_rule')) {
                $connection->addColumn(
                    $installer->getTable('mageplaza_autorelated_block_rule'),
                    'display_mode',
                    [
                        'type'    => Table::TYPE_SMALLINT,
                        'default' => DisplayMode::TYPE_BLOCK,
                        'comment' => 'Display type ajax or block'
                    ]
                );

                $connection->addColumn(
                    $installer->getTable('mageplaza_autorelated_block_rule'),
                    'add_ruc_product',
                    [
                        'type'    => Table::TYPE_TEXT,
                        'size'    => 255,
                        'comment' => 'Add Related Up Sell Cross Sell Product'
                    ]
                );

                $connection->addColumn(
                    $installer->getTable('mageplaza_autorelated_block_rule'),
                    'product_not_displayed',
                    [
                        'type'    => Table::TYPE_TEXT,
                        'size'    => 255,
                        'comment' => 'Product is not displayed'
                    ]
                );

                $connection->dropColumn(
                    $installer->getTable('mageplaza_autorelated_block_rule'),
                    'display'
                );
            }
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            if ($installer->tableExists('mageplaza_autorelated_block_rule')) {
                $blockRuleTable = $installer->getTable('mageplaza_autorelated_block_rule');

                if (!$connection->tableColumnExists($blockRuleTable, 'page_column_layout')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'page_column_layout',
                        [
                            'type'    => Table::TYPE_INTEGER,
                            'comment' => 'Page Column Layout'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($blockRuleTable, 'number_product_slider')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'number_product_slider',
                        [
                            'type'    => Table::TYPE_INTEGER,
                            'comment' => 'Number of products on Slider'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($blockRuleTable, 'number_product_scrolled')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'number_product_scrolled',
                        [
                            'type'    => Table::TYPE_INTEGER,
                            'comment' => 'Product Displayed When Scrolled'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($blockRuleTable, 'apply_similarity')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'apply_similarity',
                        [
                            'type'    => Table::TYPE_SMALLINT,
                            'comment' => 'Product Displayed When Scrolled'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($blockRuleTable, 'similarity_actions_serialized')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'similarity_actions_serialized',
                        [
                            'type'    => Table::TYPE_TEXT,
                            'length'  => '2M',
                            'comment' => 'Similarity Actions Serialized'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($blockRuleTable, 'token')) {
                    $installer->getConnection()->addColumn(
                        $blockRuleTable,
                        'token',
                        [
                            'type'    => Table::TYPE_TEXT,
                            'comment' => 'ARP Rule Token'
                        ]
                    );
                }
            }

            if (!$installer->tableExists('mageplaza_autorelated_cms_page_rule')) {
                $cmsPageRuleTable = $installer->getConnection()
                    ->newTable($installer->getTable('mageplaza_autorelated_cms_page_rule'))
                    ->addColumn('id', Table::TYPE_INTEGER, null, [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true
                    ], 'Id')
                    ->addColumn('rule_id', Table::TYPE_INTEGER, null, ['unsigned' => true], 'Rule Id')
                    ->addColumn('page_id', Table::TYPE_SMALLINT, null, [], 'CMS Page Id')
                    ->addColumn('position', Table::TYPE_TEXT, 255, [], 'Position')
                    ->addForeignKey(
                        $setup->getFkName(
                            'mageplaza_autorelated_cms_page_rule',
                            'rule_id',
                            'mageplaza_autorelated_block_rule',
                            'rule_id'
                        ),
                        'rule_id',
                        $setup->getTable('mageplaza_autorelated_block_rule'),
                        'rule_id',
                        Table::ACTION_CASCADE
                    )->addForeignKey(
                        $setup->getFkName(
                            'mageplaza_autorelated_cms_page_rule',
                            'page_id',
                            'cms_page',
                            'page_Id'
                        ),
                        'page_id',
                        $setup->getTable('cms_page'),
                        'page_id',
                        Table::ACTION_CASCADE
                    );

                $connection->createTable($cmsPageRuleTable);
            }
        }

        $installer->endSetup();
    }
}
