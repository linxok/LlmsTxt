<?php
namespace MyCompany\LlmsTxt\Cron;

use MyCompany\LlmsTxt\Model\Config;
use MyCompany\LlmsTxt\Model\LlmsTxtRegenerator;

class GenerateFiles
{
    private Config $config;
    private LlmsTxtRegenerator $regenerator;

    public function __construct(
        Config $config,
        LlmsTxtRegenerator $regenerator
    )
    {
        $this->config = $config;
        $this->regenerator = $regenerator;
    }

    public function execute(): void
    {
        if (!$this->config->isCronEnabled()) {
            return;
        }

        $this->regenerator->regenerateAll();
    }
}
