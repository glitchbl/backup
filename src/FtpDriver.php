<?php

namespace Glitchbl\Backup;

use Glitchbl\FtpClient;

class FtpDriver extends Driver {
    /**
     * @var FtpClient FTP Client
     */
    protected $ftp_client;

    /**
     * @param string $ftp_server FTP Server
     * @param string $ftp_login FTP Login
     * @param string $ftp_password FTP Password
     */
    function __construct($ftp_server, $ftp_login, $ftp_password, $port = 21)
    {
        $this->ftp_client = new FtpClient($ftp_server, $ftp_login, $ftp_password, $port);
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
     * @param string $backup_name File Backup name
     */
    protected function saveFile($file, $backup_name)
    {
        $this->ftp_client->put($file, $backup_name);
    }

    /**
     * @param string $file File name to delete
     */
    protected function deleteFile($file)
    {
        $this->ftp_client->delete($file);
    }

    /**
     * @return array Get backup files
     */
    public function getFiles()
    {
        return $this->ftp_client->files();
    }
}