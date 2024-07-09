<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

/**
 * Class Socialshare
 * @package Contestio\Connect\Controller\Ajax
 */
class Socialshare extends Action
{
    /**
     * @var Curl
     */
    protected $curlClient;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Socialshare constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Curl $curlClient
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        Curl $curlClient,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->httpContext = $httpContext;
        $this->curlClient = $curlClient;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute method to handle the social share AJAX request.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        $postdata = json_decode($this->getRequest()->getContent(), true);
        
        if (!isset($postdata['objectId']) || !isset($postdata['platform']) || !isset($postdata['action'])) {
            return $resultJson->setData(['success' => false, 'message' => 'Invalid objectId or platform or action']);
        }
        
        
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/activity/final-user';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
		/** @var CustomerInterface $customer */
		$customer = $this->customerSession->getCustomer();
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $customer->getId()
        ];

        $body = [
            'objectId' => $postdata['objectId'],
            'platform' => $postdata['platform'],
            'action' => $postdata['action']
        ];

        // Encode the body data as JSON
        $jsonBody = json_encode($body);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        curl_exec($curl);

        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $resultJson->setData([
            'success' => $httpStatus === 201
        ]);
    }
}
