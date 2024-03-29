<?php

namespace Glitchbl\Backup;

use Glitchbl\Backup\Traits\HasLogger;
use ZipArchive as ZipArchiveBase;
use Psr\Log\LoggerInterface;
use Exception;

class ZipArchive extends ZipArchiveBase {
    use HasLogger;

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
     * @param string $file_folder file or folder to add
     * @param string $name Name in archive
     * @param string $path Path where to add file or folder
     * @throws \Exception if file or folder does not exists
     */
    public function addFileFolder($file_folder, $name, $path = '')
    {
        if ($path) {
            $path = "{$path}/{$name}";
        } else {
            $path = $name;
        }

        if (is_dir($file_folder)) {
            // $this->addEmptyDir($path);
            // $this->log('info', "Directory '{$path}' created");
            foreach (glob("{$file_folder}/*") as $_file_folder) {
                $this->addFileFolder($_file_folder, basename($_file_folder), $path);
            }
        } elseif (is_file($file_folder)) {
            $this->addFile($file_folder, $path);
            $this->log('info', "File '{$path}' added");
        } else {
            throw new Exception("'{$file_folder}' is neither a file nor a folder");
        }
    }
}