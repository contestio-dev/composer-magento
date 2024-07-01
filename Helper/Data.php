<?php
namespace Contestio\Connect\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_API_BASE_URL = 'contestio_connect/api/base_url';

    public function getApiBaseUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_BASE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }
}