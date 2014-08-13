Exam Database
===============================================

Running in 5 mins
--------------------------

1. Register an account on [Openshift](https://www.openshift.com/).
2. Install [CLI](https://www.openshift.com/developers/rhc-client-tools-install)
3. Provision the application by running the following command:

    rhc app create examdb php-5.4 mysql-5.5 phpmyadmin-4 --from-code=https://github.com/ubc/examdb.git

4. Open the browser and enter the URL printed from the result of above command.

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
The project environment can be provision by [Vagrant](http://www.vagrantup.com/). Follow the instruction on the site to install vagrant first.

2. Checkout the Source

    git clone git@github.com:ubc/examdb.git

3. Setup the Dev VM

    cd examdb
    vagrant up

4. Install Dependencies

    vagrant ssh
    cd /vagrant
    composer install

5. Develop!

Running Tests
-------------

    bin/phing test
