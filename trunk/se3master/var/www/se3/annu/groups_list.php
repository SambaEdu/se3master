<?php


   /**
   
   * Affiche les groupes a partir de l'annuaires
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: groups_list.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


$group=$_POST['group'];
$priority_group=$_POST['priority_group'];

echo "<h1>".gettext("Annuaire")."</h1>\n";
$_SESSION["pageaide"]="Annuaire";

if ((ldap_get_right("Annu_is_admin",$login)=="Y") || (ldap_get_right("Annu_can_read",$login)=="Y") || (ldap_get_right("se3_is_admin",$login)=="Y")) {

	aff_trailer ("3");

	if (!$group) {
		$filter = "(cn=*)";
	} else {
		if ($priority_group == "contient") {
	      		$filter = "(cn=*$group*)";
	    	} elseif ($priority_group == "commence") {
	      		$filter = "(|(cn=Classe_$group*)(cn=Cours_$group*)(cn=Equipe_$group*)(cn=Matiere_$group*)(cn=$group*))";
	    	} else {
	      		// $priority_group == "finit"
	      		$filter = "(|(cn=Classe_*$group)(cn=Cours_*$group)(cn=Equipe_*$group)(cn=Matiere_*$group)(cn=*$group))";
    		}
	}

	// Remplacement *** ou ** par *
	$filter=preg_replace("/\*\*\*/","*",$filter);
	$filter=preg_replace("/\*\*/","*",$filter);
	
	#$TimeStamp_0=microtime();
	$groups=search_groups($filter);
	#$TimeStamp_1=microtime();
	  #############
	  # DEBUG     #
	  #############
	  #echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
	  #############
	  # Fin DEBUG #
	  #############
	// affichage de la liste des groupes trouves
	if (count($groups)) {
	    if (count($groups)==1) {
		echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupe r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
	    } else {
	      	echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupes r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
	    }
	    echo "<UL>\n";
	    for ($loop=0; $loop < count($groups); $loop++) {
	      	echo "<LI><A href=\"group.php?filter=".$groups[$loop]["cn"]."\">";
	      	if ($groups[$loop]["type"]=="posixGroup")
        		 echo "<STRONG>".$groups[$loop]["cn"]."</STRONG>";
	      	else
        		echo $groups[$loop]["cn"];
      			echo "</A>&nbsp;&nbsp;&nbsp;<font size=\"-2\">".$groups[$loop]["description"]."</font></LI>\n";
            }
    	    echo "</UL>\n";
	} else {
    		echo "<STRONG>".gettext("Pas de r&#233;sultats")."</STRONG> ".gettext("correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>";
	}
  
} 
	

include ("pdp.inc.php");
?>
