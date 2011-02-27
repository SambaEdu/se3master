<?php


   /**
   
   * Page permettant de creer des listes pour en faire un export de l'annuaire
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
   * file: grouplist.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// Aide
$_SESSION["pageaide"]="Annuaire";

$filter=$_GET['filter'];

if ((is_admin("Annu_is_admin",$login)=="Y") || (is_admin("sovajon_is_admin",$login)=="Y")) {
	$group=search_groups ("(cn=".$filter.")");
	$uids = search_uids ("(cn=".$filter.")");
	$people = search_people_groups ($uids,"(sn=*)","cat");
  	#$TimeStamp_1=microtime();
  	#############
  	# DEBUG     #
 	#############
  	#echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR><BR>";
  	#############
  	# Fin DEBUG #
  	#############
	if (count($people)) {
		// affichage des r?sultats
		// Nettoyage des _ dans l'intitul? du groupe
		$intitule =  strtr($filter,"_"," ");
		echo "<H1><U>".gettext("Groupe")."</U> : $intitule <font size=\"-2\">".$group[0]["description"]."</font></H1>\n";
		echo gettext("Il y a ").count($people).gettext(" membre");
		if ( count($people) >1 ) echo "s";
		echo gettext(" dans ce groupe")."<BR>\n";
		echo "<TABLE border=1><TR><TD ALIGN=Center>Nom</TD><TD ALIGN=Center>login</TD><TD ALIGN=Center>".gettext("Date naiss")."</TD></TR>\n";
		for ($loop=0; $loop < count($people); $loop++) {
			echo "<TR><TD>\n";
			if (($people[$loop]["cat"] == "Equipe") or ($people[$loop]["prof"]==1)) {
				echo "<img src=\"images/gender_teacher.gif\" alt=\"Professeur\" width=18 height=18 hspace=1 border=0>\n";

			} else {
				if ($people[$loop]["sexe"]=="F") {
					echo "<img src=\"images/gender_girl.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
				} else {
					echo "<img src=\"images/gender_boy.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
				}
			}
			preg_match("/([0-9]{8})/",$people[$loop]["gecos"],$naiss);
			echo $people[$loop]["fullname"]."</TD><TD>".$people[$loop]["uid"]."</TD><TD>".$naiss[0]."</TD>\n";

			echo "</TR>\n";
		}
		echo "</TABLE>\n";

		echo "<p>G&#233;n&#233;rer un <a href='grouplist_csv.php?filter=$filter' target='blank'>export CSV du groupe</a></p>\n";
  	} else {
    		echo " <STRONG>".gettext("Pas de membres")." </STRONG> ".gettext(" dans le groupe")." $filter.<BR>";
  	}
}
include ("pdp.inc.php");
?>
