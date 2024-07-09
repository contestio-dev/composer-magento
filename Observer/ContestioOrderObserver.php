<?php

namespace Contestio\Connect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Contestio\Connect\Helper\Data as Helper;

/**
 * Class ContestioOrderObserver
 *
 * Observes order placement and sends order data to an external API if the customer comes from Contestio.
 *
 * @package Contestio\Connect\Observer
 */
class ContestioOrderObserver implements ObserverInterface
{
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * ContestioOrderObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param Helper $helper
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        LoggerInterface $logger,
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        Helper $helper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Executes the observer.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getOrder();

            if (!$order) {
                throw new \Exception('Order object is null');
            }

            $customerId = $order->getCustomerId();
            if ($customerId) {
                // $customer = $this->customerRepository->getById($customerId);
                // $from_contestio = $customer->getCustomAttribute('from_contestio');
                // if ($from_contestio && $from_contestio->getValue() === '1') {
                //     $payload = [
                //         'userId' => $customerId,
                //         'amount' => $order->getSubtotal(),
                //         'currency' => $order->getOrderCurrencyCode()
                //     ];

                //     $this->postOrderData($payload);
                // }

                $payload = [
                    'userId' => $customerId,
                    'amount' => $order->getSubtotal(),
                    'currency' => $order->getOrderCurrencyCode()
                ];

                $this->postOrderData($payload);
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Sends the order data to the external API.
     *
     * @param array $payload
     * @return mixed
     */
    public function postOrderData($payload)
    {
        $apiUrl = $this->helper->getApiBaseUrl() . '/v1/users/final/new-order';

        $clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
        $clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');

        $headers = [
            "Content-Type: application/json",
            "clientKey: " . $clientKey,
            "clientSecret: " . $clientSecret,
            "externalId: " . $payload['userId'], // API check if the user exists and is from Contestio
        ];

        // Encode the body data as JSON
        $jsonBody = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, array(
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
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
