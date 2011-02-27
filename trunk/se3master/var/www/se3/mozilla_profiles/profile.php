<?php

   /**
   
   * Deploiement et modification des profils mozilla des postes clients 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  franck.molle@ac-rouen.fr
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: mozilla_profiles
   * file: profile.php

  */	


require("entete.inc.php");

//aide
$_SESSION["pageaide"]="Gestion_Mozilla";


//Verification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'autehtification is_admin
require("ihm.inc.php");

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-mozilla',"/var/www/se3/locale");
textdomain ('se3-mozilla');

//AUTHENTIFICATION
if (is_admin("computer_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


// Titre
echo "<h1>".gettext("D&#233;ploiement de Mozilla")."</h1>\n";


	if (file_exists("/var/se3/unattended/install/packages/firefox/firefox-config.bat") or file_exists("/usr/share/se3/logonpy/logon.py")) {
		echo "<a href=\"/mozilla_profiles/firefox-se3-NG.php\">Configuration des profils Firefox</a>";  
	}
	//EVALUE SI UNE SAISIE A ETE EFFECTUEE: AUTO-APPEL DE LA PAGE APRES FORMULAIRE REMPLI
	
	$form = "<form action=\"firefox.php\" method=\"post\">\n";
	// Form de selection d'actions
	$form .="<H3>".gettext("Deploiement ou  modification  des profils Mozilla Firefox :")." </H3>\n";
	$form .= "<SELECT name=\"choix\" onchange=submit()>\n";
	$form .= "<OPTION VALUE='choix'>-------------------------------".gettext(" Choisir ")."-------------------------------</OPTION>\n";

	$form .= "<OPTION VALUE='deploy_nosave'>".gettext("D&#233;ployer et remplacer les profils existants")."</OPTION>\n";
	$form .= "<OPTION VALUE='deploy_save'>".gettext("D&#233;ployer et remplacer les profils mais conserver les bookmarks")."</OPTION>\n";
	$form .= "<OPTION VALUE='modif'>".gettext("Modifier la page de d&#233;marrage pour un groupe ou un utilisateur")."</OPTION>\n";
	$form .= "<OPTION VALUE='modif_skel'>".gettext("Modifier la page de d&#233;marrage de l'utilisateur mod&#232;le")." \"skeluser\"</OPTION>\n";

	$form .= "</SELECT>\n";
	$form.="</form>\n";
	echo $form;

	echo "<br><br><br>";

        $form = "<form action=\"thunderbird.php?config=init\" method=\"post\">\n";
        // Form de selection d'actions
        $form .="<H3>".gettext("Deploiement des profils Mozilla Thunderbird :")." </H3>\n";
        $form .= "<SELECT name=\"choix\" onchange=submit()>\n";
        $form .= "<OPTION VALUE='choix'>-----------------------------".gettext(" Choisir ")."---------------------------------</OPTION>\n";
        $form .= "<OPTION VALUE='deploy_all'>".gettext("D&#233;ployer les profils dans tous les espaces personnels existants")."</OPTION>\n";
        $form .= "<OPTION VALUE='deploy_grp'>".gettext("D&#233;ployer les profils dans certains espaces personnels")." </OPTION>\n";
        $form .= "</SELECT>\n";
        $form.="</form>\n";
        echo $form;
        echo "<br>";

include("pdp.inc.php");
?>
