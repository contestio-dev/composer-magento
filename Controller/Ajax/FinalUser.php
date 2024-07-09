<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

/**
 * Class FinalUser
 * @package Contestio\Connect\Controller\Ajax
 */
class FinalUser extends Action
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * FinalUser constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Curl $curlClient
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        Curl $curlClient,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->httpContext = $httpContext;
        $this->curlClient = $curlClient;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute method to handle the social share AJAX request.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerSession->getCustomerDataObject();

        $resultJson = $this->resultJsonFactory->create();
        
        $postdata = json_decode($this->getRequest()->getContent(), true);
        
        if (!isset($postdata['handle']) || empty($postdata['handle'])) {
            return $resultJson->setData(['success' => false, 'message' => 'Invalid user pseudo']);
        }

        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/users/final/upsert';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret
        ];

        $body = [
            'externalId' => $customer->getId(),
            'email' => $customer->getEmail(),
            'pseudo' => $postdata['handle'],
            'fname' => $customer->getFirstName(),
            'lname' => $customer->getLastName()
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

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpStatus === 200 || $httpStatus === 201) {
            // Customer data has been successfully synced

            return $resultJson->setData([
                'success' => true,
                'message' => 'Sync with success',
                'response' => json_decode($response, true)
            ]);
        } else {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Error sync user',
                'response' => json_decode($response, true)
            ]);
        }
    }
}