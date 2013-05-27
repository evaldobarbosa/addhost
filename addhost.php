#!/usr/bin/php -q
<?
echo "CONFIGURADOR DE HOSTS APACHE\n";
echo "by: EVALDO BARBOSA\n";

$apache_virtual_hosts_dir="/etc/apache2/sites-enabled";

if ( count($argv) < 4 ) {
	echo "Sao necessarios pelo menos tres parametros: IP, nome_do_servidor e diretorio_da_aplicacao";
	die();
}

$ip = $argv[1];
$server_name = $argv[2];
$app_path = $argv[3];

if ( !file_exists($app_path) ) {
	echo "CRIANDO DIRETÓRIO\n";
	mkdir($app_path, 0775, true);
	chown($app_path, "evaldo");
	chgrp($app_path, "www-data");
}

$contents = file_get_contents("/etc/hosts");
	if ( strpos($contents, $ip) ) {
		throw new Exception("O IP informado já encontra-se em uso. Escolha outro.", 1);
		die();
	}
	echo "CONFIGURANDO HOST\n";
$contents .= "\n{$ip}\t{$server_name}";
file_put_contents("/etc/hosts", $contents);

echo "CONFIGURANDO VIRTUALHOST\n";
$vhc = array(); //virtual_host_content
$vhc[] = "### CREATED BY ADDHOST: " . date("Y-m-d H:i:s") . "###";
$vhc[] = "NameVirtualHost {$ip}:80";
$vhc[] = "<VirtualHost {$ip}:80>";
$vhc[] = "\tServerAdmin hostmaster@{$server_name}";
$vhc[] = "\tServerName {$server_name}";
$vhc[] = "\tDocumentRoot {$app_path}";
$vhc[] = "\t<Directory />";
$vhc[] = "\t\tOptions Indexes FollowSymLinks MultiViews";
$vhc[] = "\t\tAllowOverride All";
$vhc[] = "\t\tOrder allow,deny";
$vhc[] = "\t\tAllow from all";
$vhc[] = "\t</Directory>";
$vhc[] = "</VirtualHost>";

file_put_contents("{$apache_virtual_hosts_dir}/{$server_name}.conf", implode("\n", $vhc));

echo "SEU SERVIDOR FOI CRIADO CORRETAMENTE\n";

if ( in_array("--htaccess", $argv) ) {
	echo "CONFIGURANDO HTACCESS\n";	

	$vhc = array(); //virtual_host_content
	$vhc[] = "### CREATED BY ADDHOST: " . date("Y-m-d H:i:s") . "###";
	$vhc[] = "Options +FollowSymlinks";
	$vhc[] = "RewriteEngine On";

	$vhc[] = "RewriteCond %{REQUEST_URI} !\.(gif|jpg|png)$";
	$vhc[] = "RewriteCond %{REQUEST_FILENAME} !-f";
	$vhc[] = "RewriteCond %{REQUEST_FILENAME} !-d";
	$vhc[] = "RewriteRule (.*) /index.php [L]";

	file_put_contents("{$app_path}/.htaccess", implode("\n", $vhc));

	echo "SEU HTACCESS FOI CRIADO CORRETAMENTE\n";
}
?>
