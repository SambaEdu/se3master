<?php


   /**
   
   * Permet d'envoyer des popup  
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter Caen 
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: popup
   * file: index.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-popup',"/var/www/se3/locale");
textdomain ('se3-popup');


  
  $parc=$_GET['parc'];

if (is_admin("se3_is_admin",$login)=="Y") {

	//aide
        $_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";

	echo "<H1>".gettext("Pop Up")."</H1>";
 	echo "<H3>".gettext("Message du Pop Up : ").$parc."</H3><BR>\n";
?>   

	<form action="popup.php" method="post">
	<textarea cols=60 rows=5 name="message" ></textarea>
	<br>
	<br>

	<?php
	// Si pas de parc indique
	if ($parc =="") {
	?>
		<H3><?php echo gettext("Destinataires du Pop Up:"); ?></H3>
		<input type="radio" name="destination" value="poptous"><?php echo gettext("Toutes les machines actuellement connect&#233;es"); ?>
		<br>
		<input type="radio" name="destination" value="popparc"><?php echo gettext("Un parc de machines"); ?>
		<br>
		<input type="radio" name="destination" value="popcomputer"><?php echo gettext("Des machines"); ?>
	<?php } else {
		echo "<input type=\"hidden\" name=\"parc\" value=\"".$parc."\">\n";
	}
	?>
		<br>
		<br>
		<input type="submit" value="Envoyer le Pop Up">
		</form>
		<i><?php echo gettext("(Pour envoyer un Pop Up &#224; un utilisateur ou &#224; un groupe, il faut utiliser"); ?></i> <a href=/annu/annu.php><b><?php echo gettext("l'annuaire"); ?></b></a>)

<?php
	 
		

}//fin if is admin

else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");
?>
    
