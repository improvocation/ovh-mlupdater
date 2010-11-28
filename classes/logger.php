<?php


class Logger{
	public function log($str){
		echo "\n".'['.date('Y-m-d h:i:s').'] '.$str;
		
	}
	
	public function __destruct(){
		echo "\n";
	}
	
}