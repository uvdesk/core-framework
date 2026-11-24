<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\FileSystem\UploadManagers;

use PhpMimeMailParser\Attachment;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileUploadServiceInterface;

class Localhost implements FileUploadServiceInterface
{
    const UPLOAD_DIRECTORY = 'assets';

    const BLOCKED_EXTENSIONS = [
        'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'pht', 'phtm', 'phar', 'phps', 'pgif', 'inc',
        'shtml', 'shtm', 'stm', 'htaccess', 'htpasswd', 'ini', 'config',
        'js', 'mjs', 'cjs', 'jsp', 'jspx', 'jsw', 'jsv', 'jspf', 'asp', 'aspx', 'asa', 'asax', 'ascx', 'ashx', 'asmx', 'axd', 'cer', 'cshtml', 'vbhtml',
        'exe', 'com', 'bat', 'cmd', 'msi', 'msp', 'dll', 'scr', 'pif', 'cpl', 'sys', 'vbs', 'vbe', 'wsf', 'wsh', 'ws', 'hta', 'ps1', 'psm1', 'reg', 'lnk',
        'sh', 'bash', 'zsh', 'csh', 'ksh', 'cgi', 'pl', 'pm', 'py', 'pyc', 'rb', 'jar', 'war', 'swf',
        'html', 'htm', 'xhtml', 'xht',
    ];

    private $container;
    private $requestStack;
    private $projectRootDirectory;
    private $documentRootDirectory;
    
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
        
        $this->projectRootDirectory = $container->get('kernel')->getProjectDir();
        $this->documentRootDirectory = str_replace('//', '/', $this->projectRootDirectory . '/public');
    }

    private function createMissingDirectories($pathToDirectory = null)
    {
        $missingDirectories = [];
        $activeDirectoryIterations = explode('/', $pathToDirectory);
        
        // Gather missing directories
        do {
            $directory_exists = true;
            $pathToActiveDirectory = implode('/', $activeDirectoryIterations);

            if (false == is_dir($pathToActiveDirectory)) {
                $directory_exists = false;
                $missingDirectories[] = array_pop($activeDirectoryIterations);
            }
        } while ($directory_exists == false);
        
        // Iteratively create missing directories
        if (!empty($missingDirectories)) {
            $missingDirectories = array_reverse($missingDirectories);

            foreach ($missingDirectories as $directory) {
                $activeDirectoryIterations[] = $directory;
                $pathToActiveDirectory = implode('/', $activeDirectoryIterations);

                if (!is_dir($pathToActiveDirectory)) {
                    mkdir($pathToActiveDirectory, 0777, true);
                }
            }
        }
    }

    private function resolvePathToAsset($fileName, $prefix = null)
    {
        $relativePathToAssetDirectory = str_replace('//', '/', sprintf('/%s/%s', self::UPLOAD_DIRECTORY, $prefix));

        if ('/' == substr($relativePathToAssetDirectory, -1)) {
            $relativePathToAssetDirectory = substr($relativePathToAssetDirectory, 0, -1);
        }
        
        $absolutePathToAssetDirectory = $this->documentRootDirectory . $relativePathToAssetDirectory;
        $this->createMissingDirectories($absolutePathToAssetDirectory);

        $relativePathToAsset = str_replace('//', '/', $relativePathToAssetDirectory . "/$fileName");
        $absolutePathToAsset = str_replace('//', '/', $absolutePathToAssetDirectory . "/$fileName");

        return array($relativePathToAsset, $absolutePathToAsset);
    }

    public function uploadFile(UploadedFile $temporaryFile, $prefix = null, bool $renameFile = true)
    {
        $fileName = $temporaryFile->getClientOriginalName();

        if (true == $renameFile) {
            $fileName = sprintf('%s%s', TokenGenerator::generateToken(16), $temporaryFile->guessExtension() ? '.' . $temporaryFile->guessExtension() : '');
        }

        $fileName = self::safeFileName($fileName);
        list($relativePath, $absolutePath) = $this->resolvePathToAsset($fileName, $prefix);
        
        // Upload file to target directory
        $relativeTargetDirectory = substr($absolutePath, 0, strrpos($absolutePath, '/'));
        $uploadedFile = $temporaryFile->move($relativeTargetDirectory, $fileName);

        return [
            'name'           => $fileName,
            'path'           => $relativePath,
            'isPathRelative' => true,
            'size'           => $uploadedFile->getSize(),
            'content-type'   => $uploadedFile->getMimeType(),
        ];
    }

    public function uploadEmailAttachment(Attachment $attachmentStream, $prefix = null, bool $renameFile = false)
    {
        $fileName = $attachmentStream->getFilename();

        if (true == $renameFile) {
            $fileName = sprintf('%s%s', TokenGenerator::generateToken(16), strrchr($fileName, '.') ?: '');
        }

        $fileName = self::safeFileName($fileName);
        list($relativePath, $absolutePath) = $this->resolvePathToAsset($fileName, $prefix);

        // Upload file to target directory
        file_put_contents($absolutePath, $attachmentStream->getStream());

        return [
            'name'           => $fileName,
            'path'           => $relativePath,
            'isPathRelative' => true,
            'size'           => filesize($absolutePath),
            'content-type'   => $attachmentStream->getContentType(),
        ];
    }

    public static function safeFileName($name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = trim(preg_replace('/["\x00-\x1F\x7F]/', '', $name));

        $parts = explode('.', $name);
        $safeParts = [array_shift($parts)];
        foreach ($parts as $part) {
            if (!in_array(strtolower(trim($part)), self::BLOCKED_EXTENSIONS, true)) {
                $safeParts[] = $part;
            }
        }

        $name = rtrim(implode('.', $safeParts), '. ');

        return '' === $name ? 'attachment' : $name;
    }
}
