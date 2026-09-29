<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Controller\Api;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use SMG\FrankfurterExchange\Api\ExchangeClientInterface;
use Throwable;

class Currencies implements ActionInterface
{
    /**
     * Constructor.
     *
     * @param ExchangeClientInterface $exchangeClient
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        private readonly ExchangeClientInterface $exchangeClient,
        private readonly JsonFactory $resultJsonFactory
    ) {
        //
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        try {
            $currencies = $this->exchangeClient->getCurrencies();

            return $this->resultJsonFactory
                ->create()
                ->setData([
                    'success' => true,
                    'currencies' => $currencies
                ]);
        } catch (Throwable $e) {
            return $this->resultJsonFactory
                ->create()
                ->setHttpResponseCode(500)
                ->setData([
                    'success' => false,
                    'message' => 'Unable to load currencies.'
                ]);
        }
    }
}
