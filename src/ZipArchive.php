<?php

namespace Glitchbl\Backup;

use Psr\Log\LoggerInterface;
use ZipArchive as ZipArchiveBase;
use Exception;

class ZipArchive extends ZipArchiveBase {
    /**
     * @var \Psr\Log\LoggerInterface|null Logger
     */
    protected $logger = null;

    /**
     * @param \Psr\Log\LoggerInterface|null $logger Logger
     */
    function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $type Log type
     * @param string $message Log message
     * @throws Exception
     */
    protected function log($type, $message)
    {
        if ($this->logger) {
            if (method_exists($this->logger, $type)) {
                call_user_func([$this->logger, $type], $message);
            } else {
                throw new Exception("Logger has not '{$type}' method");
            }
        }
    }

    /**
     * @param string $file_folder file or folder to add
     * @param string $path Path where to add file or folder
     * @throws Exception if file or folder does not exists
     */
    public function addFileFolder($file_folder, $path = '')
    {
        $basename = basename($file_folder);

        if ($path) {
            $path = "{$path}/{$basename}";
        } else {
            $path = $basename;
        }

        if (is_dir($file_folder)) {
            $this->addEmptyDir($path);
            $this->log('info', "ZipArchive: Directory '{$path}' created");
            foreach (glob("{$file_folder}/*") as $_file_folder) {
                $this->addFileFolder($_file_folder, $path);
            }
        } elseif (is_file($file_folder)) {
            $this->addFile($file_folder, $path);
            $this->log('info', "ZipArchive: File '{$path}' added");
        } else {
            throw new Exception("'{$file_folder}' is neither a file nor a folder");
        }
    }
}