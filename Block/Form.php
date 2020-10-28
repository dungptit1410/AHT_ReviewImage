<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace AHT\ReviewImage\Block;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Context;
use Magento\Customer\Model\Url;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;

/**
 * Review form block
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * Review data
     *
     * @var \Magento\Review\Helper\Data
     */
    protected $_reviewData = null;

    /**
     * Catalog product model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * Message manager interface
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    public $_storeManager;

    protected $_reviewCollection; 

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;
    
    private $helper;

    /**
     * Form constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Review\Helper\Data $reviewData
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Url $customerUrl
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @throws \RuntimeException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Review\Helper\Data $reviewData,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \AHT\ReviewImage\Model\ResourceModel\Review\CollectionFactory $reviewCollection,
        \AHT\ReviewImage\Helper\Data $helper,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->urlEncoder = $urlEncoder;
        $this->_reviewData = $reviewData;
        $this->productRepository = $productRepository;
        $this->_ratingFactory = $ratingFactory;
        $this->messageManager = $messageManager;
        $this->httpContext = $httpContext;
        $this->customerUrl = $customerUrl;
        $this->_storeManager = $storeManager;
        $this->_reviewCollection = $reviewCollection;
        $this->helper = $helper;
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * Initialize review form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setAllowWriteReviewFlag(
            $this->httpContext->getValue(Context::CONTEXT_AUTH)
            || $this->_reviewData->getIsGuestAllowToWrite()
        );
        if (!$this->getAllowWriteReviewFlag()) {
            $queryParam = $this->urlEncoder->encode(
                $this->getUrl('*/*/*', ['_current' => true]) . '#review-form'
            );
            $this->setLoginLink(
                $this->getUrl(
                    'customer/account/login/',
                    [Url::REFERER_QUERY_PARAM_NAME => $queryParam]
                )
            );
        }

        $this->setTemplate('Magento_Review::form.phtml');
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        return $this->serializer->serialize($this->jsLayout);
    }

    /**
     * Get product info
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductInfo()
    {
        return $this->productRepository->getById(
            $this->getProductId(),
            false,
            $this->_storeManager->getStore()->getId()
        );
    }
    public function getImageReviewsByProductId()
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $reviewCollection = $this->_reviewCollection->create()
                        ->addStatusFilter(
                            \Magento\Review\Model\Review::STATUS_APPROVED
                        )->addEntityFilter(
                            'product',
                            $this->getProductId()
                        )->setDateOrder();
        $images = [];
        foreach ($reviewCollection as $review) {
            $imgReview = json_decode($review->getData()['images']);
                if($imgReview != null){  
                    for($i=0;$i < count($imgReview);$i++){
                            $images[] = $mediaUrl.'review_image'.$imgReview[$i];
                        } 
                    }
        }
        return $images;
    }

    public function getValueConfig() {
        return $this->helper->getData('reviewimage/reviewimagepage/is_enabled');
    }
    
    /**
     * Get review product post action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getUrl(
            'review_img/product/post',
            [
                '_secure' => $this->getRequest()->isSecure(),
                'id' => $this->getProductId(),
            ]
        );
    }

    /**
     * Get collection of ratings
     *
     * @return RatingCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRatings()
    {
        return $this->_ratingFactory->create()->getResourceCollection()->addEntityFilter(
            'product'
        )->setPositionOrder()->addRatingPerStoreName(
            $this->_storeManager->getStore()->getId()
        )->setStoreFilter(
            $this->_storeManager->getStore()->getId()
        )->setActiveFilter(
            true
        )->load()->addOptionToItems();
        //echo "<pre>"; print_r($m->debug()); die("dead");
    }

    /**
     * Return register URL
     *
     * @return string
     */
    public function getRegisterUrl()
    {
        return $this->customerUrl->getRegisterUrl();
    }

    /**
     * Get review product id
     *
     * @return int
     */
    protected function getProductId()
    {
        return $this->getRequest()->getParam('id', false);
    }
}
