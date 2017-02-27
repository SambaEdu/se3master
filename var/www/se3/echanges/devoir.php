<?php


	/** 

	* Deploie des devoirs ou documents aux utilisateur
	
	* @Version $Id$
   	* @Projet LCS-SE3
   
   	* @Auteurs Jean Gourdin
   
   	* @Licence Distribue selon les termes de la licence GPL
    
    	*/

	/**

	* file: devoir.php
	* @Repertoire: echanges/
	*/


require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');

?>
<html>
  <head>
    <title><?php echo gettext("Distribution d'un texte aux &#233;l&#232;ves"); ?></title>
    <link href="style/style.css" type="text/css" rel="StyleSheet">
<?php

require("entete.inc.php");
require("ldap.inc.php");
require("fonc_outils.inc.php");

foreach ($_POST as $cle=>$val) {
    $$cle = $val;
}
$id=$_GET['id'];


$login=isauth();
$now =date("Y-m-d");
$table="devoirs";

//aide
$_SESSION["pageaide"]="L%27interface_prof#Ressources_et_partages";

// connexion a la base 
// @connexion ($dbhost,$dbuser,$dbpass,$dbname);

// requete pour avoir le detail de ce devoir
$req = "SELECT * FROM $table WHERE id = '$id'";
$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $req);
$ligne= mysqli_fetch_array($resultat);
list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_distrib, $liste_retard,$etat) = $ligne; 

$liste_distrib=preg_replace("/\|$/","",$liste_distrib);
// echo $liste_distrib;
$tab_distrib = liste_tab($liste_distrib);       // tableau associatif : nom_classe --> uid1#uid2#..


if (empty($liste_retard))  {
echo "<h1>".gettext("Etat du devoir")." <em>$id_devoir</em> ".gettext("distribu&#233; le ").affiche_date($date_distrib)."</h1><hr>";
if ($etat=='D') 
  echo "<table width='100%' border=2><tr><th>".gettext("Ce devoir doit &#234;tre remis (au plus tard) le ").affiche_date($date_retour)." par</th></tr>";
else 
  echo "<table width='100%' border=2><tr><th>".gettext("Ce devoir a &#233;t&#233; remis par tous les &#233;l&#232;ves")."</th></tr>";

echo "<tr><td>";
  // boucle sur toutes les classes/groupes
foreach ($tab_distrib as $classe => $liste_distrib_classe) {
  $liste_distrib_classe=preg_replace("/#$/","",$liste_distrib_classe);
  $tab_eleves_classe=preg_split("/#/",$liste_distrib_classe);
  $nb_eleves_classe=count($tab_eleves_classe);

  if (preg_match("/^Classe_/", $classe)) 
    echo gettext("Classe")." <b>".$classe."</b><br>";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>";  
  // boucle sur tous les eleves de la classe/groupe
  for ($p=0; $p < $nb_eleves_classe; $p++) {
   $uid=$tab_eleves_classe[$p];
   $param= params_eleve($uid);
   echo ($param['sexe']=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
    "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
   echo $param["nom"]."<br>";  
 }
 echo "<br />";
} 
 echo "</td></tr></table>";
}  // fin if

else {
$tab_eleves_retard = array();
$tab_eleves_retour = array();

$liste_retard = preg_replace("/\|$/","",$liste_retard);
$tab_retard = liste_tab($liste_retard);       // tableau associatif : nom_classe --> uid1#uid2#..

$nb_eleves_retard=0;
$nb_eleves_retour=0;

foreach ($tab_distrib as $classe => $liste_distrib_classe) {
  $liste_distrib_classe=preg_replace("/#$/","",$liste_distrib_classe);
  $tab_eleves_classe=preg_split("/#/",$liste_distrib_classe);
  $nb_eleves_classe=count($tab_eleves_classe);
  // echo "$classe -->$nb_eleves_classe<br>";
  
  for ($p=0; $p < $nb_eleves_classe; $p++) {
   // $tab_eleves_classe[$p] se trouve t-il dans $tab_retard[$classe]  ? 
    if (preg_match("/$tab_eleves_classe[$p]#/", $tab_retard[$classe])) {  // -->  OUI
    $tab_eleves_retard[$classe][]=$tab_eleves_classe[$p];
    $nb_eleves_retard++;   
    }
    else {        //  --> NON
    $tab_eleves_retour[$classe][]=$tab_eleves_classe[$p];
    $nb_eleves_retour++;
   }
 }
}

echo "<h1>".gettext("Etat du devoir")." <em>$id_devoir</em></h1> ";
if ($now <= $date_retour) {
  echo "<h2 align='center'>".gettext("pr&#233;vu pour le ").affiche_date($date_retour)."</h2>";
  echo "<table width='100%' border=2><th>".gettext("D&#233;j&#224; remis par")."</th><th>".gettext("A remettre par")."</th></tr>";
  }
else { 
  echo "<h2 align='center'><font color='red'>".gettext("En retard")."</font> :".gettext(" date de remise pr&#233;vue le")." <font color='red'>".affiche_date($date_retour)."</font></h2>";
  echo "<table width='100%' border=2><th>".gettext("El&#232;ves &#224; jour ")."</th><th><font color='red'>".gettext("El&#232;ves en retard")."</font></th></tr>";
 }
echo "<tr><td>";
 if ($nb_eleves_retour==0)
   echo "&nbsp;</td>";
 else {
 // boucle sur les eleves a jour
 foreach ($tab_eleves_retour as $classe => $tab_eleves) {
  if (preg_match("/^Classe_/", $classe))
    echo gettext("Classe")." <b>".$classe."</b><br>";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>";  
  for ($p=0; $p < count($tab_eleves); $p++) {
   $uid=$tab_eleves[$p];
   $param= params_eleve($uid);
   echo ($param['sexe']=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
    "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
   echo $param["nom"]."<br>";  
  }
  echo "<br/>";
 }
 echo "</td>";
 }
 echo "<td>";
 
 if ($nb_eleves_retard==0)
   echo "&nbsp;</td>";  
 else {
 // boucle sur les eleves en retard
 foreach ($tab_eleves_retard as $classe => $tab_eleves) {
  if (preg_match("/^Classe_/", $classe))
    echo gettext("Classe")." <b>".$classe."</b><br>";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>";  

  for ($p=0; $p < count($tab_eleves); $p++) {
   $uid=$tab_eleves[$p];
   $param= params_eleve($uid);
   echo ($param['sexe']=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
    "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
   echo $param["nom"]."<br>";  
  }
  echo "<br />";
 }
 echo "</td></tr></table>";
 }
}
?>
<p></p>
<div align="center"><input type="button" value="<?php echo gettext("Fermer"); ?>" onclick="window.close();"></div>

<?php
include("pdp.inc.php");
