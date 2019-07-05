<?php

namespace Glitchbl\Backup;

use Psr\Log\LoggerInterface;
use Exception;

class Backup {
    /**
     * @var string Name of the backup
     */
    protected $name;

    /**
     * @var Repository The repository to backup
     */
    protected $repository;

    /**
     * @var Driver The driver to manager backup
     */
    protected $driver;

    /**
     * @var integer The number of backup iteration
     */
    protected $number_iteration = 7;

    /**
     * @param string $name The name of the backup
     * @param \Psr\Log\LoggerInterface $logger Logger
     * @throws Exception If name is empty
     */
    function __construct($name, LoggerInterface $logger = null)
    {
        if (!$name)
            throw new Exception('Name can not be empty');

        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * @param Repository $repository The repository to backup
     * @throws Exception If repository is empty
     */
    public function setRepository(Repository $repository)
    {
        if ($repository->isEmpty())
            throw new Exception('Repository can not be empty');

        $this->repository = $repository;
    }

    /**
     * @param Driver $driver The driver to manager backup
     * @throws Exception If repository is empty
     */
    public function setDriver(Driver $driver)
    {
        $driver->setName($this->name);
        if ($this->logger !== null)
            $driver->setLogger($this->logger);
        $this->driver = $driver;
    }

    /**
     * @param integer $number_iteration The number of backup iteration
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
     * @return string Path of the repository archive
     */
    protected function getArchive()
    {
        $name = tempnam(sys_get_temp_dir(), $this->name);
        $this->repository->zip($name);
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

        $this->driver->begin();

        $archive = $this->getArchive();
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