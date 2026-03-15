<?php
namespace MyCompany\LlmsTxt\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RegenerateButton extends Field
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $url = $this->getUrl('mycompany_llmstxt/system/regenerate');

        return '<button type="button" class="action-default" onclick="setLocation(\''
            . $this->escapeUrl($url)
            . '\')"><span>'
            . $this->escapeHtml(__('Regenerate'))
            . '</span></button>';
    }
}
