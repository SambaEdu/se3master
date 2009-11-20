<?php


   /**
   
   * Creation d'un groupe tpe
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
   * file: groupetpe.php
   */





include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if (is_admin("Annu_is_admin",$login)=="Y") {
		
	$_SESSION["pageaide"]="Annuaire";	
	echo "<h1>".gettext("Annuaire")."</h1>\n";
	aff_trailer ("1");
	echo "<form action=\"affichageleve.php\" method=\"post\">";
	echo "<table border=\"0\">\n";
	echo "<TR><TD><B>".gettext("Nouveau groupe :")."</B></TD>\n";
	echo "<TR><TD>".gettext("Intitul&#233; :")."</TD><td><input type=\"text\" name=\"cn\" value=\"$cn\" size=\"20\"></TD></TR>";
	echo "<TR><TD>".gettext("Description :")."</TD><td><input type=\"text\" name=\"description\" value=\"$description\" size=\"40\"></TD></TR>";
	echo "</table><BR>";
	?>
	<P><B><?php echo gettext("Cr&#233;er un repertoire classe_grp dans");?> /var/se3/Classe :</B></P>
	<INPUT TYPE=RADIO NAME=CREER_REP VALUE="o" CHECKED><?php echo gettext("Oui"); ?>
	<INPUT TYPE=RADIO NAME=CREER_REP VALUE="n"><?php echo gettext("Non"); ?> <BR><BR>
	<?php
	echo "";
	echo "<B>".gettext("S&#233;lectionner le(s) groupe(s) dans le(s)quel(s) se situent les personnes &#224; mettre dans le groupe ci-dessus :")."</B><BR><BR>";
	// Etablissement des listes des groupes disponibles
	$list_groups=search_groups("(&(cn=*) $filter )");
	// Etablissement des sous listes de groupes :
	$j =0; $k =0; $m = 0; $n = 0;
	for ($loop=0; $loop < count ($list_groups) ; $loop++) {
    		// Classe
    		if ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) ) {
			$classe[$j]["cn"] = $list_groups[$loop]["cn"];
			$classe[$j]["description"] = $list_groups[$loop]["description"];
			$j++;
		}
    		// Equipe
    		elseif ( preg_match ("/Equipe_/", $list_groups[$loop]["cn"]) ) {
			$equipe[$k]["cn"] = $list_groups[$loop]["cn"];
			$equipe[$k]["description"] = $list_groups[$loop]["description"];
			$k++;
		}
    		elseif ( preg_match ("/Matiere_/",$list_groups[$loop]["cn"]) ) {
    			$matiere[$n]["cn"] = $list_groups[$loop]["cn"];
			$matiere[$n]["description"] = $list_groups[$loop]["description"];
			$n++;
		}
    		// Autres
   		elseif (//	!preg_match ("/^Eleves/", $list_groups[$loop]["cn"]) &&
            		!preg_match ("/^overfill/", $list_groups[$loop]["cn"]) &&
            		!preg_match ("/^Cours_/", $list_groups[$loop]["cn"]) &&
			//   !preg_match ("/^Matiere_/", $list_groups[$loop]["cn"]) &&
            		!preg_match ("/^lcs-users/", $list_groups[$loop]["cn"]) &&
            		!preg_match ("/^machines/", $list_groups[$loop]["cn"])
			// &&
            		//	!preg_match ("/^Profs/", $list_groups[$loop]["cn"])
			) {
            		$autres[$m]["cn"] = $list_groups[$loop]["cn"];
            		$autres[$m]["description"] = $list_groups[$loop]["description"];
            		$m++;
		}
  	}
	// Affichage des boites de selection des nouveaux groupes secondaires
	?>
	<table border="0" cellspacing="10">
	<tr>
	<td><?php echo gettext("Classes"); ?></td>
	<td><?php echo gettext("Equipes"); ?></td>
	<td><?php echo gettext("Autres"); ?></td>
	<td><?php echo gettext("Mati&#232;res"); ?></td>
	</tr>
	<tr>
	<td valign="top">
	<?php
	echo "<select name= \"classe_gr[]\" size=\"10\" multiple=\"multiple\">\n";
    	for ($loop=0; $loop < count ($classe) ; $loop++) {
		echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"];
    	}
    	echo "</select>";
    	echo "</td>";

    	echo "<td>\n";
    	echo "<select name= \"equipe_gr[]\"  size=\"10\" multiple=\"multiple\">\n";
    	for ($loop=0; $loop < count ($equipe) ; $loop++) {
		echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"];
    	}
    	echo "</select></td>\n";

   	echo "<td valign=\"top\">
    	      <select name=\"autres_gr[]\" size=\"10\" multiple=\"multiple\">";
    	for ($loop=0; $loop < count ($autres) ; $loop++) {
		echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"];
    	}

    	echo "</select></td>\n";
    	echo "<td>\n";
    	echo "<select name=\"matiere_gr[]\" size=\"10\" multiple=\"multiple\">";
    	for ($loop=0; $loop < count ($matiere) ; $loop++) {
    		echo "<option value=".$matiere[$loop]["cn"].">".$matiere[$loop]["cn"];
    	}
	//    echo "</select></td>\n";

    	echo "</select></td></tr></table>";
    	echo " <input type=\"submit\" value=\"".gettext("Valider")."\">
	       <input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">";

    	echo "</form>";

} else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");
?>
