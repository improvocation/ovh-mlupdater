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
	private $force = false;
	private static $CACHE_FOLDER_NAME = 'cache';
	private static $CACHE_LIFESPAN = 14000; // a bit less than 4 hours
	private static $DEST_UPDATE_LIFESPAN = 14000; // a it less than 4 hours

	private static $ML_OP_IN_PROGRESS = 'soap:211';

	
	public function __construct($domain,$username,$password,Logger $logger,$rootfolder,$force=false){
		$this->domain = $domain;
		$this->username = $username;
		$this->password = $password;
		$this->logger = $logger;
		$this->force = $force;
		$this->cacheFolder = $rootfolder.'/'.Updater::$CACHE_FOLDER_NAME.'/';
	}
	
	
	public function update($sources,$destinations){
		$this->ensureConnected();
		
		foreach($sources as $s)
			$this->updateSourceCache($s);
		foreach($destinations as $d)
			$this->updateDestination($d,$sources);
					
	}

	public function removeFromList($addresses,$list){
                $this->ensureConnected();

                $this->logger->log('Removing from list '.count($addresses).' addresses...');

                $this->logger->log("Checking for the addresses really present in the list in the ".count($addresses)."...");
                $destlist = $this->retreiveMembersList($list);
                if($destlist){
                        $addresses = array_intersect($addresses,$destlist);

                        $this->logger->log("Really removing ".count($addresses)." mails...");
                        foreach($addresses as $mail)
                                $this->removeMemberFromList($list,$mail);
                        $this->logger->log('removing finished.');
                }else{
                        $this->logger->log('Could not remove addresses: unable to load existing address list.');
                }
	}

	public function addToList($addresses,$list){
		$this->ensureConnected();
		
		$this->logger->log('Adding to list '.count($addresses).' addresses...');
		
		$this->logger->log("Checking for duplicates in the ".count($addresses)."...");
		$destlist = $this->retreiveMembersList($list);
		if($destlist){
			$addresses = array_diff($addresses,$destlist);
			
			$this->logger->log("Really adding ".count($addresses)." mails...");
			foreach($addresses as $mail)
				$this->addMemberToList($list,$mail);
			$this->logger->log('Adding finished.');
		}else{
			$this->logger->log('Could not add new addresses: unable to load existing address list.');
		}
	}
	
	public function getExceptions(){
		return $this->exceptions;
	}
	
	private function updateSourceCache($source){
		$this->logger->log('Updating source cache for '.$source,'debug');
		$timestamp = @file_get_contents($this->cacheFolder.'/'.$source.'.last-fetch');
		
		if( time() - $timestamp > Updater::$CACHE_LIFESPAN || $this->force){
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
		

		if( time() - $timestamp > Updater::$DEST_UPDATE_LIFESPAN  || $this->force){
			$this->logger->log('Updating destination : "'.$dest.'".');
			
			$this->logger->log('Retrieving list from destination '.$dest.'. ','debug');
			$destlist = $this->retreiveMembersList($dest);
			
			foreach($sources as $source){
				$this->logger->log('Updating "'.$dest.'" with source "'.$source.'".');
				$cf = new Config($this->cacheFolder.'/'.$source.'.yml',$this->logger);
				
				$data = $cf->getData();
				// give everybody a chance to be first :)
				shuffle($data);
				
				$c = 0;
				foreach($data as $address){
					if( ! in_array($address,$destlist) ){
						$this->logger->log('Adding '.$address.' (not in '.$dest.') ','debug');
						$op=$this->addMemberToList($dest,$address);
						if( -5 == $op ){
							$this->logger->log("operation in progress on mailing-list. Stopping.");
							return;
						}else{
							$c+=$op;		
						}
					}else{
						$this->logger->log('Not adding '.$address.' (already in '.$dest.') ','debug');
					}
				}
				$this->logger->log('Added '.$c.' mails to "'.$dest.'" from source "'.$source.'".');
			}
		}else{
			$this->logger->log('Not updating destination, last update is recent enough.','debug');
		}
			
		file_put_contents($this->cacheFolder.'/'.$dest.'.last-dest-update',time());
		
	}
	
	private function removeMemberFromList($list,$address){
                $this->logger->log('Soap request: mailingListSubscriberDel('.$this->session.','.$this->domain.','.$list.','.$address.')','debug');
                try{
                        $this->soap->mailingListSubscriberDel(
                                        $this->session,
                                        $this->domain,
                                        $list,
                                        $address);
                }catch(SoapFault $fault) {
                        $this->exceptions[] = $fault;
                        $this->logger->log("Exception:".$fault->faultstring,'debug');
                        $this->logger->log("Code:[".$fault->faultcode.']','debug');
                        if($fault->faultcode == Updater::$ML_OP_IN_PROGRESS){
                                $this->logger->log('Waiting 50 seconds for task to finish on server....','debug');
                                sleep(50);
                                return -5;
                        }
                        return 0;
                }
                $this->logger->log('Waiting 50 seconds for task to finish on server....','debug');
                sleep(50);
                $this->logger->log('Soap request finished.','debug');
                return 1;
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
			$this->logger->log("Exception:".$fault->faultstring,'debug');
			$this->logger->log("Code:[".$fault->faultcode.']','debug');
			if($fault->faultcode == Updater::$ML_OP_IN_PROGRESS){
				$this->logger->log('Waiting 50 seconds for task to finish on server....','debug');
				sleep(50);
				return -5;	
			}
			return 0;
		}
		$this->logger->log('Waiting 50 seconds for task to finish on server....','debug');
		sleep(50);
		$this->logger->log('Soap request finished.','debug');
		return 1;
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
			$this->logger->log("Exception:".$fault->faultstring,'debug');
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
