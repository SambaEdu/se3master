<?php


   /**
   
   * Permet de lsiter les partages Classe
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Equipe TICE Crdp de Caen
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Ce script est conserve pour le cas d'un serveur esclave avec repertoires classes deportes (utilise admind)
   
   */

   /**

   * @Repertoire: partages/
   * file: liste_folders_classes.php

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
      	$texte=gettext("Vous &#234tes administrateur du serveur SE3.<BR>Avec le menu ci-dessous, vous pouvez lister les r&#233pertoires classes disponibles sur vos serveurs SE3");
      	mkhelp($titre,$texte);
 
	echo "<h1>".gettext("Liste des r&#233;pertoires classes disponibles")."</h1>";	
 
    // Fin Aide en ligne
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
        echo "<P><H3>".gettext("S&#233lection du serveur ou vous souhaitez lister les ressources classes disponibles :")." </H3>";
        $servers=search_computers ("(|(l=esclave)(l=maitre))");
        echo "<form action=\"liste_folders_classes.php\" method=\"post\">\n";
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
        if ( preg_match("/^Classe_/", $ressource) ) {
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
    // Presentation de la liste  des ressources disponibles
    if (  ($stat_srv == "maitre" || $stat_srv == "esclave")  ) {

      echo "<H3>".gettext("Liste des ressources  Classes disponibles sur le serveur "). "$cn_srv</H3>\n";
      if (count($list_ressources) == 0 ) {
        echo "<P>".gettext("Il n'y a pas de ressources Classes sur ce serveur !")."</P>\n";
      }  else {
        if   ( count($list_ressources)>10) $size=10; else $size=count($list_ressources);
        echo "<form>\n";
        // Affichage liste des ressources disponibles
        echo "<select size=\"".$size."\" name=\"list_del_classes[]\" multiple=\"multiple\">\n";
        for ($loop=0; $loop<count($list_ressources);$loop++) {
          echo "<option value=".$list_ressources[$loop].">".$list_ressources[$loop]."\n";
        }
        echo "</select><br>\n";
        echo "</form>\n";
      }
    }
  } // Fin if is_admin
  include ("pdp.inc.php");
?>
