<?php

   /**
   * Affiche le menu en appelant les includes de menu.d
  
   * @Version $Id  menu.inc.php 2647 2007-12-23 17:50:08Z plouf $
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE CRDP de caen
   * @auteurs wawa - crob - keyser - plouf
   
   * @Note Appelle 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: menu.inc.php
   * @Repertoire: includes/ 
   */  
  



include "ldap.inc.php";
    // Prepositionnement variables
    $mono_srv = false;
    $multi_srv = false;
    // Recherche de la nature mono ou multi serveur de la plateforme SE3
    $master=search_machines ("(l=maitre)", "computers");
    $slaves= search_machines ("(l=esclave)", "computers");
    if ( count($master) == 0 ) {
      echo "<P>".gettext("ERREUR : Il n'y a pas de serveur maitre d&#233clar&#233 dans l'annuaire ! <BR>Veuillez contacter le super utilisateur du serveur SE3.")."</P>";
    } elseif (  count($master) == 1  && count($slaves) == 0 ) {
       // Plateforme mono-serveur
       $mono_srv = true;
    } elseif (  count($master) == 1  && count($slaves) > 0  ) {
       $multi_srv = true;
    }
    // Fin Recherche de la nature mono ou multi serveur de la plateforme SE3
//=============================================
$resultat=mysql_query("SELECT * FROM params WHERE name='menu_fond_ecran'");
if(mysql_num_rows($resultat)==0){
	$menu_fond_ecran=0;
}
else{
	$ligne=mysql_fetch_object($resultat);
	if($ligne->value=="1"){
		$menu_fond_ecran=1;
	}
	else{
		$menu_fond_ecran=0;
	}
}
// La valeur de $menu_fond_ecran est utilisee dans la page 70windowz.inc
//=============================================

$resultat=mysql_query("SELECT * FROM params WHERE name='unattended'");
if(mysql_num_rows($resultat)==0){
	$menu_unattended=0;
}
else{
	$ligne=mysql_fetch_object($resultat);
	if($ligne->value=="1"){
		$menu_unattended=1;
	}
	else{
		$menu_unattended=0;
	}
}
// La valeur de $menu_unattended est utilisee dans la page 98tftp.inc
//=============================================

$liens=array(0
    );
    exec("ls /var/www/se3/includes/menu.d/*.inc",$files,$return);
    for ($i=0; $i< count($files); $i++) {
   	if ($files[$i] == "/var/www/se3/includes/menu.d/50ressources.inc") {
		if ($mono_srv == "true") {
    			include ($files[$i]);
		}

   	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/51ressources.inc") {
		if ($multi_srv == "true") {
    			include ($files[$i]);
		}

   	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/95sauvegarde.inc") {
		if (($backuppc == "1") || ($savbandactiv == "1")) {
    			include ($files[$i]);
		}
	
	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/90inventaire.inc") {
		if ($inventaire == "1") {
    			include ($files[$i]);
		}
	
	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/75secu.inc") {
		if ($antivirus == "1") {
    			include ($files[$i]);
		}

	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/97dhcp.inc") {
		if ($dhcp == "1") {
    			include ($files[$i]);
		}
		
	} elseif ($files[$i] == "/var/www/se3/includes/menu.d/98wpkg.inc") {
		if ($wpkg == "1") {
    			include ($files[$i]);
		}	

	} else {
    		include ($files[$i]);
	}	
    }
    
?>
