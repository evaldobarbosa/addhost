addhost
=======

PHP Script to create hosts using command line.

Use:
sudo path_to/addhost IP DOMAIN PATH_TO_PROJECT_FOLDER [--htaccess] [--composer] [--errorlog]

Example:
sudo /opt/addhost/addhost.php 127.0.2.1 dev.localdomain /home/user/project

How happens?

First addhost verifies if the project source folder exists, if no so create it. After it opens the /etc/hosts file and appends
the IP and DOMAIN. Finally creates virtual host config file on the apache enabled sites folder.

With --htaccess option you create automatically the .htaccess on your public folder.
With --composer addhost download composer.phar file and creates composer.json.
With --errorlog you will have a virtualhost's error log file.

Restart apache.
