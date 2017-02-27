<?php


	/** 

	* Permet de gerer les devoirs deployes 
	
	* @Version $Id$
   	* @Projet LCS-SE3
   
   	* @Auteurs Jean Gourdin
   
   	* @Licence Distribue selon les termes de la licence GPL
    
    	*/

	/**

	* file: gerer.php
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
$now =date("Y-m-d");
$table="devoirs";
$libelle_etat= array('A'=>gettext("archiv&#233;"),'R'=>gettext("r&#233;cup&#233;ration en cours"),'F'=>gettext("r&#233;cup&#233;ration termin&#233;e"),'D'=>gettext("juste distribu&#233;"));


// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
  $$cle = $val;
 // echo "$$cle =  $val<br>";
}



echo "<body >
<h1>".gettext("Gestion des devoirs")." <FONT size='-2'>(".gettext("le ").affiche_date($now).")</FONT></h1>
<hr>";

// Suppression d'un devoir avant recup
if (isset($action) and $action=='s') {
// supprimer d'adord tout les dossiers du devoir 
 $req =" SELECT * FROM $table WHERE id='$id'";
 $res=mysqli_query($GLOBALS["___mysqli_ston"], $req);
 $ligne=mysqli_fetch_array($res);
 list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib_dev,$date_retour_dev,$description_dev,$liste_distrib,$liste_retard, $etat) = $ligne; 

 $liste_distrib=preg_replace("/#$/","",$liste_distrib);
 $tab_eleves= preg_split("/#/",$liste_distrib);
 $nb_eleves=sizeof($tab_eleves);
 
// boucle sur tous les eleves pour suppression de leur dossier de devoir
echo gettext("Suppression du dossier du devoir")." <em>$id_devoir</em> ".gettext("pour les &#233;l&#232;ves :")."<p>";

 for ($p=0; $p < $nb_eleves; $p++) {
  $uid_eleve=$tab_eleves[$p];
  $param= params_eleve($uid_eleve);
  $classe=$param['classe'];
  $rep="/var/se3/Classes/$classe/$uid_eleve/$id_devoir";        
  $ch ="/usr/bin/sudo  /usr/share/se3/scripts/supp_rep_dev.sh  $rep" ;
  $cr= exec($ch) ;
  if ($cr) {
    echo $im=($param['sexe']=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
         "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
    echo $param["nom"]." - ".$param["classe"]."<br>";       
   }  
   else        
   echo " ---> &#233;chec de la suppression du dossier pour $uid_eleve<br>";
 }        
           
// enfin supprimer l'enregistrement
$req_sup="delete from $table where id='$id'";
mysqli_query($GLOBALS["___mysqli_ston"], $req_sup);
echo "<h4>".gettext("Le devoir")." <em>$id_devoir</em> ".gettext("a &#233;t&#233; d&#233;finitivement supprim&#233;")." </h4>";

}
if (isset($action) and $action=='r') {
$req = "UPDATE $table SET etat='R' WHERE id='$id' ";
@mysqli_query($GLOBALS["___mysqli_ston"], $req);
echo "<h4>".gettext("Le devoir")." <em>$id_devoir</em> ".gettext("est remis en &#233;tat de \"r&#233;cup&#233;ration\"")." </h4>";
}
if (isset($action) and $action=='a') {
$req_archiv = "UPDATE $table SET etat='A' WHERE id='$id' ";
@mysqli_query($GLOBALS["___mysqli_ston"], $req_archiv);
echo "<strong>".gettext("Le devoir")." <em>$id_devoir</em> ".gettext("a bien &#233;t&#233; archiv&#233;")."<br>
 <font size='-1'>".gettext("Il est possible toutefois en cas de n&#233;cessit&#233; de reprendre une phase de \"r&#233;cup&#233;ration\"")."</font></strong>";
}

// recherche de tous les devoirs du prof
$req =" SELECT * FROM $table WHERE id_prof='$login' AND etat <> 'A' order by etat, date_distrib ";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $req);
$nb_devoirs=mysqli_num_rows($resultat);

$req_arch =" SELECT * FROM $table WHERE id_prof='$login' AND etat = 'A' order by date_distrib,date_recup ";
$resultat_arch=mysqli_query($GLOBALS["___mysqli_ston"], $req_arch);
$nb_devoirs_arch=mysqli_num_rows($resultat_arch);

if ($nb_devoirs +$nb_devoirs_arch ==0) {
 die ("$login ".gettext(" n'a distribu&#233; aucun devoir"));
 }

if ($nb_devoirs > 0) {
echo "<h4><FONT color='#ff0e7e'>".gettext("Devoirs en cours de traitement")."</FONT></h4>";
echo "<table width='100%' border=2>
  <tr><th>".gettext("identifi&#233; par")."</th><th>".gettext("distribu&#233; le")."</th><th>".gettext("&#224; rendre le")."</th><th>".gettext("sous le nom")."</th><th>".gettext("Etat actuel")."</th><th align='center' width='25%' colspan=2>".gettext("actions")."</th></tr>";
  
for ($i=0;$i<$nb_devoirs;$i++) {
  $ligne=mysqli_fetch_array($resultat);
  list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib_dev,$date_retour_dev,$description_dev,$liste_dev,$liste_retard, $etat) = $ligne; 

  // afficher les listes des eleves a la demande dans une fenetre
  echo "<tr><td><a href='devoir.php?id=$id' onClick=\"ouvrirFenetre(this.href); return false\">$id_devoir</a></td>";
  echo "<td>".affiche_date($date_distrib_dev)."</td>";
  if ($date_retour_dev <= $now )
    echo "<td><font color='red'>".affiche_date($date_retour_dev)."</font></td>";
  else
    echo "<td>".affiche_date($date_retour_dev)."</td>";
  echo "<td>$nom_devoir</td>"; 
  echo "<td>$libelle_etat[$etat]</td>";

   
 if ($etat =='D' ) { 
 /* etat D (tout juste distribue) on peut avant 1ere recup : 
  - changer tous les parametres du devoir, sauf changer les documents
  - supprimer totalement (y compris les fichiers distribues)
  */
  /////// Suspension de la possibilite de supprimer definitivement le devoir //////////  
  /*  echo "<td><form action='gerer.php' method='post'>
       <input type='button'  value='Supprimer' onclick=\"if (confirm('ATTENTION ! Confimez-vous cette d&#233;cision irr&#233;versible de suppression ?') ) this.form.submit();\">   
       <input type='hidden' name='id' value='$id'><input type='hidden' name='action' value='s'>      
       </form></td >";
  */
    echo "<td align='center' colspan=2><form action='gestion.php' method='post'>
       <input type='submit'  value='".gettext("Modifier")."'>   
       <input type='hidden' name='id' value='$id'>       
       </form></td></tr>";
   }

  if ($etat =='R' ) {
 /* etat R (en cours de recup)  :
  - id_devoir est fige, 
  - pour le supprimer il faudra declarer la phase de recup terminee 
  - changer les autres parametres du devoir, sauf changer les documents
  */
    echo "<td align='center' width='25%' colspan=2>
     <form  action='gestion.php'  method='post'>
     <input type='submit'  value='".gettext("Modifier")."'>   
     <input type='hidden' name='id' value='$id'>        
     </form></td></tr>";     
  }
   if ($etat =='F' ) {
 /* etat F (phase de recuperation terminee), cf page recuperer
  - pas de changement de parametres du devoir 
  - suppression ?
  - archivage possible (signaler ou liaison avec envoi de corrige  )
  */
   echo "<td><form action='gerer.php' method='post'>
     <input type='button'  value=\"".gettext("Reprise du devoir")."\" onclick=\"if (confirm".gettext("('Permettre une nouvelle r&#233;cup&#233;ration du devoir ?')")." ) this.form.submit();\">   
     <input type='hidden' name='id' value='$id'><input type='hidden' name='id_devoir' value='$id_devoir'><input type='hidden' name='action' value='r'>      
     </form></td>";

 /*  echo "<td><form action='corriger.php' method='post'>
       <input type='submit'  value='Correction'>   
       <input type='hidden' name='id' value='$id'> </form></td> ";      
 */
    echo "<td ><form  action='gerer.php' method='post' >
       <input type='button'  value='".gettext("Archiver")."' onclick=\"if (confirm".gettext("('Confimez-vous cette d&#233;cision d\'archivage ?')")." ) this.form.submit();\">   
       <input type='hidden' name='id' value='$id'><input type='hidden' name='id_devoir' value='$id_devoir'><input type='hidden' name='action' value='a'>
       </form></td></tr>";    
  } 
}
echo "</table>"; 
}

// sinon affichage de la table (complete) des devoirs
if ($nb_devoirs_arch > 0) {
echo "<h4><FONT color='#ff0e7e'>".gettext("Devoirs archiv&#233;s")."</FONT></h4>";
echo "<table width='100%' border=2>
  <tr><th>".gettext("nom devoir")."</th><th>".gettext("date distribution")."</th><th>".gettext("date retour")."</th><th>".gettext("Etat actuel")."</th><th align='center' width='25%' colspan=2>".gettext("actions")."</th></tr>";
// <th>description</th><th>liste</th>

for ($i=0;$i<$nb_devoirs_arch;$i++) {
  $ligne_arch=mysqli_fetch_array($resultat_arch);
  list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib_dev,$date_retour_dev,$description_dev,$liste_dev,$liste_retard, $etat) = $ligne_arch; 
  // afficher les listes des eleves a la demande dans une fenetre
  echo "<tr><td><a href='devoir.php?id=$id' onClick=\"ouvrirFenetre(this.href); return false\">$id_devoir</a></td>";
  echo "<td>".affiche_date($date_distrib_dev)."</td>";
  echo "<td>".affiche_date($date_retour_dev)."</td>";
  echo "<td>$libelle_etat[$etat]</td>";
  if ($etat =='A' ) {  
 // suppression possible du repertoire du devoir  /home/prof/$login  
 
  echo "<td align='center' ><form action='gerer.php' method='post'>
     <input type='button'  value=\"".gettext("Reprise du devoir")."\" onclick=\"if (confirm".gettext("('Permettre une nouvelle r&#233;cup&#233;ration du devoir ?')")." ) this.form.submit();\">   
     <input type='hidden' name='id' value='$id'><input type='hidden' name='id_devoir' value='$id_devoir'><input type='hidden' name='action' value='r'>      
     </form></td>";
    
 /* echo "<td><form action='gerer.php' method='post'>";
  // <input type=checkbox name=sup_rep ><font size='-1'>avec le dossier ?</font>
  echo "<input type='button'  value='Supprimer' onclick=\"if (confirm('ATTENTION ! cette op&#233;ration va supprimer l'enregistrement du devoir. Confimez-vous cette d&#233;cision irr&#233;versible ?') ) this.form.submit();\">   
     <input type='hidden' name='id' value='$id'><input type='hidden' name='id_devoir' value='$id_devoir'><input type='hidden' name='action' value='s'>      
     </form></td></tr>"; 
  */    
  }
}   
echo "</table>";
}

// lien sur identifiant pour ouverture fenetre ou tout sera affiche sur le devoir
// mettre boite de verif javascript AVANT validation

include("pdp.inc.php");
?>

