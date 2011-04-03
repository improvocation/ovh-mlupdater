<?php


class Updater{
	
	private $domain;
	private $username;
	private $password;
	private $logger;
	private $session;
	private $cacheFolder;
	private $soap;
	private $connected = false;
	private $exceptions = Array();
	private static $CACHE_FOLDER_NAME = 'cache';
	private static $CACHE_LIFESPAN = 86300; // a bit less than day
	private static $DEST_UPDATE_LIFESPAN = 86300; // a it less than day
	
	public function __construct($domain,$username,$password,Logger $logger,$rootfolder){
		$this->domain = $domain;
		$this->username = $username;
		$this->password = $password;
		$this->logger = $logger;
		
		$this->cacheFolder = $rootfolder.'/'.Updater::$CACHE_FOLDER_NAME.'/';
	}
	
	
	public function update($sources,$destinations){
		$this->ensureConnected();
		
		foreach($sources as $s)
			$this->updateSourceCache($s);
		foreach($destinations as $d)
			$this->updateDestination($d,$sources);
					
	}

	public function addToList($addresses,$list){
		$this->ensureConnected();
		
		$this->logger->log('Adding to list...');
		foreach($addresses as $mail)
			$this->addMemberToList($list,$mail);
		$this->logger->log('Adding finished.');
	}
	
	public function getExceptions(){
		return $exceptions;
	}
	
	private function updateSourceCache($source){
		$this->logger->log('Updating source cache for '.$source,'debug');
		$timestamp = @file_get_contents($this->cacheFolder.'/'.$source.'.last-fetch');
		
		if( time() - $timestamp > Updater::$CACHE_LIFESPAN){
			$this->logger->log('Refreshing cached list of "'.$source.'"');
			$list = $this->retreiveMembersList($source);
			$this->logger->log('New list has '.count($list).' entries.');
			$cf = new Config($this->cacheFolder.'/'.$source.'.yml',$this->logger);
			$cf->setData($list);
			$cf->dump();
			
			file_put_contents($this->cacheFolder.'/'.$source.'.last-fetch',time());
		}else{
			$this->logger->log('Not updating source, last update is recent enough.','debug');
		}
	}
	
	private function updateDestination($dest,$sources){
		$this->logger->log('Updating destination '.$dest.' with sources '.implode('; ',$sources),'debug');
		$timestamp = @file_get_contents($this->cacheFolder.'/'.$dest.'.last-dest-update');
		
		if( time() - $timestamp > Updater::$DEST_UPDATE_LIFESPAN){
			$this->logger->log('Updating destination : "'.$dest.'".');
			foreach($sources as $source){
				$this->logger->log('Updating "'.$dest.'" with source "'.$source.'".');
				$cf = new Config($this->cacheFolder.'/'.$source.'.yml',$this->logger);
				$data = $cf->getData();
				$c = 0;
				foreach($data as $address){
					$c++;
					$this->addMemberToList($dest,$address);
				}
				$this->logger->log('Added '.$c.' mails to "'.$dest.'" from source "'.$source.'".');
			}
		}else{
			$this->logger->log('Not updating destination, last update is recent enough.','debug');
		}
			
		file_put_contents($this->cacheFolder.'/'.$dest.'.last-dest-update',time());
		
	}
	
	private function addMemberToList($list,$address){
		$this->logger->log('Soap request: mailingListSubscriberAdd('.$this->session.','.$this->domain.','.$list.','.$address.')','debug');
		try{				
			$this->soap->mailingListSubscriberAdd(
					$this->session, 
					$this->domain, 
					$list, 
					$address);
		}catch(SoapFault $fault) {
			$this->exceptions[] = $fault;
		}
		//$this->logger->log('Soap request finished.');
	}
	
	private function retreiveMembersList($source){
		$this->logger->log('Soap request: mailingListSubscriberList('.$this->session.','.$this->domain.','.$source.')','debug');
		$result = false;
		try{
			$result = $this->soap->mailingListSubscriberList(
				$this->session, 
				$this->domain, 
				$source);
		}catch(SoapFault $fault) {
			$this->exceptions[] = $fault;
		}
		 	
		return $result;
		
	}

	private function ensureConnected(){
		if(!$this->connected){
			$this->connect();
		}  
	}
	
	private function connect(){
		$this->logger->log('Connecting...');
		try{
			$this->soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.12.wsdl");
			$this->session = $this->soap->login($this->username, $this->password,"en", false);
			$this->connected = true;
			$this->logger->log('Connected.');
		}catch(SoapFault $fault) {
			$this->logger->log("Connection failed : ".$fault);
			$this->connected = false;
		}
	}	
	
	private function disconnect(){
		if($this->connected){
			$soap->logout($session);
			$this->connected = false;
		}
	}
	
}
