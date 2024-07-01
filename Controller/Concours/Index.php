<?php
declare(strict_types=1);

namespace Contestio\Connect\Controller\Concours;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     */
    public function __construct(
        PageFactory $resultPageFactory,
        RequestInterface $request
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        // Retrieve URL parameters
        $params = $this->request->getParams();
        $partid = isset($params['partid']) ? $params['partid'] : null;
		/* echo $userId;
		exit; */

        // Check if userid parameter exists
        if ($partid) {
            // If userid exists, set a different template
            $resultPage = $this->resultPageFactory->create();
			
			 // Get the block with name 'concours.index'
            $layout = $resultPage->getLayout();
            
            // Get the block with name 'concours.index'
            $block = $layout->getBlock('concours.index');

            $block->setTemplate('Contestio_Connect::concours/participant.phtml');
            $block->setData('participationid', $partid);
            
            $blockhead = $layout->getBlock('concours.head');
            $blockhead->setData('participationid', $partid);

		
            //$resultPage->getConfig()->setTemplate('Contestio_Connect::concours/participant.phtml');
            return $resultPage;
        } else {
            // If userid doesn't exist, use the default template
            return $this->resultPageFactory->create();
        }
    }
}
