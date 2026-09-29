<?php

declare(strict_types=1);

namespace SMG\FrankfurterExchange\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()
            ->getTitle()
            ->set('Currency Exchange');

        return $resultPage;
    }
}
