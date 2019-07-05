<?php

namespace Glitchbl\Backup;

use Exception;

class FsDriver extends Driver {
    /**
     * @var string Directory of backups
     */
    protected $path;

    /**
     * @param string $path Directory of backups
     * @throws Exception If not a directory
     */
    function __construct($path = '.')
    {
        if (!is_dir($path))
            throw new Exception("'{$path}' is not a directory");

        $this->path = rtrim($path, '/');
    }
    /**
     * @param string $file File to save
     * @param string $backup_name File Backup name
     */
    protected function saveFile($file, $backup_name)
    {
        copy($file, "{$this->path}/{$backup_name}");
    }

    /**
     * @param string $file File name to delete
     */
    protected function deleteFile($file)
    {
        unlink("{$this->path}/{$file}");
    }

    /**
     * @return array Get backup files
     */
    public function getFiles()
    {
        return glob("{$this->path}/*");
    }
}