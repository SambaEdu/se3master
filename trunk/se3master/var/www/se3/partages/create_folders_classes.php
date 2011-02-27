<?php


   /**
   
   * Permet de creer les partages Classe
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
   * file: synchro_folders_classes.php

  */	
  
  
  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-partages',"/var/www/se3/locale");
  textdomain ('se3-partages');


  if (is_admin("se3_is_admin",$login)=="Y") {
    // Aide en ligne
      $titre=gettext("Aide en ligne");
      $texte=gettext("Vous &#234tes administrateur du serveur SE3.<BR>Avec le menu ci-dessous, vous pouvez cr&#233er des ressources pour chacune des  classes de votre annuaire.<BR>Une ressource classe, se pr&#233sente comme un sous dossier du partage Classes (H:).Dans le sous dossier d'une classe, vous trouverez :\n<ul><li>un sous dossier par eleve de la classe,<li>un sous dossier «_profs» &#224 usage des membres de l'&#233quipe p&#233dagogique de cette classe,<li>un sous dossier «_travail» qui est un espace d'&#233change entre les professeurs et les &#233;l&#232;ves de la classe.</ul><p>Droits d'acc&#232;s aux sous dossiers :<ul><li> <u>Les &#233;l&#232;ves acc&#232;dent </u> :<ul><li> En rw- a leur sous dossier personnel,<li> En r-- au dossier travail,</ul><li> <u>Les &#233;l&#232;ves n'acc&#233;dent pas</u> :<ul><li>Aux sous-dossiers des autres &#233;l&#232;ves de la classe.<li>Aux sous-dossiers «_profs» des professeurs.</ul></ul><ul><li> <u>Les professeurs acc&#232;dent </u> :  en rw- sur l'ensemble de l'arborescence de leurs classes.</ul>");
      mkhelp($titre,$texte);
     // Fin Aide en ligne

echo "<h1>".gettext("Cr&#233;ation des r&#233;pertoires classes")."</h1>";

// Definition des messages d'alerte
    $alerte_1="<div class='error_msg'>".gettext("Votre demande de cr&#233ation de nouvelles ressources classes n'a pas &#233;t&#233; prise en compte car une t&#226;che d'administration est en cours sur le serveur")." <b>";
    $alerte_2="</b>,".gettext(" veuillez r&#233;it&#233;rer votre demande plus tard. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
    $alerte_3="<div class='error_msg'>".gettext("Votre demande de cr&#233;ation de nouvelles ressources classes a &#233;chou&#233;e. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
     // Definition des messages d'info
    $info_1 = gettext("Cette t&#226;che est ordonnanc&#233e, vous recevrez un mail de confirmation de cr&#233;ation dans quelques instants...");

    #------------------------------------------
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
    if ( $mono_srv ) {
      // configuration mono serveur  : determination des parametres du serveur
      $serveur=search_machines ("(l=maitre)", "computers");
      $cn_srv= $serveur[0]["cn"];
      $stat_srv = $serveur[0]["l"];
      $ipHostNumber =  $serveur[0]["ipHostNumber"];
    } elseif ($multi_srv) {
      // configuration multi-serveurs : presentation d'un form de selection du serveur
      if ( !$selected_srv && !$create_folders_classes) {
        echo "<H3>".gettext("S&#233lection du serveur ou vous souhaitez cr&#233er des ressources classes:")." </H3>";
        $servers=search_computers ("(|(l=esclave)(l=maitre))");
        echo "<form action=\"create_folders_classes.php\" method=\"post\">\n";
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
    // Fin selection et recherche des caracteristiques du serveur

    if ( (!$create_folders_classes &&  ($stat_srv == "maitre" || $stat_srv == "esclave")  ) || ( $create_folders_classes && count($new_folders_classes)==0 ) ) {
      // Recherche de la liste des classes dans l'annuaire
       $list_classes=search_groups("cn=Classe_*");
      // Recherche des sous dossiers classes deja existant sur le serveur selectionne
      // Si serveur maitre
      if ($stat_srv == "maitre" ) {
        // Constitution d'un tableau avec les ressources deja existantes
        $dirClasses = dir ("/var/se3/Classes");
        $indice=0;
        while ( $Entry = $dirClasses ->read() ) {
          if ( preg_match("/^Classe_/", $Entry) ) {
            $RessourcesClasses[$indice] = $Entry;
            $indice++;
          }
        }
      } else {
      // Si serveur esclave
        exec ("ssh -l remote_adm $ipHostNumber 'ls /var/se3/Classes'", $RessourcesClasses, $ReturnValue);
      }
      // Creation d'un tableau des nouvelles ressources a creer  par
      // elimination des ressources deja existantes
      $k=0;
      for ($i=0; $i < count($list_classes); $i++ ) {
        for ($j=0; $j < count($RessourcesClasses); $j++ ) {
          if (  $list_classes[$i]["cn"] ==  $RessourcesClasses[$j])  {
            $exist = true;
            break;
          } else { $exist = false; }
        }
        if (!$exist) {
          $list_new_classes[$k]["cn"]= $list_classes[$i]["cn"];
          $k++;
        }
      }
      // Affichage menu de selection des sous-dossiers classes a creer
      if   ( count($list_new_classes)>10) $size=10; else $size=count($list_new_classes);
      if ( count($list_new_classes)>0) {
        echo "<form action=\"create_folders_classes.php\" method=\"post\">\n";
        echo "<h3>".gettext("Cr&#233ation de ressources Classes sur")." $cn_srv : </h3>\n";
        echo "<p>".gettext("S&#233lectionnez les ressources classes &#224 cr&#233er :")."</p>\n";
        echo "<select size=\"".$size."\" name=\"new_folders_classes[]\" multiple=\"multiple\">\n";
        for ($loop=0; $loop < count($list_new_classes); $loop++) {
          echo "<option value=".$list_new_classes[$loop]["cn"].">".$list_new_classes[$loop]["cn"]."\n";
        }
        echo "</select><br>\n";
        echo "<input type=\"hidden\" name=\"create_folders_classes\" value=\"true\">\n";
        echo "<input type=\"hidden\" name=\"cn_srv\" value=\"$cn_srv\">\n";
        echo "<input type=\"hidden\" name=\"stat_srv\" value=\"$stat_srv\">\n";
        echo "<input type=\"hidden\" name=\"ipHostNumber\" value=\"$ipHostNumber\">\n";
        echo "<input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
        echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
        echo "</form>\n";
        // Verification selection d'au moins une classe
        if ( $create_folders_classes && count($new_folders_classes)==0 ) {
          echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
        }
      } else {
          echo "<div class='error_msg'>".gettext("Il n'y a pas de nouvelles classes &#224 ajouter !")."</div>\n";
      }
    } elseif ($create_folders_classes) {
      // Creation du script bash
      echo "<H3>".gettext("Vous avez s&#233lectionn&#233 "). count($new_folders_classes).gettext(" classe(s)  &#224 cr&#233er sur le serveur")." $stat_srv <b>$cn_srv</b></H3>\n";
      // Construction du script admin.sh
      $commandes = "#!/bin/bash\n";
      for ($loop=0; $loop < count($new_folders_classes); $loop++) {
        $classe=$new_folders_classes[$loop];
        $folder_classe="/var/se3/Classes/".$new_folders_classes[$loop];
        // Creation du repertoire Classe
        $commandes .="#".gettext("Creation du repertoire Classe")." $classe\n";
        $commandes .="mkdir $folder_classe\n";
        $commandes.="chown admin:nogroup $folder_classe\n";
        $commandes.="chmod 700 $folder_classe\n";

        // Application des acl posix pour le groupe de cette classe
        $commandes.="#".gettext("Application des acl posix pour le groupe")." $classe\n";
        $commandes.="setfacl -m d:m::rwx $folder_classe\n";
        $commandes.="setfacl -m m::rwx $folder_classe\n";
        $commandes.="setfacl -m g:$classe:rx $folder_classe\n";

        // Application acl posix pour le groupe admins
        $commandes.="\n#".gettext("Application des acl posix pour le groupe admins SE3 sur l'ensemble de l'arborescence")."\n";
        $commandes.="setfacl -m d:g:admins:rwx $folder_classe\n";
        $commandes.="setfacl -m g:admins:rwx $folder_classe\n";

        // Application acl posix pour le groupe Equipe_ de cette classe
        $equipe = preg_replace("/Classe_/","Equipe_",$classe);
        $commandes.="#".gettext("Application des acl posix pour le groupe")." $equipe\n";
        $commandes.="setfacl -m d:g:$equipe:rwx $folder_classe\n";
        $commandes.="setfacl -m g:$equipe:rx $folder_classe\n";

        // Recherche des eleves de cette classe
        $uids = search_uids ("(cn=".$classe.")", "half");
        $commandes.="#Creation des sous dossiers eleves\n";
        for  ($i=0; $i < count($uids); $i++) {
          $eleve = $uids[$i]["uid"];
          $commandes.="mkdir $folder_classe/$eleve\n";
          $commandes.="chown admin:nogroup $folder_classe/$eleve\n";
          $commandes.="chmod 700 $folder_classe/$eleve\n";
          $commandes.="setfacl -m u:$eleve:rwx $folder_classe/$eleve\n";
          $commandes.="setfacl -m d:u:$eleve:rwx $folder_classe/$eleve\n";
          $commandes.="setfacl -m m::rwx $folder_classe/$eleve\n";
          $commandes.="\n";
        }
        // Creation du sous dossier Profs
        $commandes.="#".gettext("Creation du sous dossier professeurs")."\n";
        $commandes.="mkdir $folder_classe/_profs\n";
        $commandes.="chown admin:nogroup $folder_classe/_profs\n";
        $commandes.="chmod 700 $folder_classe/_profs\n";
        $commandes.="setfacl -m m::rwx $folder_classe/_profs\n";

        $commandes.="\n";
        // Creation du sous dossier travail
        $commandes.="#".gettext("Creation du sous dossier travail")."\n";
        $commandes.="mkdir $folder_classe/_travail\n";
        $commandes.="chown admin:nogroup $folder_classe/_travail\n";
        $commandes.="chmod 700 $folder_classe/_travail\n";
        $commandes.="setfacl -m d:g:$classe:rx $folder_classe/_travail\n";
        $commandes.="setfacl -m g:$classe:rx $folder_classe/_travail \n";
        $commandes.="setfacl -m m::rwx $folder_classe/_travail\n";
        $commandes.="\n";
      }
      // mel CR de creation ressources Classes
      $Subject=gettext("[SE3 T&#226;che d'administration] Cr&#233ation de ressources Classes")."\n";
      list($user,$groups)=people_get_variables("admin", true);
      $mel_adm=$user["email"];

      $commandes.="\n#".gettext("Mel CR creation ressources Classes")."\n";
      $commandes.="cat > /tmp/admind.tmp <<-EOF\n";
      $commandes.= gettext("La cr&#233;ation des ressources Classes suivantes :")."\n";
      for ($loop=0; $loop < count($new_folders_classes); $loop++) {
        $commandes.=$new_folders_classes[$loop]."\n";
      }
      $commandes.= gettext("sur le serveur")." $cn_srv ".gettext("a &#233;t&#233; effectu&#233e avec succ&#232;s.")."\n";
      $commandes.= "\n";
      $commandes.= "EOF\n";
      $commandes.= "mail -s \"$Subject\" $mel_adm < /tmp/admind.tmp\n";
//leb
      $commandes1 = $commandes;
      #echo "<tt>".str_replace("\n", "<br>\n",$commandes1)."</tt>";
//leb
      // Creation du script tmp_$stat_srv.sh sur le serveur maitre
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
            rename ("/var/remote_adm/tmp_".$stat_srv.".sh", "/var/remote_adm/admin.sh");
            chmod ("/var/remote_adm/admin.sh", 0750);
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
           exec ("ssh -v -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
           // Si pas de presence de admin.sh sur l'esclave
           if (! $AllOutput[0]) {
             // Copie du script sur l'esclave avec scp
             exec ("/usr/bin/scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh", $AllOutput, $ReturnValue);
             // chmod +x , renommage du script bash
             exec ("ssh -l remote_adm  $ipHostNumber 'chmod +x /var/remote_adm/tmp_$stat_srv.sh;mv  /var/remote_adm/tmp_$stat_srv.sh /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
             if ($ReturnValue==0) {
			 	// Effacement de tmp_esclave.sh cree sur le maitre
				unlink ("/var/remote_adm/tmp_esclave.sh");
			 	echo $info_1;
			 } else echo $alerte_3;
           } else {
             // Message d'alerte : Presence d'un admin_esclave.sh !!
             echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
           }
        }
      } else {
        echo "<div  class='error_msg'>".gettext("ERREUR : Impossible de cr&#233er le fichier d'ordonnancement de cr&#233ation de ressources classes !"). "</div>\n";
      }
    } // Fin creation du script bash
  } // Fin if is_admin
  include ("pdp.inc.php");
?>
