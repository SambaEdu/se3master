<?php

/**

* Interface de gestion des acl
* @Version $Id$ 


* @Projet LCS / SambaEdu 

* @auteurs  Equipe Tice academie de Caen

* @Licence Distribue selon les termes de la licence GPL

* @note 

*/

/**

* @Repertoire: acls
* file: acls.php

*/	





include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-acls',"/var/www/se3/locale");
textdomain ('se3-acls');

$path=isset($_GET['path']) ? $_GET['path'] : "";
$repsup=isset($_GET['repsup']) ? $_GET['repsup'] : "";
$repinf=isset($_GET['repinf']) ? $_GET['repinf'] : "";
$chemin=isset($_GET['chemin']) ? $_GET['chemin'] : "";

if (is_admin("se3_is_admin",$login)=="Y") {
	
	// Aide
	$_SESSION["pageaide"]="ACL#En_utilisant_l.27interface_SambaEdu";    
	
	echo "<H1>".gettext("Attribution d'acls")."</H1><P>";
	echo "<small>";
	echo "<B>".gettext("R&#233;pertoire dont vous voulez modifier les acls :")."</B><BR>";
	$chemininit="/var/se3";
	if($path=="") {
		$chemin=$chemininit;
	}
	if($repsup==1) {
		$repinf=substr("$repinf",0,-1);
		$ici=$repinf;
		$repinf=explode("/",$repinf);
		$repinf=end($repinf);
		$ici=ereg_replace($repinf,"",$ici);
		$test=$ici;
		$test=substr("$test",0,-1);
	}
	else
	{
		$ici=$chemin;
		$ici.=$path;
		$test=$ici;
		$ici.="/";
	}
	if($test!=$chemininit)
	{
		echo "<a href=\"acls.php?repsup=1&repinf=$ici\">".gettext("R&#233;pertoire parent")."<BR></a>";
	}
	$repsup=0;
	exec ("/usr/bin/sudo /usr/share/se3/scripts/ls.sh \"$ici\"");
	$rep = file ("/tmp/resultat");
	for ($i=0 ; $i < count ($rep); $i++) {
		echo "<a href=\"acls.php?path=$rep[$i]&chemin=$ici\">$rep[$i]</a><br>";
	}
	
	$test=substr("$ici",0,-1);
	$type="repertoire";
	exec ("/usr/bin/sudo /usr/share/se3/scripts/testfichier.sh \"$test\"");
	$fich = file ("/tmp/testfichier.tmp");
	$fich = trim($fich[0]);
	if ($fich == "oui"){
		$type="fichier";
	}
	$repertoire=$test;
	echo "<BR><BR>".gettext("Le")." <B>$type</B> ".gettext("s&#233;lectionn&#233; est :")."<B>$test</B>";

	echo "<form action=\"visuacls.php\" method=\"post\">";
	
	/*
	//Stephane Boireau (21/03/2006)
	//Modification de la variable 'type' en 'type_fich'
	//parce que la variable 'type' est utilisee avec
	//plusieurs autres significations dans la page visuacls.php
	echo "<input type=\"hidden\" name=\"repertoire\" value=\"$repertoire\">
	<input type=\"hidden\" name=\"type\" value=\"$type\">
	<input type=\"submit\" value=\"valider\">";
	*/
	echo "<input type=\"hidden\" name=\"repertoire\" value=\"$repertoire\">
	<input type=\"hidden\" name=\"type_fich\" value=\"$type\">
	<input type=\"submit\" value=\"".gettext("valider")."\">";
	echo "</form></small>";
	
}	//fin is_admin

else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");

?>
