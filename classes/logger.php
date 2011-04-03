<?php


class Logger{
	private $debug=false;

	public function setDebug($d){
		$this->debug = $d;
		$this->log('Debug set to '.$d,'debug');
	}

	public function log($str,$level='normal'){
		
		if('normal' == $level || 'debug' == $level && $this->debug){
			echo "\n".'['.date('Y-m-d h:i:s').'] '.$str;
		}
		
		
	}
	
	public function __destruct(){
		echo "\n";
	}
	
}
