<?php

   /**
   
   * Interface de deploiement 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: deploy
   * file: accueil.php

  */	

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-deploy',"/var/www/se3/locale");
textdomain ('se3-deploy');

//aide
$_SESSION["pageaide"]="Le_module_D%C3%A9ploiement_dans_les_r%C3%A9pertoires_des_utilisateurs";

if (is_admin("se3_is_admin",$login)=="Y") {

	$path=$_GET['path'];
	$chemin=$_GET['chemin'];
	$repsup=$_GET['repsup'];
	$repinf=$_GET['repinf'];

	echo "<H1>".gettext("D&#233ploiement de fichiers")."</H1><P>";
	echo "<small>";
	echo "<B>".gettext("R&#233pertoire dans lequel sera effectu&#233e la copie :")."</B><BR>";
	$chemininit="/etc/skel/user";
    	if($path=="") {
		$chemin=$chemininit;
    	}
    	
	if($repsup==1) {
		$repinf=substr("$repinf",0,-1);
		$ici=$repinf;
		$repinf=explode("/",$repinf);
		$repinf=end($repinf);
		$ici=preg_replace("/$repinf/","",$ici);
		$test=$ici;
		$test=substr("$test",0,-1);
    	} else {
		$ici=$chemin;
		$ici.=$path;
		$test=$ici;
		$ici.="/";
    	}


     	if($test!=$chemininit) {
		echo "<a href=\"accueil.php?repsup=1&repinf=$ici\">".gettext("R&#233pertoire parent")."<BR></a>";
    	}
    	$repsup=0;
    	exec ("/usr/bin/sudo /usr/share/se3/scripts/ls.sh \"$ici\"");
    	$rep = file ("/tmp/resultat");
        
	for ($i=0 ; $i < count ($rep); $i++) {
		echo "<a href=\"accueil.php?path=$rep[$i]&chemin=$ici\">$rep[$i]</a><br>";
	}

    	$test=substr("$ici",0,-1);
    	$type="r&#233;pertoire";
    	exec ("/usr/bin/sudo /usr/share/se3/scripts/testfichier.sh \"$test\"");
    	$fich = file ("/tmp/testfichier.tmp");
    	$fich = trim($fich[0]);
    	if ($fich == "oui"){
		$type="fichier";
    	}
    	$repertoire=$test;
    	$repertoire=substr($repertoire,14);
    	if ($fich == "oui"){
		echo "<BR><BR><B>".gettext("Attention vous n'avez pas s&#233lectionn&#233 un r&#233pertoire")."</B>";
	} else  echo "<BR><BR>".gettext("Le ")."<B>$type</B>".gettext(" s&#233lectionn&#233 est :")."<B>/home$repertoire</B>";


    ?>

<form action="valideformulaire.php" method="post">
<B><?php echo gettext("Voulez-vous &#233craser le r&#233pertoire ou fichier si celui-ci  existe d&#233j&#224?"); ?></B><BR>
<label for='ecraser_oui'><?php echo gettext("Oui"); ?></label>	     <input type="radio" name="ecraser" id="ecraser_oui" value="oui"><BR>
<label for='ecraser_non'><?php echo gettext("Non"); ?></label> 	     <input type="radio" name="ecraser" id="ecraser_non" value="non"><BR><BR>

<B><?php echo gettext("S&#233lectionner le(s) groupe(s) pour le d&#233ploiement :"); ?>	</B><BR><BR>
<?php
// Etablissement des listes des groupes disponibles
$list_groups=search_groups("(&(cn=*) $filter )");
      // Etablissement des sous listes de groupes :
      $i=0; $j =0; $k =0;$l=0; $m = 0;
      for ($loop=0; $loop < count ($list_groups) ; $loop++) {
	  // Cours
	  if ( preg_match ("/Cours_/", $list_groups[$loop]["cn"]) ) {
	  $cours[$i]["cn"] = $list_groups[$loop]["cn"];
	  $cours[$i]["description"] = $list_groups[$loop]["description"];
	  $i++;}
	  // Classe
	  elseif ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) ) {
	  $classe[$j]["cn"] = $list_groups[$loop]["cn"];
	  $classe[$j]["description"] = $list_groups[$loop]["description"];
	  $j++;}
	  // Equipe
          elseif ( preg_match ("/Equipe_/", $list_groups[$loop]["cn"]) ) {
	  $equipe[$k]["cn"] = $list_groups[$loop]["cn"];
	  $equipe[$k]["description"] = $list_groups[$loop]["description"];
	  $k++;}
	  // Matiere
	  elseif ( preg_match ("/Matiere_/", $list_groups[$loop]["cn"]) ) {
	  $matiere[$l]["cn"] = $list_groups[$loop]["cn"];
	  $matiere[$l]["description"] = $list_groups[$loop]["description"];
	  $l++;}
	  // Autres
	  elseif (!preg_match ("/^overfill/", $list_groups[$loop]["cn"]) &&
		  !preg_match ("/^lcs-users/", $list_groups[$loop]["cn"]) &&
	          !preg_match ("/^machines/", $list_groups[$loop]["cn"])
		// &&
	        //  !preg_match ("/^Profs/", $list_groups[$loop]["cn"])
		) {
	  $autres[$m]["cn"] = $list_groups[$loop]["cn"];
	  $autres[$m]["description"] = $list_groups[$loop]["description"];
	  $m++;
	  }
}
// Affichage des boites de selection des nouveaux groupes secondaires
?>
<table border="0" cellspacing="10">
<thead>
<tr>
<B><td><?php echo gettext("Classes"); ?></td>
<td><?php echo gettext("Mati&#232res"); ?></td>
<td><?php echo gettext("Cours"); ?></td>
<td><?php echo gettext("Equipes"); ?></td>
<td><?php echo gettext("Autres"); ?></td></B>
</tr>
</thead>
<tbody>
<tr>
<td valign="top">
<?php
echo "<select name= \"classe_gr[]\" value=\"$classe_gr\" size=\"10\" multiple=\"multiple\">\n";
for ($loop=0; $loop < count ($classe) ; $loop++) {
echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"];
}
echo "</select>";
echo "</td>";
echo "<td valign=\"top\">\n";
echo "<select name= \"matiere_gr[]\" value=\"$matiere_gr\" size=\"10\" multiple=\"multiple\">\n";
for ($loop=0; $loop < count ($matiere) ; $loop++) {
echo "<option value=".$matiere[$loop]["cn"].">".$matiere[$loop]["cn"];
}
echo "</select>";
echo "</td>";
echo "<td valign=\"top\">\n";
echo "<select name= \"cours_gr[]\" value=\"$cours_gr\" size=\"10\" multiple=\"multiple\">";
for ($loop=0; $loop < count ($cours) ; $loop++) {
echo "<option value=".$cours[$loop]["cn"].">".$cours[$loop]["cn"];
}
echo "</select>";
echo "</td>";
echo "<td valign=\"top\">\n";
echo "<select name= \"equipe_gr[]\" value=\"$equipe_gr\" size=\"10\" multiple=\"multiple\">\n";
for ($loop=0; $loop < count ($equipe) ; $loop++) {
echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"];
}
echo "</select></td>\n";
echo "<td valign=\"top\">";
echo "<select name=\"autres_gr[]\" value=\"$autres_gr\" size=\"5\" multiple=\"multiple\">";
for ($loop=0; $loop < count ($autres) ; $loop++) {
echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"];
}
echo "</select></td></tr></table>";
echo "<input type=\"hidden\" name=\"repertoire\" value=\"$repertoire\">
       <input type=\"hidden\" name=\"fich\" value=\"$fich\">
       <input type=\"submit\" value=\"".gettext("valider")."\">
       <input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">";

echo "</form></small>";

}//fin is_admin

else echo gettext("Vous n'avez pas les droits n&#233cessaires pour ouvrir cette page...");

include ("pdp.inc.php");

?>

