<?php

/**
 * php源码加密
 * @author paperen<paperen@foxmail.com>
 * @url 加密部分参考来源 http://www.cnblogs.com/keheng/articles/2496970.html
 */
class encrypt
{

	const FILE_ERROR = '-1';
	const FILE_ENCRYPT = '1';
	const BACK_ERROR = '-2';

	private $_dir;
	function __construct($dir) {
		$this->_dir = $dir;
	}
	
	/**
	 * 记录涉及所有文件
	 */
	private $_files = array();
	
	public function run() {
		if ( empty( $this->_dir ) ) {
			$this->_log(self::FILE_ERROR, $this->_dir);
			return;
		}
		if ( $this->_backup($this->_dir) ) {
			foreach($this->_files as $file) $this->_encrypt_file($file);
			return;
		}
		$this->_log(self::FILE_ERROR, $this->_dir);
	}
	
	private function _log($code, $file) {
		if ( self::FILE_ERROR == $code ) {
			$this->_log[] = "file or directory not exists - {$file}";
		} else if( self::FILE_ENCRYPT == $code ) {
			$this->_log[] = "encrypt success - {$file}";
		}
	}
	
	private function _backup($d, $bak_dir = NULL) {
		// is file
		if ( is_file($d) ) {
			$p = pathinfo($d);
			// 不对php以外的文件加密
			if ( $p['extension'] != 'php' ) return;
			$bak_dir = ($bak_dir) ? dirname($bak_dir) : $p['dirname'] . DIRECTORY_SEPARATOR . 'bak' . DIRECTORY_SEPARATOR;
			!is_dir($bak_dir) && mkdir($bak_dir);
			if (!copy($d, $bak_dir.DIRECTORY_SEPARATOR.$p['basename'])) {
				$this->_log(self::BACK_ERROR, $d);
				return FALSE;
			}
			$this->_files[] = $d;
		}
		// is dir
		if ( is_dir( $d ) ) {
			$bak_dir = empty($bak_dir) ? dirname($d) . DIRECTORY_SEPARATOR . 'bak' . DIRECTORY_SEPARATOR : $bak_dir . DIRECTORY_SEPARATOR;
			!is_dir($bak_dir) && mkdir($bak_dir);
			if ($dh = opendir($d)) {
				while (($file = readdir($dh)) !== false) {
					if ( $file != '..' && $file != '.' ) {
						if ( is_dir($d.DIRECTORY_SEPARATOR.$file) ) {
							$this->_backup($d.DIRECTORY_SEPARATOR.$file, $bak_dir.$file);
						} else {
							$p = pathinfo($bak_dir.DIRECTORY_SEPARATOR.$file);
							$this->_backup($d.DIRECTORY_SEPARATOR.$file, $bak_dir.$p['basename']);
						}
					}
				}
				closedir($dh);
			}
		}
		return TRUE;
	}
	
	/**
	 * 输出结果
	 */
	public function result() {
		foreach( $this->_log as $line ) {
			echo "{$line}\n";
		}
	}
	
	/**
	 * 日志
	 */
	private $_log = array();

	private function _randstr() {
		$str="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		return str_shuffle($str);
	}

	/**
	 * PHP加密
	 * @param string $file 文件
	 * @return NULL
	 */
	private function _encrypt_file($file) {
		if ( !file_exists($file) ) {
			$this->_log(self::FILE_ERROR, $file);
			return;
		}
		$k1 = $this->_randstr();
		$k2 = $this->_randstr();
		$vstr = file_get_contents($file);
		$v1 = base64_encode($vstr);
		$c = strtr($v1,$k1,$k2);
		$c = $k1.$k2.$c;
		$q1 = "O00O0O";
		$q2 = "O0O000";
		$q3 = "O0OO00";
		$q4 = "OO0O00";
		$q5 = "OO0000";
		$q6 = "O00OO0";
		$s = '$'.$q6.'=urldecode("%6E1%7A%62%2F%6D%615%5C%76%740%6928%2D%70%78%75%71%79%2A6%6C%72%6B%64%679%5F%65%68%63%73%77%6F4%2B%6637%6A");$'.$q1.'=$'.$q6.'{3}.$'.$q6.'{6}.$'.$q6.'{33}.$'.$q6.'{30};$'.$q3.'=$'.$q6.'{33}.$'.$q6.'{10}.$'.$q6.'{24}.$'.$q6.'{10}.$'.$q6.'{24};$'.$q4.'=$'.$q3.'{0}.$'.$q6.'{18}.$'.$q6.'{3}.$'.$q3.'{0}.$'.$q3.'{1}.$'.$q6.'{24};$'.$q5.'=$'.$q6.'{7}.$'.$q6.'{13};$'.$q1.'.=$'.$q6.'{22}.$'.$q6.'{36}.$'.$q6.'{29}.$'.$q6.'{26}.$'.$q6.'{30}.$'.$q6.'{32}.$'.$q6.'{35}.$'.$q6.'{26}.$'.$q6.'{30};eval($'.$q1.'("'.base64_encode('$'.$q2.'="'.$c.'";eval(\'?>\'.$'.$q1.'($'.$q3.'($'.$q4.'($'.$q2.',$'.$q5.'*2),$'.$q4.'($'.$q2.',$'.$q5.',$'.$q5.'),$'.$q4.'($'.$q2.',0,$'.$q5.'))));').'"));';
		$s='<?
		'.$s.
		'
		 ?>';
		$h = fopen($file,'w');
		if (fwrite($h,$s)) {
			$this->_log(self::FILE_ENCRYPT, $file);
		}
		fclose($h);
	}
}

$readme =<<<EOT
---------------------------------------
encrypt readme

-d	file or directory
example:
php encrypt.php -d D:\\test\\a.php
php encrypt.php -d D:\\test\\
php encrypt.php -d /home/www/index.php

It will backup the source file to "bak" folder
EOT;

if ( isset( $argv ) ) {
	$command = isset($argv[1]) ? $argv[1] : '-h';
	switch($command) {
		case '-h':
		case '--h':
			echo $readme;
			break;
		case '-d':
			if ( !isset($argv[2]) ) {
				exit('please tell me file or directory.');
			} else {
				$t = new encrypt($argv[2]);
				$t->run();
				$t->result();
			}
	}
}