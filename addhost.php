#!/usr/bin/php -q
<?
//phpinfo();
if (in_array("mod_rewrite",get_loaded_extensions())) {
	echo "Esta instalado";
	die();
}

require("addhost.class.php");

$lang = require( strtolower( LANGUAGE ) . '.lang.php' );

echo $lang[ 'project_name' ], "\n";
echo "by: EVALDO BARBOSA\n";

$addhost = new AddHost( $argv[1], $argv[2] );

if ( in_array( '--removehost', $argv ) ) {
	
	$log = $addhost->removeHost();

} else if ( in_array( '--checkhost', $argv ) ) {
	
	$log = $addhost->checkHost();

} else {

	$addhost->setFolder( $argv[3] );
	$addhost->setHTAccessOn( in_array( '--htaccess', $argv) );
	$addhost->setComposerDownloadOn( in_array( '--composer', $argv) );
	$addhost->setErrorLogOn( in_array( '--errorlog', $argv) );

	$log = $addhost->run();

}

if ( isset($log['success']) && count($log['success']) > 0 ) {
	echo "\n===================\n";
	echo "| {$lang['success']} (ADDHOST)\n";
	
	foreach ($log['success'] as $key => $value) {
		echo "| {$value}\n";
	}
	
	echo "===================\n";

}

if ( isset($log['alert']) && count($log['alert']) > 0 ) {
	echo "\n===================\n";
	echo "| {$lang['alert']} (ADDHOST)\n";
	
	foreach ($log['alert'] as $key => $value) {
		echo "| {$value}\n";
	}
	
	echo "===================\n";

}

if ( isset($log['error']) && count($log['error']) > 0 ) {
	echo "\n===================\n";
	echo "| {$lang['error']} ( ADDHOST )\n";

	foreach ($log['error'] as $key => $value) {
		echo "| {$value}\n";
	}

	echo "===================\n";
}
