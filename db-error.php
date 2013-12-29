<?php

/*
This file will handle situations when (not if) your database is not available.
It will show some content to your users. If the page has been previously cached by WP Super Cache
that content will be shown. You can specify the full HTML coode that can be shown in case if the cache is not available.

// Installation and Configuration
Copy this file (db-error.php) in your wp-content folder
Then configure at least '$mail_to' variable which defaults to admin@yoursite

// Questions/suggestions should be sent via the github's issue tracker.
// https://github.com/orbisius/graceful-db-failure

@author Svetoslav (SLAVI) Marinov | http://orbisius.com
@version 1.0.0
@license GPL v2
*/

// Leave this line as is. // hostname -f is full host e.g. orbisius.com (linux), windows cmd doesn't have that parameter.
$host = empty($_SERVER['HTTP_HOST']) ? trim(strncasecmp(PHP_OS, 'WIN', 3) ? `hostname` : `hostname -f`) : str_replace('www.', '', $_SERVER['HTTP_HOST']);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Configuration start

// Who is going to receive the notifications, separate multiple emails with commas
// your provider might have a way to convert email to text e.g. 000123456@vmobile.ca that will be sent as a text.
// default: admin@yourcurrenthost.com
$mail_to = "admin@$host";

// When serving cached conent should we let the users know that there is something wrong with the db?
// This will add a short notice at the top of the page informing users that the content is read-only (e.g. good for forums)
// Values: 0 or 1
$show_notice = 1;

// If yes, what to show them
$notice_text = "The database server is down. Serving cached content. Falling back to read only mode.";

// what will the from email be
$mail_from = "wp-db-error@$host";

// How many times the script should alert you.
// Alerts are sent at 5m, 10m and 15m
$max_alerts = 3;

// After the max alerts have been reached. When should we start the alerts again?
// default: 4h i.e. if the db is still gone after 4 hours alert me again.
$alert_reset_time = 4 * 3600;

// This sends "503 Service Temporarily Unavailable" header. 
// This is helpful if you have another service that monitors your site which will know that something is wrong when the header is sent.
// Values: 0 or 1
$send_error_header = 1;

// If the cache is not available show this. Since db is not available don't try to use 
// other WP functions e.g. get_header() or get_footer()
$default_page_content = <<<PAGE_EOF
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Technical Difficulties</title>
	<style>
	body {
		width:80%;
		min-height:40%;
		margin:18% auto;
		border:1px solid #ccc;
		padding:10px;
		font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
		text-align:center;
	}
	
	.app_error_container h1 {
		color:red;
	}
	</style>
</head>
<body>
	<div class='app_error_container'>
		<h1>Database Error</h1>
		<div>
			Oops! We are having some technical difficulties.
			<br/>
			Please come back at a later time. The techies have been notified.
		</div>
		
		<div class='powered_by'><small>Powered by <a href='https://github.com/orbisius/graceful-db-failure' target="_blank">Graceful DB Failure</a></small></div>
	</div>
</body>
</html>
PAGE_EOF;
// Configuration end
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$flag_file = __FILE__ . '.flag';

$ip = empty($_SERVER['REMOTE_ADDR']) ? '0.0.0.0' : $_SERVER['REMOTE_ADDR']; // command line scripts won't have IP set.

// Sample requets URL: http://club.orbisius.com/forums/topic/problem-with-other-plugin/
// Allow fake request URLs to be supplied (using req_uri parameter) but allow it only for local installs.
$req_uri = !empty($_REQUEST['req_uri']) && preg_match('#^(?:127\.0\.0\.1|192\.168\.[0-2]\.)#si', $ip) ? $_REQUEST['req_uri'] : $_SERVER['REQUEST_URI'];

$req_uri = preg_replace('#\?.*#si', '', $req_uri); // rm params
$req_uri = preg_replace('#\#.*#si', '', $req_uri); // Just in case
$req_uri = rtrim($req_uri, '/');

if (preg_match('#/wp-content/db-error\.php#si', $req_uri)) { // make sure we don't have direct calls to the file.
	return;
}

if ($send_error_header) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
}

$file_buff = '';
$cache_file_html = dirname(__FILE__) . "/cache/supercache/$host$req_uri/index.html";
$cache_file_html_gz = dirname(__FILE__) . "/cache/supercache/$host$req_uri/index.html.gz";

if (empty($show_notice) 
		&& ( !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) // browser knows gzip?
		&& is_file($cache_file_html_gz)) { // if we don't want to show a message we can use the gzipped version of the page (if any)
    $file_buff = file_get_contents($cache_file_html_gz);
    ini_set('zlib.output_compression', 'Off');
    header('Content-Encoding: gzip');
} elseif (is_file($cache_file_html)) {
    $file_buff = file_get_contents($cache_file_html);

    if ($show_notice) {
        $warning = "<div style='background:#FFC332;padding:3px;text-align:center;'>$notice_text</div>";
        $file_buff = preg_replace('#<body[^>]*>#si', '$0' . $warning, $file_buff);
    }
} else {
	$file_buff = $default_page_content;
}

if (!empty($file_buff)) {
    header('Content-Length: ' . strlen($file_buff));
    header('X-Orb-Db-Error: Cache');
    echo $file_buff;
}

$headers = "From: " . $mail_from . "\r\n" 
		. "X-Mailer: PHP/" . phpversion() . "\r\n"
		. "X-Priority: 1 (High)";
$message = "Site: $host. " 
		. "It broke when someone (IP: $ip) tried to open this page: "
		. "http://$host$req_uri";

$subject = "DB error at $host";

$cnt = 1;

if (is_file($flag_file)) {
	if ( (time() - filemtime($flag_file) ) > $alert_reset_time) { // flag has been set for > 4h
		unlink($flag_file);
	} else {
		$cnt = file_get_contents($flag_file);
		$cnt = empty($cnt) ? 1 : intval($cnt);
	}
}

// Send an email every 5m, 10m, 15m
if ( !is_file($flag_file) 
		|| ( (time() - filemtime($flag_file) > 5 * $cnt * 60) && $cnt < $max_alerts) ) {

	mail($mail_to, $subject, $message, $headers);
	file_put_contents($flag_file, $cnt + 1);
}
