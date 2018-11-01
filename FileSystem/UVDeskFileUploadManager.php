<?php

namespace Webkul\UVDesk\CoreBundle\FileSystem;

use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class UVDeskFileUploadManager
{
    abstract public function upload(UploadedFile $file);
}