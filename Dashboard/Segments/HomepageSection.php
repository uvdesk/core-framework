<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;

abstract class HomepageSection implements HomepageSectionInterface
{
    private $collection = [];

    public abstract static function getTitle() : string;
    public abstract static function getDescription() : string;

    public static function getRoles() : array
    {
        return [];
    }

    public function appendItem(HomepageSectionItemInterface $item) : HomepageSectionInterface
    {
        $this->collection[] = $item;

        return $this;
    }

    public function getItemCollection() : array
    {
        return $this->collection;
    }

    public static function dynamicTranslation($data) : string
    {
        $request = Request::createFromGlobals(); 
        $path = $request->getPathInfo(); 
        $locale = explode("/", $path);
        $translator = new Translator($locale[1]);

        switch($locale[1])
        {
            case 'en':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.en.yml", 'en');
                break;
            case 'es':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.es.yml", 'es');
                break;
            case 'fr':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.fr.yml", 'fr');
                break;
            case 'da':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.da.yml", 'da');
                break;
            case 'de':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.de.yml", 'de');
                break;
            case 'it':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.it.yml", 'it');
                break;
            case 'ar':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.ar.yml", 'ar');
                break;
            case 'tr':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../translations/messages.tr.yml", 'tr');
                break;
            default:
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.en.yml", 'en');
                break;
        }

        return $translator->trans($data); 
    }
}
