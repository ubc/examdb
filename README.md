Exam Database
===============================================

Running in 5 mins
--------------------------

1. Register an account on [Openshift](https://www.openshift.com/).
2. Install [CLI](https://www.openshift.com/developers/rhc-client-tools-install)
3. Provision the application by running the following command:

        rhc app create examdb php-5.4 mysql-5.5 phpmyadmin-4 --from-code=https://github.com/ubc/examdb.git

4. Setup custom parameters by custom environment variable: (for full list of available env variables, see app/config/params.php)s

        rhc env set auth2_username=service_username auth2_password=service_password auth2_service_application=service_app -a examdb 

5. Open the browser and enter the URL printed from the result of above command.

Detailed Version
----------------

### 1) Provision the Application

Provision a php application (phpmyadmin-4 is optional)

    rhc app create examdb php-5.4 mysql-5.5 phpmyadmin-4 --from-code=https://github.com/ubc/examdb.git

Once the application is created, Openshift will give out the URL of the app, SSH address and git repo. 
Those information can be obtained by running the following command as well:

    rhc app show examdb

The database parameters are setup in app/config/params.php, which is imported by app/config/config.yml.

### 2) Checking your System Configuration

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

### 3) To access other component directly

If mysql or other database is installed and you want to access it directly. E.g. using 
MySQLBench or other tools. You can use the port forward mentioned above.

    rhc port-forward

The port will be forwarded to localhost and you can setup your tool to connect to localhost.


Development
-----------

### Installation

1. Install Vagrant
The project environment can be provision by [Vagrant](http://www.vagrantup.com/). Follow the instruction on the site to install vagrant first. Then install vagrant-hostmanager plugin if you want vagrant to manage your /etc/hosts for you.

    ```
    vagrant plugin install vagrant-hostmanager
    vagrant plugin install vagrant-bindfs
    ```

    Otherwise, you will need to manually add the following line to your /etc/hosts

    ```
    127.0.0.1 examdb.dev
    ```

2. Checkout the Source

    ```
    git clone git@github.com:ubc/examdb.git
    ```

3. Setup the Dev VM

    ```
    cd examdb && vagrant up
    ```

4. Open a browser from host
    
    ```
    http://examdb.dev:8089/app_dev.php
    ```

5. Develop!

### Running Tests

    bin/phing test

NOTES
-----
* Default username and password are both "admin"
* When testing student features, the student user puid should be 12345678, which is the ID used in app/fixtures data for local SIS data repository
* The test fixture contains two sections: MATH 101 and ENGL 100
* Update dependencies: it may run into memory limits when running `composer update`, so use the following command:

    ```
    php -d memory_limit=-1 /path/to/composer update
    ```
    
* to see changes if on production environment, then you'll need to run a few commands in console to see the changes (aka move from src folder to web folder)

    ```
    php app/console cache:clear --env=prod
    ```

* Default authentication for production is CAS and for development is internal login. To make the system work standalone (aka skipping out using CAS), you'll need to make a few changes:
  * modify app/config/config_prod.yml and change security_cas.yml to security_internal.yml
* To create a user from command line: 

    ```
    vagrant ssh
    cd /vagrant
    php app/console exam:user:create USERNAME PASSWORD ROLE_ADMIN
    ```
    
* If there is an existing exam database, the search index has to be rebuild in order for search to function.

    ```
    vagrant ssh
    cd /vagrant
    php app/console exam:index:build
    ```

* Upgrade notes:

    ```
    sudo su app
    cd /www_data/app
    git pull
    chmod 775 app/data/uploads app/data/uploads/documents
    ```
    
