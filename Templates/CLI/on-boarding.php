<?php

$love = '\\' === \DIRECTORY_SEPARATOR ? 'love' : '💖 ';

return <<<PROMPT
<fg=magenta>
_   ___     ______            _       ____                                      _ _         
| | | \ \   / /  _ \  ___  ___| | __  / ___|___  _ __ ___  _ __ ___  _   _ _ __ (_) |_ _   _ 
| | | |\ \ / /| | | |/ _ \/ __| |/ / | |   / _ \| '_ ` _ \| '_ ` _ \| | | | '_ \| | __| | | |
| |_| | \ V / | |_| |  __/\__ \   <  | |__| (_) | | | | | | | | | | | |_| | | | | | |_| |_| |
 \___/   \_/  |____/ \___||___/_|\_\  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|_|\__|\__, |
                                                                                       |___/ 
</>
Welcome to the <info>UVDesk Community</info> project! UVDesk Community is an <comment>open-source e-commerce helpdesk system</comment>
which is built on top of reliable set of tools to provide you and your customers with the best support 
solution possible.

To start things off, here are a few commands to help you setup:

  * <fg=blue;options=bold;>Bootstrapping your project:</>

    <comment>php bin/console uvdesk:guides:bootstrapping-project</comment>

  * <fg=blue;options=bold;>View all available guides:</>

    <comment>php bin/console list uvdesk:guides</comment>


Made with $love by the UVDesk Team. Happy helping :)

PROMPT;
   
?>