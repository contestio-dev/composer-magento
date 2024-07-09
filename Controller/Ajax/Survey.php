<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

class Survey extends Action
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
		$surveyId = $this->getRequest()->getPost('surveyId');

		if (!$surveyId) {
			// Create JSON response
			$resultJson = $this->resultJsonFactory->create();
			return $resultJson->setData(['error' => 'Invalid survey ID']);
		}

		$surveyDetails = $this->fetchSurveyDetails($surveyId);
		
		// Create JSON response
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($surveyDetails);
	}

  public function fetchSurveyDetails($surveyId) {
		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/surveys/' . $surveyId;
		
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		$headers = [
			"Content-Type: application/json",
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret,
			"externalId: " . $this->customerSession->getCustomerId(),
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
		  CURLOPT_POSTFIELDS => '',
		  CURLOPT_HTTPHEADER =>$headers,
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$decoded = json_decode($response, true);

		$data = array(
			'success' => !!isset($decoded['_id']), 
			'data' => $decoded,
			'message' => isset($decoded['message']) ? $decoded['message'] : null
		);

		if (json_last_error() === JSON_ERROR_NONE) {
			return $data;
		}

		// Return false if unable to decode JSON
		return false;
	}
}
