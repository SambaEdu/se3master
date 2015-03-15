<?php


   /**
   
   * Interface de deploiement 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: deploy
   * file: transfert.php

  */	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-deploy',"/var/www/se3/locale");
textdomain ('se3-deploy');


//aide
$_SESSION["pageaide"]="Le_module_D%C3%A9ploiement_dans_les_r%C3%A9pertoires_des_utilisateurs";

if (is_admin("se3_is_admin",$login)=="Y") {

	$repertoire = $_POST['repertoire'];
	$choix = $_POST['choix'];
	$ecraser = $_POST['ecraser'];
	$files0 = $_POST['files0'];
	$filter = $_POST['filter'];

	// Definition des messages d'alerte
	$alerte_1="<div class='error_msg'>".gettext("Votre demande de d&#233ploiement n'a pas &#233t&#233 prise en
		    compte car une t&#226che d'administration est en cours sur le serveur,
		    veuillez r&#233it&#233rer votre demande plus tard. Si le probl&#232me persiste,
		    veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";

	$alerte_2="<div class='error_msg'>".gettext("Votre demande de d&#233ploiement a &#233chou&#233e. Si le
		    probl&#232me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."
		    </div><BR>\n";
	// Definition des messages d'info
	$info_1 = gettext("Cette t&#226che est ordonnanc&#233e, vous recevrez un mel
		   de confirmation de d&#233ploiement.");

	// Titre
	echo "<h1>".gettext("D&#233ploiement de fichiers")."</h1>";

	$a=0;    

	if ($repertoire=="") $repertoire="x";    
	if ($ecraser=="oui") $a=1;
    
	for ($filt=0; $filt < count($filter); $filt++) {
    		$uids=search_uids("(cn=".$filter[$filt].")");
    		$people=search_people_groups($uids,"(sn=*)","cat");
        
		for ($loop=0; $loop < count($people); $loop++) {    
    			if (is_dir("/home/".$people[$loop]["uid"])) {
		 		$nom = $people[$loop]["uid"];
		 		exec ("/usr/bin/sudo /usr/share/se3/scripts/deploy.sh  $nom $repertoire $a \"$files0\"");
	     		}
		}
		echo "<br><center>";
		echo "<H2>".gettext("Le d&#233ploiement est effectu&#233.")."</H2>";
		echo "</center>";
    
	}
}//fin is_admin
else echo gettext("Vous n'avez pas les droits n&#233cessaires pour ouvrir cette page...");


include ("pdp.inc.php");
  
?>
