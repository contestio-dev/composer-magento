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
use Magento\Framework\Image\AdapterFactory;

class CheckImage extends Action
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
	 * @var AdapterFactory
	 */
	protected $imageFactory;
	
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
		AdapterFactory $imageFactory,
		CustomerSession $customerSession
  ) {
		parent::__construct($context);
		$this->httpContext = $httpContext;
		$this->curlClient = $curlClient;
		$this->scopeConfig = $scopeConfig;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->customerRepository = $customerRepository;
		$this->helper = $helper;
		$this->imageFactory = $imageFactory;
		$this->customerSession = $customerSession;
	}

  public function execute() {
		$uploadImgApiUrl = $this->helper->getApiBaseUrl() . '/v1/checkImage';
		$clientKey = $this->scopeConfig->getValue('authkeys/clientkey/clientpubkey');
		$clientSecret = $this->scopeConfig->getValue('authkeys/clientkey/clientsecret');
		
		$path = '';
		if(!empty($_FILES['image']['tmp_name'])) {
			$path = $_FILES['image']['tmp_name'];
		} else {
			return $this->resultJsonFactory->create()->setData(['success' => 'false', 'message' => 'Aucune image trouvée, veuillez réessayer.']);
		}
		
		$file = new \CURLFile($path);
		
		$curl = curl_init();
		
		$headers = [
			"clientKey: " . $clientKey,
			"clientSecret: " . $clientSecret
		];

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $uploadImgApiUrl,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => array('file'=> $file),
		  CURLOPT_HTTPHEADER => $headers,
		));
	
		$response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return $this->resultJsonFactory->create()->setData([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la requête cURL: ' . $error_msg,
            'error' => true,
        ]);
    }

    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    curl_close($curl);

    if (strpos($contentType, 'application/octet-stream') !== false) {
        // Image binary response
        $response = base64_encode($response); // Encode the binary data in base64 to send it via JSON
        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'image' => $response
        ]);
    } else {
        // Error response
        $data = json_decode($response, true);
        return $this->resultJsonFactory->create()->setData([
            'success' => false,
            'message' => isset($data['message']) ? $data['message'] : 'Une erreur est survenue lors de l\'envoi de l\'image, veuillez réessayer.',
            'error' => true,
        ]);
    }
		
  }
}
