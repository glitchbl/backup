<?php

namespace Glitchbl\Backup\Driver;

use Glitchbl\Backup\Traits\HasLogger;
use Psr\Log\LoggerInterface;
use Exception;

abstract class Driver {
    use HasLogger;

    /**
     * @var string Backup name
     */
    protected $name;

    /**
     * @var \Psr\Log\LoggerInterface|null Logger
     */
    protected $logger = null;

    /**
     * @var array Iterations
     */
    private $iterations = null;

    /**
     * @param string $name Backup name
     * @throws \Exception If name is not valid
     */
    public function setName($name)
    {
        $pattern = '/^[-_a-zA-Z0-9]+(\.[-_a-zA-Z0-9]+)*$/';
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
        return "/{$name}\.bak(\d+)$/";
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
        $this->log('info', "Archive '{$archive}' saved as '{$next_name}'");
        $this->iterations[] = $this->getCurrentIteration() + 1;
    }

    /**
     * @param integer $iteration Iteration to remove
     */
    public function delete($iteration)
    {
        $file_name = $this->getBackupName($iteration);
        $this->deleteFile($file_name);
        $this->log('info', "Archive '{$file_name}' deleted");
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
     * @return string Get current iteration
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
     * @param string $file File to save
     * @param string $file_name File name
     */
    abstract protected function saveFile($file, $file_name);

    /**
     * @param string $file File to delete
     */
    abstract protected function deleteFile($file);

    /**
     * @return array Get files
     */
    abstract protected function getFiles();
}