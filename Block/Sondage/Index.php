<?php
declare(strict_types=1);

namespace Contestio\Connect\Block\Sondage;

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
    public function fetchNavButtons()
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/header-buttons';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');

        /** @var CustomerInterface $customer */
		$customer = $this->customerSession->getCustomer();

        $userId = null;
        $handle = null;

		if ($customer) {
			// Get user data from the session
            $userId = $customer->getId();
            $handle = $customer->getData('contestio_pseudo');
		}
        
        // Request Headers
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret
        ];

        $body = [
            'userId' => $userId,
            'handle' => $handle
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
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => $jsonBody,
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
        $isLogged = $this->customerSession->isLoggedIn();

        // User not found in response, add it and reset local pseudo
        if ($isLogged && $user === null) {
            // Reset pseudo if exists
            if ($handle) {
                $customer->setData('contestio_pseudo', null);
                $customer->save();
            }

            $this->upsertFinalUser();
        } else if ($isLogged && $user !== null) {
            // If user.pseudo not exists or is different from the current pseudo, update it
            if (!isset($user['pseudo']) || $user['pseudo'] !== $handle) {
                // Reset pseudo if exists
                if ($handle) {
                    $customer->setData('contestio_pseudo', null);
                    $customer->save();
                }

                $this->upsertFinalUser();
            } else {
                // User found in response, check if the firstname, lastname and email are the same
                $customerFirstName = $customer->getFirstName();
                $customerLastName = $customer->getLastName();
                $customerEmail = $customer->getEmail();

                if ($user['firstName'] !== $customerFirstName || $user['lastName'] !== $customerLastName || $user['email'] !== $customerEmail) {
                    $this->upsertFinalUser();
                }
            }
        }
        
        // Return the buttons data
        return $data && isset($data['buttons']) ? $data['buttons'] : [];
    }
    
    /**
     * Fetch data from API
     *
     * @return array|false
     */
    public function fetchSurveyData()
    {
        // API URL
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/survey';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        // Request Headers
        $headers = [
            "clientKey" => $clientKey,
            "clientSecret" => $clientSecret,
            "x-userid" => $this->customerSession->getCustomerId() ?? null,
            "x-handle" => $this->customerSession->getCustomer()->getData('contestio_pseudo') ?? null
        ];

        // Set request headers
        $this->curlClient->setHeaders($headers);

        // Make GET request
        $this->curlClient->get($apiUrl);

        // Get response
        $response = $this->curlClient->getBody();

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

    public function getPseudo()
    {
        $customerNickName = null;

        if ($this->customerSession->isLoggedIn()) {
            $customerNickName = $this->customerSession->getCustomer()->getData('contestio_pseudo') ?? null;
        }
        
        return $customerNickName;
    }

    public function getFirstname()
    {
        $customerNickName = null;

        if ($this->customerSession->isLoggedIn()) {
            $customerNickName = $this->customerSession->getCustomer()->getFirstname() ?? null;
        }
        
        return $customerNickName;
    }
    
    // Method to retrieve configuration values
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function upsertFinalUser()
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerSession->getCustomerDataObject();

        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/user';
        
        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
        
        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret
        ];

        $fromContestio = $customer->getCustomAttribute('from_contestio') && $customer->getCustomAttribute('from_contestio')->getValue() === 1
            ? true
            : false;

        $pseudo = $customer->getCustomAttribute('contestio_pseudo') ? $customer->getCustomAttribute('contestio_pseudo')->getValue() : null;

        $body = [
            'externalId' => $customer->getId(),
            'email' => $customer->getEmail(),
            'pseudo' => $pseudo,
            'fname' => $customer->getFirstName(),
            'lname' => $customer->getLastName(),
            'isFromContestio' => $fromContestio,
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
        
        if ($httpStatus === 200) {
            return true;
        } else {
            return false;
        }
    }
}
