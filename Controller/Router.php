<?php
namespace MyCompany\LlmsTxt\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{
    private ActionFactory $actionFactory;

    public function __construct(ActionFactory $actionFactory)
    {
        $this->actionFactory = $actionFactory;
    }

    public function match(RequestInterface $request)
    {
        $pathInfo = trim((string) $request->getPathInfo(), '/');
        if ($pathInfo !== 'llms.txt') {
            return null;
        }

        $request->setModuleName('llmstxt');
        $request->setControllerName('index');
        $request->setActionName('index');
        $request->setAlias(
            \Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS,
            'llms.txt'
        );

        return $this->actionFactory->create(\MyCompany\LlmsTxt\Controller\Index\Index::class);
    }
}
