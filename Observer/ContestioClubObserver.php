<?php

namespace Contestio\Connect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Contestio\Connect\Helper\Data as Helper;
use Magento\Framework\Event\Observer;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Class ContestioClubObserver
 *
 * @package Contestio\Connect\Observer
 */
class ContestioClubObserver implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * ContestioClubObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param Helper $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        LoggerInterface $logger,
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        Helper $helper,
        CustomerRepositoryInterface $customerRepository,
        CookieManagerInterface $cookieManager
    ) {
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Execute observer method.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var CustomerInterface $customer */
        $customer = $observer->getCustomer();
        $cookieValue = $this->cookieManager->getCookie('contestioclub');

        try {
            if ($cookieValue) {
                $customer->setCustomAttribute('from_contestio', 1);
                $this->customerRepository->save($customer);

                $pseudo = $customer->getCustomAttribute('contestio_pseudo') && $customer->getCustomAttribute('contestio_pseudo')->getValue() !== 'null'
                    ? $customer->getCustomAttribute('contestio_pseudo')->getValue()
                    : "";

                $payload = [
                    'externalId' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'pseudo' => $pseudo,
                    'fname' => $customer->getFirstName(),
                    'lname' => $customer->getLastName(),
                    'isFromContestio' => true
                ];
                $response = $this->postCustomerData($payload);
                $this->logger->info('cabesto club customer #' . $customer->getId() . ' registration post data: ' . json_encode($payload));
                $this->logger->info('cabesto club customer #' . $customer->getId() . ' registration data submit response: ' . $response);
            }
        } catch (\Exception $e) {
            $this->logger->error('cabesto club customer generation error: ' . $e->getMessage());
        }
    }

    // /**
    //  * Calculate age based on DOB.
    //  *
    //  * @param string $dob
    //  * @return int
    //  */
    // private function calculateAge($dob)
    // {
    //     if (!$dob) {
    //         return "";
    //     }

    //     $dobDate = new \DateTime($dob);
    //     $now = new \DateTime();
    //     $age = $now->diff($dobDate)->y;

    //     return $age;
    // }

    /**
     * Post customer data to external API.
     *
     * @param array $payload
     * @return string
     */
    public function postCustomerData($payload)
    {
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/user';

        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');

        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret
        ];

        // Encode the body data as JSON
        $jsonBody = json_encode($payload);

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

        return $response;
    }
}
