<?php
namespace MyCompany\LlmsTxt\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use MyCompany\LlmsTxt\Model\LlmsTxtRegenerator;

class Regenerate extends Action
{
    public const ADMIN_RESOURCE = 'MyCompany_LlmsTxt::config';

    private LlmsTxtRegenerator $regenerator;

    public function __construct(
        Context $context,
        LlmsTxtRegenerator $regenerator
    ) {
        parent::__construct($context);
        $this->regenerator = $regenerator;
    }

    public function execute(): Redirect
    {
        try {
            $generated = $this->regenerator->regenerateAll();
            $this->messageManager->addSuccessMessage(
                __('LLMS.txt files were regenerated: %1', implode(', ', $generated ?: ['none']))
            );
        } catch (\Throwable $throwable) {
            $this->messageManager->addErrorMessage(__('Unable to regenerate LLMS.txt files.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'llmstxt']);

        return $resultRedirect;
    }
}
