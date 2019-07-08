<?php

namespace Glitchbl\Backup;

use Psr\Log\LoggerInterface;
use Exception;

class Backup {
    /**
     * @var string Backup name
     */
    protected $name;

    /**
     * @var Repository Repository
     */
    protected $repository;

    /**
     * @var Driver Driver
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
     * @param Driver $driver Driver
     * @param \Psr\Log\LoggerInterface|null $logger Logger
     * @throws Exception If name is empty
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

        $this->repository = new Repository;
    }

    /**
     * @param string $files,... File(s) to add
     */
    public function addFile(...$files)
    {
        $this->repository->addFile(...$files);
    }

    /**
     * @param string $folders,... Folder(s) to add
     */
    public function addFolder(...$folders)
    {
        $this->repository->addFolder(...$folders);
    }

    /**
     * @param string $file File to remove
     * @throws Exception If file is not present
     */
    public function removeFile($file)
    {
        $this->repository->removeFile($file);
    }

    /**
     * @param string $folder Folder to remove
     * @throws Exception If folder is not present
     */
    public function removeFolder($folder)
    {
        $this->repository->removeFolder($folder);
    }

    /**
     * @param integer $number_iteration Number of backup iteration
     * @throws Exception If number is invalid
     */
    public function setNumberIteration($number_iteration)
    {
        $number_iteration = (int)$number_iteration;
        if ($number_iteration <= 0)
            throw new Exception('The number of iteration is not valid');
        $this->number_iteration = $number_iteration;
    }

    /**
     * @return string Path of repository archive
     */
    protected function getArchive()
    {
        $name = tempnam(sys_get_temp_dir(), $this->name);
        $this->repository->zip($name, $this->logger);
        return $name;
    }

    /**
     * @throws Exception If repository or driver not set
     */
    public function backup()
    {
        if ($this->driver === null)
            throw new Exception('Driver is not set');

        if ($this->repository === null)
            throw new Exception('Repository is not set');

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