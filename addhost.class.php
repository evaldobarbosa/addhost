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
	private $log = array();
	private $rollback = array();

	function __construct($ip,$hostname,$folder,$htacess = false,$composer = false,$errorlog = false) {
		$this->setIP( $ip );
		$this->setHostname($hostname);
		$this->setFolder($folder);
		$this->htaccessCreation = $htacess;
		$this->composerDownload = $composer;
		$this->createErrorLog = $errorlog;
	}

	function setIP($value) {
		$this->ip = $value;
	}

	function setHostname($value) {
		$this->hostname = $value;
	}

	function setFolder($value) {
		$this->folder = $value;
	}

	function getPublicFolder() {
		return "{$this->folder}/public";
	}

	private function createVHost() {
		$filename = APACHE_VHOST_PATH . "/" . strtolower($this->hostname) . ".conf";
		if ( file_exists($filename) ) {
			throw new Exception("VHost já configurado", 1);
		}

		$this->log['vhost'] = "CONFIGURANDO VIRTUALHOST";

		$vhc = array(); //virtual_host_content
		$vhc[] = "### CREATED BY ADDHOST: " . date("Y-m-d H:i:s") . "###";
		$vhc[] = "NameVirtualHost {$this->ip}:80";
		$vhc[] = "<VirtualHost {$this->ip}:80>";
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
			$vhc[] = '\tErrorLog ${APACHE_LOG_DIR}/error.log';
		}

		$vhc[] = "</VirtualHost>";

		$f = file_put_contents($filename, implode("\n", $vhc));

		unset($vhc);

		if ( !$f ) {
			$this->rollback['vhost'] = $filename;
			throw new Exception("Erro ao criar arquivo de vhost", 1);
		}
	}

	private function appendHostName() {
		$this->log['hostname'] = "CONFIGURANDO HOST";

		$contents = file_get_contents(HOSTS_FILE);
		$hostname .= "\n{$this->ip}\t{$this->hostname}";

		$pos = strpos($contents, "\t{$this->hostname}");
		if ( $pos === true ) {
			$this->rollback['hosts'] = true;
			throw new Exception("Erro ao adicionar host no arquivo", 1);
		}

		$contents .= $hostname;
		$f = file_put_contents( dirname(__FILE__ ). "/hosts.temp" , $contents);

		if ( !$f ) {
			$this->rollback['hosts'] = true;
			unlink($f);
			throw new Exception("Erro ao adicionar host no arquivo", 1);			
		}

		$this->log['hostname1'] = "HOSTNAME CONFIGURADO";
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
			throw new Exception("Erro ao criar pastas do host", 1);			
		}
	}

	private function createHTAccess() {
		$this->log['htaccess'] = "CONFIGURANDO HTACCESS\n";

		$path = "{$this->getPublicFolder()}/.htaccess";

		$vhc = array(); //virtual_host_content
		$vhc[] = "### CREATED BY ADDHOST: " . date("Y-m-d H:i:s") . "###";
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
			throw new Exception("Erro ao criar htaccess", 1);
		}

		//htaccess
		chown($path, CURRENT_USER);
		chgrp($path, APACHE_GROUP);

		$this->log['htaccess1'] = "SEU HTACCESS FOI CRIADO CORRETAMENTE";
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

		$this->log['composer'] = "DOWNLOAD DO COMPOSER\n";

		$ch = $this->getCurlInstance($url);
	    $data = curl_exec($ch);
	    curl_close($ch);	 

		if ( !$data || !file_put_contents($path, $data) ) {
			$this->rollback['composer'] = true;
			throw new Exception("Erro no download do composer", 1);
		}

		//htaccess
		chown($path, CURRENT_USER);
		chgrp($path, APACHE_GROUP);
		$this->log['composer1'] = "DOWNLOAD DO COMPOSER REALIZADO\n";

		$this->log['composer2'] = "CRIANDO ARQUIVO composer.json PADRÃO";

		$contents = array();
		$contents[] = '{';
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
		$contents[] = '	}';
		$contents[] = '}';

		if ( !file_put_contents("{$this->folder}/composer.json", implode("\n",$contents) ) ) {
			$this->log['composer'] = "ERRO AO CRIAR composer.json";
		}

		chown("{$this->folder}/composer.json", CURRENT_USER);
		chgrp("{$this->folder}/composer.json", APACHE_GROUP);
	}

	private function validateIP() {
		if ( !preg_match("(^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$)", $this->ip) ) {
			throw new Exception("O primeiro parâmetro deve ser o IP da aplicação a ser configurado");
		}
	}

	function getLog() {
		return $this->log;
	}

	function run() {
		try {
			$this->validateIP();
			$this->createVHost();
			$this->appendHostName();
			$this->createFolder();

			if ( $this->htaccessCreation ) {
				$this->createHTAccess();
			}

			if ( $this->composerDownload ) {
				$this->downloadComposer();
			}

			$filename = dirname( __FILE__ ). "/hosts.temp";
			if ( file_exists($filename) ) {
				copy( $filename, HOSTS_FILE );
				unlink( $filename );
				echo "ARQUIVOS COPIADOS\n";
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

			return array("error"=>$e->getMessage());
		}
	}
}
