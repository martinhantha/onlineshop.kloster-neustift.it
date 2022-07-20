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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab\CMSPage;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column\Filter\Text;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
use Mageplaza\AutoRelated\Block\Adminhtml\Grid\Filter\Column\Checkbox as FilterCheckbox;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPage\Grid\CollectionFactory;
use Mageplaza\AutoRelated\Block\Adminhtml\Grid\Renderer\Column\Checkbox;
use Mageplaza\AutoRelated\Block\Adminhtml\Grid\Renderer\Column\Select;
use Mageplaza\AutoRelated\Model\Config\Source\CmsPosition;

/**
 * Class Grid
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Program\Edit\Tab\CMSPage
 */
class Grid extends Extended
{
    /**
     * @type CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CmsPosition
     */
    private $cmsPosition;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param CmsPosition $cmsPosition
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        CmsPosition $cmsPosition,
        Registry $registry,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->cmsPosition       = $cmsPosition;
        $this->registry          = $registry;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        $collection = $this->collectionFactory->create();
        $this->setId('mparp_cms_page_grid');
        $this->setUseAjax(true);
        $this->setCollection($collection);
        $checkboxElement = '$(\'#mparp_cms_page_grid_table tbody input[type="checkbox"]\')';
        $this->setAdditionalJavaScript("
            require([
            'jquery'
                ], function ($) {
                    $(document).ready(function () {
                        $('#mparp_cms_page_grid_table tbody td').on('click', function() {
                            if(this.className.indexOf('col-position') < 0){
                                var checkboxElement = $(this).parent().find('input');

                                checkboxElement.prop('checked', !checkboxElement.is(':checked'));
                            }
                        });

                         $('#mparp_cms_page_grid_table thead .data-grid-actions-cell input').on('click', function() {
                            {$checkboxElement}.prop('checked', this.checked);
                        });
                    });
                }
            );
        ");
    }

    /**
     * Prepare grid columns
     *
     * @return Extended
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $rule   = $this->registry->registry('autorelated_rule');
        $ruleId = $rule->getId();

        $this->addColumn('page_id_checkbox', [
            'renderer'   => Checkbox::class,
            'index'      => 'page_id_checkbox',
            'type'       => 'checkbox',
            'filter'     => $ruleId ? FilterCheckbox::class : false,
            'sortable'   => $ruleId ? true : false,
            'field_name' => 'page_id_checkbox'
        ]);

        $this->addColumn('page_id', [
            'header' => __('ID'),
            'index'  => 'page_id',
            'type'   => 'text'
        ]);

        $this->addColumn('title', [
            'header'   => __('Title'),
            'index'    => 'title',
            'sortable' => true,
            'type'     => 'text',
        ]);

        $this->addColumn('position', [
            'renderer' => Select::class,
            'header'   => __('Position'),
            'index'    => 'position',
            'type'     => 'select',
            'filter'   => $ruleId ? Text::class : false,
            'sortable' => $ruleId ? true : false,
            'options'  => $this->cmsPosition->getPosition()
        ]);

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/cmsPageGrid', ['_current' => true]);
    }
}
