<?php
  
   /**	
   * Permet d'envoyer des popup a un parc 
   * @Version $Id: popparc.inc.php 2939 2008-05-04 14:20:22Z plouf $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter Caen 
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: popup
   * file: popparc.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-popup',"/var/www/se3/locale");
textdomain ('se3-popup');


  
  $computer=$_POST['computer'];
  $filtrecomp=isset($_POST['filtrecomp']) ? $_POST['filtrecomp'] : "";

if (is_admin("computers_is_admin",$login)=="Y") {

	//aide
        $_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";
    	
	// Affichage du formulaire de selection de machine
    	if (!isset($computers)) {
		echo "<H1>".gettext("Pop Down :-) ")."</H1>\n";
		echo "<BR>";
        	echo "<H3>".gettext("S&#233;lection de la machine destinataire du Pop Up")."</H3>";
		echo "<FORM action=\"popcomputer.inc.php\" method=\"post\">\n";
                echo "<P>".gettext("Lister les noms contenant: ");
                echo "<INPUT TYPE=\"text\" NAME=\"filtrecomp\"\n VALUE=\"$filtrecomp\" SIZE=\"8\">";
                echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
                echo "<br><br></FORM>\n";

		
	 	if ($filtrecomp == '') 
			$filtrel = '*';
		else 
			$filtrel = "*$filtrecomp*";
		$list_machines=search_machines("(&(cn=$filtrel)(objectClass=ipHost))","computers");
        	if ( count($list_machines)>0) {
			echo gettext("Choisir les machines:")." \n";
            		echo "<FORM method=\"post\" action=\"popcomputer.inc.php\">\n"; 
            		echo "<SELECT NAME=\"computers[]\" multiple=\"multiple\" SIZE=\"".count($list_machines)."\">";
            		for ($loop=0; $loop < count($list_machines); $loop++) {
               	 		echo "<option value=\"".$list_machines[$loop]["cn"]."\">".$list_machines[$loop]["cn"]."\n";
                        }
            		echo "</SELECT>&nbsp;&nbsp;\n";
            		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
            		echo "</FORM>\n";
               }
       } else {
		$computers = $computers;
		$nbrconnect=0;
		for ($loop=0; $loop < count($computers); $loop++) {
			$connect=`smbstatus |grep -w $computers[$loop]`;
			if (empty($connect)) {
				echo "<LI><small><b>$computers[$loop]</b> n'est pas connect&#233;e !</small></LI>";
	                } else {
	            				$nbrconnect= $nbrconnect + 1;
	            				exec ("cat /tmp/popup.txt|smbclient -U 'Administrateur Samba Edu 3' -M $computers[$loop]");
	            				echo "<LI><small><b>$computers[$loop]</b>".gettext(" est destinataire du Pop Up")."</small></LI>";
			}
		}
		echo "<br><br>";
     		if ($nbrconnect==0) {
       			echo "<b><small>".gettext("Pas d'&#233;mission de Pop Up car aucune machine n'est actuellement connect&#233;e !")."</small></b>";
                } else {
       			echo gettext("Nombre total de popup &#233;mis: ")." $nbrconnect";
       		}

	}
}

include ("pdp.inc.php");

?>

