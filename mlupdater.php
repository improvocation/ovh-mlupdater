<?php

$config_file = dirname(__FILE__).'/mlupdater.yml';

require_once('libs/spyc.php');
require_once('libs/smtp_function.php');
require_once('classes/config.php');
require_once('classes/logger.php');
require_once('classes/updater.php');

$log = new Logger();
$log->log("Starting.");
$log->setDebug(in_array('debug',$argv));

$sendmails = !in_array('nomail',$argv);
$force = in_array('force',$argv);

$config = new Config($config_file,$log);


$updater = new Updater(
	$config->g('Config.domain'),
	$config->g('Config.username'),
	$config->g('Config.password'),
	$log,
	dirname(__FILE__),
	$force
	);



foreach($config->g('Transfers') as $transfer){
	try{
		$updater->update($transfer['origins'],$transfer['destinations']);
	}catch(Exception $e){
		$log->log("Caught exception !");
		handleExceptions(array($e));
		$log->log($str);
	}
	$log->log("Handling exceptions...");
	handleExceptions($updater->getExceptions());
}

function handleExceptions($exceptionsArray){
	global $log;

	if(!count($exceptionsArray))
		return;
		
	$str = '';
	foreach($exceptionsArray as $e){
		$str.="\nFailed with exception:";
		$str.="\n".$e->getMessage();
		$str.="\n".$e->getTraceAsString();
	}

	$log->log($str);
	if($sendmails){
		$log->log('Sending mail...');
		$str.="\n\nLast error:\n".print_r(error_get_last(),1)."\n\nBacktrace:\n".print_r(debug_backtrace(),1);
		smtp_mail('admin@impro-vocation.org','Failure on improvoc mlupdater',$str,"From: admin@impro-vocation.org\r\n");
	}
}
