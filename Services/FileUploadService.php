<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Services;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class FileUploadService extends \Webkul\UVDesk\CoreFrameworkBundle\FileSystem\UploadManagers\Localhost
{
    public function uploadFile(UploadedFile $temporaryFile, $prefix = null, bool $renameFile = true)
    {
        $fileName = $temporaryFile->getClientOriginalName();
        $fileData = parent::uploadFile($temporaryFile, $prefix, $renameFile);
        $fileData['name'] = $fileName;
        return $fileData;
    }

    public function fileRemoveFromFolder($filepath)
    {
        $fs = new Filesystem();
        if($fs->exists("$filepath")) {
            $fs->remove("$filepath");
            return true;
        }
        return false;
    }
}