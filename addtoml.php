#!/usr/bin/env php
<?php

$config_file = dirname(__FILE__).'/mlupdater.yml';

require_once('libs/spyc.php');
require_once('classes/config.php');
require_once('classes/logger.php');
require_once('classes/updater.php');

$log = new Logger();
$log->log("Starting.");

$k = array_search('debug',$argv);
if( FALSE !== $k ){
	$log->setDebug(true);
	$argv[$k] = False;
	$argv = array_values(array_filter($argv));
}

$config = new Config($config_file,$log);


$updater = new Updater(
	$config->g('Config.domain'),
	$config->g('Config.username'),
	$config->g('Config.password'),
	$log,
	dirname(__FILE__)
	);


if(3 != count($argv)){
	$log->log("Script requires two arguments: source file and destination mailing list.");
	exit();
}

$source = $argv[1];
$destination = $argv[2];

$log->log("Reading file to add.");
if(!file_exists($source)){
	$log->log("Error. File does not exist: $source.");
	exit();
}
$emails=file_get_contents($source);
$log->log("Cleaning & parsing.");
$emails=preg_replace('/[ ,;|\s]+/',',',$emails);
$emails = array_filter(explode(',',$emails));

$log->log("Adding ".count($emails)." e-mails.");
$updater->addToList($emails,$destination);

$log->log("Done.");
