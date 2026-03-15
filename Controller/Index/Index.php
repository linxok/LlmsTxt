<?php
namespace MyCompany\LlmsTxt\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;
use MyCompany\LlmsTxt\Model\Config;
use MyCompany\LlmsTxt\Model\LlmsTxtFileManager;

class Index extends Action
{
    private RawFactory $resultRawFactory;
    private LlmsTxtFileManager $fileManager;
    private Config $config;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LlmsTxtFileManager $fileManager,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->fileManager = $fileManager;
        $this->config = $config;
        $this->storeManager = $storeManager;
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

        $host = strtolower((string) parse_url((string) $this->storeManager->getStore()->getBaseUrl(), PHP_URL_HOST));
        $content = $this->fileManager->read($this->fileManager->buildIdentifier($host));
        if ($content === null) {
            $this->getResponse()->setHttpResponseCode(503);
            $result->setContents('llms.txt is not generated yet');
            return $result;
        }

        $result->setContents($content);
        return $result;
    }
}
