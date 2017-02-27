<?php

	/** 

	* Permet de gerer les devoirs deployes 
	
	* @Version $Id$ 
   	* @Projet LCS-SE3
   
   	* @Auteurs Jean Gourdin
   
   	* @Licence Distribue selon les termes de la licence GPL
    
    	*/

	/**

	* file: gestion.php
	* @Repertoire: echanges/
	*/

?>

<head><script LANGUAGE="JavaScript">
parametres="toolbar=0,location=0,directories=0,menuBar=0,scrollbars=1,resizable=0,width=700,height=600,left=150,top=50";

/**
* ouvre une nouvelle fenetre pour le lien
* @language Javascript
* @Parametres lien
* @Return Action ouvre une nouvelle fenetre
*/

function ouvrirFenetre(lien) {
   f=window.open(lien,"Nouvelle_fenetre",parametres);
   f.focus();
}
</script></head>

<?php
require("entete.inc.php");
require("ldap.inc.php");
require("fonc_outils.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');


//aide
$_SESSION["pageaide"]="L%27interface_prof#Ressources_et_partages";

$login=isauth();
$an=date("Y"); $mois=date("m"); $jour=date("d");
$table="devoirs";
$self="gestion.php";


// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
  $$cle = $val;
}

// recuperer l'enregistrement
$req =" SELECT * FROM $table WHERE id_prof='$login' AND id='$id'";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $req);
$ligne=mysqli_fetch_array($resultat);
list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_distrib,$liste_retard, $etat) = $ligne; 

// traitement de la modification
if (isset($modif)) {

 if ($etat=='D') {      
   /* ATTENTION si changement de $id_devoir :  
    - SIGNALER LE CHANGEMENT D'IDENTIFIANT aux eleves (mails ??)
    */
   
  $date_distrib_nv = $an_distrib_nv."-".$mois_distrib_nv."-".$jour_distrib_nv;       
  if ($date_distrib != $date_distrib_nv) 
       $date_distrib = $date_distrib_nv;
  
  if ($id_devoir_nv != $id_devoir)  {
 
// verifier d'abord que le nouvel identifiant n'a pas deja ete utilise
// ATTENTION : tenir compte de la casse avec BINARY
  $req_verif =" SELECT id FROM $table WHERE BINARY id_devoir='$id_devoir_nv' ";
  $res_verif=mysqli_query($GLOBALS["___mysqli_ston"], $req_verif);
  $nb= mysqli_num_rows($res_verif); 
  if ($nb != 0) {
    echo gettext("Modification du devoir")." <em>$id_devoir</em> ".gettext("en")." <em>$id_devoir_nv</em> :";
    echo "<h4><em>\"$id_devoir_nv\"</em> ".gettext("a d&#224;j&#224; &#224;t&#224; utilis&#224; !")."<br>".gettext("Veuillez choisir un autre identifant")."</h4>";
  }
  else {
  echo gettext("Modification du devoir")." <em>$id_devoir</em> ".gettext("en")." <em>$id_devoir_nv</em> ".gettext(" pour :")."<br>";
  // liste complete des eleves beneficiaires du devoir   
  $liste_distrib=preg_replace("/\|$/","",$liste_distrib);
  $tab_distrib = liste_tab($liste_distrib);       // tableau associatif : nom_classe --> uid1#uid2#..
  foreach ($tab_distrib as $classe => $liste_distrib_classe) {
    $liste_distrib_classe=preg_replace("/#$/","",$liste_distrib_classe);
    $tab_eleves_classe=preg_split("/#/",$liste_distrib_classe);
    $nb_eleves_classe=count($tab_eleves_classe);
    // boucle sur tous les eleves de la classe/groupe
   for ($p=0; $p < $nb_eleves_classe; $p++) { 
     $uid_eleve = $tab_eleves_classe[$p];
     $param= params_eleve($uid_eleve);
     $cla=classe_eleve($uid_eleve);       // $cla est la VRAIE classe de l'eleve
     $chemin="/var/se3/Classes/$cla/".inverse_login($uid_eleve);        
     
     $ch ="/usr/bin/sudo  /usr/share/se3/scripts/modif_rep_dev.sh  $chemin  $id_devoir  $id_devoir_nv" ;
     $cr= exec($ch) ;
     if ($cr) {
       $im=($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
       "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
       echo $im.$param["nom"]."<br>";       
     }
     else   
       echo "---> <FONT color='red'>".gettext("&#224;chec")."</FONT> ".gettext("pour")." $param[nom]</h4>";
   }
  }  // fin foreach
  $id_devoir = $id_devoir_nv;
 }  // fin else
}  // fin if  
} // fin cas "D"

 if ($etat=='D' or $etat=='R') {      
  $date_retour  = $an_retour_nv."-".$mois_retour_nv."-".$jour_retour_nv;
  $description  = $description_nv;
 } 
// Mise a jour dans la table 
  $req_devoir="UPDATE $table ";  
  $req_devoir .=" SET id_devoir='$id_devoir',date_distrib='$date_distrib',date_recup='$date_retour', description='$description' WHERE id='$id' ";  
  $ok = mysqli_query($GLOBALS["___mysqli_ston"], $req_devoir);
 }

echo "<body >
<h1>".gettext("Modification des param&#232;tres du devoir")." <em>$id_devoir</em></h1>";

/* PREVOIR CHANGEMENT D ETAT :
 -  archiver, supprimer (avertir de la disparition ! )
 -  A -> R (si archive) revenir a l'etat R (en recuperation) 
 -  F -> A (si etat fini, le signaler par libelle dans la page de recuperation (enlever 
*/
echo "<table width='100%' border=2>
     <form  action=$self  method='post'>";
     
   if ($etat=='D') {      
     echo "<tr><td>".gettext("Identifiant :")." <FONT color='red'>$id_devoir</FONT></td>
     <td><input type='text' name='id_devoir_nv' value='$id_devoir'></td></tr>
     <tr><td>".gettext("Date de distribution :")." <FONT color='red'>".affiche_date($date_distrib)."</FONT></td>
     <td>"; choix_date($date_distrib, 'distrib_nv'); echo "</td></tr>";
    } else  { 
     echo "<tr><td>".gettext("Identifiant")." </td>
     <td align='center'> <FONT color='red'>$id_devoir</FONT></td></tr>
     <tr><td>".gettext("Date de distribution")." </td>
     <td align='center'> <FONT color='red'>".affiche_date($date_distrib)."</FONT></td></tr>";
   } 

   if ($etat=='D' or $etat=='R') {      
     echo "<tr><td>".gettext("Date de retour :")." <FONT color='red'>".affiche_date($date_retour)."</FONT></td>
     <td>"; choix_date($date_retour, 'retour_nv'); echo "</td></tr> 
     <tr><td>".gettext("Commentaire")." </td>
     <td><textarea cols=30 rows=3 name=description_nv >$description</textarea></td></tr> ";
    } else  { 
     echo "<tr><td>".gettext("Date de retour")." </td>
     <td align='center'> <FONT color='red'>".affiche_date($date_retour)."</FONT></td></tr>    
     <tr><td>".gettext("Commentaire")." </td>
     <td> $description</td></tr>";        
   }
    
     echo "<tr><td>".gettext("Validation des modifications")."</td>      
     <td><input type='button'  name='modif' value='Modifier' onclick=\"if (confirm".gettext("('ATTENTION ! Confimez-vous ces modifications ?')")." ) this.form.submit();\">   
     <input type=\"hidden\" name='modif' value=1> 
     <input type=\"hidden\" name='id' value=$id>
     <input type=\"hidden\" name='id_devoir' value=$id_devoir>
     </td></tr></form></table>"; 

include("pdp.inc.php");
?>

