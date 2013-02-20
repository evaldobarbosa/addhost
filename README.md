addhost
=======

PHP Script to create hosts using command line.

Use:
sudo path_to/addhost IP DOMAIN PATH_TO_PUBLIC_FOLDER

Example:
sudo /opt/addhost/addhost.php 127.0.2.1 dev.localdomain /home/user/project/public

How happens?

First addhost verifies if the project source folder exists, if no so create it. After it opens the /etc/hosts file and appends
the IP and DOMAIN. Finally creates virtual host config file on the apache enabled sites folder.

Restart apache.
