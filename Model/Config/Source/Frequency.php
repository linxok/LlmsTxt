<?php
namespace MyCompany\LlmsTxt\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Frequency implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'daily', 'label' => __('Daily')],
            ['value' => 'weekly', 'label' => __('Weekly')]
        ];
    }
}
