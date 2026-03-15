<?php
namespace MyCompany\LlmsTxt\Model;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class LlmsTxtRegenerator
{
    private StoreManagerInterface $storeManager;
    private Config $config;
    private LlmsTxtGenerator $generator;
    private LlmsTxtFileManager $fileManager;
    private Emulation $appEmulation;
    private LoggerInterface $logger;

    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        LlmsTxtGenerator $generator,
        LlmsTxtFileManager $fileManager,
        Emulation $appEmulation,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->generator = $generator;
        $this->fileManager = $fileManager;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    public function regenerateAll(): array
    {
        $generated = [];

        foreach ($this->getPrimaryStoresByHost() as $storeId) {
            $environment = null;

            try {
                $store = $this->storeManager->getStore($storeId);
                $host = strtolower((string) parse_url((string) $store->getBaseUrl(), PHP_URL_HOST));
                $identifier = $this->fileManager->buildIdentifier($host);

                $environment = $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                $content = $this->generator->generate();
                $this->fileManager->write($identifier, $content);
                $generated[] = $identifier;
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
            } finally {
                if ($environment !== null) {
                    $this->appEmulation->stopEnvironmentEmulation($environment);
                }
            }
        }

        return $generated;
    }

    private function getPrimaryStoresByHost(): array
    {
        $storesByIdentifier = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            if (!$this->config->isEnabled($storeId)) {
                continue;
            }

            $host = strtolower((string) parse_url((string) $store->getBaseUrl(), PHP_URL_HOST));
            $identifier = $this->fileManager->buildIdentifier($host);

            if (!isset($storesByIdentifier[$identifier])) {
                $storesByIdentifier[$identifier] = $store;
                continue;
            }

            if (strcmp((string) $store->getCode(), (string) $storesByIdentifier[$identifier]->getCode()) < 0) {
                $storesByIdentifier[$identifier] = $store;
            }
        }

        ksort($storesByIdentifier);

        return array_map(static fn ($store): int => (int) $store->getId(), array_values($storesByIdentifier));
    }
}
