<?php
	require ("config.inc.php");
   	require_once ("functions.inc.php");
	require ("ldap.inc.php");
	require ("ihm.inc.php");
	include ("fonction_backup.inc.php");

	require_once("lang.inc.php");
	bindtextdomain('sauvegarde',"/var/www/se3/locale");
	textdomain ('sauvegarde');


	// Verifie les droits
	$login =isauth();
	if (ldap_get_right("system_is_admin",$login)!="Y")
       	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
	
	$NAS_mntsuffix = $_GET['NAS_mntsuffix'];
	
	if ($NAS_mntsuffix == 'rien')
		$NAS_mntsuffix ='';
	
	if ( ($NAS_mntsuffix != 'pc') && ($NAS_mntsuffix != '') )
		die("Il faut choisir /var/lib/backuppc ou /var/lib/backuppc/pc comme point de montage");
	$sql= "SELECT * FROM `params` WHERE name = 'NAS_mntsuffix';";
	$c = mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_mntsuffix', '$NAS_mntsuffix', '0', 'suffixe de montage NAS', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` =  '$NAS_mntsuffix', `cat` = 5  WHERE `params`.`name` ='NAS_mntsuffix' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}

	
	###################################################################################

	
	$NAS_protocol = $_GET['NAS_protocol'];
	
	if ( ($NAS_protocol != 'cifs') && ($NAS_protocol != 'nfs') )
		die("Il faut choisir cifs ou nfs comme protocole");
	$sql= "SELECT * FROM `params` WHERE name = 'NAS_protocol';";
	$c = mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_protocol', '$NAS_protocol', '0', 'protocole NAS cifs ou nfs', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` = '$NAS_protocol' , `cat` = 5 WHERE `params`.`name` ='NAS_protocol' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}


	###################################################################################
	$NAS_ip = $_GET['NAS_ip'];
	$test = explode('.',$NAS_ip);
	if  (count($test) != 4)
		die("Votre adresse IP est invalide exemple: x.x.x.x !");
	$sql= "SELECT * FROM `params` WHERE name = 'NAS_ip';";
	$c= mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_ip', '$NAS_ip', '0', 'adresse IP du NAS', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` = '$NAS_ip' , `cat` = 5  WHERE `params`.`name` ='NAS_ip' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}

	###################################################################################
	$NAS_share = $_GET['NAS_share'];
	if (trim($NAS_share) == '')
		die("Le nom de partage est invalide!");
	$sql= "SELECT * FROM `params` WHERE name = 'NAS_share';";
	$c= mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_share', '$NAS_share', '0', 'nom de partage du NAS', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` = '$NAS_share' , `cat` = 5  WHERE `params`.`name` ='NAS_share' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}


	###################################################################################
	if ($NAS_protocol == 'nfs') {
		$NAS_login = '';
	} 
	else
		$NAS_login = $_GET['NAS_login'];
	

	$sql= "SELECT * FROM `params` WHERE name = 'NAS_login';";
	$c= mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_login', '$NAS_login', '0', 'login de connexion au NAS', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` = '$NAS_login' , `cat` = 5 WHERE `params`.`name` ='NAS_login' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}


	###################################################################################
	if ($NAS_protocol == 'nfs') {
		$NAS_pass = '';
	} 
	else
		$NAS_pass = $_GET['NAS_pass'];
	

	$sql= "SELECT * FROM `params` WHERE name = 'NAS_pass';";
	$c= mysql_query($sql) or die("ERREUR: $sql");
	if (mysql_num_rows($c) == 0 ) {
		$sql2 = "INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )"
			 ."VALUES (NULL , 'NAS_pass', '$NAS_pass', '0', 'mot de passe du NAS', '5');";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	} else {
		$sql2 = "UPDATE `params` SET `value` = '$NAS_pass' , `cat` = 5 WHERE `params`.`name` ='NAS_pass' ;";
		$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	}


	die("Modifications réalisées avec succes !");

?>
