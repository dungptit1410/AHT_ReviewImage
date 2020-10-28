<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AHT\ReviewImage\Block\Adminhtml\Edit;
use \Magento\Review\Block\Adminhtml\Edit\Form as FormMagento;
/**
 * Adminhtml Review Edit Form
 */
class Form extends FormMagento
{
    /**
     * Prepare edit review form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected $helper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Review\Helper\Data $reviewData,
        \AHT\ReviewImage\Helper\Data $helper,
        array $data = []
    ){
        $this->helper = $helper;
        parent::__construct(
            $context, 
            $registry, 
            $formFactory, 
            $systemStore, 
            $customerRepository,
            $productFactory,
            $reviewData,
            $data
        );
    }

    protected function _prepareForm()
    {
        $config = $this->helper->getData('reviewimage/reviewimagepage/is_enabled');
        $review = $this->_coreRegistry->registry('review_data');
        $product = $this->_productFactory->create()->load($review->getEntityPkValue());

        $formActionParams = [
            'id' => $this->getRequest()->getParam('id'),
            'ret' => $this->_coreRegistry->registry('ret')
        ];
        if ($this->getRequest()->getParam('productId')) {
            $formActionParams['productId'] = $this->getRequest()->getParam('productId');
        }
        if ($this->getRequest()->getParam('customerId')) {
            $formActionParams['customerId'] = $this->getRequest()->getParam('customerId');
        }
        $reviewImages = json_decode($review->getImages());
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl(
                        'review/*/save',
                        $formActionParams
                    ),
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'review_details',
            ['legend' => __('Review Details'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'product_name',
            'note',
            [
                'label' => __('Product'),
                'text' => '<a href="' . $this->getUrl(
                    'catalog/product/edit',
                    ['id' => $product->getId()]
                ) . '" onclick="this.target=\'blank\'">' . $this->escapeHtml(
                    $product->getName()
                ) . '</a>'
            ]
        );

        try {
            $customer = $this->customerRepository->getById($review->getCustomerId());
            $customerText = __(
                '<a href="%1" onclick="this.target=\'blank\'">%2 %3</a> <a href="mailto:%4">(%4)</a>',
                $this->getUrl('customer/index/edit', ['id' => $customer->getId(), 'active_tab' => 'review']),
                $this->escapeHtml($customer->getFirstname()),
                $this->escapeHtml($customer->getLastname()),
                $this->escapeHtml($customer->getEmail())
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customerText = ($review->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                ? __('Administrator') : __('Guest');
        }

        $fieldset->addField('customer', 'note', ['label' => __('Author'), 'text' => $customerText]);

        $fieldset->addField(
            'summary-rating',
            'note',
            [
                'label' => __('Summary Rating'),
                'text' => $this->getLayout()->createBlock(
                    \Magento\Review\Block\Adminhtml\Rating\Summary::class
                )->toHtml()
            ]
        );

        $fieldset->addField(
            'detailed-rating',
            'note',
            [
                'label' => __('Detailed Rating'),
                'required' => true,
                'text' => '<div id="rating_detail">' . $this->getLayout()->createBlock(
                    \Magento\Review\Block\Adminhtml\Rating\Detailed::class
                )->toHtml() . '</div>'
            ]
        );

        $fieldset->addField(
            'status_id',
            'select',
            [
                'label' => __('Status'),
                'required' => true,
                'name' => 'status_id',
                'values' => $this->_reviewData->getReviewStatusesOptionArray()
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->hasSingleStore()) {
            $field = $fieldset->addField(
                'select_stores',
                'multiselect',
                [
                    'label' => __('Visibility'),
                    'required' => true,
                    'name' => 'stores[]',
                    'values' => $this->_systemStore->getStoreValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
            $review->setSelectStores($review->getStores());
        } else {
            $fieldset->addField(
                'select_stores',
                'hidden',
                ['name' => 'stores[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $review->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'nickname',
            'text',
            ['label' => __('Nickname'), 'required' => true, 'name' => 'nickname']
        );

        $fieldset->addField(
            'title',
            'text',
            ['label' => __('Summary of Review'), 'required' => true, 'name' => 'title']
        );

        $fieldset->addField(
            'detail',
            'textarea',
            ['label' => __('Review'), 'required' => true, 'name' => 'detail', 'style' => 'height:24em;']
        );
        $reviewData = $review->getData();
        if($config){
            if($reviewImages != null){
                for($i = 1; $i <= count($reviewImages); $i++){
                    $reviewData['images'.$i] = 'review_image'.$reviewImages[$i-1];
                    $fieldset->addField(
                        'images'.$i,
                        'image',
                        ['label' => __('Image'), 'required' => true, 'name' => 'images'.$i]
                    );
                }
            }
        }

        $form->setUseContainer(true);
        $form->setValues($reviewData);
        $this->setForm($form);
        return \Magento\Backend\Block\Widget\Form\Generic::_prepareForm();
    }
}
