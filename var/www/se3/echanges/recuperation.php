<?php


	/** 

	* Permet de recuperer les devoirs  
	* @Projet LCS-SE3
	
	* @Version $Id$ 
	* @Auteurs Jean Gourdin
   
	* @Licence Distribue selon les termes de la licence GPL
    
	*/

	/**

	* file: recuperer.php
	* @Repertoire: echanges/
	*/

require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');

?>

<html>
<head>
    <title><?php echo gettext("R&#233;cup&#233;ration de devoirs"); ?></title>
    <link href="style/style.css" type="text/css" rel="StyleSheet">
</head>

<?php



require("entete.inc.php");
require("ldap.inc.php");
require("fonc_outils.inc.php");

//aide
$_SESSION["pageaide"]="L%27interface_prof#Ressources_et_partages";


// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
  $$cle = $val;
}


$login=isauth();
$now =date("Y-m-d");
$table="devoirs";

// requete pour avoir le detail de ce devoir $id
$req = "SELECT * FROM $table WHERE id = '$id'";
$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $req);
$ligne= mysqli_fetch_array($resultat);
list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_distrib, $liste_retard) = $ligne; 

// liste complete des eleves beneficiaires du devoir 
$liste_distrib=preg_replace("/\|$/","",$liste_distrib);
$tab_distrib = liste_tab($liste_distrib);       // tableau associatif : nom_classe --> uid1#uid2#..

$tab_eleves_retard = array();
$tab_eleves_remis = array();
$tab_retard = array();        // tableau associatif : nom_classe --> uid1#uid2#..
$nb_eleves_remis=0;
$nb_eleves_retard=0;

echo "<body >
<h1>".gettext("R&#233;cup&#233;ration du devoir")." <em>$id_devoir</em> <font size=-2>(<em>".gettext("par")." $login, ".gettext("le ").affiche_date($now)."</em>)</font></h1>
<hr>";

/* Si le rep de reception n'existe pas dans le home prof, le creer au nom $id_devoir
 ***********************************************************************************/
$rep_devoir= "$id_devoir";
$ch ="/usr/bin/sudo  /usr/share/se3/scripts/creer_rep_dev.sh  $login $rep_devoir" ;
$cr= exec($ch) ;
if ($cr) 
  echo "<strong><font size='-1'>Cr&#233;ation du dossier K:/Devoirs/$rep_devoir</font></strong>";

/* 1er cas : $liste_retard est vide : 
 * premiere tentative de recup, boucle sur TOUS les $nb_eleves
 *************************************************************/
if ($liste_retard == "") {
 // boucle sur toutes les classes/groupes
 foreach ($tab_distrib as $classe => $liste_distrib_classe) {
  $liste_distrib_classe=preg_replace("/#$/","",$liste_distrib_classe);
  $tab_eleves_classe=preg_split("/#/",$liste_distrib_classe);
  $nb_eleves_classe=count($tab_eleves_classe);
  $liste_classe_retard ="";          // liste eleves en retard par classe
  
  // boucle sur tous les eleves de la classe/groupe
  for ($p=0; $p < $nb_eleves_classe; $p++) {
   $uid_eleve = $tab_eleves_classe[$p];
   $cla=classe_eleve($uid_eleve);                    // $cla est la VRAIE classe de l'eleve (# $classe si $classe designe un groupe !)
   // $param= params_eleve($uid_eleve);
          
   $ch ="/usr/bin/sudo  /usr/share/se3/scripts/copie_dev.sh $login $id_devoir $nom_devoir ".inverse_login($uid_eleve)." $cla ";
   $cr=exec($ch);                         //  echo "$ch --> $cr<br>";
   if ($cr) {
     $tab_eleves_remis[$classe][]="$uid_eleve";
     $nb_eleves_remis++;
   } else {
     $liste_classe_retard .= "$uid_eleve#";  
     $tab_eleves_retard[$classe][] = "$uid_eleve";
     $nb_eleves_retard++;
   }

  }  // fin boucle eleves / classe
  //echo "liste retard = $liste_classe_retard<br>";
  $tab_retard[$classe]=$liste_classe_retard;
 }
}
 /*  fin 1ere fois, debut des recup d'eleves en retard
  ****************************************************/
else {
  // traiter la liste des eleves en retard 
   $liste_retard = preg_replace("/\|$/","",$liste_retard);
   $tab_retard = liste_tab($liste_retard);       // tableau associatif : nom_classe --> uid1#uid2#..

  foreach ($tab_retard as $classe => $liste_classe) {
   $liste_classe=preg_replace("/#$/","",$liste_classe);
   $tab_eleves_classe=preg_split("/#/",$liste_classe);
   $nb_eleves_classe=count($tab_eleves_classe);
   $liste_classe_retard ="";          // liste eleves en retard par classe
   
   if ($nb_eleves_classe == 0) continue;             // continue ! il n'y a rien a recuperer dans ce groupe !
   
   // boucle sur la liste des eleves en retard de cette classe/groupe
  for ($p=0; $p < $nb_eleves_classe; $p++) {
   $uid_eleve = $tab_eleves_classe[$p];
   $cla=classe_eleve($uid_eleve);                    // $cla est la VRAIE classe de l'eleve (# $classe si $classe designe un groupe !)
  // $param= params_eleve($uid_eleve);
   
   $ch ="/usr/bin/sudo  /usr/share/se3/scripts/copie_dev.sh $login $id_devoir $nom_devoir ".inverse_login($uid_eleve)." $cla ";
   $cr=exec($ch);                  //   echo "$ch --> $cr<br>";
   if ($cr) {
     $tab_eleves_remis[$classe][]="$uid_eleve";
     $nb_eleves_remis++;
   } else {
     $liste_classe_retard .= "$uid_eleve#";  
     $tab_eleves_retard[$classe][] = "$uid_eleve";
     $nb_eleves_retard++;
   }

  }  // fin boucle eleves / classe
  $tab_retard[$classe]=$liste_classe_retard;
  // echo "$classe --> $liste_classe_retard<br>";
 }
}

/* Compte-rendu global
 *********************/
if ($nb_eleves_remis==0) 
  echo "<h4>".gettext("Aucun &#233;l&#232;ve nouveau n'a remis le devoir")." <em>$id_devoir</em></h4>";
else {
  echo "<h4>".gettext("Le devoir")." <em>$id_devoir</em> ".gettext("vient d'&#234;tre remis par").($nb_eleves_remis==1?gettext("l'&#233;l&#232;ve :"):gettext("les")." $nb_eleves_remis ".gettext(" &#233;l&#232;ves :"))."</h4>";
  
  foreach ($tab_eleves_remis as $classe => $tab_eleves) {
  if (preg_match("/^Classe_/", $classe))
    echo gettext("Classe")." <b>".$classe."</b><br>";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>";  

   for ($p=0; $p < count($tab_eleves); $p++) {
   $param= params_eleve($tab_eleves[$p]);
   echo ($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
      "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
   echo $param["nom"]."<br>";          
  }  
 }
}

if ($nb_eleves_retard==0) {
  $etat="F";
  echo "<h4>".gettext("Tous les &#233;l&#232;ves ont maintenant remis leur devoir")."</h4>".gettext("
   Il est possible :<br>
 -  d'envoyer un corrig&#233; (\"Envoi de corrig&#233;s\")<br>
 -  de l'archiver ou de revenir &#224; une phase de r&#233;cup&#233;ration (\"Gestion des devoirs\")<br>");  
} 
else {
  $etat="R"; 
  if ($now <= $date_retour) {
    echo "<h4>".gettext("Il doit &#234;tre remis, au plus tard le ").affiche_date($date_retour).",".gettext(" par")." </h4>";
  } else {
    echo "<h4>".($nb_eleves_retard==1?gettext("El&#232;ve")." <font color='red'>".gettext("en retard")."</font> :":gettext("Liste des")." $nb_eleves_retard ".gettext("&#233;l&#232;ves")." <font color='red'>".gettext("en retard")."</font> :")."</h4>";
  }
  
 foreach ($tab_eleves_retard as $classe => $tab_eleves) {
  if (preg_match("/^Classe_/", $classe))
    echo gettext("Classe")."<b>".$classe."</b><br>";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>";  
  for ($p=0; $p < count($tab_eleves); $p++) {
   $param= params_eleve($tab_eleves[$p]);
   echo ($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
      "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
   echo $param["nom"]."<br>";          
  }  
 }
}
// conclusion
$liste_retard=tab_liste($tab_retard);          // transformation du tableau (classe, eleves en retard) en liste a enregistrer  
// Dans tous les cas, mettre a jour le champ liste_retard et les indicateurs de recup
$req_maj = "UPDATE $table SET liste_retard='$liste_retard', etat='$etat' WHERE id='$id' ";
@mysqli_query($GLOBALS["___mysqli_ston"], $req_maj);

include("pdp.inc.php");
?>

