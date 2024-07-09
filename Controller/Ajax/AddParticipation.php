<?php

namespace Contestio\Connect\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Contestio\Connect\Helper\Data as Helper;

class AddParticipation extends Action
{
	protected $curlClient;
	protected $scopeConfig;
	protected $httpContext;
	protected $resultJsonFactory;
	protected $customerSession;
	
	/**
	 * @var CustomerRepositoryInterface
	 */
	protected $customerRepository;
	
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
		CustomerRepositoryInterface $customerRepository,
		Helper $helper,
		CustomerSession $customerSession
  ) {
		parent::__construct($context);
		$this->httpContext = $httpContext;
		$this->curlClient = $curlClient;
		$this->scopeConfig = $scopeConfig;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->customerRepository = $customerRepository;
		$this->helper = $helper;
		$this->customerSession = $customerSession;
	}

  public function execute() {
    $postdata = $this->getRequest()->getPostValue();

		if (!isset($postdata['contestId'])) {
			return $this->resultJsonFactory->create()->setData(['success' => false]);
		}

		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/participation/upload-image/' . $postdata['contestId'];
		$path = '';

		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		if(!empty($_FILES['image']['tmp_name'])) {
			$path = $_FILES['image']['tmp_name'];
		} else {
			return $this->resultJsonFactory->create()->setData([
				'success' => false,
				'error' => true,
				'message' => 'Aucune image trouvée, veuillez réessayer.'
			]);
		}
		
		$file = new \CURLFile($path);
		
		$curl = curl_init();
		
		$headers = [
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret,
			"externalId: " . $this->customerSession->getCustomer()->getId(),
		];

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $apiUrl,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => array('file'=> $file, 'contestId' => $postdata['contestId']),
		  CURLOPT_HTTPHEADER => $headers,
		));
	
		$response = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		$data = json_decode($response, true);
		$imageUrl = "";
		
		if ($httpStatus === 201 && isset($data['Key'])) {
			$imageUrl = $data['Key'];
		} else {
			return $this->resultJsonFactory->create()->setData([
				'success' => false,
				'message' => isset($data['message']) ? $data['message'] : 'Une erreur est survenue lors de l\'envoi de l\'image, veuillez réessayer.',
				'error' => true
			]);
		}

		$data = $this->submitContest($postdata, $imageUrl);
		
		$resultJson = $this->resultJsonFactory->create();

    return $resultJson->setData($data);
  }

	public function submitContest($postdata, $imageUrl) {
		$apiUrl = $this->helper->getApiBaseUrl() . '/v1/participation/' . $postdata['contestId'];
		
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		/** @var CustomerInterface $customer */
		$customer = $this->customerSession->getCustomer();

		if (!$customer) {
			return $this->resultJsonFactory->create()->setData(['success' => false]);
		}

		$headers = [
			"Content-Type: application/json",
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret,
			"externalId: " . $customer->getId(),
		];

		$body = [
			'description' => $postdata['description'],
			'imageUrl' => $imageUrl
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
		
		if ($httpStatus === 201) {
			$data = array(
				'success' => true,
				'message' => isset($data['message']) ? $data['message'] : 'Votre participation a bien été enregistrée !',
				'error' => false
			);
		} else {
			$data = array(
				'success' => false,
				'error' => true,
				'message' => isset($data['message']) ? $data['message'] : 'Une erreur est survenue'
			);
		}

		if ($httpStatus === 201 || json_last_error() === JSON_ERROR_NONE) {
			return $data;
		}

		// Return false if unable to decode JSON
		return false;
	}
}
