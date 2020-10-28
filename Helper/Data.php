<?php
namespace AHT\ReviewImage\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
        parent::__construct($context);
    }

    public function getData($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}