<?php
namespace MyCompany\LlmsTxt\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;
use MyCompany\LlmsTxt\Model\LlmsTxtFileManager;

class FileStatus extends Field
{
    private StoreManagerInterface $storeManager;
    private LlmsTxtFileManager $fileManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        StoreManagerInterface $storeManager,
        LlmsTxtFileManager $fileManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->fileManager = $fileManager;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $rows = [];

        foreach ($this->storeManager->getStores() as $store) {
            $host = strtolower((string) parse_url((string) $store->getBaseUrl(), PHP_URL_HOST));
            $identifier = $this->fileManager->buildIdentifier($host);
            $rows[$identifier] = [
                'host' => $host !== '' ? $host : 'default',
                'exists' => $this->fileManager->exists($identifier),
                'file' => $identifier,
            ];
        }

        $html = '<div style="padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">';
        foreach ($rows as $row) {
            $html .= '<div style="margin-bottom: 6px;">'
                . '<strong>' . $this->escapeHtml($row['host']) . '</strong>: '
                . $this->escapeHtml($row['exists'] ? 'generated' : 'missing')
                . ' (' . $this->escapeHtml($row['file']) . ')</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
