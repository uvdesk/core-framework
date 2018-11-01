<?php

namespace Webkul\UVDesk\CoreBundle\FileSystem;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultManager extends UVDeskFileUploadManager
{
    const PREFIX = '/';
    const TARGET_DIRECTORY = 'assets';

    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();
        $directory = $file->move(self::TARGET_DIRECTORY, $fileName);

        return self::PREFIX . $directory->getPathname();
    }
}