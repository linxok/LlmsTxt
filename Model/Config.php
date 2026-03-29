<?php
namespace MyCompany\LlmsTxt\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    private const XML_PATH_ENABLED = 'llmstxt/general/enabled';
    private const XML_PATH_SITE_TITLE = 'llmstxt/general/site_title';
    private const XML_PATH_SUMMARY = 'llmstxt/general/summary';
    private const XML_PATH_INTRO = 'llmstxt/general/intro';
    private const XML_PATH_INCLUDE_CATEGORIES = 'llmstxt/general/include_categories';
    private const XML_PATH_INCLUDE_PRODUCTS = 'llmstxt/general/include_products';
    private const XML_PATH_INCLUDE_CMS_PAGES = 'llmstxt/general/include_cms_pages';
    private const XML_PATH_MAX_CATEGORIES = 'llmstxt/general/max_categories';
    private const XML_PATH_MAX_PRODUCTS = 'llmstxt/general/max_products';
    private const XML_PATH_MAX_CMS_PAGES = 'llmstxt/general/max_cms_pages';
    private const XML_PATH_FEATURED_CATEGORY_IDS = 'llmstxt/general/featured_category_ids';
    private const XML_PATH_FEATURED_PRODUCT_SKUS = 'llmstxt/general/featured_product_skus';
    private const XML_PATH_FEATURED_CMS_PAGE_IDENTIFIERS = 'llmstxt/general/featured_cms_page_identifiers';
    private const XML_PATH_MANUAL_LINKS = 'llmstxt/general/manual_links';
    private const XML_PATH_OPTIONAL_LINKS = 'llmstxt/general/optional_links';
    private const XML_PATH_CACHE_LIFETIME = 'llmstxt/general/cache_lifetime';
    private const XML_PATH_CRON_ENABLED = 'llmstxt/general/cron_enabled';
    private const XML_PATH_CRON_FREQUENCY = 'llmstxt/general/cron_frequency';
    private const XML_PATH_CRON_WEEKDAY = 'llmstxt/general/cron_weekday';
    private const XML_PATH_CRON_TIME = 'llmstxt/general/cron_time';
    private const XML_PATH_CRON_SCHEDULE = 'llmstxt/general/cron_schedule';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->isFlagSet(self::XML_PATH_ENABLED, $storeId);
    }

    public function getSiteTitle(?int $storeId = null): string
    {
        $value = trim((string) $this->getValue(self::XML_PATH_SITE_TITLE, $storeId));
        if ($value !== '') {
            return $value;
        }

        return (string) $this->storeManager->getStore($storeId)->getName();
    }

    public function getSummary(?int $storeId = null): string
    {
        return trim((string) $this->getValue(self::XML_PATH_SUMMARY, $storeId));
    }

    public function getIntro(?int $storeId = null): string
    {
        return trim((string) $this->getValue(self::XML_PATH_INTRO, $storeId));
    }

    public function shouldIncludeCategories(?int $storeId = null): bool
    {
        return $this->isFlagSet(self::XML_PATH_INCLUDE_CATEGORIES, $storeId);
    }

    public function shouldIncludeProducts(?int $storeId = null): bool
    {
        return $this->isFlagSet(self::XML_PATH_INCLUDE_PRODUCTS, $storeId);
    }

    public function shouldIncludeCmsPages(?int $storeId = null): bool
    {
        return $this->isFlagSet(self::XML_PATH_INCLUDE_CMS_PAGES, $storeId);
    }

    public function getMaxCategories(?int $storeId = null): int
    {
        return max(1, (int) $this->getValue(self::XML_PATH_MAX_CATEGORIES, $storeId));
    }

    public function getMaxProducts(?int $storeId = null): int
    {
        return max(1, (int) $this->getValue(self::XML_PATH_MAX_PRODUCTS, $storeId));
    }

    public function getMaxCmsPages(?int $storeId = null): int
    {
        return max(1, (int) $this->getValue(self::XML_PATH_MAX_CMS_PAGES, $storeId));
    }

    public function getFeaturedCategoryIds(?int $storeId = null): array
    {
        return $this->parseList((string) $this->getValue(self::XML_PATH_FEATURED_CATEGORY_IDS, $storeId));
    }

    public function getFeaturedProductSkus(?int $storeId = null): array
    {
        return $this->parseList((string) $this->getValue(self::XML_PATH_FEATURED_PRODUCT_SKUS, $storeId));
    }

    public function getFeaturedCmsPageIdentifiers(?int $storeId = null): array
    {
        return $this->parseList((string) $this->getValue(self::XML_PATH_FEATURED_CMS_PAGE_IDENTIFIERS, $storeId));
    }

    public function getManualLinks(?int $storeId = null): array
    {
        return $this->parseLinks((string) $this->getValue(self::XML_PATH_MANUAL_LINKS, $storeId));
    }

    public function getOptionalLinks(?int $storeId = null): array
    {
        return $this->parseLinks((string) $this->getValue(self::XML_PATH_OPTIONAL_LINKS, $storeId));
    }

    public function getCacheLifetime(?int $storeId = null): int
    {
        return max(0, (int) $this->getValue(self::XML_PATH_CACHE_LIFETIME, $storeId));
    }

    public function isCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CRON_ENABLED);
    }

    public function getCronFrequency(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_CRON_FREQUENCY)) ?: 'daily';
    }

    public function getCronWeekday(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_CRON_WEEKDAY);
    }

    public function getCronTime(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_CRON_TIME)) ?: '02:00';
    }

    public function getCronSchedule(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_CRON_SCHEDULE));
    }

    private function getValue(string $path, ?int $storeId = null): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function isFlagSet(string $path, ?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function parseLinks(string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $label = $parts[0] ?? '';
            $url = $parts[1] ?? '';
            $description = $parts[2] ?? '';

            if ($label === '' || $url === '') {
                continue;
            }

            $result[] = [
                'label' => $label,
                'url' => $url,
                'description' => $description,
            ];
        }

        return $result;
    }

    private function parseList(string $value): array
    {
        $normalized = str_replace(["\r\n", "\r", "\n", ';'], ',', $value);
        $items = array_map('trim', explode(',', $normalized));

        return array_values(array_filter(array_unique($items), static fn (string $item): bool => $item !== ''));
    }
}
