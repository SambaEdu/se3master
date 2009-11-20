<?php
/* $Id$ */


   /**	
   * Permet d'envoyer des popup
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter Caen 
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: popup
   * file: popup.php

  */	



// include("entete.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-popup',"/var/www/se3/locale");
textdomain ('se3-popup');


if($_POST['message']!="") {
	$file = fopen("/tmp/popup.txt","w+");
	fwrite($file,($_POST['message']));
	fclose($file);
} else {
	include "entete.inc.php";
	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";
   	echo "<H1>".gettext("Pop Down :-) ")."</H1>\n";
	echo "<BR><BR><B>".gettext("Il faut mettre un texte !")."</B>";

	include("pdp.inc.php");
	exit;
}

// Si le parc est deja connu

if ($_POST['parc']) {
	include "entete.inc.php";
	include "ldap.inc.php";
	include "ihm.inc.php";
      	echo "<H1>".gettext("Envoi du Pop Up au parc")." $parc </H1>\n";
	// Lecture des membres du parc
	$parc=$_POST['parc'];
	$mp_all=gof_members($parc,"parcs",1);  
	// Filtrage selon critere
	if ("$filtrecomp"=="") $mp=$mp_all;
	else {
      		$lmloop=0;
       		$mpcount=count($mp_all);
       		for ($loop=0; $loop < count($mp_all); $loop++) {
       			$mach=$mp_all[$loop];
       			if (preg_match("/$filtrecomp/",$mach)) $mp[$lmloop++]=$mach;
       		}
	}
	if ( count($mp)>15) $size=15; else $size=count($mp);
	if ( count($mp)==0) {
       		echo "<BR><small><B>".gettext(" Ce parc est vide !")."</small></B>";
	}
 	 if ( count($mp)>0) {
		sort($mp);

		echo "<H3>".gettext("R&#233;sultat du Pop Up aux machines du parc")." $parc: </H3>\n";
		echo gettext("Le parc")." $parc ".gettext("contient "). count($mp).gettext(" machine(s)");

      		$nbrconnect=0;
      
      		for ($loop=0; $loop < count($mp); $loop++) {
       			$connect=`smbstatus |grep -w $mp[$loop]`;
       			if (empty($connect)) {
       				//echo "<LI><small><b>$mp[$loop]</b> n'est pas connect&#233;e !</small></LI>";
                        } else {
       				$nbrconnect= $nbrconnect + 1;
       				exec ("cat /tmp/popup.txt|smbclient -U 'Administrateur Samba Edu 3' -M $mp[$loop]");
       				echo "<LI><small><b>$mp[$loop]</b>".gettext("est destinataire du Pop Up")."</small></LI>";
       			}

                 }
			
		echo "<br><br>";
     		if ($nbrconnect==0) {
        		echo "<b><small>".gettext("Pas d'&#233;mission de Pop Up car aucune machine du parc n'est actuellement connect&#233;e !")."</small></b>";
                } else {
        		echo gettext("Nombre total de popup &#233;mis: ")." $nbrconnect";
        	}

	}
exit;
}

if (empty($_POST['destination'])){
	include "entete.inc.php";
	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";
   	echo "<H1>".gettext("Pop Down :-) ")."</H1><BR><BR><B>".gettext("Il faut imp&#233;rativement cocher une case !")."</B>";
} elseif (!(($_POST['destination']=="poptous")||($_POST['destination']=="popparc")||($_POST['destination']=="popcomputer"))){
    	die (gettext("Valeur incorrecte"));
} elseif ($_POST['destination']=="poptous") {
	include "poptous.inc.php";
} elseif ($_POST['destination']=="popparc") {
	include "popparc.inc.php";
} elseif ($_POST['destination']=="popcomputer") {
	include "popcomputer.inc.php";
}

include("pdp.inc.php");
?>
