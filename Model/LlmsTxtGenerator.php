<?php
namespace MyCompany\LlmsTxt\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Escaper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class LlmsTxtGenerator
{
    private const CACHE_KEY_PREFIX = 'MYCOMPANY_LLMSTXT_';

    private Config $config;
    private StoreManagerInterface $storeManager;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private PageCollectionFactory $pageCollectionFactory;
    private CacheInterface $cache;
    private Escaper $escaper;

    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        PageCollectionFactory $pageCollectionFactory,
        CacheInterface $cache,
        Escaper $escaper
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->cache = $cache;
        $this->escaper = $escaper;
    }

    public function generate(): string
    {
        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $relatedStores = $this->getRelatedStoresForCurrentHost($storeId);
        $aggregateMode = count($relatedStores) > 1;
        $cacheLifetime = $this->config->getCacheLifetime($storeId);
        $cacheKey = self::CACHE_KEY_PREFIX . ($aggregateMode
            ? 'HOST_' . md5(implode('|', array_map(static fn (StoreInterface $relatedStore): string => (string) $relatedStore->getId(), $relatedStores)))
            : 'STORE_' . $storeId);

        if ($cacheLifetime > 0) {
            $cachedValue = $this->cache->load($cacheKey);
            if (is_string($cachedValue) && $cachedValue !== '') {
                return $cachedValue;
            }
        }

        $result = $aggregateMode
            ? $this->generateAggregated($storeId, $relatedStores)
            : $this->generateForStore($storeId);

        if ($cacheLifetime > 0) {
            $this->cache->save($result, $cacheKey, ['MYCOMPANY_LLMSTXT'], $cacheLifetime);
        }

        return $result;
    }

    private function generateForStore(int $storeId): string
    {
        return implode("\n", $this->buildStoreLines($storeId)) . "\n";
    }

    private function generateAggregated(int $primaryStoreId, array $relatedStores): string
    {
        $lines = [];
        $lines[] = '# ' . $this->escapeText($this->config->getSiteTitle($primaryStoreId));

        $summary = $this->config->getSummary($primaryStoreId);
        if ($summary !== '') {
            $lines[] = '> ' . $this->escapeText($summary);
        }

        $intro = $this->config->getIntro($primaryStoreId);
        if ($intro !== '') {
            $lines[] = '';
            foreach ($this->splitParagraphs($intro) as $paragraph) {
                $lines[] = $this->escapeText($paragraph);
                $lines[] = '';
            }
            if (end($lines) === '') {
                array_pop($lines);
            }
        }

        $lines[] = '';
        $lines[] = '## Language Versions';
        foreach ($relatedStores as $relatedStore) {
            $lines[] = $this->formatLink([
                'label' => (string) $relatedStore->getName(),
                'url' => (string) $relatedStore->getBaseUrl(),
                'description' => '',
            ]);
        }

        foreach ($relatedStores as $relatedStore) {
            $storeId = (int) $relatedStore->getId();
            $storeLines = $this->buildStoreLines($storeId, true);
            if ($storeLines === []) {
                continue;
            }

            $lines[] = '';
            foreach ($storeLines as $storeLine) {
                $lines[] = $storeLine;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function buildStoreLines(int $storeId, bool $nested = false): array
    {
        $headingPrefix = $nested ? '### ' : '## ';
        $lines = [];

        if ($nested) {
            $lines[] = '## ' . $this->escapeText($this->storeManager->getStore($storeId)->getName());
        } else {
            $lines[] = '# ' . $this->escapeText($this->config->getSiteTitle($storeId));

            $summary = $this->config->getSummary($storeId);
            if ($summary !== '') {
                $lines[] = '> ' . $this->escapeText($summary);
            }

            $intro = $this->config->getIntro($storeId);
            if ($intro !== '') {
                $lines[] = '';
                foreach ($this->splitParagraphs($intro) as $paragraph) {
                    $lines[] = $this->escapeText($paragraph);
                    $lines[] = '';
                }
                if (end($lines) === '') {
                    array_pop($lines);
                }
            }
        }

        $manualLinks = $this->config->getManualLinks($storeId);
        if ($manualLinks !== []) {
            $lines[] = '';
            $lines[] = $headingPrefix . 'Key Pages';
            foreach ($manualLinks as $link) {
                $lines[] = $this->formatLink($link);
            }
        }

        if ($this->config->shouldIncludeCmsPages($storeId)) {
            $cmsLinks = $this->getCmsLinks($storeId, $this->config->getMaxCmsPages($storeId), $this->config->getFeaturedCmsPageIdentifiers($storeId));
            if ($cmsLinks !== []) {
                $lines[] = '';
                $lines[] = $headingPrefix . 'Shopping Information';
                foreach ($cmsLinks as $link) {
                    $lines[] = $this->formatLink($link);
                }
            }
        }

        if ($this->config->shouldIncludeCategories($storeId)) {
            $categoryLinks = $this->getCategoryLinks($storeId, $this->config->getMaxCategories($storeId), $this->config->getFeaturedCategoryIds($storeId));
            if ($categoryLinks !== []) {
                $lines[] = '';
                $lines[] = $headingPrefix . 'Main Categories';
                foreach ($categoryLinks as $link) {
                    $lines[] = $this->formatLink($link);
                }
            }
        }

        if ($this->config->shouldIncludeProducts($storeId)) {
            $productLinks = $this->getProductLinks($storeId, $this->config->getMaxProducts($storeId), $this->config->getFeaturedProductSkus($storeId));
            if ($productLinks !== []) {
                $lines[] = '';
                $lines[] = $headingPrefix . 'Featured Products';
                foreach ($productLinks as $link) {
                    $lines[] = $this->formatLink($link);
                }
            }
        }

        $optionalLinks = $this->config->getOptionalLinks($storeId);
        if ($optionalLinks !== []) {
            $lines[] = '';
            $lines[] = $headingPrefix . 'Optional';
            foreach ($optionalLinks as $link) {
                $lines[] = $this->formatLink($link);
            }
        }

        return $lines;
    }

    private function getRelatedStoresForCurrentHost(int $currentStoreId): array
    {
        $currentStore = $this->storeManager->getStore($currentStoreId);
        $currentHost = $this->extractHost((string) $currentStore->getBaseUrl());
        if ($currentHost === '') {
            return [$currentStore];
        }

        $relatedStores = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            if (!$this->config->isEnabled($storeId)) {
                continue;
            }

            if ($this->extractHost((string) $store->getBaseUrl()) !== $currentHost) {
                continue;
            }

            $relatedStores[] = $store;
        }

        usort($relatedStores, static fn (StoreInterface $left, StoreInterface $right): int => strcmp((string) $left->getCode(), (string) $right->getCode()));

        return $relatedStores !== [] ? $relatedStores : [$currentStore];
    }

    private function extractHost(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        return strtolower($host);
    }

    private function getCmsLinks(int $storeId, int $limit, array $featuredIdentifiers = []): array
    {
        $links = [];
        $seen = [];

        if ($featuredIdentifiers !== []) {
            $featuredCollection = $this->pageCollectionFactory->create();
            $featuredCollection->addFieldToFilter('is_active', 1);
            $featuredCollection->addStoreFilter($storeId);
            $featuredCollection->addFieldToFilter('identifier', ['in' => $featuredIdentifiers]);

            foreach ($featuredCollection as $page) {
                $link = $this->buildCmsLink($page, $storeId);
                if ($link === null || isset($seen[$link['url']])) {
                    continue;
                }

                $links[] = $link;
                $seen[$link['url']] = true;
            }
        }

        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addStoreFilter($storeId);
        $collection->setOrder('page_id', 'DESC');

        foreach ($collection as $page) {
            $link = $this->buildCmsLink($page, $storeId);
            if ($link === null || isset($seen[$link['url']])) {
                continue;
            }

            $links[] = $link;
            $seen[$link['url']] = true;

            if (count($links) >= $limit) {
                break;
            }
        }

        return $links;
    }

    private function getCategoryLinks(int $storeId, int $limit, array $featuredCategoryIds = []): array
    {
        $rootCategoryId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $links = [];
        $seen = [];

        if ($featuredCategoryIds !== []) {
            $featuredCollection = $this->categoryCollectionFactory->create();
            $featuredCollection->setStoreId($storeId);
            $featuredCollection->addAttributeToSelect(['name', 'url']);
            $featuredCollection->addAttributeToFilter('entity_id', ['in' => $featuredCategoryIds]);
            $featuredCollection->addAttributeToFilter('is_active', 1);

            foreach ($featuredCollection as $category) {
                $link = $this->buildCategoryLink($category, $rootCategoryId);
                if ($link === null || isset($seen[$link['url']])) {
                    continue;
                }

                $links[] = $link;
                $seen[$link['url']] = true;
            }
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect(['name', 'url']);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addAttributeToFilter('include_in_menu', 1);
        $collection->addAttributeToFilter('level', ['gteq' => 2]);
        $collection->addAttributeToSort('level', 'ASC');
        $collection->addAttributeToSort('position', 'ASC');

        foreach ($collection as $category) {
            $link = $this->buildCategoryLink($category, $rootCategoryId);
            if ($link === null || isset($seen[$link['url']])) {
                continue;
            }

            $links[] = $link;
            $seen[$link['url']] = true;

            if (count($links) >= $limit) {
                break;
            }
        }

        return $links;
    }

    private function getProductLinks(int $storeId, int $limit, array $featuredSkus = []): array
    {
        $links = [];
        $seen = [];

        if ($featuredSkus !== []) {
            $featuredCollection = $this->productCollectionFactory->create();
            $featuredCollection->setStoreId($storeId);
            $featuredCollection->addStoreFilter($storeId);
            $featuredCollection->addAttributeToSelect(['name', 'url_key']);
            $featuredCollection->addAttributeToFilter('status', 1);
            $featuredCollection->addAttributeToFilter('visibility', ['in' => [2, 3, 4]]);
            $featuredCollection->addAttributeToFilter('sku', ['in' => $featuredSkus]);

            foreach ($featuredCollection as $product) {
                $link = $this->buildProductLink($product);
                if ($link === null || isset($seen[$link['url']])) {
                    continue;
                }

                $links[] = $link;
                $seen[$link['url']] = true;
            }
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToSelect(['name', 'url_key']);
        $collection->addAttributeToFilter('status', 1);
        $collection->addAttributeToFilter('visibility', ['in' => [2, 3, 4]]);
        $collection->setPageSize($limit);
        $collection->setCurPage(1);
        $collection->addAttributeToSort('entity_id', 'DESC');

        foreach ($collection as $product) {
            $link = $this->buildProductLink($product);
            if ($link === null || isset($seen[$link['url']])) {
                continue;
            }

            $links[] = $link;
            $seen[$link['url']] = true;

            if (count($links) >= $limit) {
                break;
            }
        }

        return $links;
    }

    private function buildCmsLink($page, int $storeId): ?array
    {
        $identifier = (string) $page->getIdentifier();
        if (in_array($identifier, ['no-route', 'enable-cookies'], true)) {
            return null;
        }

        $title = trim((string) $page->getTitle());
        $url = $this->storeManager->getStore($storeId)->getBaseUrl() . ltrim($identifier, '/');
        if ($title === '' || $url === '') {
            return null;
        }

        return [
            'label' => $title,
            'url' => $url,
            'description' => '',
        ];
    }

    private function buildCategoryLink($category, int $rootCategoryId): ?array
    {
        $path = explode('/', (string) $category->getPath());
        if (!in_array((string) $rootCategoryId, $path, true)) {
            return null;
        }

        $url = (string) $category->getUrl();
        $name = trim((string) $category->getName());
        if ($name === '' || $url === '') {
            return null;
        }

        return [
            'label' => $name,
            'url' => $url,
            'description' => '',
        ];
    }

    private function buildProductLink($product): ?array
    {
        $name = trim((string) $product->getName());
        $url = (string) $product->getProductUrl();
        if ($name === '' || $url === '') {
            return null;
        }

        return [
            'label' => $name,
            'url' => $url,
            'description' => '',
        ];
    }

    private function splitParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', str_replace(["\r\n", "\r"], "\n", trim($text))) ?: [];
        return array_values(array_filter(array_map('trim', $paragraphs)));
    }

    private function formatLink(array $link): string
    {
        $line = '- [' . $this->escapeText((string) $link['label']) . '](' . $this->escapeUrl((string) $link['url']) . ')';
        $description = trim((string) ($link['description'] ?? ''));
        if ($description !== '') {
            $line .= ': ' . $this->escapeText($description);
        }

        return $line;
    }

    private function escapeText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($this->escaper->escapeHtml($value)), ENT_QUOTES | ENT_HTML5));
    }

    private function escapeUrl(string $value): string
    {
        return str_replace([' ', ')', '('], ['%20', '%29', '%28'], trim($value));
    }
}
