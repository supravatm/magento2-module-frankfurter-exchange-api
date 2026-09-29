<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use SMG\FrankfurterExchange\Model\Config;

/**
 * ConfigProvider Class
 *
 * ConfigProvider ViewModel to provide configuration values to templates.
 */
class ConfigProvider implements ArgumentInterface
{
    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Get default Form-Currency.
     *
     * @return string
     */
    public function getDefaultFromCurrency(): string
    {
        return $this->config->getDefaultFromCurrency();
    }

    /**
     * Get default to-Currency.
     *
     * @return string
     */
    public function getDefaultToCurrency(): string
    {
        return $this->config->getDefaultToCurrency();
    }

    /**
     * Get default HistoryDays.
     *
     * @return int
     */
    public function getDefaultHistoryDays(): int
    {
        return $this->config->getDefaultHistoryDays();
    }

    /**
     * Get current store date.
     *
     * @return string
     */
    public function getCurrentStoreDate(): string
    {
        return $this->config->getCurrentStoreDate();
    }
}
