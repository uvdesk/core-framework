<?php

namespace Webkul\UVDesk\CoreBundle\FileSystem;

use PhpMimeMailParser\Attachment;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultManager extends UVDeskFileUploadManager
{
    const PREFIX = '/';
    const TARGET_DIRECTORY = 'assets';

    public function setRootProjectDirectory($root_dir)
    {
        $this->rootDirectory = $root_dir;
    }

    public function upload(UploadedFile $file)
    {
        $extension = explode('.', $file->getClientOriginalName());
        $fileName = md5(uniqid()) . '.' . array_pop($extension);
        $directory = $file->move(self::TARGET_DIRECTORY, $fileName);

        return self::PREFIX . $directory->getPathname();
    }

    public function uploadFromEmail(Attachment $attachment, $prefix = null)
    {
        $root = $this->rootDirectory ?: getcwd();
        $directory = $root . self::PREFIX . self::TARGET_DIRECTORY . "/";
        $resolvedPath = self::PREFIX . self::TARGET_DIRECTORY . "/";

        if (!empty($prefix)) {
            $directory .= $prefix;
            $resolvedPath .= $prefix;
        }
        
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $path = $directory . $attachment->getFilename();
        $resolvedPath .= $attachment->getFilename();

        file_put_contents($path, $attachment->getStream());

        return [
            'path' => $resolvedPath,
            'size' => filesize($path), 
            'filename' => $attachment->getFilename(),
        ];
    }
}