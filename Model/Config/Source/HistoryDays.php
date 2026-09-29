<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for historical days configuration options.
 */
class HistoryDays implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs.
     *
     * @return array<int, array{value: int, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 30,
                'label' => __('30 Days')
            ],
            [
                'value' => 90,
                'label' => __('90 Days')
            ],
            [
                'value' => 365,
                'label' => __('1 Year')
            ]
        ];
    }
}
