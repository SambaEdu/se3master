<?php

	/** 

	* Permet de recuperer les devoirs  
	
	* @Version $Id$ 
   	* @Projet LCS-SE3
   
   	* @Auteurs Jean Gourdin
   
   	* @Licence Distribue selon les termes de la licence GPL
    
    	*/

	/**

	* file: recuperer.php
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


// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
  $$cle = $val;
  // echo "$$cle =  $val<br>";
}



echo "<body >
<h1>".gettext("R&#233;cup&#233;ration des devoirs")." <font size=-2>(<em>".gettext("donn&#233;s par")." $login, ".gettext("le").affiche_date($now)."</em>)</font></h1>
<hr>";

if (isset($action) and $action=='f') {
$req_archiv = "UPDATE $table SET etat='F' WHERE id='$id' ";
@mysqli_query($GLOBALS["___mysqli_ston"], $req_archiv);

echo "<h4>".gettext("La r&#233;cup&#233;ration du devoir")." <em>$id_devoir</em> ".gettext("est maintenant termin&#233;e")."</h4>
 <font size='-1'>".gettext("Il est possible :")."<br>".gettext("
 -  d'envoyer un corrig&#233; (page \"Envoi de corrig&#233;s\")<br>
 -  de l'archiver ou de revenir &#224;  une nouvelle phase de r&#233;cup&#233;ration (page \"Gestion des devoirs\")")."</font><p>";
}

// le prof a t-il active son compte ? tester s'il a un home, si non le creer 
// en invoquant : /usr/share/se3/sbin/mkhome.pl
$ch ="/usr/bin/sudo  /usr/share/se3/scripts/test_home.sh  $login" ;
$cr= exec($ch) ;
if (! $cr) {
  $ch1 ="/usr/bin/sudo  /usr/share/se3/sbin/mkhome.pl  $login" ;
  exec($ch1) ;
  echo "<h4>".gettext("Cr&#233;ation du r&#233;pertoire personnel")."</h4>";
}

// recherche des devoirs du prof
$req =" SELECT * FROM $table WHERE id_prof='$login' AND etat IN ('D','R') order by date_distrib,date_recup ";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $req);
$nb_devoirs=mysqli_num_rows($resultat);

$req_arch =" SELECT * FROM $table WHERE id_prof='$login' AND (etat = 'A' OR etat= 'F') order by date_distrib,date_recup ";
$resultat_arch=mysqli_query($GLOBALS["___mysqli_ston"], $req_arch);
$nb_devoirs_arch=mysqli_num_rows($resultat_arch);

if ($nb_devoirs +$nb_devoirs_arch ==0) {
 echo "$login ".gettext("n'a pas distribu&#233; de devoirs !");
 }
 else if ($nb_devoirs ==0) {
 echo "$login ".gettext("n'a pas de devoir en attente")." ($nb_devoirs_arch ".gettext("devoir(s) termin&#233;s ou archiv&#233;(s))"); 
 }
 else { 
// affichage de la table des devoirs "actifs"
echo "<table width='100%' border=2>
  <tr><th>".gettext("identifi&#233; par")."</th><th>".gettext("distribu&#233; le")."</th><th>".gettext("&#224; rendre le")." </th><th>".gettext("sous le nom")."</th><th align='center' width='25%' colspan=2>".gettext("actions")."</th></tr>";
// <th>description</th><th>liste</th>

for ($i=0;$i<$nb_devoirs;$i++) {

  $ligne=mysqli_fetch_array($resultat);
  list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_dev,$liste_retard, $etat) = $ligne; 

  // afficher les listes des eleves a la demande dans une fenetre
  echo "<tr><td><a href='devoir.php?id=$id' onClick=\"ouvrirFenetre(this.href); return false\">$id_devoir</a></td>";
  echo "<td>".affiche_date($date_distrib)."</td>";
  if ($date_retour <= $now )
    echo "<td><font color='red'>".affiche_date($date_retour)."</font></td>";
  else
    echo "<td>".affiche_date($date_retour)."</td>";
  echo "<td>$nom_devoir</td>";

  if ($etat =="D" ) { // etat "distribue"
  // onclick=verif()  verifier !!
    echo "<td align='center' width='25%' colspan=2><form name='formu1' action='recuperation.php' method='post'>
        <input type='submit' name='envoi' value='1&#232;re r&#233;cup&#233;ration'><input type='hidden' name='id' value='$id'></form></td></tr>";
  }
  if ($etat =='R' ) { // etat "en recuperation"
    echo "<td ><form name='formu2' action='recuperation.php' method='post'>
       <input type='submit' name='envoi' value='Nouvelle r&#233;cup&#233;ration'>  
       <input type='hidden' name='id' value='$id'> </form></td >";
    
    echo "<td ><form name='formu3' action='recuperer.php' method='post' >
       <input type='button'  value='Terminer' 
       onclick=\"if (confirm".gettext("('Avez-vous fait une derni&#232;re op&#233;ration de r&#233;cup&#233;ration avant de d&#233;clarer la phase de r&#233;cup&#233;ration close ?')").") this.form.submit();\">   
       <input type='hidden' name='id' value='$id'><input type='hidden' name='id_devoir' value='$id_devoir'><input type='hidden' name='action' value='f'>
       </form></td></tr>";
  }
  // else echo "<td>&nbsp;</td></tr>";
}
echo "</table>";
// lien sur identifiant pour ouverture fenetre ou tout sera affiche sur le devoir
// mettre boite de verif javascript AVANT validation
}

include("pdp.inc.php");

?>
