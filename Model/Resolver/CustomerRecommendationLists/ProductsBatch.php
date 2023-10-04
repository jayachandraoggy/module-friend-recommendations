<?php

namespace SwiftOtter\FriendRecommendations\Model\Resolver\CustomerRecommendationLists;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use SwiftOtter\FriendRecommendations\Model\Resolver\DataProvider\CustomerRecommendationLists\Products as ProductsProvider;

class ProductsBatch implements BatchResolverInterface
{
    private BatchResponseFactory $batchResponseFactory;
    private ProductsProvider $productsProvider;

    public function __construct(
        BatchResponseFactory $batchResponseFactory,
        ProductsProvider     $productsProvider
    ) {
        $this->batchResponseFactory = $batchResponseFactory;
        $this->productsProvider = $productsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        ContextInterface $context,
        Field            $field,
        array            $requests
    ): BatchResponse {
        /** @var Batchresponse $response */
        $response = $this->batchResponseFactory->create();

        foreach ($requests as $request) {
            $value = $request->getValue();
            if (!isset($value['id'])) {
                continue;
            }
            $this->productsProvider->addListIdFilter((int)$value['id']);
        }

        foreach ($requests as $request) {
            $value = $request->getValue();
            if (!isset($value['id'])) {
                continue;
            }

            $listProducts = $this->productsProvider->getListProducts((int)$value['id']);
            $response->addResponse($request, $listProducts);
        }
        return $response;
    }
}
