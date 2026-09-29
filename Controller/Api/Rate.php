<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Controller\Api;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use SMG\FrankfurterExchange\Api\ExchangeClientInterface;
use Magento\Framework\App\RequestInterface;
use Throwable;

/**
 * Class Rate
 *
 * API controller to fetch current currency exchange rate.
 */
class Rate implements ActionInterface
{
    /**
     * Constructor
     *
     * @param ExchangeClientInterface $exchangeClient
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly ExchangeClientInterface $exchangeClient,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $from = strtoupper(trim((string) $this->getParam('from')));
        $to = strtoupper(trim((string) $this->getParam('to')));

        if (!$this->isValidCurrency($from) || !$this->isValidCurrency($to)) {
            return $this->resultJsonFactory
                ->create()
                ->setHttpResponseCode(400)
                ->setData([
                    'success' => false,
                    'message' => 'Invalid currency.'
                ]);
        }

        try {
            $rate = $this->exchangeClient->getRate($from, $to);

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => true,
                    'data' => $rate
                ]);
        } catch (Throwable $e) {
            return $this->resultJsonFactory
                ->create()
                ->setHttpResponseCode(500)
                ->setData([
                    'success' => false,
                    'message' => 'Unable to retrieve exchange rate.'
                ]);
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
}
