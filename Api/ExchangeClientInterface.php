<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Api;

interface ExchangeClientInterface
{
    /**
     * Get supported currencies.
     *
     * @return array<string, string>
     */
    public function getCurrencies(): array;

    /**
     * Get current exchange rate.
     *
     * @param string $from
     * @param string $to
     * @return array<string, mixed>
     */
    public function getRate(string $from, string $to): array;

    /**
     * Get historical exchange rates.
     *
     * @param string $from
     * @param string $to
     * @param string $startDate
     * @param string $endDate
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(
        string $from,
        string $to,
        string $startDate,
        string $endDate
    ): array;
}
