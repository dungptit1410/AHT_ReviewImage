<?php

namespace AHT\ReviewImage\Observer;

class ReviewSave implements \Magento\Framework\Event\ObserverInterface
{
    protected $_request;
    protected $logger;
    protected $reviewFactory;
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \AHT\ReviewImage\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_request = $request;
        $this->reviewFactory = $reviewFactory;
    }

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
        $review = $observer->getObject();
        //$review = $this->reviewFactory->create()->load(362);
        $data = $this->_request->getParams();
        $i = 1;
        $images = [];
        while(true){
            if(isset($data['images'.$i])){
                if( isset($data['images'.$i]['delete']) == false ){
                    $images []= str_replace('review_image','',$data['images'.$i]['value']);
                }
                $i++;
            }
            else{
                break;
            }
        }
        //$data['images'] = json_encode($images);
        $review->setImages(json_encode($images));
        return $this;
        /* $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($review->getId()); */
	}
}