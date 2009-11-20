<?php


   /**
   
   * Supprime un groupe dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: del_group.php
   */


  
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$_SESSION["pageaide"]="Annuaire";
echo "<h1>".gettext("Annuaire")."</h1>";

$cn = $_GET['cn'];

if (is_admin("Annu_is_admin",$login)=="Y") {

	aff_trailer ("6");
	$group=search_groups ("(cn=".$cn.")");
    	if ( $cn !="Eleves" && $cn !="Profs" && $cn !="Administratifs" && $group[0]["gidnumber"]!=$defaultgid) {
      		exec ("/usr/share/se3/sbin/groupDel.pl $cn",$AllOutPut,$ReturnValue);
      		if ($ReturnValue == "0") {
        		echo "<strong>".gettext("Le groupe")." $cn ".gettext(" a &#233;t&#233; supprim&#233; avec succ&#232;s.")."</strong><br>\n";
      		} else {
        		echo "<div class='error_msg'>".gettext("Echec de la suppression ")."<font color='black'>".gettext(" (type d'erreur :")." $ReturnValue)</font>, ".gettext(" Veuillez contacter ")."<A HREF='mailto:$MelAdminLCS?subject=PB changement mot de passe'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
       		}
    	} else {
      		echo "<div class=error_msg>".gettext("La suppression des groups principaux (Eleves, Profs, Administratifs) ou du groupe par d&#233;faut n'est pas autoris&#233;e !")."</div>";
    	}
} else {
    echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");
?>
