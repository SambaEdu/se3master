<?php

// Nettoyage des variables

require_once("../se3/includes/library/HTMLPurifier.auto.php");

$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'utf-8'); // replace with your encoding
$config->set('HTML.Doctype', 'XHTML 1.0 Strict'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$magic_quotes = get_magic_quotes_gpc();

foreach($_GET as $key => $value) {
	if(!is_array($value)) {
		if ($magic_quotes) $value = stripslashes($value);
		$_GET[$key]=$purifier->purify($value);
		if ($magic_quotes) $_GET[$key] = addslashes($_GET[$key]);
	}
	else {
		foreach($_GET[$key] as $key2 => $value2) {
			if ($magic_quotes) $value2 = stripslashes($value2);
			$_GET[$key][$key2]=$purifier->purify($value2);
			if ($magic_quotes) $_GET[$key][$key2] = addslashes($_GET[$key][$key2]);
		}
	}
}

foreach($_POST as $key => $value) {
	if(!is_array($value)) {
		if ($magic_quotes) $value = stripslashes($value);
		$_POST[$key]=$purifier->purify($value);
		if ($magic_quotes) $_POST[$key] = addslashes($_POST[$key]);
	}
	else {
		foreach($_POST[$key] as $key2 => $value2) {
			if ($magic_quotes) $value2 = stripslashes($value2);
			$_POST[$key][$key2]=$purifier->purify($value2);
			if ($magic_quotes) $_POST[$key][$key2] = addslashes($_POST[$key][$key2]);
		}
	}
}

?>
