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

namespace Mageplaza\AutoRelated\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\AutoRelated\Model\Rule;
use Mageplaza\AutoRelated\Model\RuleFactory;
use Mageplaza\AutoRelated\Model\ResourceModel\Rule as RuleResource;

/**
 * Class CmsPageGrid
 * @package Mageplaza\AutoRelated\Controller\Adminhtml\Rule
 */
class CmsPageGrid extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Mageplaza_AutoRelated::rule';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * CmsPageGrid constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param LayoutFactory $layoutFactory
     * @param RuleFactory $ruleFactory
     * @param RuleResource $ruleResource
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        LayoutFactory $layoutFactory,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registry          = $registry;
        $this->layoutFactory     = $layoutFactory;
        $this->ruleResource      = $ruleResource;
        $this->ruleFactory       = $ruleFactory;

        parent::__construct($context);
    }

    /**
     * Load layout, set breadcrumbs
     *
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);

        return $resultPage;
    }

    /**
     * @return Rule
     */
    protected function initRule()
    {
        $rule = $this->ruleFactory->create();

        if ($objId = $this->getRequest()->getParam('id')) {
            $this->ruleResource->load($rule, $objId);
        }

        return $rule;
    }

    /**
     * Execute
     *
     * @return Layout
     */
    public function execute()
    {
        $this->registry->register('autorelated_rule', $this->initRule());

        return $this->layoutFactory->create();
    }
}
