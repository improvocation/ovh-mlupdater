<?php

$config_file = dirname(__FILE__).'/mlupdater.yml';

require_once('libs/spyc.php');
require_once('classes/config.php');
require_once('classes/logger.php');
require_once('classes/updater.php');

$log = new Logger();
$log->log("Starting.");

$config = new Config($config_file,$log);


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

