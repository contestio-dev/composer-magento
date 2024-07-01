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
		$surveyDetails = $this->fetchSurveyDetails($surveyId);
		
		// Create JSON response
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($surveyDetails);
	}

  public function fetchSurveyDetails($surveyId) {
		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/survey/details';
		
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		$headers = [
			"Content-Type: application/json",
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret
		];
		
		$body = [
			"surveyId" => $surveyId,
			"userId" => $this->customerSession->getCustomerId() ?? null,
			"handle" => $this->customerSession->getCustomer()->getData('contestio_pseudo') ?? ""
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
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_POSTFIELDS =>$jsonBody,
		  CURLOPT_HTTPHEADER =>$headers,
		));

		$response = curl_exec($curl);

		$data = json_decode($response, true);
		
		if (isset($data['_id'])) {
			$data = array('success' => 'true', "data" => $data);
		} else {
			$data = array('success' => 'false', "data" => $data);
		}

		if (json_last_error() === JSON_ERROR_NONE) {
			return $data;
		}

		// Return false if unable to decode JSON
		return false;
	}
}
