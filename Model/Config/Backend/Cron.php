<?php
namespace MyCompany\LlmsTxt\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Store\Model\ScopeInterface;

class Cron extends Value
{
    private const CRON_STRING_PATH = 'llmstxt/general/cron_schedule';
    private const CRON_ENABLED_PATH = 'llmstxt/general/cron_enabled';

    private ValueFactory $valueFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $valueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->valueFactory = $valueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave(): self
    {
        $groups = $this->getData('groups') ?: [];
        $group = $groups['general']['fields'] ?? [];

        $frequency = $group['cron_frequency']['value'] ?? 'daily';
        $time = $group['cron_time']['value'] ?? '00:00';
        $weekday = $group['cron_weekday']['value'] ?? '1';
        $enabled = (string) ($group['cron_enabled']['value'] ?? '0');

        [$hour, $minute] = array_pad(explode(':', (string) $time), 2, '0');

        $cronExpression = match ($frequency) {
            'weekly' => sprintf('%d %d * * %s', (int) $minute, (int) $hour, $weekday),
            default => sprintf('%d %d * * *', (int) $minute, (int) $hour),
        };

        $this->saveConfigValue(self::CRON_STRING_PATH, $cronExpression);
        $this->saveConfigValue(self::CRON_ENABLED_PATH, $enabled);

        return parent::afterSave();
    }

    private function saveConfigValue(string $path, string $value): void
    {
        $this->valueFactory->create()
            ->load($path, 'path')
            ->setPath($path)
            ->setValue($value)
            ->setScope(ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
            ->setScopeId(0)
            ->save();
    }
}
