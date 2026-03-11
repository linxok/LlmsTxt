<?php
namespace MyCompany\LlmsTxt\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use MyCompany\LlmsTxt\Model\Config;
use MyCompany\LlmsTxt\Model\LlmsTxtGenerator;

class Index extends Action
{
    private RawFactory $resultRawFactory;
    private LlmsTxtGenerator $generator;
    private Config $config;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LlmsTxtGenerator $generator,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->generator = $generator;
        $this->config = $config;
    }

    public function execute(): Raw
    {
        $result = $this->resultRawFactory->create();
        $result->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);

        if (!$this->config->isEnabled()) {
            $this->getResponse()->setHttpResponseCode(404);
            $result->setContents('Not found');
            return $result;
        }

        $result->setContents($this->generator->generate());
        return $result;
    }
}
