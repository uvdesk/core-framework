<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Search;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;

class SavedReplies implements SearchItemInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" viewBox="0 0 60 60">
    <path fill-rule="evenodd" d="M49.206,6.014H10.789a4.794,4.794,0,0,0-4.778,4.8L5.987,54,15,45H49c2.641,0,5.008-1.753,5.008-4.393V10.813A4.815,4.815,0,0,0,49.206,6.014ZM45,36H15V31H45v5Zm0-8H15V23H45v5Zm0-8H15V15H45v5Z"></path>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return self::dynamicTranslation("Saved Replies");
    }

    public static function getRouteName() : string
    {
        return 'helpdesk_member_saved_replies';
    }

    public function getChildrenRoutes() : array
    {
        return [];
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
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.en.yml", 'en');
                break;
            case 'es':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.es.yml", 'es');
                break;
            case 'fr':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.fr.yml", 'fr');
                break;
            case 'da':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.da.yml", 'da');
                break;
            case 'de':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.de.yml", 'de');
                break;
            case 'it':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.it.yml", 'it');
                break;
            case 'ar':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.ar.yml", 'ar');
                break;
            case 'tr':
                $translator->addLoader('yaml', new YamlFileLoader()); 
                $translator->addResource('yaml',__DIR__."/../../../../../../translations/messages.tr.yml", 'tr');
                break;
        }

        return $translator->trans($data); 
    }
}
