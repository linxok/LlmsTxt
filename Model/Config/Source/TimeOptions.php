<?php
namespace MyCompany\LlmsTxt\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TimeOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = [];

        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $options[] = [
                    'value' => $time,
                    'label' => $time,
                ];
            }
        }

        return $options;
    }
}
