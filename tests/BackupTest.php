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
    
    public static function setUpBeforeClass() : void
    {
        $dir = __DIR__;

        file_put_contents("{$dir}/file1", 'file1');
        file_put_contents("{$dir}/file2", 'file2');

        mkdir("{$dir}/folder1/folder2", 0777, true);

        file_put_contents("{$dir}/folder1/file", 'folder1/file');
        file_put_contents("{$dir}/folder1/folder2/file", 'folder2/file');

        mkdir("{$dir}/backups");

        $server = 'ftp.server.com';
        $login = 'login';
        $password = 'password';

        if (is_file(__DIR__ . '/config.php'))
            require __DIR__ . '/config.php';

        self::$ftp_client = new FtpClient($server, $login, $password);
        self::$ftp_client->connect();
    }

    public static function tearDownAfterClass(): void
    {
        $dir = __DIR__;

        if (is_file("{$dir}/test.zip.bak1"))
            unlink("{$dir}/test.zip.bak1");

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

        if (self::$ftp_client->isFile('test.zip.bak2'))
            self::$ftp_client->delete('test.zip.bak2');

        if (self::$ftp_client->isFile('test.zip.bak3'))
            self::$ftp_client->delete('test.zip.bak3');

        self::$ftp_client->close();
        self::$ftp_client = null;
    }

    public function testArchive()
    {
        $dir = __DIR__;

        $backup_fs = new Backup('test.zip', new FsDriver($dir));
        $backup_fs->addFolder("{$dir}/folder1");
        $backup_fs->addFile("{$dir}/file1", "{$dir}/file2");
        $backup_fs->backup();

        $zip = new ZipArchive;
        $zip->open("{$dir}/test.zip.bak1");

        $this->assertEquals($zip->numFiles, 6);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) { 
            $stat = $zip->statIndex($i); 
            $entries[] = $stat['name']; 
        }

        $files_folders = [
            'file1',
            'file2',
            'folder1/',
            'folder1/file',
            'folder1/folder2/',
            'folder1/folder2/file',
        ];
        
        sort($entries);
        sort($files_folders);

        $this->assertEquals($files_folders, $entries);
    }

    public function testFs()
    {
        $dir = __DIR__;

        $backup_fs = new Backup('test.zip', new FsDriver("{$dir}/backups"));
        $backup_fs->setNumberIteration(2);

        $backup_fs->addFolder("{$dir}/folder1");
        $backup_fs->addFile("{$dir}/file1", "{$dir}/file2");

        $backup_fs->backup();
        $backup_fs->backup();
        $backup_fs->backup();

        $this->assertFalse(is_file("{$dir}/backups/test.zip.bak1"));
        $this->assertTrue(is_file("{$dir}/backups/test.zip.bak2"));
        $this->assertTrue(is_file("{$dir}/backups/test.zip.bak3"));
    }

    public function testFtp()
    {
        $dir = __DIR__;

        $server = 'ftp.server.com';
        $login = 'login';
        $password = 'password';

        if (is_file(__DIR__ . '/config.php'))
            require __DIR__ . '/config.php';

        $backup_ftp = new Backup('test.zip', new FtpDriver($server, $login, $password));
        $backup_ftp->setNumberIteration(2);

        $backup_ftp->addFolder("{$dir}/folder1");
        $backup_ftp->addFile("{$dir}/file1", "{$dir}/file2");

        $backup_ftp->backup();
        $backup_ftp->backup();
        $backup_ftp->backup();

        $this->assertFalse(self::$ftp_client->isFile('test.zip.bak1'));
        $this->assertTrue(self::$ftp_client->isFile('test.zip.bak2'));
        $this->assertTrue(self::$ftp_client->isFile('test.zip.bak3'));
    }
}