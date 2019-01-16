<?php

namespace Webkul\UVDesk\CoreBundle\FileSystem;

use Webkul\UVDesk\CoreBundle\Entity\Attachment;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webkul\UVDesk\CoreBundle\FileSystem\UploadManagers\Localhost as DefaultFileUploadManager;

class FileSystem
{
    private $container;
    private $requestStack;
    private $projectRootDirectory;
    private $documentRootDirectory;
    private $fileUploadManagerServiceId;
    
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;

        $this->projectRootDirectory = $container->get('kernel')->getProjectDir();
        $this->documentRootDirectory = str_replace('//', '/', $this->projectRootDirectory . '/public');
        $this->fileUploadManagerServiceId = $container->getParameter('uvdesk.upload_manager.id') ?: DefaultFileUploadManager::class;
    }

    public function getUploadManager()
    {
        return $this->container->get($this->fileUploadManagerServiceId);
    }

    private function getAssetIconURL(Attachment $attachment = null)
    {
        $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-unknown.png';

        if (!empty($attachment)) {
            switch (strrchr($attachment->getName(), '.') ?: '') {
                case '.jpg':
                case '.png':
                case '.jpeg':
                    $relativePathToAsset = $attachment->getPath();
                    break;
                case '.zip':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-zip.png';
                    break;
                case '.doc':
                case '.docx':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-doc.png';
                    break;
                case '.pdf':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-pdf.png';
                    break;
                case '.xls':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-xls.png';
                    break;
                case '.csv':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/csv.png';
                    break;
                case '.ppt':
                case '.pptx':
                    $relativePathToAsset = '/bundles/uvdeskcore/images/icons/file-system/icon-file-ppt.png';
                    break;
                default:
                    break;
            }
        }

        return $relativePathToAsset;
    }

    public function getFileTypeAssociations(Attachment $attachment, $firewall = 'member')
    {
        $router = $this->container->get('router');
        $baseURL = 'http:////' . $this->container->getParameter('uvdesk.site_url') . '/';

        $assetDetails = [
            'id' => $attachment->getId(),
            'name' => $attachment->getName(),
            'path' => $baseURL . $attachment->getPath(),
            'iconURL' => $baseURL . $this->getAssetIconURL($attachment),
            'downloadURL' => null,
        ];

        if ('member' == $firewall) {
            $assetDetails['downloadURL'] = $router->generate('helpdesk_member_ticket_download_attachment', [
                'attachmendId' => $attachment->getId(),
            ]);
        } else {
            $assetDetails['downloadURL'] = $router->generate('helpdesk_customer_download_ticket_attachment', [
                'attachmendId' => $attachment->getId(),
            ]);
        }

        $assetDetails['path'] = str_replace('//', '/', $assetDetails['path']);
        $assetDetails['iconURL'] = str_replace('//', '/', $assetDetails['iconURL']);

        return $assetDetails;
    }
}