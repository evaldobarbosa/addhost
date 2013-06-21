#!/usr/bin/php -q
<?
require("addhost.class.php");

echo "CONFIGURADOR DE HOSTS APACHE\n";
echo "by: EVALDO BARBOSA\n";

$htaccess = ( in_array( '--htaccess', $argv) );
$composer = ( in_array( '--composer', $argv) );

$addhost = new AddHost( $argv[1], $argv[2], $argv[3],$htaccess,$composer);

$log = $addhost->run();

if ( isset($log['success'])  ) {
	echo "\n===================\n";
	echo "| SUCCESS (ADDHOST)\n";
	
	foreach ($log['success'] as $key => $value) {
		echo "| {$value}\n";
	}
	
	echo "===================\n";

} else {
	echo "\n===================\n";
	echo "| ERROR - ( ADDHOST )\n";
	echo "| {$log['error']}\n";
	echo "===================\n";
}
