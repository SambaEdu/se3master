<?php


   /**
   
   * Permet de supprimer un partage Classe
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Equipe TICE Crdp de Caen
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Ce script est conserve pour le cas d'un serveur esclave avec repertoires classes deportes (utilise admind)
   * @note Remplace par rep_classe.php   
   */

   /**

   * @Repertoire: partages/
   * file: delete_folders_classes.php

  */	



  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-partages',"/var/www/se3/locale");
  textdomain ('se3-partages');


  if (is_admin("se3_is_admin",$login)=="Y") {
 
 	echo "<h1>".gettext("Suppression de r&#233;pertoire classe")."</h1>";

	// Definition des messages d'alerte
	$alerte_1="<div class='error_msg'>".gettext("Votre demande de suppression de ressources classes n'a pas &#233;t&#233; prise en compte car une t&#226;che d'administration est en cours sur le serveur <b>");
    	$alerte_2=gettext("</b>, veuillez r&#233;it&#233;rer votre demande plus tard. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
    	$alerte_3="<div class='error_msg'>".gettext("Votre demande de suppression de ressources classes a &#233;chou&#233;e. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
     	
	// Definition des messages d'info
    	$info_1 = gettext("Cette t&#226;che est ordonnanc&#233e, vous recevrez un mail de confirmation de suppression de ressources dans quelques instants...");

    	// Prepositionnement variables
    	$mono_srv = false;
    	$multi_srv = false;
    	// Recherche de la nature mono ou multi serveur de la plateforme SE3
    	$master=search_machines ("(l=maitre)", "computers");
    	$slaves= search_machines ("(l=esclave)", "computers");
    	if ( count($master) == 0 ) {
      		echo gettext("<P>ERREUR : Il n'y a pas de serveur maitre d&#233clar&#233 dans l'annuaire ! <BR>Veuillez contacter le super utilisateur du serveur SE3.</P>");
    	} elseif (  count($master) == 1  && count($slaves) == 0 ) {
       		// Plateforme mono-serveur
       		$mono_srv = true;
    	} elseif (  count($master) == 1  && count($slaves) > 0  ) {
       		$multi_srv = true;
    	}
    	
	// Fin Recherche de la nature mono ou multi serveur de la plateforme SE3
    	if ( $mono_srv ) {
      		// configuration mono serveur  : determination des parametres du serveur
      		$serveur=search_machines ("(l=maitre)", "computers");
      		$cn_srv= $serveur[0]["cn"];
      		$stat_srv = $serveur[0]["l"];
      		$ipHostNumber =  $serveur[0]["ipHostNumber"];
    	} elseif ($multi_srv) {
      		// configuration multi-serveurs : presentation d'un form de selection du serveur
      		if ( !$selected_srv && !$del_folders_classes) {
        		echo "<P><H3>".gettext("S&#233lection du serveur ou vous souhaitez supprimer des ressources classes:")." </H3>";
        		$servers=search_computers ("(|(l=esclave)(l=maitre))");
        		echo "<form action=\"delete_folders_classes.php\" method=\"post\">\n";
        		for ($loop=0; $loop < count($servers); $loop++) {
          			echo $servers[$loop]["description"]." ".$servers[$loop]["cn"]."&nbsp;<input type=\"radio\" name=\"cn_srv\" value =\"".$servers[$loop]["cn"]."\"";
          			if ($loop==0) echo "checked";
            			echo "><BR>\n";
        		}
        		
			$form="<input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
        		$form ="<input type=\"hidden\" name=\"selected_srv\" value=\"true\">\n";
        		$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
        		$form.="</form>\n";
        		echo $form;
      
      		} elseif ( $selected_srv && $multi_srv) {
        		// configuration multi serveurs  : determination des parametres du serveur
        		$serveur=search_machines ("(cn=$cn_srv)", "computers");
        		$stat_srv = $serveur[0]["l"];
        		$ipHostNumber =  $serveur[0]["ipHostNumber"];
      		}
    	}
    
    	// Recherche des ressources classes existantes
    	if ($stat_srv == "maitre") {
      		// Serveur maitre  :  Recherche des ressources classes existantes
      		// ouverture du repertoire Classes
      		$loop=0;
      		$repClasses = dir ("/var/se3/Classes/");
      		// recuperation de chaque entree
      		while ($ressource =  $repClasses->read()) {
        		if ( preg("/^Classe_/", $ressource) ) {
          			$list_ressources[$loop]= $ressource;
          			$loop++;
        		}
      		}
      		
		$repClasses->close();
    	} elseif  ($stat_srv == "esclave") {
      		// Serveur esclave :  Recherche des ressources classes existantes
      		exec ("ssh -l remote_adm $ipHostNumber 'ls /var/se3/Classes'", $list_ressources, $ReturnValue);
    	}
    	// Fin  Recherche des ressources classes existantes
    
    	// Presentaion du formulaire de selection des ressources a supprimer
    	if (  (!$del_folders_classes && ($stat_srv == "maitre" || $stat_srv == "esclave")  ) || ( $del_folders_classes && count($list_del_classes)==0 ) ) {
      		echo "<H3>".gettext("Suppression de ressources  Classes sur le serveur "). "$cn_srv</H3>\n";
      		if (count($list_ressources) == 0 ) {
        		echo "<P>".gettext("Pas de ressources a supprimer sur ce serveur !")."</P>\n";
      		}  else {
        		if   ( count($list_ressources)>10) $size=10; else $size=count($list_ressources);
        		echo "<form action=\"delete_folders_classes.php\" method=\"post\">\n";
        		echo "<P>".gettext("S&#233lectionnez les ressources classes &#224 supprimer :")."<BR>\n";
        		// Affichage liste des ressources a supprimer
        		echo "<select size=\"".$size."\" name=\"list_del_classes[]\" multiple=\"multiple\">\n";
        		for ($loop=0; $loop<count($list_ressources);$loop++) {
          			echo "<option value=".$list_ressources[$loop].">".$list_ressources[$loop]."\n";
        		}
        		echo "</select><br>\n";
        		echo "<input type=\"hidden\" name=\"del_folders_classes\" value=\"true\">\n";
        		echo "<input type=\"hidden\" name=\"cn_srv\" value=\"$cn_srv\">\n";
        		echo "<input type=\"hidden\" name=\"stat_srv\" value=\"$stat_srv\">\n";
        		echo "<input type=\"hidden\" name=\"ipHostNumber\" value=\"$ipHostNumber\">\n";
        		echo "<input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
        		echo "<input type=\"submit\" value=\"Valider\"  onclick= \"return getconfirm();\">\n";
        		echo "</form>\n";
        		
			// Verification selection d'au moins une classe
        		if ( $del_folders_classes && count($list_del_classes)==0 ) {
          			echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
        		}
      		}
    	} elseif ($del_folders_classes) {
       
       		// Creation du script bash
       		echo "<h3>".gettext("Vous avez s&#233lectionn&#233 "). count($list_del_classes).gettext(" classe(s)  &#224 supprimer sur le serveur")." $stat_srv <b>$cn_srv</b></h3>\n";
       		// Construction du script admin.sh
       		$path_Classes="/var/se3/Classes";
       		$commandes = "#!/bin/bash\n";
       		$commandes .="#".gettext(" Effacement repertoire(s) Classe")." \n";
       		$commandes .="cd $path_Classes\n";
       		for ($loop=0; $loop<count($list_del_classes); $loop++) {
        		if ($list_del_classes[$loop]) $commandes .="rm -R ".$list_del_classes[$loop]."\n";
       		}
       
       		// mel CR de suppression ressources Classes
      		$Subject=gettext("[SE3 T&#226;che d'administration] Suppression de ressources Classes")."\n";
      		list($user,$groups)=people_get_variables("admin", true);
      		$mel_adm=$user["email"];

      		$commandes.="\n#".gettext("Mel CR suppression de ressources Classes")."\n";
      		$commandes.="cat > /tmp/admind.tmp <<-EOF\n";
      		$commandes.= gettext("La suppression des ressources Classes suivantes :\n");
      		
		for ($loop=0; $loop < count($list_del_classes); $loop++) {
        		$commandes.=$list_del_classes[$loop]."\n";
      		}
      		$commandes.= gettext("sur le serveur")." $cn_srv ".gettext("a &#233;t&#233; effectu&#233;e avec succ&#232;s.")."\n";
      		$commandes.= "\n";
      		$commandes.= "EOF\n";
      		$commandes.= "mail -s \"$Subject\" $mel_adm < /tmp/admind.tmp\n";

       		// Depot du script tmp_$stat_srv.sh sur le serveur maitre
      		$fp=@fopen("/var/remote_adm/tmp_".$stat_srv.".sh","w");
      		if($fp) {
        		fputs($fp,$commandes."\n");
        		fclose($fp);
        		chmod ("/var/remote_adm/tmp_$stat_srv.sh", 0600);
        		// Si creation sur le maitre
        		if ( $stat_srv == "maitre" ) {
          			// Si pas de presence de admin.sh
          			if ( !is_file("/var/remote_adm/admin.sh") ) {
            				// Renommage et chmod +x du script sur le maitre
            				rename ("/var/remote_adm/tmp_".$stat_srv.".sh",  "/var/remote_adm/admin.sh");
            				chmod ("/var/remote_adm/admin.sh", 0700);
            				if (file_exists("/var/remote_adm/admin.sh"))
                				echo $info_1;
            				else echo $alerte_3;
          			}  else {
            
	    				// Message d'alerte  : Presence d'un admin.sh !!
            				echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
          			}
        		
				// Si creation sur un esclave  copie du maitre vers l'esclave
        		} elseif ( $stat_srv == "esclave" ) {
           			
				//  Recherche de la presence d'un admin.sh sur le serveur esclave
           			exec ("ssh -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
           			// Si pas de presence de admin.sh sur l'esclave
           			if (! $AllOutput[0]) {
             				// Copie du script sur l'esclave avec scp
             				exec ("/usr/bin/scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh", $AllOutput, $ReturnValue);
             				// chmod +x , renommage du script bash et gogogo
             				exec ("ssh -l remote_adm  $ipHostNumber 'chmod +x /var/remote_adm/tmp_$stat_srv.sh;mv  /var/remote_adm/tmp_$stat_srv.sh /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
             				if ($ReturnValue==0) {
					 	// Effacement de tmp_esclave.sh cree sur le maitre
						unlink ("/var/remote_adm/tmp_esclave.sh");
					 	echo $info_1;
					 }else echo $alerte_3;
           			} else {
             				
					// Message d'alerte : Presence d'un admin.sh sur l'esclave!!
             				echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
           			}
        		}
      		} else {
        		echo "<div  class='error_msg'>".gettext("ERREUR : Impossible de cr&#233er le fichier"). "$path_to_wwwse3/Admin/tmp_$stat_srv</div>\n";
      		}
    	
	} // Fin creation du script bash
  } // Fin if is_admin
  include ("pdp.inc.php");
?>
