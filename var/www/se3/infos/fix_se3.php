<?php

   /**
   
   * Correction de problemes
   * @Version $Id: savstatus.php 4187 2009-06-19 09:22:12Z gnumdk $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Cedric Bellegarde

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: se3_fix.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");
require ("ldap.inc.php");
require ("crob_ldap_functions.php");
require ("printers.inc.php");
require ("fonc_parc.inc.php");


require_once ("lang.inc.php");

bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');



$action=$_GET['action'];

//aide
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Correction_de_probl.C3.A8mes";

if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

echo "<h1>".gettext("Correction de probl&#232;mes")."</h1>\n";
if (isset($action)) {
    if ($action == "rmprofiles") {
        echo "<h2>".gettext("Reg&#233;n&#233;rer les profils errants Windows...")."</h2>";
        system("sudo /usr/share/se3/sbin/clean_profiles.sh all");
    }
    if ($action == "permse3") {
        echo "<h2>".gettext("Remise en place des droits syst&#232;me...")."</h2>";
        system("sudo /usr/share/se3/scripts/permse3");
        echo "Termin&#233;.";
    }
    if ($action == "adminse3pass") {

	
        if (($login==admin)||($login==assist)||($login==aieple01)) {

            echo "<h2>".gettext("Affichage du mot de passe adminse3...")."</h2>";
            echo "Le mot de passe adminse3 est actuellement <b>$xppass</b>";
        }
        else { 
            echo "<h2>".gettext("Affichage du mot de passe adminse3...")."</h2>";
            echo "Affichage non permis avec votre compte";
        }
    }
    if ($action == "restore_droits") {
        echo "<h2>".gettext("Remise en place des droits sur les comptes utilisateurs...")."</h2>";
        system("sudo /usr/share/se3/scripts/restore_droits.sh --home html");
    }
    if ($action == "restore_droits_full") {
        echo "<h2>".gettext("Remise en place de tous les droits...")."</h2>";
        system("sudo /usr/share/se3/scripts/restore_droits.sh acl_default auto html");
    }
    
    if ($action == "adminse3_rest") {
        echo "<h2>".gettext("Remise en place des droits d'int&#233;gration pour adminse3...")."</h2>";
        echo '<pre>';
        system("sudo /usr/share/se3/sbin/create_adminse3.sh");
        echo '</pre>';
        echo "ok";
    }
    
    if ($action == "force_profils_wpkg") {
        echo "<h2>".gettext("Raffraichissement des machines visibles dans wpkg...")."</h2>";
        // Lance le script pour wpkg
	system ("/bin/bash /usr/share/se3/scripts/update_hosts_profiles_xml.sh ou=Computers ou=Parcs $ldap_base_dn");
	system ("/bin/bash /usr/share/se3/scripts/update_droits_xml.sh");
        echo "ok";
    }
    if ($action == "force_rapports_wpkg") {
        echo "<h2>".gettext("Renouvellement des rapports wpkg...")."</h2>";
        
        system("rm -f /var/se3/unattended/install/wpkg/rapports/rapports.xml ; /var/www/se3/wpkg/bin/rapports.sh");
        system ("/bin/bash /usr/share/se3/scripts/update_hosts_profiles_xml.sh ou=Computers ou=Parcs $ldap_base_dn");
	system ("/bin/bash /usr/share/se3/scripts/update_droits_xml.sh");
	echo "ok";
    }
    
    if ($action == "test_profiles") {
        echo "<h2>".gettext("Recherche des profils Windows 7 corrompus...")."</h2>";
        echo '<pre>';
        system("sudo /usr/share/se3/sbin/test_profiles.sh");
        echo '</pre>';
        echo "ok";
    }
    if ($action == "search_doublons") {
        echo "<h2>".gettext("Recherche de doublons dans l'annuaire")."</h2>";
        search_doublons_sambasid();
        search_doublons_mac('n');
    }
}
elseif (isset($_POST['suppr_doublons_sid'])) {
	$suppr = isset ( $_POST ['suppr'] ) ? $_POST ['suppr'] : NULL;
	echo '<pre>';
	for($i = 0; $i < count ( $suppr ); $i ++) {
		if (preg_match("/^.*\\$$/",$suppr[$i])) {
			echo suppression_computer(preg_replace("/\\$/", "", $suppr[$i]));
		} else {
			exec ( "/usr/share/se3/sbin/userDel.pl $suppr[$i],$AllOutPut,$ReturnValue" );
			if ($ReturnValue == "0") {
				echo gettext ( "Le compte" ) . " <strong>$suppr[$i]</strong> " . gettext ( " a &#233;t&#233; effac&#233; avec succ&#232;s !" ) . "<BR>\n";
			} else {
				echo "<div class=error_msg>" . gettext ( "Echec, l'utilisateur $suppr[$i] n'a pas &#233;t&#233; effac&#233; !" );
				echo gettext ( "(type d'erreur : " ) . "$ReturnValue), " . gettext ( " veuillez contacter" );
				echo "<A HREF='mailto:$MelAdminLCS?subject=" . gettext ( "Effacement utilisateur" ) . " $suppr[$i]'>" . gettext ( "l'administrateur du syst&#232;me" ) . "</A></div><BR>\n";
			}
		}
	}
	$mod = isset ( $_POST ['mod'] ) ? $_POST ['mod'] : NULL;
	$sid = isset ( $_POST ['sid'] ) ? $_POST ['sid'] : NULL;
	
	echo '<pre>';
	for($i = 0; $i < count ( $mod ); $i ++) {
		if (preg_match("/^.*\\$$/",$mod[$i])) {
			echo "pas de modif du sid pour les machines !";
		} else {
			$attrs = array ("sambasid" => $sid[$i]);
			modify_attribut("uid=$mod[$i]", "people", $attrs, "replace");
			echo "nouveau sid pour uid=$mod[$i] :" . $sid[$i];
		}
	}
	
	echo '</pre>';
} elseif (isset($_POST ['suppr_doublons_ldap'])) {
	$suppr=isset($_POST['suppr']) ? $_POST['suppr'] : NULL;
	echo '<pre>';
	for($i=0;$i<count($suppr);$i++) {
		echo suppression_computer($suppr[$i]);
	}
	echo '</pre>';
}


else {

    if (($login==admin)||($login==assist)||($login==aieple01)) {
	echo "<a href=\"fix_se3.php?action=adminse3pass\" onClick=\"alert('Vous allez afficher un mot de passe important, attention aux regards indiscrets !!');\">".gettext("Afficher le mot de passe adminse3")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des lenteurs de connexions')")."\"><img name=\"action_image1\"  src=\"../elements/images/system-help.png\"></u><br>";
    }
    echo "<a href=\"fix_se3.php?action=adminse3_rest\">".gettext("Remise en place des droits d'int&#233;gration pour adminse3")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des probl&#232;mes d\'int&#233;gration des postes Windows')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=rmprofiles\" onclick=\"return getlongconfirm();\">".gettext("Reg&#233;n&#233;rer l'ensemble des profils errants Windows")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des lenteurs de connexions')")."\"><img name=\"action_image1\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=permse3\" onclick=\"return getlongconfirm();\">".gettext("Remise en place des droits syst&#232;me par d&#233;faut")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des dysfonctionnements dans l\'interface ou lors des connexions')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=restore_droits\" onclick=\"return getlongconfirm();\">".gettext("Remise en place des droits sur les comptes utilisateurs")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des probl&#232;mes de droits pour les utilisateurs')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\"></u><br>";
	echo "<a href=\"fix_se3.php?action=test_profiles\">".gettext("Recherche des profils Windows 7 d&#233;faillants")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous recherchez les sessions corompues')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=restore_droits_full\" onclick=\"return getlongconfirm();\">".gettext("Remise en place de tous les droits")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des probl&#232;mes de droits')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=search_doublons\">".gettext("recherche des doublons ldap")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action avant une migration annuelle ou une mise a jour importante ')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
    if (file_exists("/var/se3/unattended/install/wpkg")) {
    echo "<a href=\"fix_se3.php?action=force_profils_wpkg\">".gettext("Raffraichissement des machines visibles dans wpkg")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez que certaines machines sont manquantes dans wpkg')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
    echo "<a href=\"fix_se3.php?action=force_rapports_wpkg\">".gettext("Renouvellement des rapports wpkg")."</a>&nbsp;<u onmouseover=\"return escape".gettext("('Effectuez cette action si vous constatez des probl&#232;mes de remont&#233;&#233;s des rapport wpkg')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\"></u><br>";
    }
}
require ("pdp.inc.php");

?>
