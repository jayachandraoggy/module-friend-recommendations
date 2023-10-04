<?php

namespace SwiftOtter\FriendRecommendations\Model\Resolver\DataProvider\CustomerRecommendationLists;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\ImageFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Image\Placeholder as PlaceholderProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListProductInterface;
use SwiftOtter\FriendRecommendations\Api\RecommendationListProductRepositoryInterface;

class Products
{
    private RecommendationListProductRepositoryInterface $listProductRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ProductCollectionFactory $productCollectionFactory;
    private ImageFactory $productImageFactory;
    private PlaceholderProvider $placeholderProvider;

    private $placeholderCache = [];
    private $listIds = [];
    private $storage;

    /**
     * @param RecommendationListProductRepositoryInterface $listProductRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ImageFactory $productImageFactory
     * @param PlaceholderProvider $placeholderProvider
     */
    public function __construct(
        RecommendationListProductRepositoryInterface $listProductRepository,
        SearchCriteriaBuilder                        $searchCriteriaBuilder,
        ProductCollectionFactory                     $productCollectionFactory,
        ImageFactory                                 $productImageFactory,
        PlaceholderProvider                          $placeholderProvider
    ) {
        $this->listProductRepository = $listProductRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productImageFactory = $productImageFactory;
        $this->placeholderProvider = $placeholderProvider;
    }

    /**
     * @param int $listId
     * @return $this
     */
    public function addListIdFilter(int $listId): Products
    {
        $this->listIds[] = $listId;
        return $this;
    }

    /**
     * @param int $listId
     * @return array|null
     * @throws \Exception
     */
    public function getListProducts(int $listId): ?array
    {
        if ($this->storage === null) {
            $this->storage = [];

            $listProducts = $this->getFilteredListProducts();

            // Load all relevant product models
            $skus = [];
            foreach ($listProducts as $listProduct) {
                $skus[] = $listProduct->getSku();
            }
            $skus = array_unique($skus);

            /** @var ProductCollection $productCollection */
            $productCollection = $this->productCollectionFactory->create();
            /** @var ProductInterface[] $products */
            $products = $productCollection->addAttributeToSelect(['name', 'thumbnail'])
                ->addFieldToFilter('sku', ['in' => $skus])
                ->getItems();

            // Prepare sku => productData array
            $productDataBySku = [];
            foreach ($products as $product) {
                $productDataBySku[$product->getSku()] = $this->formatProductData($product);
            }

            // Product data by listId storage
            foreach ($listProducts as $listProduct) {
                if (isset($productDataBySku[$listProduct->getSku()])) {
                    $this->storage[$listProduct->getListId()][] = $productDataBySku[$listProduct->getSku()];
                }
            }
        }
        return $this->storage[$listId] ?? [];
    }

    /**
     * @return RecommendationListProductInterface[]
     */
    private function getFilteredListProducts(): array
    {
        if (empty($this->listIds)) {
            return [];
        }

        $listIds = array_unique($this->listIds);
        $this->searchCriteriaBuilder->addFilter('list_id', $listIds, 'in');
        return $this->listProductRepository->getList($this->searchCriteriaBuilder->create())->getItems();
    }

    /**
     * @param ProductInterface $product
     * @return array
     * @throws \Exception
     */
    private function formatProductData(
        ProductInterface $product
    ) {
        return [
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'thumbnailUrl' => $this->getImageUrl('thumbnail', $product->getData('thumbnail'))
        ];
    }

    /**
     * Get image URL
     *
     * @param string $imageType
     * @param string|null $imagePath
     * @return string
     * @throws \Exception
     */
    private function getImageUrl(string $imageType, ?string $imagePath): string
    {
        if (empty($imagePath) && !empty($this->placeholderCache[$imageType])) {
            return $this->placeholderCache[$imageType];
        }
        $image = $this->productImageFactory->create();
        $image->setDestinationSubdir($imageType)
            ->setBaseFile($imagePath);

        if ($image->isBaseFilePlaceholder()) {
            $this->placeholderCache[$imageType] = $this->placeholderProvider->getPlaceholder($imageType);
            return $this->placeholderCache[$imageType];
        }

        return $image->getUrl();
    }
}
