<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace AHT\ReviewImage\Controller\Product;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use AHT\ReviewImage\Controller\Product as ProductController;
use Magento\Framework\Controller\ResultFactory;
use Magento\Review\Model\Review;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Post
 */
class Post extends ProductController implements HttpPostActionInterface
{
    /**
     * Submit new review action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $number_img = $this->helper->getData('reviewimage/reviewimagepage/number_images');
        $images = $this->getRequest()->getFiles('images');
        if(count($images) <= $number_img){
            $list_img = array();
            if(count($images)){
                $i = 0;
                foreach ($images as $files) {
                if (isset($files['tmp_name']) && strlen($files['tmp_name']) > 0) {
                    $uploaderFactory = $this->uploaderFactory->create(['fileId' => $images[$i]]);
                    $uploaderFactory->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                    $imageAdapter = $this->adapterFactory->create();
                    $uploaderFactory->addValidateCallback('custom_image_upload',
                    $imageAdapter,'validateUploadFile');
                    $uploaderFactory->setAllowRenameFiles(true);
                    $uploaderFactory->setFilesDispersion(true);
                    $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                    $destinationPath = $mediaDirectory->getAbsolutePath('review_image');
                    $result = $uploaderFactory->save($destinationPath);
                    $list_img[] = $result['file'];
                    if (!$result) {
                        throw new LocalizedException(
                            __('File cannot be saved to path: $1', $destinationPath)
                        );
                    }
                
                }
                    $i++;
                }
            } 
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            }

            $data = $this->reviewSession->getFormData(true);
            if ($data) {
                $rating = [];
                if (isset($data['ratings']) && is_array($data['ratings'])) {
                    $rating = $data['ratings'];
                }
            } else {
                $data = $this->getRequest()->getPostValue();
                $rating = $this->getRequest()->getParam('ratings', []);
            }
            if (($product = $this->initProduct()) && !empty($data)) {
                /** @var \Magento\Review\Model\Review $review */
                $data['images'] =  json_encode($list_img);
                $review = $this->reviewFactory->create()->setData($data);
                $review->unsetData('review_id');

                $validate = $review->validate();
                if ($validate === true) {
                    try {
                        $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                            ->setEntityPkValue($product->getId())
                            ->setStatusId(Review::STATUS_PENDING)
                            ->setCustomerId($this->customerSession->getCustomerId())
                            ->setStoreId($this->storeManager->getStore()->getId())
                            ->setStores([$this->storeManager->getStore()->getId()])
                            ->save();

                        foreach ($rating as $ratingId => $optionId) {
                            $this->ratingFactory->create()
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->setCustomerId($this->customerSession->getCustomerId())
                                ->addOptionVote($optionId, $product->getId());
                        }

                        $review->aggregate();
                        $this->messageManager->addSuccessMessage(__('You submitted your review for moderation.'));
                    } catch (\Exception $e) {
                        $this->reviewSession->setFormData($data);
                        $this->messageManager->addErrorMessage(__('We can\'t post your review right now.'));
                    }
                } else {
                    $this->reviewSession->setFormData($data);
                    if (is_array($validate)) {
                        foreach ($validate as $errorMessage) {
                            $this->messageManager->addErrorMessage($errorMessage);
                        }
                    } else {
                        $this->messageManager->addErrorMessage(__('We can\'t post your review right now.'));
                    }
                }
            }
                $redirectUrl = $this->reviewSession->getRedirectUrl(true);
                $resultRedirect->setUrl($redirectUrl ?: $this->_redirect->getRedirectUrl());
                return $resultRedirect;
        }
        else{
            $this->messageManager->addErrorMessage(__('Maximun of images upload is '.$number_img));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirectUrl = $this->reviewSession->getRedirectUrl(true);
            $resultRedirect->setUrl($redirectUrl ?: $this->_redirect->getRedirectUrl());
            return $resultRedirect;
        }
    }
}