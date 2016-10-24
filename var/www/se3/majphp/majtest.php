<?php


   /**
   
   * Mise a jour de SambaEdu3 
   * @Version $Id: majtest.php 9189 2016-02-22 00:14:30Z keyser $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs wawa   olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: majphp 
   * file: majtest.php

  */	


require("entete.inc.php");

//aide
$_SESSION["pageaide"]="Prise_en_main#Mettre_.C3.A0_jour_le_serveur";

if (ldap_get_right("se3_is_admin",$login)!="Y")
	die ("<HTML><BODY>".gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$action=$_GET['action'];
	
if ($action == "majse3") {
	$info_1 = gettext("Mise &#224; jour lanc&#233;e, ne fermez pas cette fen&#234;tre avant que le script ne soit termin&#233;. vous recevrez un mail r&#233;capitulatif de tout ce qui sera effectu&#233;...");
	echo $info_1;
	ob_implicit_flush(true); 
	ob_end_flush();
	system('sleep 1; /usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh se3 &');
}
else {
    echo "<H1>Mise  &#224; jour du serveur Se3</H1>\n";
    echo "<br><br>";
    echo "<center>";
    echo "<TABLE border=\"1\" width=\"80%\">";
    // Modules disponibles
//    echo "<TR><TD colspan=\"4\" align=\"center\" class=\"menuheader\" height=\"30\">\n";
//    echo gettext("Etat des paquets");
//    echo "</TD></TR>";
    echo "<TR><TD align=\"center\" class=\"menuheader\" height=\"30\">\n";
    echo gettext("Nom du paquet &#224; mettre  &#224; jour");
    echo "</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Version install&#233;e")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Version disponible")."</TD></TR>";

    
    // paquet se3Master
    // On teste si on a bien la derniere version
    $se3_version_install = exec("apt-cache policy se3 | grep \"Install\" | cut -d\":\" -f2");
    $se3_version_dispo = exec("apt-cache policy se3 | grep \"Candidat\" | cut -d\":\" -f2");
    if ("$se3_version_install" != "$se3_version_dispo") {
            echo "<TR><TD>".gettext("Paquet principal Se3")."</TD>";
            echo "<TD align=\"center\">$se3_version_install</TD>";
            echo "<TD align=\"center\"><b>$se3_version_dispo</b></TD>";
            echo "</TR>";
    }


    // Module se3-domain
    $domain_actif = exec("dpkg -s se3-domain | grep \"Status: install ok\"> /dev/null && echo 1");
    // On teste si on a bien la derniere version
    $domain_version_install = exec("apt-cache policy se3-domain | grep \"Install\" | cut -d\":\" -f2");
    $domain_version_dispo = exec("apt-cache policy se3-domain | grep \"Candidat\" | cut -d\":\" -f2");
    if ("$domain_version_install" != "$domain_version_dispo") {
            echo "<TR><TD>".gettext("Scripts de jonction au domaine (se3-domain)")."</TD>";
            echo "<TD align=\"center\">$domain_version_install</TD>";
            echo "<TD align=\"center\"><b>$domain_version_dispo</b></TD>";
            echo "</TR>";
    }

    // Module se3-logonpy
    $logonpy_actif = exec("dpkg -s se3-logonpy | grep \"Status: install ok\"> /dev/null && echo 1");
    // On teste si on a bien la derniere version
    $logonpy_version_install = exec("apt-cache policy se3-logonpy | grep \"Install\" | cut -d\":\" -f2");
    $logonpy_version_dispo = exec("apt-cache policy se3-logonpy | grep \"Candidat\" | cut -d\":\" -f2");
    if ("$logonpy_version_install" != "$logonpy_version_dispo") {
        echo "<TR><TD>".gettext("Gestion de l'environnement (se3-logonpy)")."</TD>";
        echo "<TD align=\"center\">$logonpy_version_install</TD>";
        echo "<TD align=\"center\"><b>$logonpy_version_dispo</b></TD>";
        echo "</TR>";
    }

    // Module backup
    $backup_actif = exec("dpkg -s se3-backup | grep \"Status: install ok\"> /dev/null && echo 1");
    if ($backup_actif =="1") {
        // On teste si on a bien la derniere version
        $backup_version_install = exec("apt-cache policy se3-backup | grep \"Install\" | cut -d\":\" -f2");
        $backup_version_dispo = exec("apt-cache policy se3-backup | grep \"Candidat\" | cut -d\":\" -f2");
        if ("$backup_version_install" != "$backup_version_dispo") {
            echo "<TR><TD>".gettext("Sauvegarde sur disque ou NAS (se3-backup)")."</TD>";
            echo "<TD align=\"center\">$backup_version_install</TD>";
            echo "<TD align=\"center\"><b>$backup_version_dispo</b></TD>";
            echo "</TR>";
        }
    }
    
    // Module Inventaire
    $ocs_actif = exec("dpkg -s se3-ocs | grep \"Status: install ok\"> /dev/null && echo 1");
    if ($ocs_actif =="1") {
        $ocs_version_install = exec("apt-cache policy se3-ocs | grep \"Install\" | cut -d\":\" -f2");
        $ocs_version_dispo = exec("apt-cache policy se3-ocs | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$ocs_version_install" != "$ocs_version_dispo") {
            echo "<TR><TD>".gettext("Syst&#232;me d'inventaire (se3-ocs)")."</TD>";
            echo "<TD align=\"center\">$ocs_version_install</TD>";
            echo "<TD align=\"center\"><b>$ocs_version_dispo</b></TD>";
            echo "</TR>";
        }
    }

    // Module Antivirus
    $clam_actif = exec("dpkg -s se3-clamav | grep \"Status: install ok\"> /dev/null && echo 1");
    if($clam_actif == "1") {
        $clam_version_install = exec("apt-cache policy se3-clamav | grep \"Install\" | cut -d\":\" -f2");
        $clam_version_dispo = exec("apt-cache policy se3-clamav | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$clam_version_install" != "$clam_version_dispo") {
            echo "<TR><TD>".gettext("Syst&#232;me anti-virus (se3-clamav)")."</TD>";
            echo "<TD align=\"center\">$clam_version_install</TD>";
            echo "<TD align=\"center\"><b>$clam_version_dispo</b></TD>";
           echo "</TR>";
        }

    }

    // Module DHCP
    $dhcp_actif = exec("dpkg -s se3-dhcp | grep \"Status: install ok\" > /dev/null && echo 1");
    
    if($dhcp_actif == "1") {
        $dhcp_version_install = exec("apt-cache policy se3-dhcp | grep \"Install\" | cut -d\":\" -f2");
        $dhcp_version_dispo = exec("apt-cache policy se3-dhcp | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$dhcp_version_install" != "$dhcp_version_dispo") {
            echo "<TR><TD>".gettext("Serveur DHCP (se3-dhcp)")."</TD>";
            echo "<TD align=\"center\">$dhcp_version_install</TD>";
            echo "<TD align=\"center\"><b>$dhcp_version_dispo</b></TD>";
           echo "</TR>";
        }
        
    }
    // Module clients-linux
    $clinux_actif = exec("dpkg -s se3-clients-linux | grep \"Status: install ok\" > /dev/null && echo 1");
    
    if($clinux_actif == "1") {
        $clinux_version_install = exec("apt-cache policy se3-clients-linux | grep \"Install\" | cut -d\":\" -f2");
        $clinux_version_dispo = exec("apt-cache policy se3-clients-linux | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$clinux_version_install" != "$clinux_version_dispo") {
            echo "<TR><TD>".gettext("Support des clients linux (se3-clients-linux)")."</TD>";
            echo "<TD align=\"center\">$clinux_version_install</TD>";
            echo "<TD align=\"center\"><b>$clinux_version_dispo</b></TD>";
           echo "</TR>";
        }
        
    }
    

    // Module pla
    $pla_actif = exec("dpkg -s se3-pla | grep \"Status: install ok\" > /dev/null && echo 1");
    if($pla_actif == "1") {
        $pla_version_install = exec("apt-cache policy se3-pla | grep \"Install\" | cut -d\":\" -f2");
        $pla_version_dispo = exec("apt-cache policy se3-pla | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$pla_version_install" != "$pla_version_dispo") {
            echo "<TR><TD>".gettext("Administration de ldap avec phpldapadmin (se3-pla)")."</TD>";
            echo "<TD align=\"center\">$pla_version_install</TD>";
            echo "<TD align=\"center\"><b>$pla_version_dispo</b></TD>";
           echo "</TR>";
        }
        
    }    
    // Module radius
    $radius_actif = exec("dpkg -s se3-radius | grep \"Status: install ok\" > /dev/null && echo 1");
    if($radius_actif == "1") {
        $radius_version_install = exec("apt-cache policy se3-radius | grep \"Install\" | cut -d\":\" -f2");
        $radius_version_dispo = exec("apt-cache policy se3-radius | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$radius_version_install" != "$radius_version_dispo") {
            echo "<TR><TD>".gettext("Serveur free-radiuis (se3-radius)")."</TD>";
            echo "<TD align=\"center\">$radius_version_install</TD>";
            echo "<TD align=\"center\"><b>$radius_version_dispo</b></TD>";
           echo "</TR>";
        }
        
    }
    // Module clonage 
    $clonage_actif = exec("dpkg -s se3-clonage | grep \"Status: install ok\" > /dev/null && echo 1");
    if($clonage_actif == "1") {
        $clonage_version_install = exec("apt-cache policy se3-clonage | grep \"Install\" | cut -d\":\" -f2");
        $clonage_version_dispo = exec("apt-cache policy se3-clonage | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$clonage_version_install" != "$clonage_version_dispo") {
            echo "<TR><TD>".gettext("Clonage / sauvegarde - restauration des stations (se3-clonage)")."</TD>";
            echo "<TD align=\"center\">$clonage_version_install</TD>";
            echo "<TD align=\"center\"><b>$clonage_version_dispo</b></TD>";
           echo "</TR>";
        }
        
    }
    
    // Module unattended
    
    $unattended_actif = exec("dpkg -s se3-unattended | grep \"Status: install ok\" > /dev/null && echo 1");
    
    if($unattended_actif == "1") {
        $unattended_version_install = exec("apt-cache policy se3-unattended | grep \"Install\" | cut -d\":\" -f2");
        $unattended_version_dispo = exec("apt-cache policy se3-unattended | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$unattended_version_install" != "$unattended_version_dispo") {
            echo "<TR><TD>".gettext("Installation de stations (se3-unattended)")."</TD>";
            echo "<TD align=\"center\">$unattended_version_install</TD>";
            echo "<TD align=\"center\"><b>$unattended_version_dispo</b></TD>";
            echo "</TR>";
        }
        
    }
    
    

    // Module wpkg
    $wpkg_actif = exec("dpkg -s se3-wpkg | grep \"Status: install ok\" > /dev/null && echo 1");
    if($wpkg_actif == "1") {
        $wpkg_version_install = exec("apt-cache policy se3-wpkg | grep \"Install\" | cut -d\":\" -f2");
        $wpkg_version_dispo = exec("apt-cache policy se3-wpkg | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$wpkg_version_install" != "$wpkg_version_dispo") {
            echo "<TR><TD>".gettext("D&#233;ploiement d'applications sur les clients windows (se3-wpkg)")."</TD>";
            echo "<TD align=\"center\">$wpkg_version_install</TD>";
            echo "<TD align=\"center\"><b>$wpkg_version_dispo</b></TD>";
            echo "</TR>";
        }
        
    }
    
    

    // Module internet
    $internet_actif = exec("dpkg -s se3-internet | grep \"Status: install ok\" > /dev/null && echo 1");
    if($internet_actif == "1") {
        $internet_version_install = exec("apt-cache policy se3-internet | grep \"Install\" | cut -d\":\" -f2");
        $internet_version_dispo = exec("apt-cache policy se3-internet | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$internet_version_install" != "$internet_version_dispo") {
            echo "<TR><TD>".gettext("contr&#244;le de l'acc&#232;s internet (se3-internet)")."</TD>";
            echo "<TD align=\"center\">$internet_version_install</TD>";
            echo "<TD align=\"center\"><b>$internet_version_dispo</b></TD>";
            echo "</TR>";
        }
        
    }
    
    // Module synchro
    $synchro_actif = exec("dpkg -s se3-synchro | grep \"Status: install ok\" > /dev/null && echo 1");
    if($synchro_actif == "1") {
        $synchro_version_install = exec("apt-cache policy se3-synchro | grep \"Install\" | cut -d\":\" -f2");
        $synchro_version_dispo = exec("apt-cache policy se3-synchro | grep \"Candidat\" | cut -d\":\" -f2");
        // On teste si on a bien la derniere version
        if ("$synchro_version_install" != "$synchro_version_dispo") {
            echo "<TR><TD>".gettext("synchronisation distante de fichiers (se3-synchro)")."</TD>";
            echo "<TD align=\"center\">$synchro_version_install</TD>";
            echo "<TD align=\"center\"><b>$synchro_version_dispo</b></TD>";
            echo "</TR>";
        }
        
    }
    
    
    echo "</table>";
    echo "<BR><BR>";
        
        
	echo "Vous pouvez consulter la liste des changements en consultant <a href='http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Mises_%C3%A0_jour' TARGET='_blank' >cette page</a> \n";
	echo "<BR><BR>";
        echo "<FORM action=\"majtest.php?action=majse3 \"method=\"post\"><CENTER><INPUT type='submit' VALUE='Lancer la mise &#224; jour'></CENTER></FORM>\n";
        

}
# pied de page
include ("pdp.inc.php");

?>
