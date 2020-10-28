<?php

namespace AHT\ReviewImage\Plugin;

class ReviewSavePlugin
{
    protected $_request;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->_request = $request;
    }


	public function beforeSave(\Magento\Framework\Model\AbstractModel $subject)
	{
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
        $subject->setImages(json_encode($images));
	}

}