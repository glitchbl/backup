<?php

namespace Glitchbl\Backup\Driver;

use Exception;

class FsDriver extends Driver {
    /**
     * @var string Path of backups
     */
    protected $path;

    /**
     * @param string $path Path of backups
     * @throws \Exception If not a directory
     */
    function __construct($path = '.')
    {
        if (!is_dir($path))
            throw new Exception("'{$path}' is not a directory");

        $this->path = rtrim($path, '/');
    }
    /**
     * @param string $file File to save
     * @param string $file_name File name
     */
    protected function saveFile($file, $file_name)
    {
        copy($file, "{$this->path}/{$file_name}");
    }

    /**
     * @param string $file File to delete
     */
    protected function deleteFile($file)
    {
        unlink("{$this->path}/{$file}");
    }

    /**
     * @return array Get files
     */
    public function getFiles()
    {
        return glob("{$this->path}/*");
    }
}