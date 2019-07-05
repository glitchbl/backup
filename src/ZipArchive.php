<?php

namespace Glitchbl\Backup;

use ZipArchive as ZipArchiveBase;
use Exception;

class ZipArchive extends ZipArchiveBase {
    /**
     * @param string $file_folder file or folder to add
     * @param string $path Path where to add file or folder
     * @throws Exception if file or folder does not exists
     */
    public function addFileFolder($file_folder, $path = '')
    {
        $basename = basename($file_folder);

        if ($path) {
            $path = "{$path}/{$basename}";
        } else {
            $path = $basename;
        }

        if (is_dir($file_folder)) {
            $this->addEmptyDir($path);
            foreach (glob("{$file_folder}/*") as $_file_folder) {
                $this->addFileFolder($_file_folder, $path);
            }
        } elseif (is_file($file_folder)) {
            $this->addFile($file_folder, $path);
        } else {
            throw new Exception("'{$file_folder}' is neither a file nor a folder");
        }
    }
}