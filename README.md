# Add Virtualhosts Tool

PHP Script to create apache virtual hosts using command line.

#### Use:
> sudo path_to/addhost IP DOMAIN PATH_TO_PROJECT_FOLDER [--htaccess] [--composer] [--errorlog]

#### Example:
> sudo /opt/addhost/addhost.php 127.0.2.1 dev.localdomain /home/user/project

#### How happens?

First addhost verifies if the project source folder exists, if no so create it. After it opens the /etc/hosts file and appends
the IP and DOMAIN. Finally creates virtual host config file on the apache enabled sites folder.

#### I want to configure some things. What can I do?

* With --htaccess option you create automatically the .htaccess on your public folder.
* With --composer addhost download composer.phar file and creates composer.json.
* With --errorlog you will have a virtualhost's error log file.

#### I need to use proxy, it works?
Yeah! This package now is preparet to support proxy. You need edit conf file and define two constants. See below:

###### PROXY_HOST: Here you should write the IP or proxy host name and port number separated by ':'.

Example: define('PROXY_HOST','192.168.0.15:3128');

###### PROXY_USER: Here you should write the proxy user and password separated by ':'.

Example: define('PROXY_USER','evaldobarbosa:myproxypassword');

Restart apache.
