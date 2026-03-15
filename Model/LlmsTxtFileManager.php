<?php
namespace MyCompany\LlmsTxt\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as IoFile;

class LlmsTxtFileManager
{
    private const BASE_DIRECTORY = 'var/mycompany/llmstxt';

    private Filesystem $filesystem;
    private File $fileDriver;
    private IoFile $ioFile;

    public function __construct(
        Filesystem $filesystem,
        File $fileDriver,
        IoFile $ioFile
    ) {
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
        $this->ioFile = $ioFile;
    }

    public function write(string $identifier, string $content): void
    {
        $path = $this->getAbsolutePath($identifier);
        $directory = dirname($path);
        $this->ioFile->checkAndCreateFolder($directory);
        $this->fileDriver->filePutContents($path, $content);
    }

    public function read(string $identifier): ?string
    {
        $path = $this->getAbsolutePath($identifier);
        if (!$this->fileDriver->isExists($path) || !$this->fileDriver->isFile($path)) {
            return null;
        }

        $content = $this->fileDriver->fileGetContents($path);
        return is_string($content) && $content !== '' ? $content : $content;
    }

    public function exists(string $identifier): bool
    {
        $path = $this->getAbsolutePath($identifier);
        return $this->fileDriver->isExists($path) && $this->fileDriver->isFile($path);
    }

    public function buildIdentifier(string $host): string
    {
        $normalizedHost = strtolower(trim($host));
        $normalizedHost = preg_replace('/[^a-z0-9._-]+/', '_', $normalizedHost) ?: 'default';

        return $normalizedHost . '.txt';
    }

    private function getAbsolutePath(string $identifier): string
    {
        $rootDirectory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        return $rootDirectory->getAbsolutePath(self::BASE_DIRECTORY . '/' . ltrim($identifier, '/'));
    }
}
