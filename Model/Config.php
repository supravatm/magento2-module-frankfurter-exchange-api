<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_DEFAULT_FROM = 'frankfurter_exchange/general/default_from_currency';
    private const XML_PATH_DEFAULT_TO = 'frankfurter_exchange/general/default_to_currency';
    private const XML_PATH_DEFAULT_HISTORY_DAYS = 'frankfurter_exchange/general/default_history_days';
    private const API_URL = 'https://api.frankfurter.dev/v2';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Get default From-Currency code.
     *
     * @return string
     */
    public function getDefaultFromCurrency(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_FROM,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get default To-Currency code.
     *
     * @return string
     */
    public function getDefaultToCurrency(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_TO,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get default history days.
     *
     * @return int
     */
    public function getDefaultHistoryDays(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_HISTORY_DAYS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Base API Endpoint.
     *
     * @return string
     */
    public function getApiEndpointUrl(): string
    {
        return self::API_URL;
    }

    /**
     * Get current store date.
     *
     * @return string
     */
    public function getCurrentStoreDate(): string
    {
        return $this->timezone->date()->format('Y-m-d');
    }
}
