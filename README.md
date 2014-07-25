Symfony Standard Edition with Openshift Support
===============================================

Welcome to the Symfony Standard Edition - a fully-functional Symfony2
application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start
using Symfony. For a more detailed explanation, see the [Installation][1]
chapter of the Symfony Documentation.

1) Getting Started
------------------

To use this repo with Openshift:
* Provision a php application (mysql-5.5 and phpmyadmin-4 are optional)

    rhc app create MYAPP php-5.4 mysql-5.5 phpmyadmin-4 --from-code=https://github.com/xcompass/openshift-symfony.git

Once the application is created, Openshift will give out the URL of the app, SSH address and git repo. 
Those information can be obtained by running the following command as well:

    rhc app show MYAPP

The database parameters are setup in app/config/params.php, which is imported by app/config/config.yml.

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    ssh OPENSHIFT_SSH_ADDRESS
    cd $OPENSHIFT_REPO_DIR
    php app/check.php

The script returns a status code of `0` if all mandatory requirements are met,
`1` otherwise.

To access the `config.php` script from a browser, a ssh tunnel has to be created 
as config.php is only accessible from localhost

    rhc port-forward

Then open the URL from the browser:

    http://your_openshift_domain/config.php

If you get any warnings or recommendations, fix them before moving on.

3) Browsing the Demo Application
--------------------------------

Congratulations! You're now ready to use Symfony.

From the `config.php` page, click the "Bypass configuration and go to the
Welcome page" link to load up your first Symfony page.

You can also use a web-based configurator by clicking on the "Configure your
Symfony Application online" link of the `config.php` page.

To see a real-live Symfony page in action, access the following page:

    web/app_dev.php/demo/hello/Fabien

4) Getting started with Symfony
-------------------------------

This distribution is meant to be the starting point for your Symfony
applications, but it also contains some sample code that you can learn from
and play with.

A great way to start learning Symfony is via the [Quick Tour][4], which will
take you through all the basic features of Symfony2.

Once you're feeling good, you can move onto reading the official
[Symfony2 book][5].

A default bundle, `AcmeDemoBundle`, shows you Symfony2 in action. After
playing with it, you can remove it by following these steps:

  * delete the `src/Acme` directory;

  * remove the routing entry referencing AcmeDemoBundle in `app/config/routing_dev.yml`;

  * remove the AcmeDemoBundle from the registered bundles in `app/AppKernel.php`;

  * remove the `web/bundles/acmedemo` directory;

  * remove the `security.providers`, `security.firewalls.login` and
    `security.firewalls.secured_area` entries in the `security.yml` file or
    tweak the security configuration to fit your needs.

5) To access other component directly
-------------------------------------

If mysql or other database is installed and you want to access it directly. E.g. using 
MySQLBench or other tools. You can use the port forward mentioned above.

    rhc port-forward

The port will be forwarded to localhost and you can setup your tool to connect to localhost.

What's inside?
---------------

The Symfony Standard Edition is configured with the following defaults:

  * Twig is the only configured template engine;

  * Doctrine ORM/DBAL is configured;

  * Swiftmailer is configured;

  * Annotations for everything are enabled.

It comes pre-configured with the following bundles:

  * **FrameworkBundle** - The core Symfony framework bundle

  * [**SensioFrameworkExtraBundle**][6] - Adds several enhancements, including
    template and routing annotation capability

  * [**DoctrineBundle**][7] - Adds support for the Doctrine ORM

  * [**TwigBundle**][8] - Adds support for the Twig templating engine

  * [**SecurityBundle**][9] - Adds security by integrating Symfony's security
    component

  * [**SwiftmailerBundle**][10] - Adds support for Swiftmailer, a library for
    sending emails

  * [**MonologBundle**][11] - Adds support for Monolog, a logging library

  * [**AsseticBundle**][12] - Adds support for Assetic, an asset processing
    library

  * **WebProfilerBundle** (in dev/test env) - Adds profiling functionality and
    the web debug toolbar

  * **SensioDistributionBundle** (in dev/test env) - Adds functionality for
    configuring and working with Symfony distributions

  * [**SensioGeneratorBundle**][13] (in dev/test env) - Adds code generation
    capabilities

  * **AcmeDemoBundle** (in dev/test env) - A demo bundle with some example
    code

All libraries and bundles included in the Symfony Standard Edition are
released under the MIT or BSD license.

Enjoy!

[1]:  http://symfony.com/doc/2.3/book/installation.html
[2]:  http://getcomposer.org/
[3]:  http://symfony.com/download
[4]:  http://symfony.com/doc/2.3/quick_tour/the_big_picture.html
[5]:  http://symfony.com/doc/2.3/index.html
[6]:  http://symfony.com/doc/2.3/bundles/SensioFrameworkExtraBundle/index.html
[7]:  http://symfony.com/doc/2.3/book/doctrine.html
[8]:  http://symfony.com/doc/2.3/book/templating.html
[9]:  http://symfony.com/doc/2.3/book/security.html
[10]: http://symfony.com/doc/2.3/cookbook/email.html
[11]: http://symfony.com/doc/2.3/cookbook/logging/monolog.html
[12]: http://symfony.com/doc/2.3/cookbook/assetic/asset_management.html
[13]: http://symfony.com/doc/2.3/bundles/SensioGeneratorBundle/index.html
