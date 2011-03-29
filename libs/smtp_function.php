<?php

// This is my modification of fluxbb's modification
// jehan.




function error($txt){
	echo $txt;
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit FluxBB and it's coding standards.
//
function server_parse($socket, $expected_response)
{
	$server_response = '';
	while (substr($server_response, 3, 1) != ' ')
	{
		if (!($server_response = fgets($socket, 256)))
			error('Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__);
	}

	if (!(substr($server_response, 0, 3) == $expected_response))
		error('Unable to send e-mail. Please contact the forum administrator with the following error message reported by the SMTP server: "'.$server_response.'"', __FILE__, __LINE__);
}



//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit FluxBB and it's coding standards.
//
function smtp_mail($to, $subject, $message, $headers = '')
{
	//global $pun_config;


	$pun_config = Array(
		'o_smtp_host' => 'smtp.ulb.ac.be',
		'o_smtp_user' => '',
		'o_smtp_pass' => '',
		'o_webmaster_email' => 'webmaster@impro-vocation.org'
	);

	$recipients = explode(',', $to);

	// Sanitize the message
	$message = str_replace("\r\n.", "\r\n..", $message);
	$message = (substr($message, 0, 1) == '.' ? '.'.$message : $message);

	// Are we using port 25 or a custom port?
	if (strpos($pun_config['o_smtp_host'], ':') !== false)
		list($smtp_host, $smtp_port) = explode(':', $pun_config['o_smtp_host']);
	else
	{
		$smtp_host = $pun_config['o_smtp_host'];
		$smtp_port = 25;
	}

	if (!($socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15)))
		error('Could not connect to smtp host "'.$pun_config['o_smtp_host'].'" ('.$errno.') ('.$errstr.')', __FILE__, __LINE__);

	server_parse($socket, '220');

	if ($pun_config['o_smtp_user'] != '' && $pun_config['o_smtp_pass'] != '')
	{
		fwrite($socket, 'EHLO '.$smtp_host."\r\n");
		server_parse($socket, '250');

		fwrite($socket, 'AUTH LOGIN'."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($pun_config['o_smtp_user'])."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($pun_config['o_smtp_pass'])."\r\n");
		server_parse($socket, '235');
	}
	else
	{
		fwrite($socket, 'HELO '.$smtp_host."\r\n");
		server_parse($socket, '250');
	}

	fwrite($socket, 'MAIL FROM: <'.$pun_config['o_webmaster_email'].'>'."\r\n");
	server_parse($socket, '250');

	$to_header = 'To: ';

	@reset($recipients);
	while (list(, $email) = @each($recipients))
	{
		fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
		server_parse($socket, '250');

		$to_header .= '<'.$email.'>, ';
	}

	fwrite($socket, 'DATA'."\r\n");
	server_parse($socket, '354');

	fwrite($socket, 'Subject: '.$subject."\r\n".$to_header."\r\n".$headers."\r\n\r\n".$message."\r\n");

	fwrite($socket, '.'."\r\n");
	server_parse($socket, '250');

	fwrite($socket, 'QUIT'."\r\n");
	fclose($socket);

	return true;
}
