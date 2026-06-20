<?php
declare(strict_types=1);
namespace ETechFlow\PageSpeedOptimizerPremium\Controller\Adminhtml\License;

use ETechFlow\PageSpeedOptimizerPremium\Model\LicenseValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Dashboard extends Action
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
        if (!$this->licenseValidator->isValid()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath('etechflow_psopremium/license/gate');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Page Speed Optimizer Premium'));
        return $page;
    }
}
