#!/usr/bin/php -q
<?
echo "CONFIGURADOR DE HOSTS APACHE\n";
echo "by: EVALDO BARBOSA\n";

$apache_virtual_hosts_dir="/etc/apache2/sites-enabled";
$project_owner="evaldo";
$apache_user="www-data";

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
	chown($app_path, $project_owner);
	chgrp($app_path, $apache_user);
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
$vhc[] = "### CREATED " . date("Y-m-d H:i:s") . "###";
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
?>
