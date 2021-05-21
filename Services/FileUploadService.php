<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Services;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService extends \Webkul\UVDesk\CoreFrameworkBundle\FileSystem\UploadManagers\Localhost
{
    public function uploadFile(UploadedFile $temporaryFile, $prefix = null, bool $renameFile = true)
    {
        $fileName = $temporaryFile->getClientOriginalName();
        $fileData = parent::uploadFile($temporaryFile, $prefix, $renameFile);
        $fileData['name'] = $fileName;
        return $fileData;
    }
}
