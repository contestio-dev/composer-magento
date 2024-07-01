<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

class Addflag extends Action
{
	protected $curlClient;
	protected $scopeConfig;
	protected $httpContext;
	protected $resultJsonFactory;
	protected $customerSession;
	
	/**
	 * @var Helper
	 */
	protected $helper;

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

	public function execute() {
		$postdata = json_decode($this->getRequest()->getContent(), true);

		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/contest/participation/flag';
		
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		$headers = [
			"Content-Type: application/json",
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret
		];

		/** @var CustomerInterface $customer */
		$customer = $this->customerSession->getCustomer();

		// Get user data from the session
		$userId = $customer->getId();
		$handle = $customer->getData('contestio_pseudo');
		
		if (!$customer) {
			return $this->resultJsonFactory->create()->setData(['success' => 'false']);
		}

		$body = [
			'participationId' => $postdata['participationId'],
			'contestId' => $postdata['contestId'],
			'userId' => $userId,
			'handle' => $handle,
			'reason' => $postdata['reason'],
		];

		// Encode the body data as JSON
		$jsonBody = json_encode($body);
		
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
		  CURLOPT_POSTFIELDS =>$jsonBody,
		  CURLOPT_HTTPHEADER =>$headers,
		));

		$response = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		$data = json_decode($response, true);
		
		if (isset($data['message'])) {
			$data = array(
				'success' => 'true',
				'message' => $data['message'],
				'alreadyFlagged' => !!isset($data['alreadyFlagged']),
				'error' => !!isset($data['error']),
				'status' => $httpStatus
			);
		} else {
			$data = array('success' => 'false');
		}

		if (json_last_error() === JSON_ERROR_NONE) {
			return $this->resultJsonFactory->create()->setData($data);
		}

		// Return false if unable to decode JSON
		return $this->resultJsonFactory->create()->setData(['success' => 'false']);
	}
}
