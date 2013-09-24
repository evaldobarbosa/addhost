<?
ini_set("display_errors", "Off");
ini_set("error_reporting",E_ALL ^ E_NOTICE ^ E_WARNING);

define("APACHE_VHOST_PATH","/etc/apache2/sites-enabled");
define("APACHE_GROUP","www-data");
define("CURRENT_USER","evaldo");
define("HOSTS_FILE","/etc/hosts");
define("DEFAULT_PRJ_PATH", $_SERVER['HOME'] . "/Projetos");

//define("PROXY_HOST",'proxy.host.com.br:3128');
//define("PROXY_USER",'evaldobarbosa:myproxypassword');

define("LANGUAGE",'pt_BR');
//define("LANGUAGE",'de_DE');
//define("language",'en_US');
