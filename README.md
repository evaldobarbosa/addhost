# Add Virtualhosts Tool

PHP Script to create Spache virtual hosts (VHost) using the command line.

#### Usage:
> sudo path_to/addhost.php IP DOMAIN PATH_TO_PROJECT_FOLDER [--htaccess] [--composer] [--errorlog]

or

> sudo path_to/addhost.php IP DOMAIN --removehost

or

> sudo path_to/addhost.php IP DOMAIN --checkhost

#### Example:
> sudo path_to/addhost.php 127.0.2.1 dev.localdomain /home/user/project

#### How does this work?

First addhost verifies if the project source folder exists, if not it is creatd. After that addhost
opens the /etc/hosts file and appends the IP and DOMAIN. Finally the virtual host config file is creates in the apache enabled sites folder.

#### I want to configure something. What is possible?

* With --htaccess option automatically creates the .htaccess in your public folder.
* With --composer addhost downloads composer.phar file and creates a project composer.json.
* With --errorlog provides you with a virtualhost's error log file.

#### I need to use proxy, does this works?
Yeah! This package now is preparet to support proxy. You need to edit the conf file and define two constants.

###### EXAMPLE:
PROXY_HOST: Here you should write the IP or proxy host name and port number separated by ':'.
Example: define('PROXY_HOST','192.168.0.15:3128');

PROXY_USER: Here you should write the proxy user and password separated by ':'.
Example: define('PROXY_USER','evaldobarbosa:myproxypassword');

#### Can Addhost remove old VHosts?
Yes! See below for the syntax to remove old hosts.
> sudo path_to/addhost.php 127.0.2.1 dev.localdomain --removehost

#### Finally...

Restart apache.

Article (brazilian portuguese)
---
PHP-DF.org - http://bit.ly/19zPh3A
iMasters - http://bit.ly/1aYFFjz
