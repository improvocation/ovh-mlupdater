<?php

$config_file = 'mlupdater.yml';

require_once('libs/spyc.php');
require_once('classes/config.php');
require_once('classes/logger.php');
require_once('classes/updater.php');

$log = new Logger();
$config = new Config($config_file,$log);


// soap connexion
$updater = new Updater(
	$config->g('Config.domain'),
	$config->g('Config.username'),
	$config->g('Config.password'),
	$log,
	dirname(__FILE__)
	);



foreach($config->g('Transfers') as $transfer){
	$updater->update($transfer['origins'],$transfer['destinations']);
}



/*

#$domain = "impro-vocation.org";
foreach(transfer)
	$mlupdater->update(sources,destinations);



update : 
getCurrentStatus() // yaml file : "last-updates.yaml"

1) if user list too old

foreach(source)
	getusers()
	dump to yaml

2) if user update too old

foreach(user)
	add user
	remove from yaml



*/
