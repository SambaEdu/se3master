<?php


   /**
   
   * Gestion du professeur remplacant
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Schwarz
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: remplace.php
   */






include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$_SESSION["pageaide"]="Annuaire";
echo "<h1>".gettext("Annuaire")."</h1>\n";


if (is_admin("Annu_is_admin",$login)=="Y") {
	$filter=profs;

	aff_trailer ("1");
	#$TimeStamp_0=microtime();
	$group=search_groups ("(cn=".$filter.")");
	$uids = search_uids ("(cn=".$filter.")");
	$people = search_people_groups ($uids,"(sn=*)","cat");

	if (count($people)) {
		// affichage des resultats
		// Nettoyage des _ dans l'intitule du groupe
		
		echo "<FORM action=\"remplacant.php\" method=\"post\">\n";
		$intitule =  strtr($filter,"_"," ");
	
		$liste="";
		for ($loop=0; $loop < count($people); $loop++) {
	      		$liste=$liste. "<OPTION VALUE=\"".$people[$loop]["uid"]."\">".$people[$loop]["fullname"]."</OPTION>";
	    	}


		// Professeur abs
		echo "<TABLE BORDER=0><TR><TD><BR>".gettext("Professeur absent ")." </TD><TD><BR>";
		echo"<SELECT name='uid'>";
		  echo $liste;					
		echo"</SELECT>	</TD>\n";
		echo"</TR></TABLE><BR><HR>\n";

		// Professeur remplacant
		echo "<TABLE BORDER=0><TR><TD><BR>".gettext("Professeur rempla&#231;ant ")."</TD><TD><BR>";
		echo"<SELECT name='remplacant'>";
		echo $liste;
		echo "</SELECT>	</TD>\n";
		echo "</TR>\n";

		echo "</TABLE><HR><div align=center><input type=\"submit\" Value=\"".gettext("V&#233;rifier les droits de l'absent et du rempla&#231;ant")."\"></div></FORM>"; 
	}

} else {
        echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");
?>
