<?php

namespace SwiftOtter\FriendRecommendations\Model\Resolver;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use SwiftOtter\FriendRecommendations\Api\RecommendationListRepositoryInterface;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListInterfaceFactory;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListProductInterfaceFactory;
use SwiftOtter\FriendRecommendations\Model\ResourceModel\RecommendationListProduct as RecommendationListProductResource;


class CreateRecommendationList implements ResolverInterface
{
    private RecommendationListRepositoryInterface $recommendationListRepository;
    private RecommendationListInterfaceFactory $recommendationListInterfaceFactory;
    private RecommendationListProductInterfaceFactory $recommendationListProductInterfaceFactory;
    private RecommendationListProductResource $recommendationListProductResource;

    /**
     * @param RecommendationListRepositoryInterface $recommendationListRepository
     * @param RecommendationListInterfaceFactory $recommendationListInterfaceFactory
     * @param RecommendationListProductInterfaceFactory $recommendationListProductInterfaceFactory
     * @param RecommendationListProductResource $recommendationListProductResource
     */
    public function __construct(
        RecommendationListRepositoryInterface $recommendationListRepository,
        RecommendationListInterfaceFactory $recommendationListInterfaceFactory,
        RecommendationListProductInterfaceFactory $recommendationListProductInterfaceFactory,
        RecommendationListProductResource $recommendationListProductResource
    ) {
        $this->recommendationListRepository = $recommendationListRepository;
        $this->recommendationListInterfaceFactory = $recommendationListInterfaceFactory;
        $this->recommendationListProductInterfaceFactory = $recommendationListProductInterfaceFactory;
        $this->recommendationListProductResource = $recommendationListProductResource;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var \SwiftOtter\FriendRecommendations\Api\Data\RecommendationListInterface $list */
        $list = $this->recommendationListInterfaceFactory->create();
        $list->setEmail($args['email'])
            ->setFriendName($args['friendName'])
            ->setTitle($args['title'] ?? '')
            ->setNote($args['note'] ?? '');

        $this->recommendationListRepository->save($list);

        $this->saveProductsToList((int)$list->getId(), $args['productSkus']);

        return [
            'email' => $list->getEmail(),
            'friendName' => $list->getFriendName(),
            'title' => $list->getTitle(),
            'note' => $list->getNote()
        ];
    }

    /**
     * @param int $listId
     * @param array $skus
     * @return void
     * @throws AlreadyExistsException
     */
    private function saveProductsToList(int $listId, array $skus)
    {
        foreach ($skus as $sku) {
            $item = $this->recommendationListProductInterfaceFactory->create();
            $item->setListId($listId)
                ->setSku($sku);
            $this->recommendationListProductResource->save($item);
        }
    }
}
