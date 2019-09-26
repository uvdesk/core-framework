<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Navigation;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\NavigationInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;

class Ticket implements NavigationInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="18px">
    <path fill-rule="evenodd" fill="rgb(158, 158, 158)" d="M19.000,4.000 L17.000,4.000 L16.995,12.998 L4.000,13.000 L4.000,15.000 C4.000,15.550 4.450,16.000 5.000,16.000 L16.000,16.000 L20.000,20.000 L20.000,5.000 C20.000,4.450 19.550,4.000 19.000,4.000 ZM15.000,10.000 L15.000,1.000 C15.000,0.450 14.550,0.000 14.000,0.000 L1.000,0.000 C0.450,0.000 -0.000,0.450 -0.000,1.000 L-0.000,15.000 L4.000,11.000 L14.000,11.000 C14.550,11.000 15.000,10.550 15.000,10.000 Z"></path>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return self::dynamicTranslation("Tickets");
    }

    public static function getRouteName() : string
    {
        return 'helpdesk_member_ticket_collection';
    }

    public function getChildrenRoutes() : array
    {
        return [];
    }

    public  static function dynamicTranslation($data) : string
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
