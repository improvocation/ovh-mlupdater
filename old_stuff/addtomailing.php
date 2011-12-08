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


$destination = "news";

$log->log("Reading file to add.");
$emails=file_get_contents('input/mails-for-news.txt');
$log->log("Cleaning & parsing.");
$emails=preg_replace('/[ ,;|\s]+/',',',$emails);
$emails = array_filter(explode(',',$emails));

	
$log->log("Adding ".count($emails)." e-mails.");
$updater->addToList($emails,$destination);

$log->log("Done.");
