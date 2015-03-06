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

1. Install Vagrant
The project environment can be provision by [Vagrant](http://www.vagrantup.com/). Follow the instruction on the site to install vagrant first. Then install vagrant-hostmanager plugin if you want vagrant to manage your /etc/hosts for you.

    ```
    vagrant plugin install vagrant-hostmanager
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

4. Install Dependencies

    ```
    vagrant ssh
    cd /vagrant
    composer install
    ```
5. Setup Exam Database system
  1. Create the DB tables: ```php app/console doctrine:schema:update --force```
  2. Copy over CSS and JavaScript from src to web folder: ```php app/console assetic:dump --env=prod```
  3. Refresh the subject/department code (without --local to refresh from SIS): ```php app/console exam:subjectcode:refresh --local```
  4. Create a test user: ```php app/console exam:user:create admin PASSWORD ROLE_SUPER_ADMIN;```
    * Username and password are both test
    * puid should be 12345678, which is the ID used in app/fixtures data for local SIS data repository
    * The test fixture contains two sections: MATH 101 and ENGL 100

5. Open a browser from host
    
    ```
    http://examdb.dev:8089/app_dev.php
    ```

6. Develop!

Notes:

* Update dependencies: it may run into memory limits when running `composer update`, so use the following command:

    php -d memory_limit=-1 /path/to/composer update


Running Tests
-------------

    bin/phing test

NOTES
-----
* to see changes if on production environment, then you'll need to run a few commands in console to see the changes (aka move from src folder to web folder)

    ```
    php app/console cache:clear --env=prod
    ```

    ```
    sudo rm -rf app/cache/*
    ```
* to make the system work standalone (aka skipping out using CAS), you'll need to make a few changes:
  * modify app/config/security_prod.yml and change security_cas.yml to security_internal.yml
  * insert a user into DB manually 
      ```
      vagrant ssh
      cd /vagrant
      php app/console exam:user:create admin PASSWORD ROLE_SUPER_ADMIN;
      ```
