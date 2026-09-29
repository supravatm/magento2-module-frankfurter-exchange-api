<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Model;

use SMG\FrankfurterExchange\Api\ExchangeClientInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use SMG\FrankfurterExchange\Model\Config;
use RuntimeException;

class ExchangeClient implements ExchangeClientInterface
{
    private const CACHE_TAG = 'SMG_FRANKFURTER_EXCHANGE';
    private const CURRENCY_CACHE_KEY = 'smg_frankfurter_currencies';
    private const CURRENCY_CACHE_LIFETIME = 86400;

    /**
     * Construcotr
     *
     * @param Curl $curl
     * @param CacheInterface $cache
     * @param Json $json
     * @param Config $config
     */
    public function __construct(
        private readonly Curl $curl,
        private readonly CacheInterface $cache,
        private readonly Json $json,
        private readonly Config $config
    ) {
    }

    /**
     * Get supported currencies.
     *
     * @return array<string, string>
     */
    public function getCurrencies(): array
    {
        $cacheTags = [self::CACHE_TAG];
        $cacheKey = self::CURRENCY_CACHE_KEY . '_currencies';
        $cachedCurrencies = $this->cache->load($cacheKey);
        if ($cachedCurrencies !== false) {
            return $this->json->unserialize($cachedCurrencies);
        }
        $currencies = $this->request('/currencies');

        $this->cache->save(
            $this->json->serialize($currencies),
            $cacheKey,
            $cacheTags,
            self::CURRENCY_CACHE_LIFETIME
        );
        return $currencies;
    }

    /**
     * Get current exchange rate.
     *
     * @param string $from
     * @param string $to
     * @return array<string, mixed>
     */
    public function getRate(string $from, string $to): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        $storeCurrentDate = $this->config->getCurrentStoreDate();

        if ($from === $to) {
            return [
                'date' => $storeCurrentDate,
                'base' => $from,
                'quote' => $to,
                'rate' => 1.0
            ];
        }

        return $this->request(
            sprintf('/rate/%s/%s', $from, $to)
        );
    }

    /**
     * Get historical rates.
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
    ): array {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return [
                [
                    'date' => $startDate,
                    'base' => $from,
                    'quote' => $to,
                    'rate' => 1.0
                ],
                [
                    'date' => $endDate,
                    'base' => $from,
                    'quote' => $to,
                    'rate' => 1.0
                ]
            ];
        }

        $path = sprintf(
            '/rates?base=%s&quotes=%s&from=%s&to=%s',
            rawurlencode($from),
            rawurlencode($to),
            rawurlencode($startDate),
            rawurlencode($endDate)
        );

        return $this->request($path);
    }

    /**
     * Perform GET request.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    private function request(string $path): array
    {
        $this->curl->setTimeout(10);

        $this->curl->get($this->config->getApiEndpointUrl() . $path);

        $status = $this->curl->getStatus();
        $body = $this->curl->getBody();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                sprintf(
                    'Frankfurter API request failed. HTTP status: %s',
                    $status
                )
            );
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid response received from Frankfurter API.'
            );
        }

        return $data;
    }
}
