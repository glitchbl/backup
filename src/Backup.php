<?php

namespace Glitchbl\Backup;

use Glitchbl\Backup\Driver\Driver;
use Psr\Log\LoggerInterface;
use Exception;

class Backup {
    /**
     * @var string Backup name
     */
    protected $name;
    /**
     * @var array $files Backup files
     */
    protected $files = [];

    /**
     * @var array $files Backup folders
     */
    protected $folders = [];

    /**
     * @var Driver\Driver Driver
     */
    protected $driver;

    /**
     * @var integer Number of backup iteration
     */
    protected $number_iteration = 7;

    /**
     * @var \Psr\Log\LoggerInterface|null Logger
     */
    protected $logger = null;

    /**
     * @param string $name Backup name
     * @param \Glitchbl\Backup\Driver\Driver $driver Driver
     * @param \Psr\Log\LoggerInterface|null $logger Logger
     * @throws \Exception If name is empty
     */
    function __construct($name, Driver $driver, LoggerInterface $logger = null)
    {
        if (!$name)
            throw new Exception('Name can not be empty');

        $this->name = $name;
        $this->logger = $logger;

        $driver->setName($this->name);
        if ($this->logger !== null)
            $driver->setLogger($this->logger);
        $this->driver = $driver;
    }

    /**
     * @param integer $number_iteration Number of backup iteration
     * @throws \Exception If number is invalid
     */
    public function setNumberIteration($number_iteration)
    {
        $number_iteration = (int)$number_iteration;
        if ($number_iteration <= 0)
            throw new Exception('The number of iteration is not valid');
        $this->number_iteration = $number_iteration;
    }

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
                throw new Exception("'{$folder}' is not a folder");
            }
        }
    }

    /**
     * @param string $file File to remove
     * @throws \Exception If file is not present
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
     * @throws \Exception If folder is not present
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
     * @return bool True if no files or folders to backup
     */
    public function isEmpty()
    {
        return count($this->files) + count($this->folders) === 0;
    }

    /**
     * @return string Path of repository archive
     */
    protected function getArchive()
    {
        $name = tempnam(sys_get_temp_dir(), $this->name);

        $zip = new ZipArchive($this->logger);
        $zip->open($name, ZipArchive::CREATE);

        $files_folders = array_merge($this->files, $this->folders);

        foreach ($files_folders as $file_folder) {
            $zip->addFileFolder($file_folder);
        }

        $zip->close();

        return $name;
    }

    /**
     * @throws \Exception If no files or folders or driver not set
     */
    public function backup()
    {
        if ($this->driver === null)
            throw new Exception('Driver is not set');

        if ($this->isEmpty())
            throw new Exception('The backup is empty');

        $archive = $this->getArchive();

        $this->driver->begin();
        
        $this->driver->save($archive);

        unlink($archive);

        $iterations = $this->driver->getIterations();
        $number_iteration_to_delete = count($iterations) - $this->number_iteration;

        if ($number_iteration_to_delete > 0) {
            foreach (array_slice($iterations, 0, $number_iteration_to_delete) as $iteration_to_delete) {
                $this->driver->delete($iteration_to_delete);
            }
        }

        $this->driver->end();
    }
}