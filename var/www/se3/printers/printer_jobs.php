<?php


   /**
   
   *  Visualisation et suppression des travaux en cours
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: printer_jobs.php

  */	





// Affichage des travaux en cours avec possibilite de suppression

include "entete.inc.php";
include "ihm.inc.php";     // pour is_admin()

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');


  
  $printer=$_POST['printer'];
  $tag=$_POST['tag'];
  $list_job=$_POST['list_job'];

//aide
$_SESSION["pageaide"]="Imprimantes";

if (is_admin("printers_is_admin",$login)=="Y") {
//Affichage des travaux


    	echo "<H1>".gettext("Travaux en cours pour l'imprimante")." <B>$printer</B></H1>\n";
	if (!$list_job){
    		// Retourne le nombre de travaux
    		$nb_jobs=exec("lpstat -o $printer | wc -l");
    		// Retourne les travaux
    		$return=exec ("lpstat -R $printer",$job);
    		if ($nb_jobs>0) {          // Teste l'existence de travaux
        		echo "<P>".gettext("S&#233lectionnez les travaux que vous voulez supprimer")."</P>";
        		// Affichage du filtre sur utilisateur
        		if (!isset($filtre)) {
            			echo "<P>".gettext("Nom d'utilisateur:")." </P>";
            			echo "<FORM ACTION=\"printer_jobs.php\" METHOD=\"post\">";
            			echo "<INPUT TYPE=\"text\" NAME=\"filtre\" VALUE=\"$filtre\" SIZE=\"20\">";
            			echo "<INPUT TYPE=\"hidden\" NAME=\"printer\" VALUE=\"$printer\">";
            			echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Filtrer")."\">";
            			echo "</FORM>";
        		}
        		//Affichage du formulaire de liste des travaux
        		echo "<FORM ACTION=\"printer_jobs.php\" METHOD=\"post\">";
        		echo "<SELECT NAME=\"list_job[]\" SIZE=\"15\" MULTIPLE>";
        		for ($i=0;$i<$nb_jobs;$i++) {
            			$id_job=preg_split("/ +/",$job[$i]);  // La commande retournee par lstat donne une info brut qu' on splite pour la rendre
            			$num_job[$i]=$id_job[1];       // + digeste
            			$user_job[$i]=$id_job[3];
            			$size_job[$i]=$id_job[4];
            			if ($size_job[$i]>=1024) {
                			if ($size_job[$i]>=1024*1024) {
                    				$size_job[$i]=round($size_job[$i]/(1024*1024))." Mo";
                			} else {
                    				$size_job[$i]=round($size_job[$i]/1024)." Ko";
                			}
            			}
            			$time_job[$i]=$id_job[8];
            			$month_job[$i]=$id_job[6];
            			$day_job[$i]=$id_job[7];
            			$year_job[$i]=$id_job[9];
            			if ( !isset($filtre) || ( ($user_job[$i]==$filtre) ) ) {
                			echo "<OPTION VALUE=\"$job[$i]\">$num_job[$i];&nbsp&nbsp;$user_job[$i];&nbsp&nbsp; $size_job[$i]";
                			echo ";&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp;";
                			echo "$time_job[$i];&nbsp&nbsp&nbsp&nbsp;$day_job[$i] $month_job[$i] $year_job[$i]";
                			echo "</OPTION>";
                			echo "<br>";
            			}
        		}
        		echo "</SELECT>\n";
        		echo "<BR><BR>\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"printer\" VALUE=\"$printer\">\n";

				// AJOUT: boireaus pour permettre un retour apres consultation des travaux
				echo "<INPUT TYPE=\"hidden\" VALUE=\"$tag\" NAME=\"tag\">\n";

        		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\"><BR>\n";
        		echo "</FORM>\n";
        		// Rafraichissement de la page
        		echo "<FORM ACTION=\"printer_jobs.php\" METHOD=\"post\">\n";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"printer\" VALUE=\"$printer\">\n";
        		echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Rafra&#238chir")."\">\n";
        		echo "</FORM>\n";
    		} else {
        		echo "<P>".gettext("Pas de travaux en cours")."</P>\n";
    		}

		echo "<p>Retour &#224; la page de <a href='view_printers.php?one_printer=$printer'>Gestion de l'imprimante $printer</a></p>\n";
	}
	//Suppression des travaux selectionnes
	else {
    		for ($i=0;$i<count($list_job);$i++) {
        		$id_job=preg_split("/ +/",$list_job[$i]);
			if ($id_job[0] != ""){
				array_unshift($id_job, "");
			}	
        		exec ("/usr/bin/cancel $id_job[2]");
        		echo gettext("Travail")." <B>$id_job[0]</B> ".gettext("de l'utilisateur")." <B>$id_job[3]</B> ".gettext("supprim&#233;")."<BR>";
    		}

			if (is_admin("se3_is_admin",$login)=="Y") {
				echo "<p>Retour &#224; la page de <a href='view_printers.php#$tag'>Gestion de l'imprimante $printer</a></p>\n";
			}
			else{
				echo "<p>Retour &#224; la page de <a href='view_printers.php?one_printer=$printer'>Gestion de l'imprimante $printer</a></p>\n";
			}
	}
}

include "pdp.inc.php";
?>
