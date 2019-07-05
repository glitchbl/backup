<?php

namespace Glitchbl\Backup;

use Psr\Log\LoggerInterface;
use Exception;

abstract class Driver {
    /**
     * @var string Name of the backup
     */
    protected $name;

    /**
     * @var \Psr\Log\LoggerInterface Logger
     */
    protected $logger = null;

    /**
     * @var array Iterations
     */
    private $iterations = null;

    /**
     * @param string $name Name of the backup
     * @throws Exception If name is not valid
     */
    public function setName($name)
    {
        $pattern = '/[-_a-zA-Z0-9]+(\.[-_a-zA-Z0-9]+)*/';
        if (!preg_match($pattern, $name))
            throw new Exception("Name '{$name}' is not valid to pattern {$pattern}");
        $this->name = $name;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger Logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string Pattern for searching backups
     */
    protected function getPattern()
    {
        $name = str_replace('.', '\.', $this->name);
        return "/^{$name}\.bak(\d+)$/";
    }

    /**
     * @param integer $iteration Iteration number
     * @return string Backup archive name
     */
    public function getBackupName($iteration)
    {
        return "{$this->name}.bak{$iteration}";
    }

    /**
     * @return string Next backup archive name
     */
    public function getNextBackupName()
    {
        return $this->getBackupName($this->getCurrentIteration() + 1);
    }

    public function begin()
    {
        
    }

    public function end()
    {
        
    }

    /**
     * @param string $archive Archive to save
     */
    public function save($archive)
    {
        $next_name = $this->getNextBackupName();
        $this->saveFile($archive, $next_name);
        $this->iterations[] = $this->getCurrentIteration() + 1;
    }

    /**
     * @param integer $iteration Iteration to remove
     */
    public function delete($iteration)
    {
        $file_name = $this->getBackupName($iteration);
        $this->deleteFile($file_name);
    }

    /**
     * @return string Get current backup iteration
     */
    public function getCurrentIteration()
    {
        $iterations = $this->getIterations();

        if (!count($iterations)) {
            return 0;
        } else {
            return end($iterations);
        }
    }

    /**
     * @return array Get iterations
     */
    public function getIterations()
    {
        if ($this->iterations === null) {
            $this->iterations = [];
            $pattern = $this->getPattern();
            $matches = [];

            foreach ($this->getFiles() as $file) {
                if (preg_match($pattern, $file, $matches))
                    $this->iterations[] = (int)$matches[1];
            }
            sort($this->iterations);
        }
        return $this->iterations;
    }

    /**
     * @param string $file File to save
     * @param string $backup_name File name of the backup
     */
    abstract protected function saveFile($file, $backup_name);

    /**
     * @param string $file File name to delete
     */
    abstract protected function deleteFile($file);

    /**
     * @return array Get backup files
     */
    abstract protected function getFiles();
}