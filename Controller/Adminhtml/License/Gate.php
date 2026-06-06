<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Controller\Adminhtml\License;

use ETechFlow\PageSpeedOptimizerPremium\Model\LicenseValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * License-required gate page. Shows plan cards + "Enter License Key".
 * Redirects to the Premium config section when the license is already valid.
 */
class Gate extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_PageSpeedOptimizerPremium::config';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly LicenseValidator $licenseValidator
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        if ($this->licenseValidator->isValid()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath('adminhtml/system_config/edit/section/etechflow_pso_premium');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Page Speed Optimizer Premium — License Required'));

        // Hand the portal "plans" endpoint to the gate so it can render the
        // correct cards: a single lifetime card when the portal admin set this
        // module to one-time, or the weekly/monthly/yearly cards for recurring.
        $portalBase = rtrim(str_replace('/license/validate', '', $this->licenseValidator->getPortalUrl()), '/');
        $domain     = $this->licenseValidator->getCurrentHost();
        $plansUrl   = $portalBase . '/license/plans?module=page-speed-optimizer-premium&domain=' . urlencode($domain);

        $block = $page->getLayout()->getBlock('etechflow.psoprem.license.gate');
        if ($block) {
            $block->setData('plans_url', $plansUrl);
        }
        return $page;
    }
}
