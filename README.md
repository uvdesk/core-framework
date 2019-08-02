<p align="center"><a href="https://www.uvdesk.com/en/" target="_blank">
    <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.uvdesk.com/uvdesk/bundles/webkuldefault/images/uvdesk-wide.svg">
</a></p>

[UVDesk Community Edition][1] is an easy-to-use, highly customizable open-source **helpdesk solution** built on top of the reliable [Symfony][2] **PHP framework**, enabling organizations to provide their customers with the best level of support solution possible.

UVDeskCoreBundle
--------------

The standalone **UVDeskCoreBundle** lies at the heart of the [UVDesk Community][1] helpdesk, providing the core essential functionalities and integration tools to easily integrate any other community helpdesk packages, furhter extending the capabilities of the helpdesk system.

The core framework bundle comes loaded with the following features:

  * **Ticket Query System** - Easily transform your customer queries into tickets

  * **Mailboxes** - Pipeline your support email mailboxes directly into tickets for easy query managment

  * **Email Templates** - Draft your frequent query responses to improve your productivity and minimize response time

  * **User Management System** - Easily manage your support staff members into Admins, Groups & Teams with varying level of system privileges

Installation
--------------

This bundle can be easily integrated into any Symfony application (though it is recommended that you're using [Symfony 4][3], or your project has a dependency on [Symfony Flex][4], as things have changed drastically with the newer Symfony versions). Before continuing, make sure that you're using PHP 7 or higher and have [Composer][5] installed. You also need to ensure that you have the following PHP extensions installed:

  * [PHP IMAP][6]
  * [PHP Mailparse][7]

To require the core framework bundle into your symfony project, simply run the following from your project root:

```bash
$ composer require uvdesk/core-framework
```

License
--------------

The **UVDeskCoreBundle** and libraries included within the bundle are released under the MIT or BSD license.

[1]: https://www.uvdesk.com/
[2]: https://symfony.com/
[3]: https://symfony.com/4
[4]: https://flex.symfony.com/
[5]: https://getcomposer.org/
[6]: http://php.net/manual/en/book.imap.php
[7]: http://php.net/manual/en/book.mailparse.php