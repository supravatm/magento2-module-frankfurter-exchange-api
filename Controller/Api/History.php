<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Controller\Api;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use SMG\FrankfurterExchange\Api\ExchangeClientInterface;
use Magento\Framework\App\RequestInterface;
use Throwable;

/**
 * Class History
 *
 * API controller to fetch historical currency exchange rates.
 */
class History implements ActionInterface
{
    /**
     * Constructor
     *
     * @param ExchangeClientInterface $exchangeClient
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly ExchangeClientInterface $exchangeClient,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RequestInterface $request,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $from = strtoupper(trim((string) $this->getParam('from')));
        $to = strtoupper(trim((string) $this->getParam('to')));
        $days = (int) ($this->getParam('days') ?? 30);

        if (!$this->isValidCurrency($from) || !$this->isValidCurrency($to)) {
            return $this->errorResponse(
                400,
                'Invalid currency.'
            );
        }

        if (!in_array($days, [30, 90, 365], true)) {
            $days = 30;
        }

        try {
            $today = $this->timezone->date();
            $startDate = (clone $today)->modify(
                sprintf('-%d days', $days)
            );

            $rates = $this->exchangeClient->getHistory(
                $from,
                $to,
                $startDate->format('Y-m-d'),
                $today->format('Y-m-d')
            );

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => true,
                    'base' => $from,
                    'quote' => $to,
                    'days' => $days,
                    'rates' => $rates
                ]);
        } catch (Throwable $e) {
            return $this->errorResponse(
                500,
                'Unable to retrieve historical rates.'
            );
        }
    }

    /**
     * Retrieve request parameter by name.
     *
     * @param string $name
     * @return string|null
     */
    private function getParam(string $name): ?string
    {
        $param = $this->request->getParam($name);
        return $param !== null ? (string) $param : null;
    }

    /**
     * Validate currency code format.
     *
     * @param string $currency
     * @return bool
     */
    private function isValidCurrency(string $currency): bool
    {
        return (bool) preg_match('/^[A-Z]{3}$/', $currency);
    }
    /**
     * Generate error response.
     *
     * @param int $status
     * @param string $message
     * @return Json
     */
    private function errorResponse(
        int $status,
        string $message
    ): Json {
        return $this->resultJsonFactory
            ->create()
            ->setHttpResponseCode($status)
            ->setData([
                'success' => false,
                'message' => $message
            ]);
    }
}
