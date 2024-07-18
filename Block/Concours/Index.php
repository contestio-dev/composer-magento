<?php
declare(strict_types=1);

namespace Contestio\Connect\Block\Concours;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

class Index extends \Magento\Framework\View\Element\Template
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
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Curl $curlClient
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerSession $customerSession
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        Curl $curlClient,
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
        $this->curlClient = $curlClient;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    /**
     * Fetch data from API : get home,contest and survey buttons
     * 
     * And update customer pseudo and infos if needed
     *
     * @return array|false
     */
    public function buttonsAndUser()
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/org/header';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        // Request Headers
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $this->customerSession->getCustomerId()
        ];
                
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            return false;
        }

        // Decode JSON response
        $data = json_decode($response, true);

        // Return false if unable to decode JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Check if user in response is the same as the current user
        $user = $data['user'] ?? null;

        // Return the buttons data
        return array(
            'buttons' => $data && isset($data['buttons']) ? $data['buttons'] : [],
            'pseudo' => isset($user['pseudo']) ? $user['pseudo'] : null,
            'firstName' => isset($user['firstName']) ? $user['firstName'] : null,
            'user_id' => $this->customerSession->getCustomerId()
        );
    }
    
    /**
     * Fetch data from API
     *
     * @return array|false
     */
    public function fetchContentData($sortBy, $orderBy)
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/contests/' . $sortBy . '/' . $orderBy;
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $this->customerSession->getCustomerId()
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus === 401 || $httpStatus === 403) {
            return [
                'code' => 'AUTH_401',
            ];
        }

        if ($response === false) {
            return false;
        }

        // Decode JSON response
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        // Return false if unable to decode JSON
        return false;
    }

    /**
     * Fetch data from API
     *
     * @return array|false
     */
    public function fetchParticipationById($participationId)
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/participation/' . $participationId;
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $this->customerSession->getCustomerId()
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $apiUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_POSTFIELDS =>'',
          CURLOPT_HTTPHEADER =>$headers,
        ));

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus === 401 || $httpStatus === 403) {
            return [
                'code' => 'AUTH_401',
            ];
        }

        if ($response === false || $httpStatus !== 200) {
            return false;
        }

        // Decode JSON response
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        // Return false if unable to decode JSON
        return false;
    }

        /**
     * Fetch data from API
     *
     * @return array|false
     */
    public function fetchContentMetaDatas()
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/contests/metadatas';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus === 401 || $httpStatus === 403) {
            return [
                'code' => 'AUTH_401',
            ];
        }

        if ($response === false) {
            return false;
        }

        // Decode JSON response
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        // Return false if unable to decode JSON
        return false;
    }

    /**
     * Fetch data from API
     *
     * @return array|false
     */
    public function fetchParticipationByPseudo($pseudo)
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/participation/pseudo/' . $pseudo;
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $this->customerSession->getCustomerId()
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $apiUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_POSTFIELDS =>'',
          CURLOPT_HTTPHEADER =>$headers,
        ));

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus === 401 || $httpStatus === 403) {
            return [
                'code' => 'AUTH_401',
            ];
        }

        if ($response === false || $httpStatus !== 200) {
            return false;
        }

        // Decode JSON response
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        // Return false if unable to decode JSON
        return false;
    }
    
    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }
    
    // Method to retrieve configuration values
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
