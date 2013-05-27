#!/usr/bin/php -q
<?
echo "CONFIGURADOR DE HOSTS APACHE\n";
echo "by: EVALDO BARBOSA\n";

$setPermissions = true;
$apache_virtual_hosts_dir="/etc/apache2/sites-enabled";
$user="evaldo";
$group="www-data";

function printmessage($message,$isError=false) {
	$info = ( $isError )
		? "ERROR"
		: "OK";

	echo "\n+======================================================================\n";
	echo "| ADDHOST\n";
	echo "| ---------------------------------------------------------------------\n";
	echo "| [ $info ] {$message}\n";
	echo "+======================================================================\n\n";
}

if ( count($argv) < 4 ) {
	printmessage(
		"Sao necessarios pelo menos tres parametros: IP, nome_do_servidor e diretorio_da_aplicacao",
		true
	);
	die();
}

$ip = $argv[1];
$server_name = $argv[2];
$app_path = $argv[3];

if ( !preg_match("(^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$)", $ip) ) {
	printmessage(
		"O primeiro parâmetro deve ser o IP da aplicação a ser configurado\n",
		true
	);
	die();
}

if ( !file_exists($app_path) ) {
	printmessage( "CRIANDO DIRETÓRIO" );
	$setPermissions = true;
	mkdir($app_path, 0775, true);
	$setPermissions = true;
}

$contents = file_get_contents("/etc/hosts");
	if ( strpos($contents, $ip) ) {
		printmessage(
			"O IP informado já encontra-se em uso. Escolha outro.",
			true
		);
		die();
	}

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

printmessage( "SEU SERVIDOR FOI CRIADO CORRETAMENTE" );

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

	printmessage( "SEU HTACCESS FOI CRIADO CORRETAMENTE" );
}

if ( $setPermissions ) {
	echo "CONFIGURANDO PERMISSOES\n";
	//Folder
	chown(dirname($app_path), $user);
	chgrp(dirname($app_path), $group);

	//htaccess
	chown("{$app_path}/.htaccess", $user);
	chgrp("{$app_path}/.htaccess", $group);

	echo "CONFIGURANDO HOST\n";
	$contents .= "\n{$ip}\t{$server_name}";
	file_put_contents("/etc/hosts", $contents);
}
?>
