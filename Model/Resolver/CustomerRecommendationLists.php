<?php

namespace SwiftOtter\FriendRecommendations\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SwiftOtter\FriendRecommendations\Api\RecommendationListRepositoryInterface;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListInterface;

class CustomerRecommendationLists implements ResolverInterface
{
    private GetCustomer $getCustomer;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private RecommendationListRepositoryInterface $recommendationListRepository;

    /**
     * @param GetCustomer $getCustomer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RecommendationListRepositoryInterface $recommendationListRepository
     */
    public function __construct(
        GetCustomer $getCustomer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RecommendationListRepositoryInterface $recommendationListRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->recommendationListRepository = $recommendationListRepository;
    }

    /**
     * {@inheritdoc}
     * @param ContextInterface @context
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (!$context->getUserId()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customer = $this->getCustomer->execute($context);

        $this->searchCriteriaBuilder->addFilter('email', $customer->getEmail());
        $lists = $this->recommendationListRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $result = [];

        foreach ($lists as $list) {
            $result[] = $this->formatListData($list);
        }

        return $result;
    }

    /**
     * @param RecommendationListInterface $list
     * @return array
     */
    private function formatListData(RecommendationListInterface $list)
    {
        return [
            'id' => (int)$list->getId(),
            'friendName' => $list->getFriendName(),
            'title' => $list->getTitle(),
            'note' => $list->getNote()
        ];
    }
}
