<?php


   /**
   
   * Ajoute des groupes
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @Adrien CRESPIN Stage Lycee Valdon Limoges

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: aj_ssgroup.php
   */


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

//Aide
$_SESSION["pageaide"]="Annuaire";

echo "<h1>".gettext("Annuaire")."</h1>";

if (is_admin("Annu_is_admin",$login)=="Y") {
	$cn=$_GET["cn"];
	$description=$_GET["description"];
	echo "<form action=\"affichageleve.php\" method=\"post\">";
	echo "<B>".gettext("S&#233;lectionner le(s) groupe(s) dans le(s)quel(s) se situent les personnes &#224; mettre dans le groupe :")." </B><BR><BR>";

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
    	elseif (!preg_match ("/^Eleves/", $list_groups[$loop]["cn"]) &&
            !preg_match ("/^overfill/", $list_groups[$loop]["cn"]) &&
            !preg_match ("/^Cours_/", $list_groups[$loop]["cn"]) &&
//            !preg_match ("/^Matiere_/", $list_groups[$loop]["cn"]) &&
            !preg_match ("/^lcs-users/", $list_groups[$loop]["cn"]) &&
            !preg_match ("/^machines/", $list_groups[$loop]["cn"])
	    //&&
            // !preg_match ("/^Profs/", $list_groups[$loop]["cn"])
	    ) {
            $autres[$m]["cn"] = $list_groups[$loop]["cn"];
            $autres[$m]["description"] = $list_groups[$loop]["description"];
            $m++;}
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
$action='1';
echo "<select name= \"classe_gr[]\" value=\"$classe_gr\" size=\"10\" multiple=\"multiple\">\n";
    for ($loop=0; $loop < count ($classe) ; $loop++) {
	echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"];
    }
    echo "</select>";
    echo "</td>";

    echo "<td>\n";
    echo "<select name= \"equipe_gr[]\" value=\"$equipe_gr\" size=\"10\" multiple=\"multiple\">\n";
    for ($loop=0; $loop < count ($equipe) ; $loop++) {
	echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"];
    }
    echo "</select></td>\n";

    echo "<td valign=\"top\">
    <select name=\"autres_gr[]\" value=\"$autres_gr\" size=\"10\" multiple=\"multiple\">";
    for ($loop=0; $loop < count ($autres) ; $loop++) {
	echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"];
    }
    echo "<td>\n";
    echo "<select name=\"matiere_gr[]\" value=\"$matiere_gr\" size=\"10\" multiple=\"multiple\">";
    for ($loop=0; $loop < count ($matiere) ; $loop++) {
    	echo "<option value=".$matiere[$loop]["cn"].">".$matiere[$loop]["cn"];
    }


    echo "</select></td></tr></table>"; ?>
    <input type="submit" value="<?php echo gettext("valider");?>">
    <input type="reset" value="<?php echo gettext("R&#233;initialiser la s&#233;lection");?>">
    <input type="hidden" name="cn" value=<?php echo $cn ?> >
    <input type="hidden" name="description" value=<?php echo $description ?> >
    <input type="hidden" name="action" value=<?php echo $action ?> >
    <?php
    echo "</form></small>";



}//fin is_admin
else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");
include ("pdp.inc.php");
?>
