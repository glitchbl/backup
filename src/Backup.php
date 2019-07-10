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
     * @param string $string String to check
     * @param string $pattern RegEx
     * @throws \Exception If string is not valid for pattern
     */
    protected function checkPattern($string, $pattern)
    {
       if (!preg_match($pattern, $string))
            throw new Exception("'{$string}' is not valid for pattern {$pattern}");
    }

    /**
     * @param string $name Name to check
     * @throws \Exception If name is not valid
     */
    protected function checkValidName($name)
    {
        $pattern = '/^[.]?[\w,\s()@_-]+[\w,\s()@._-]*$/';
        $this->checkPattern($name, $pattern);
    }

    /**
     * @param string $path Path to check
     * @throws \Exception If path is not valid
     */
    protected function checkValidPath($path)
    {
        $pattern = '/^[\w,\s()@_-]+(\/[\w,\s()@_-]+)*$/';
        $this->checkPattern($path, $pattern);
    }

    /**
     * @param string $type Type to add (file or folder)
     * @param string $item File or folder to add
     * @param string $name New name
     * @param string $path Path to the file or folder in archive
     * @throws \Exception If file or folder already added or if type is not valid
     */
    protected function add($type, $item, $name, $path)
    {
        if ($name !== '') {
            $this->checkValidName($name);
        } else {
            $name = basename($item);
        }

        if ($path !== '')
            $this->checkValidPath($path);

        if ($type === 'file') {
            if (!key_exists($item, $this->files)) {
                $this->files[$item] = compact('name', 'path');
            } else {
                throw new Exception("File '{$item}' already added");
            }
        } elseif ($type === 'folder') {
            if (!key_exists($item, $this->folders)) {
                $this->folders[$item] = compact('name', 'path');
            } else {
                throw new Exception("Folder '{$item}' already added");
            }
        } else {
            throw new Exception("Type '{$type}' is not valid");
        }
    }

    /**
     * @param string $file File to add
     * @param string $name New name
     * @param string $path Path to the file in archive
     * @throws \Exception If not a file
     */
    public function addFile($file, $name = '', $path = '')
    {
        if (!is_file($file))
            throw new Exception("'{$file}' is not a file");

        $this->add('file', $file, $name, $path);
    }

    /**
     * @param string $folder Folder to add
     * @param string $name New name
     * @param string $path Path to the folder in archive
     * @throws \Exception If not a folder
     */
    public function addFolder($folder, $name = '', $path = '')
    {
        if (!is_dir($folder))
            throw new Exception("'{$folder}' is not a folder");

        $this->add('folder', $folder, $name, $path);
    }

    /**
     * @param string $file File to remove
     * @throws \Exception If file is not present
     */
    public function removeFile($file)
    {
        if (key_exists($file, $this->files)) {
            unset($this->files[$file]);
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
        if (key_exists($folder, $this->folders)) {
            unset($this->folders[$folder]);
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

        foreach ($this->files as $file => $data) {
            $zip->addFileFolder($file, $data['name'], $data['path']);
        }

        foreach ($this->folders as $folder => $data) {
            $zip->addFileFolder($folder, $data['name'], $data['path']);
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