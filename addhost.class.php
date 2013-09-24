<?
require "conf.php";

class AddHost {
 	private $ip;
	private $hostname;
	private $folder;
	private $folderCreated = false;
	private $htaccessCreation = false;
	private $composerDownload = false;
	private $createErrorLog = false;
	private $isCName = false;
	private $log = array();
	private $rollback = array();
	private $lang = array();

	function __construct($ip,$hostname) {
		$this->setIP( $ip );
		$this->setHostname($hostname);

		$language_file = __DIR__ . '/' . strtolower( LANGUAGE ) . '.lang.php';

		$this->lang = require( $language_file );
	}

	function writeConsoleHeader() {
		$ar = array();
		$ar[] = "   __    ____   ____   _   _  _____  ___  ____ ";
		$ar[] = "  /__\  (  _ \ (  _ \ ( )_( )(  _  )/ __)(_  _)";
		$ar[] = " /(__)\  )(_) ) )(_) ) ) _ (  )(_)( \__ \  )(  ";
		$ar[] = "(__)(__)(____/ (____/ (_) (_)(_____)(___/ (__) ";
		$ar[] = "                http://github.com/evaldobarbosa";

		echo implode("\n", $ar);
		echo "\n_______________________________________________\n";

		unset($ar);
	}

	function setIP($value) {
		$this->ip = $value;
	}

	function setHostname($value) {
		$this->hostname = $value;
	}

	function setFolder($value) {
		if ( substr($value,0,7) == "__PRJ__" ) {
			if ( defined("DEFAULT_PRJ_PATH") ) {
				$value = str_replace("__PRJ__", DEFAULT_PRJ_PATH, $value);
				echo "new folder path: {$value}";
			} else {
				$this->rollback['folder'] = true;
				$this->log['default_folder'] = $this->lang['folder']['folder_def_not_defined'];
				return false;
			}
		}
		$this->folder = $value;
	}

	function getPublicFolder() {
		return "{$this->folder}/public";
	}

	function setHTAccessOn($value) {
		$this->htaccessCreation = $value;
	}

	function setComposerDownloadOn($value) {
		$this->composerDownload = $value;
	}

	function setErrorLogOn($value) {
		$this->createErrorLog = $value;
	}

	function setCNameOn($value) {
		$this->isCName = $value;
	}

	private function createVHost() {
		$filename = APACHE_VHOST_PATH . "/" . strtolower($this->hostname) . ".conf";
		if ( file_exists($filename) ) {
			throw new Exception($this->lang['vhost_exists'], 1);
		}

		$this->log['vhost'] = $this->lang['vhost_config'];

		$myIp = ( !$this->isCName )
			? $this->ip
			: '*';

		$vhc = array(); //virtual_host_content
		$vhc[] = "### {$this->lang['created_by']} ADDHOST: " . date("Y-m-d H:i:s") . "###";
		$vhc[] = "NameVirtualHost {$myIp}:80";
		$vhc[] = "<VirtualHost {$myIp}:80>";
		$vhc[] = "\tServerAdmin hostmaster@{$this->hostname}";
		$vhc[] = "\tServerName {$this->hostname}";
		$vhc[] = "\tDocumentRoot {$this->getPublicFolder()}";
		$vhc[] = "\t<Directory />";
		$vhc[] = "\t\tOptions Indexes FollowSymLinks MultiViews";
		$vhc[] = "\t\tAllowOverride All";
		$vhc[] = "\t\tOrder allow,deny";
		$vhc[] = "\t\tAllow from all";
		$vhc[] = "\t</Directory>";

		if ( $this->createErrorLog ) {
			$vhc[] = "\tErrorLog $\{APACHE_LOG_DIR\}/{$this->hostname}.log";
		}

		$vhc[] = "</VirtualHost>";

		$f = file_put_contents($filename, implode("\n", $vhc));

		unset($vhc);

		if ( !$f ) {
			$this->rollback['vhost'] = $filename;
			throw new Exception( $this->lang['vhost_create_error'], 1);
		}
	}

	private function appendHostName() {
		$this->log['hostname'] = $this->lang['host_config'];

		$contents = file_get_contents(HOSTS_FILE);
		$hostname .= "\n{$this->ip}\t{$this->hostname}";

		$pos = strpos($contents, "\t{$this->hostname}");
		if ( $pos === true ) {
			$this->rollback['hosts'] = true;
			throw new Exception( $this->lang['host_add_name_error'], 1 );
		}

		$contents .= $hostname;
		$f = file_put_contents( dirname(__FILE__ ). "/hosts.temp" , trim($contents) );

		if ( !$f ) {
			$this->rollback['hosts'] = true;
			unlink($f);
			throw new Exception( $this->lang['host_addname_error'], 1 );			
		}

		$this->log['hostname1'] = $this->lang['host_success'];
	}

	private function createFolder() {
		$f1 = mkdir($this->folder, 0775, true);
		$p1 = mkdir($this->getPublicFolder(), 0775, true);

		//Folder
		$f2 = chown($this->folder, CURRENT_USER);
		$p2 = chgrp($this->folder, APACHE_GROUP);

		$f3 = chown($this->getPublicFolder(), CURRENT_USER);
		$p3 = chgrp($this->getPublicFolder(), APACHE_GROUP);

		if ( !$f1 || !$f2 || !$f3 || !$p1 || !$p2 || !$p3 ) {
			$this->rollback['folder'] = true;
			throw new Exception( $this->lang['folder_create_error'], 1 );			
		}
	}

	private function createHTAccess() {
		$this->log['htaccess'] = $this->lang['htaccess_label'];

		$path = "{$this->getPublicFolder()}/.htaccess";

		$vhc = array(); //virtual_host_content
		$vhc[] = "### {$this->lang['created_by']} ADDHOST: " . date("Y-m-d H:i:s") . "###";
		$vhc[] = "Options +FollowSymlinks";
		$vhc[] = "RewriteEngine On";

		$vhc[] = "RewriteCond %{REQUEST_URI} !\.(gif|jpg|png)$";
		$vhc[] = "RewriteCond %{REQUEST_FILENAME} !-f";
		$vhc[] = "RewriteCond %{REQUEST_FILENAME} !-d";
		$vhc[] = "RewriteRule (.*) /index.php [L]";

		$f = file_put_contents($path, implode("\n", $vhc));

		unset($vhc);

		if ( !$f ) {
			$this->rollback['htaccess'] = true;
			throw new Exception($this->lang['htaccess_create_error'], 1);
		}

		//htaccess
		chown($path, CURRENT_USER);
		chgrp($path, APACHE_GROUP);

		$this->log['htaccess1'] = $this->lang['htaccess_success'];
	}

	private function &getCurlInstance($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);

		if ( defined('PROXY_HOST') ) {
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
				//curl_setopt($ch, CURLOPT_PROXYPORT, $p['port']);
				curl_setopt($ch, CURLOPT_PROXY, PROXY_HOST);
		}
		if ( defined("PROXY_USER") ) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USER);
		}

		return $ch;
	}

	private function downloadComposer() {
		$url  = 'http://getcomposer.org/composer.phar';
		$path = "{$this->folder}/composer.phar";

		$this->log['composer'] = $this->lang['composer_download'];

		$ch = $this->getCurlInstance($url);
	    $data = curl_exec($ch);
	    curl_close($ch);	 

		if ( !$data || !file_put_contents($path, $data) ) {
			$this->rollback['composer'] = true;
			throw new Exception( $this->lang['composer_dl_error'], 1);
		}

		//htaccess
		chown($path, CURRENT_USER);
		chgrp($path, APACHE_GROUP);
		$this->log['composer1'] = $this->lang['composer_success'];

		$this->log['composer2'] = $this->lang['composer_json_ok'];

		$developerName = ( defined('DEVELOPER_NAME') )
			? DEVELOPER_NAME
			: 'Your name';

		$developerMail = ( defined('DEVELOPER_MAIL') )
			? DEVELOPER_MAIL
			: 'your-dev@mail.net';

		$contents = array();
		$contents[] = '{';
		$contents[] = '	"name": "' . CURRENT_USER . '/' . $this->hostname . '",';
		$contents[] = '	"description": "Type your description to ' . $this->folder . '",';
		$contents[] = '	"license": "LGPL-3.0+",';
    	$contents[] = '	"require-dev": {';
        $contents[] = '		"phpunit/phpunit": "@stable"';
    	$contents[] = '	},';
    	$contents[] = '	"require": {';
        $contents[] = '		"php": ">=5.4"';
    	$contents[] = '	},';
    	$contents[] = '	"config": { "bin-dir": "bin" },';
		$contents[] = '	"autoload": {';
		$contents[] = '		"psr-0": {';
		$contents[] = '			"": "src"';
		$contents[] = '		}';
		$contents[] = '	},';
		$contents[] = '	"authors": [';
        $contents[] = '		{';
        $contents[] = '			"name": "' . $developerName . '",';
        $contents[] = '			"email": "' . $developerMail . '"';
        $contents[] = '		}';
        $contents[] = '	]';
		$contents[] = '}';

		if ( !file_put_contents("{$this->folder}/composer.json", implode("\n",$contents) ) ) {
			$this->log['composer'] = $this->lang['composer_json_error'];
			$this->log['warning'] = $this->lang['composer_licence_warn'];
		}

		chown("{$this->folder}/composer.json", CURRENT_USER);
		chgrp("{$this->folder}/composer.json", APACHE_GROUP);
	}

	private function validateIP() {
		if ( !preg_match("(^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$)", $this->ip) ) {
			throw new Exception( $this->lang['param_first_error'] );
		}
	}

	function getLog() {
		return $this->log;
	}

	function run() {
		try {
			if ( !$this->isCName ) {
				$this->validateIP();
			}
			$this->createVHost();

			if ( !$this->isCName ) {
				$this->appendHostName();
			}

			$this->createFolder();

			if ( $this->htaccessCreation ) {
				$this->createHTAccess();
			}

			if ( $this->composerDownload ) {
				$this->downloadComposer();
			}

			if ( !$this->isCName ) {
				$filename = dirname( __FILE__ ). "/hosts.temp";
				if ( file_exists($filename) ) {
					copy( $filename, HOSTS_FILE );
					unlink( $filename );
					echo $this->lang['copy_file'], "\n";
				}
			}

			return array("success"=>$this->log);
		} catch ( Exception $e ) {
			if ( isset($this->rollback['vhost']) ) {
				unlink( $this->rollback['vhost'] );
			}

			if ( isset($this->rollback['hosts']) ) {
				$filename = dirname( __FILE__ ). "/hosts.temp";
				unlink($filename);
			}

			if ( isset($this->rollback['folder']) ) {
				$public = $this->getPublicFolder();
				unlink( $public );
				unlink( $this->folder );
			}

			if ( isset($this->rollback['htaccess']) ) {
				unlink( $this->rollback['htaccess'] );
			}

			return array("error"=>array('error'=>$e->getMessage()));
		}
	}

	function checkHost() {
		$hosts = file_get_contents(HOSTS_FILE);

		$matches = array();
		preg_match_all("({$this->ip}|{$this->hostname})", $hosts, $matches);
			$matches = $matches[0];
		
		$log = array('alert'=>array());

		if ( in_array($this->ip, $matches) && in_array($this->hostname, $matches) ) {
			$log['alert']['host_exists'] = $this->lang['host_ip_exists_alert'];
		} else if ( in_array($this->hostname, $matches) ) {
			$log['alert']['host_exists'] = $this->lang['host_exists_alert'];
		} else {
			$log['alert']['host_not_exists'] = $this->lang['host_not_exists_alert'] . ' (' . HOSTS_FILE . ')';
		}

		return $log;
	}

	function removeHost() {
		$hosts = file_get_contents(HOSTS_FILE);

		$matches = array();
		preg_match_all("({$this->ip}(.*?){$this->hostname})", $hosts, $matches);

		$log = array(
			'success'	=> array(
			),
			'error'		=> array(
			)
		);

		try {
			$projectFolderPath		= null;

			if ( count($matches) > 0 ) {

				$hosts = str_replace($matches[0][0], "", $hosts);
				file_put_contents(HOSTS_FILE, $hosts);
				$log['success']['host'] = $this->lang['host_remove_success'];

			} else {
				$log['error']['host'] = $this->lang['host_remove_ip_error'];
			}

			$vhost_file = APACHE_VHOST_PATH . "/{$this->hostname}.conf";
			if ( file_exists( $vhost_file ) ) {
				preg_match_all("(DocumentRoot (.*)/public)", file_get_contents( $vhost_file ), $matches);

				if ( isset($matches[1][0]) ) {
					$projectFolderPath = $matches[1][0];
				}

				if ( unlink($vhost_file) ) {
					$log['success']['vhost'] = $this->lang['vhost_remove_success'];
				} else {
					$log['success']['vhost'] = $this->lang['vhost_remove_error'];
				}

			} else {
				$log['error']['vhost'] = $this->lang['vhost_not_found'];
			}

			if ( is_null($projectFolderPath) ) {
				$log['error']['folder'] = $this->lang['folder_remove_manually'];
			} else if ( file_exists($projectFolderPath) ) {

				$this->removeFolder( $projectFolderPath );

				if ( !file_exists( $projectFolderPath ) ) {
					$log['success']['folder'] = $this->lang['folder_remove_success'];
				} else {
					$log['error']['folder'] = $this->lang['folder_remove_error'];
				}

			} else {
				$log['error']['folder'] = $this->lang['folder_not_found'];
			}

		} catch (Exceptionb $e) {
			$log["error"]['unknown'] = $e->getMessage();
		}

		foreach ($log['success'] as $key => $value) {
			if ( is_null( $value ) ) {
				unset($log['success'][$key]);
			}
		}

		foreach ($log['error'] as $key => $value) {
			if ( is_null( $value ) ) {
				unset($log['error'][$key]);
			}
		}

		return $log;
	}

	private function removeFolder($dir) {
	    $it = new RecursiveDirectoryIterator($dir);

	    $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

	    foreach($it as $file) {
	        if ('.' === $file->getBasename() || '..' ===  $file->getBasename()) continue;

	        if ($file->isDir()) {
	        	$this->removeFolder($file->getPathname());
	        } else {
	        	unlink($file->getPathname());
	        }
	    }

	    rmdir($dir);
	}
}
