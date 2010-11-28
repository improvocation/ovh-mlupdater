<?php

class Config{
	
	private $data;
	private $file;
	private $logger;
	
	public function __construct($config_file,Logger $logger){
		$this->file = $config_file;
		$this->load();	
		$this->logger = $logger;
	}
	
	public function load(){
		$this->data = Spyc::YAMLLoad($this->file);
	}
	
	public function dump(){
		$yaml = Spyc::YAMLDump($this->data);
		$this->logger->log('Write to : '.$this->file);
		file_put_contents($this->file, $yaml);
	}

	public function setData($data){
		$this->data = $data;		
	}
	public function getData(){
		return $this->data;		
	}
	
	public function g($key){
		$val = null;
			
		if( FALSE !== strpos($key,'.') ){
			$keys = explode('.', $key);
			$val = $this->data;
			foreach($keys as $k)
				$val = $val[$k];
		}else{
			$val = $this->data[$key];
		}
		
		return $val;
		
	} 
	
	
}