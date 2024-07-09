<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Contestio\Connect\Helper\Data as Helper;

class Submitsurvey extends Action
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
		$postdata = $this->getRequest()->getPostValue();

		$submitSurveyResponse = $this->submitSurvey($postdata);

		// Create JSON response
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($submitSurveyResponse);
	}

	public function submitSurvey($postdata) {

		if (!isset($postdata['surveyId']) || !isset($postdata['answers'])) {
			return ['success' => false, 'message' => 'Invalid survey data'];
		}
		
		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/surveys/' . $postdata['surveyId'];
		
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		$headers = [
			"Content-Type: application/json",
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret,
			"externalId: " . $this->customerSession->getCustomerId()
		];
		
		$body = [
			'answers' => $postdata['answers'],
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
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => json_encode($body),
		  CURLOPT_HTTPHEADER =>$headers,
		));

		$response = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		$decoded = json_decode($response, true);

		$data = array(
			'success' => $httpStatus === 201,
			'message' => isset($decoded['message']) ? $decoded['message'] : ($httpStatus === 201 ? 'Survey submitted successfully' : 'An error occurred'),
			'alreadyParticipated' => isset($decoded['alreadyParticipated']) ? $decoded['alreadyParticipated'] : null
		);

		if ($httpStatus === 201 || json_last_error() === JSON_ERROR_NONE) {
			return $data;
		}

		// Return false if unable to decode JSON
		return false;
	}
}
