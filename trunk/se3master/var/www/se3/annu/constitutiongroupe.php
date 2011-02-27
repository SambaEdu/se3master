<?php


   /**
   
   * Constitution des groupes
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo /usr/share/se3/scripts/creer_grpclass.sh
   */

   /**

   * @Repertoire: annu
   * file: constitutiongroupe.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');



if (is_admin("Annu_is_admin",$login)=="Y") { 
	
	$eleves=$_POST['eleves'];
	$cn=$_POST['cn'];
	$CREER_REP=$_POST['CREER_REP'];

 	// Aide
   	$_SESSION["pageaide"]="Annuaire";

     	echo "<h1>".gettext("Annuaire")."</h1>";

	// Ajout des membres au groupe

	echo "<H4>".gettext("Ajout des membres au groupe :")." <A href=\"/annu/group.php?filter=$cn\">$cn</A></H4>\n";
	for ($loop=0; $loop < count ($eleves) ; $loop++) {
		exec("/usr/share/se3/sbin/groupAddUser.pl  $eleves[$loop] $cn" ,$AllOutPut,$ReturnValue);
		echo gettext("Ajout de l'utilisateur ")."&nbsp;".$eleves[$loop]."&nbsp;";
		if ($ReturnValue == 0 ) {
			echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";

		} else { 
			echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
		}

	// Creation de la ressource groupe classe si besoin
	$CREER_REP=$_POST['CREER_REP'];
	if ($CREER_REP == "o") {
		exec ("sudo /usr/share/se3/scripts/creer_grpclass.sh $cn");
		echo "<BR><BR>";
		echo "<P><B>".gettext("Cr&#233;ation d'une ressources Groupe Classe(s) ordonnanc&#233;e :")."</B> <BR><P>";
		echo gettext("Le r&#233;pertoire ")." <B>Classe_grp_$cn</B> ".gettext("sera cr&#233;&#233; d'ici quelques instants dans ")." /var/se3/Classe...</B> ";
	}
    
include ("pdp.inc.php");

}//fin is_admin

?>
