<?php

namespace Glitchbl\Backup\Driver;

use Glitchbl\FtpClient;

class FtpDriver extends Driver {
    /**
     * @var FtpClient FTP Client
     */
    protected $ftp_client;

    /**
     * @param string $server FTP Server
     * @param string $login FTP Login
     * @param string $password FTP Password
     * @param integer $port FTP Port
     */
    function __construct($server, $login, $password, $port = 21)
    {
        $this->ftp_client = new FtpClient($server, $login, $password, $port);
    }

    public function begin()
    {
        if ($this->logger !== null)
            $this->ftp_client->setLogger($this->logger);
        $this->ftp_client->connect();
    }

    public function end()
    {
        $this->ftp_client->close();
    }

    /**
     * @param string $file File to save
     * @param string $file_name File name
     */
    protected function saveFile($file, $file_name)
    {
        $this->ftp_client->put($file, $file_name);
    }

    /**
     * @param string $file File to delete
     */
    protected function deleteFile($file)
    {
        $this->ftp_client->delete($file);
    }

    /**
     * @return array Get files
     */
    public function getFiles()
    {
        return $this->ftp_client->files();
    }
}