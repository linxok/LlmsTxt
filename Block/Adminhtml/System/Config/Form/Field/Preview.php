<?php
namespace MyCompany\LlmsTxt\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use MyCompany\LlmsTxt\Model\LlmsTxtGenerator;

class Preview extends Field
{
    private LlmsTxtGenerator $generator;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        LlmsTxtGenerator $generator,
        array $data = []
    ) {
        $this->generator = $generator;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        try {
            $content = $this->generator->generate();
        } catch (\Throwable $throwable) {
            $content = 'Unable to generate preview: ' . $throwable->getMessage();
        }

        return '<div style="padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">'
            . '<pre style="white-space: pre-wrap; margin: 0;">'
            . $this->escapeHtml($content)
            . '</pre>'
            . '</div>';
    }
}
