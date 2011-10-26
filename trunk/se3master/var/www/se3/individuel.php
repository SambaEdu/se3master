<?php

   /**
   
   * Page d'affichage de l'espace personnel. Affiche les avertissements de depassement quand les quotas sont actives...
   * @Version $Id: individuel.php 1182 2006-06-11 15:53:28Z plouf $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lacroix "Olikin"

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: individuel.php
   */


require("entete.inc.php");
require ("config.inc.php");
require ("ldap.inc.php");
require("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');


$login=isauth();
if ($login == "") header("Location:$urlauth");


//aide 
$_SESSION["pageaide"]="L%27interface_%C3%A9l%C3%A8ve#Mon_Espace_personnel";

//user authentifie
//else header("Location:people.php?uid=$login");


//actualiser les avertissements
//exec("sudo /usr/share/se3/scripts/warn_quota.sh reset");
//$test_exist=exec("cat /etc/cron.d/se3-quotas | grep \"$(echo \"warn_quota.sh /home\")\"");
//if ( $test_exist != "" ) {exec("sudo /usr/share/se3/scripts/warn_quota.sh /home");}
//$test_exist=exec("cat /etc/cron.d/se3-quotas | grep \"$(echo \"warn_quota.sh /var/se3\")\"");
//if ( $test_exist != "" ) {exec("sudo /usr/share/se3/scripts/warn_quota.sh /var/se3");}


echo "<h1>".gettext("Mon espace personnel")."</h1>";

if ($login == "admin") {
	echo "<div align='center'><h2>".gettext("Vous &#234tes l'utilisateur admin: vous n'avez donc aucun quota attribu&#233 sur les partitions /home et /var/se3.");
	echo "&nbsp;<img src=\"elements/images/recovery.png\" title=\"".gettext("Vous n'&#234tes pas en d&#233passement de quota.")."\" align=\"middle\" border=\"0\"></h2>";
  	$partit = "/home";
  	echo "<h3> <a href=\"#\" onclick=\"popuprecherche('infos/stats_user.php?partition=$partit&uid=$login','popuprecherche','width=800,height=500');\"> <img src=\"elements/images/notes.png\" title=\"".gettext("Cela correspond au lecteur")." $lettre ".gettext("visible dans le poste de travail")."\" align=\"middle\" border=\"0\">&nbsp;  ".gettext("D&#233tail...")." </a></h3>";
  	echo "</div>";
} else {
	//if ( file_exists("/tmp/tmp_quota_K")) exec("sudo /usr/share/se3/scripts/warn_quota.sh /home");
  	//if ( file_exists("/tmp/tmp_quota_H")) exec("sudo /usr/share/se3/scripts/warn_quota.sh /var/se3");

	//if ( file_exists("/tmp/tmp_quota_K") or file_exists("/tmp/tmp_quota_H"))
	//  {
    	$arr = array("/home", "/var/se3");
    	foreach ($arr as $partit) {    
		//extraction de l'occupation sur disque de $login avec repquota
      		$ligne=exec("sudo repquota $partit|grep $login|tr -s \" \"");
      		$utilise=exec("echo $ligne|cut -d \" \" -f3");
      		$utilise=$utilise/1000;
      		$softquota=exec("echo $ligne|cut -d \" \" -f4");
      		$softquota=$softquota/1000;
      		$grace=exec("echo $ligne|cut -d \" \" -f6");
    
      		if ( $grace == "none" ) {$grace=gettext("Expir&#233");} else {
        		$formatheure=exec("echo $grace|grep \":\"");
        		if ( $formatheure != "" ) { #il faut filtrer car la grace est au format H:min
          			$nbreh=exec("echo $grace|cut -d \":\" -f1|sed -e \"s/ //g\"");
          			if ( $nbreh < 24 ) {
            				//echo "coucou";
            				$grace=0;
            			} else {
            				$grace=1;
            			}
          		}  else {
          			$grace=exec("echo $grace | tr -d \"days\"");
          		}
        	}
      
      		echo "<div align='center'><h2>".gettext("Vous utilisez")." $utilise ".gettext("Mo dans ");
  
      		if ($partit == "/home" ) {echo gettext("votre espace personnel ");} else {echo gettext("les r&#233pertoires partag&#233s ");}
      
      		if ( $softquota != 0 ) {
        		if ($utilise > $softquota) {
          			echo gettext("au lieu des")." $softquota ".gettext("Mo disponibles");
          			if ($partit == "/home" ) {$lettre="K: (Mes documents, Bureau, etc....)";} else {$lettre="H: (Classes), I: (Docs) et L: (Progs)";}
          			echo ".</h2>";
          			if ($partit == "/home" ) {
            				echo "<h3>";
            				//echo "<input type=\"button\" value=\"Recherche dans l'annuaire\" onclick=\"popuprecherche('searchacls.php','popuprecherche','width=500,height=500');\">";
            				// <a href=\"#\" onclick=\"popuprecherche('infos/stats_user.php?partition=$partit&uid=$login','popuprecherche','width=800,height=500');\">  D&#233;tail sur /home... </a>
            				echo "<a href=\"#\" onclick=\"popuprecherche('infos/stats_user.php?partition=$partit&uid=$login','popuprecherche','width=800,height=400');\">";
					echo "<img src=\"elements/images/notes.png\" title=\"".gettext("Cela correspond au lecteur")." $lettre ".gettext("visible dans le poste de travail")."\" align=\"middle\" border=\"0\">&nbsp;".gettext("   D&#233tail...")."</a></h3>";
				}
            
            			//sauvegarde de la ligne precedente
            			//echo "<a href=infos/stats_user.php?partition=$partit&user=\"$login\"> <img src=\"elements/images/notes.png\" title=\"Cela correspond &#224; votre Mes Documents et au lecteur $lettre visible dans le poste de travail\" align=\"middle\" border=\"0\">&nbsp; D&#233;tail... </a></h3>";}
            
          			echo "<h2><font color=red>".gettext("Votre quota d'espace disque sur")." $partit ".gettext("est plein.");
          			if ($grace == 0) {
            				echo "<img src=\"elements/images/critical.png\" title=\"".gettext("P&#233riode de grace &#233coul&#233e. Supprimez d'urgence les fichiers inutiles.")."\" align=\"middle\" border=\"0\">&nbsp;</h2>";
            				echo "<h2>".gettext("Dor&#233;navant, vous ne pouvez plus rien &#233;crire sur ce disque.")." </h2> <h2>".gettext("ATTENTION : Tant que vous ne lib&#232;rerez pas d'espace sur")." $lettre, ".gettext("AUCUN logiciel ne fonctionnera plus correctement");
            			} else {
            				echo "<img src=\"elements/images/warning.png\" title=\"".gettext("P&#233;riode de grace en cours. Supprimez rapidement les fichiers inutiles.")."\" align=\"middle\" border=\"0\">&nbsp;</h2>";
            				echo "<h2>".gettext("Dans")." $grace ".gettext("jour(s), vous ne pourrez plus rien &#233;crire sur ce disque.")."</h2> <h2>".gettext("ATTENTION : Si vous ne lib&#233;rez pas d'espace sur")." $lettre, ".gettext("pass&#233; ce d&#233;lai AUCUN logiciel ne fonctionnera plus correctement");
            			}
				echo "</font>";
          		} else {
          			echo gettext("sur les")." $softquota ".gettext("Mo disponibles");
			        echo ".&nbsp;<img src=\"elements/images/recovery.png\" title=\"".gettext("Vous ne d&#233passez pas l'espace disponible.")."\" align=\"middle\" border=\"0\">";
          		}
        	}
      		echo "</h2></div><hr>";
      		//echo "ligne $ligne espace perso de $login sur $partit: $utilise Mo sur les $softquota disponibles , periode de grace $grace";
      	}
}

//images: zoom.png, notes.png, folder.png, 
//images couleurs: critical.png, warning.png, recovery.png
include ("pdp.inc.php");
?>
