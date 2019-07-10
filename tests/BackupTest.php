<?php

require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Glitchbl\Backup\Backup;
use Glitchbl\Backup\Driver\FtpDriver;
use Glitchbl\Backup\Driver\FsDriver;
use Glitchbl\FtpClient;

final class FtpClientTest extends TestCase {
    /**
     * @var \Glitchbl\FtpClient
     */
    protected static $ftp_client;

    /**
     * @var string FTP Server
     */
    protected static $ftp_server;

    /**
     * @var string FTP Login
     */
    protected static $ftp_login;

    /**
     * @var string FTP Password
     */
    protected static $ftp_password;
    
    public static function setUpBeforeClass() : void
    {
        $dir = __DIR__;

        file_put_contents("{$dir}/file1", 'file1');
        file_put_contents("{$dir}/file2", 'file2');

        mkdir("{$dir}/folder1/folder2", 0777, true);

        file_put_contents("{$dir}/folder1/file", 'folder1/file');
        file_put_contents("{$dir}/folder1/folder2/file", 'folder2/file');

        mkdir("{$dir}/backups");

        $ftp_server = 'ftp.server.com';
        $ftp_login = 'login';
        $ftp_password = 'password';

        if (is_file(__DIR__ . '/config.php'))
            require __DIR__ . '/config.php';

        self::$ftp_server = $ftp_server;
        self::$ftp_login = $ftp_login;
        self::$ftp_password = $ftp_password;

        self::$ftp_client = new FtpClient(self::$ftp_server, self::$ftp_login, self::$ftp_password);
        self::$ftp_client->connect();
    }

    public static function tearDownAfterClass(): void
    {
        $dir = __DIR__;

        if (is_file("{$dir}/file1"))
            unlink("{$dir}/file1");

        if (is_file("{$dir}/file2"))
            unlink("{$dir}/file2");

        if (is_file("{$dir}/folder1/folder2/file"))
            unlink("{$dir}/folder1/folder2/file");

        if (is_dir("{$dir}/folder1/folder2"))
            rmdir("{$dir}/folder1/folder2");

        if (is_file("{$dir}/folder1/file"))
            unlink("{$dir}/folder1/file");

        if (is_dir("{$dir}/folder1"))
            rmdir("{$dir}/folder1");
        
        if (is_dir("{$dir}/backups")) {
            foreach (glob("{$dir}/backups/*.*") as $backup) {
                unlink($backup);
            }
            rmdir("{$dir}/backups");
        }

        if (self::$ftp_client->isFile('ftp.zip.bak2'))
            self::$ftp_client->delete('ftp.zip.bak2');

        if (self::$ftp_client->isFile('ftp.zip.bak3'))
            self::$ftp_client->delete('ftp.zip.bak3');

        self::$ftp_client->close();
        self::$ftp_client = null;
    }

    public function testArchive()
    {
        $dir = __DIR__;

        $backup_fs = new Backup('archive.zip', new FsDriver("{$dir}/backups"));
        $backup_fs->addFolder("{$dir}/folder1");
        $backup_fs->addFile("{$dir}/file1");
        $backup_fs->addFile("{$dir}/file2");
        $backup_fs->backup();

        $zip = new ZipArchive;
        $zip->open("{$dir}/backups/archive.zip.bak1");

        $this->assertEquals($zip->numFiles, 4);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) { 
            $stat = $zip->statIndex($i); 
            $entries[] = $stat['name']; 
        }

        $files_folders = [
            'file1',
            'file2',
            'folder1/file',
            'folder1/folder2/file',
        ];
        
        sort($entries);
        sort($files_folders);

        $this->assertEquals($files_folders, $entries);
    }

    public function testFs()
    {
        $dir = __DIR__;

        $backup_fs = new Backup('fs.zip', new FsDriver("{$dir}/backups"));
        $backup_fs->setNumberIteration(2);

        $backup_fs->addFolder("{$dir}/folder1");
        $backup_fs->addFile("{$dir}/file1");
        $backup_fs->addFile("{$dir}/file2");

        $backup_fs->backup();
        $backup_fs->backup();
        $backup_fs->backup();

        $this->assertFalse(is_file("{$dir}/backups/fs.zip.bak1"));
        $this->assertTrue(is_file("{$dir}/backups/fs.zip.bak2"));
        $this->assertTrue(is_file("{$dir}/backups/fs.zip.bak3"));
    }

    public function testFtp()
    {
        $dir = __DIR__;

        $backup_ftp = new Backup('ftp.zip', new FtpDriver(self::$ftp_server, self::$ftp_login, self::$ftp_password));
        $backup_ftp->setNumberIteration(2);

        $backup_ftp->addFolder("{$dir}/folder1");
        $backup_ftp->addFile("{$dir}/file1");
        $backup_ftp->addFile("{$dir}/file2");

        $backup_ftp->backup();
        $backup_ftp->backup();
        $backup_ftp->backup();

        $this->assertFalse(self::$ftp_client->isFile('ftp.zip.bak1'));
        $this->assertTrue(self::$ftp_client->isFile('ftp.zip.bak2'));
        $this->assertTrue(self::$ftp_client->isFile('ftp.zip.bak3'));
    }

    public function testNameParent()
    {
        $dir = __DIR__;

        $backup_fs = new Backup('parent.zip', new FsDriver("{$dir}/backups"));
        $backup_fs->addFolder("{$dir}/folder1", 'folderB', 'folderA');
        $backup_fs->addFile("{$dir}/file1", 'file2', 'folderA/folderB/folder2');
        $backup_fs->backup();

        $zip = new ZipArchive;
        $zip->open("{$dir}/backups/parent.zip.bak1");

        $this->assertEquals($zip->numFiles, 3);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) { 
            $stat = $zip->statIndex($i); 
            $entries[] = $stat['name']; 
        }

        $files_folders = [
            'folderA/folderB/file',
            'folderA/folderB/folder2/file',
            'folderA/folderB/folder2/file2',
        ];
        
        sort($entries);
        sort($files_folders);

        $this->assertEquals($files_folders, $entries);
    }
}