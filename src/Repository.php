<?php

namespace Glitchbl\Backup;

use Psr\Log\LoggerInterface;
use Exception;

class Repository {
    /**
     * @var array $files Files
     */
    protected $files = [];

    /**
     * @var array $files Folders
     */
    protected $folders = [];

    /**
     * @param string $files,... File(s) to add
     */
    public function addFile(...$files)
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!in_array($file, $this->files)) {
                    $this->files[] = $file;
                }
            } else {
                throw new Exception("'{$file}' is not a file");
            }
        }
    }

    /**
     * @param string $folders,... Folder(s) to add
     */
    public function addFolder(...$folders)
    {
        foreach ($folders as $folder) {
            if (is_dir($folder)) {
                if (!in_array($folder, $this->folders)) {
                    $this->folders[] = $folder;
                }
            } else {
                throw new Exception("'{$folder}' is not a directory");
            }
        }
    }

    /**
     * @param string $file File to remove
     * @throws Exception If file is not present
     */
    public function removeFile($file)
    {
        $key = array_search($file, $this->files);
        if ($key !== false) {
            unset($this->files[$key]);
        } else {
            throw new Exception("'{$file}' is not present");
        }
    }

    /**
     * @param string $folder Folder to remove
     * @throws Exception If folder is not present
     */
    public function removeFolder($folder)
    {
        $key = array_search($folder, $this->folders);
        if ($key !== false) {
            unset($this->folders[$key]);
        } else {
            throw new Exception("'{$folder}' is not present");
        }
    }

    /**
     * @return array Files
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return array Folders
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @return bool True if the repository is empty
     */
    public function isEmpty()
    {
        return count($this->files) + count($this->folders) === 0;
    }

    /**
     * @param string $path Location where to create the zip file
     * @param \Psr\Log\LoggerInterface|null $logger Logger
     */
    public function zip($path, LoggerInterface $logger = null)
    {
        $zip = new ZipArchive($logger);
        $zip->open($path, ZipArchive::CREATE);

        $files_folders = array_merge($this->files, $this->folders);

        foreach ($files_folders as $file_folder) {
            $zip->addFileFolder($file_folder);
        }

        $zip->close();
    }
}