<?php 


	/** 

	* Retour correction des devoirs ou documents 
	
	* @Version $Id$
	* @Projet LCS-SE3
   
	* @Auteurs Jean Gourdin
   
	* @Licence Distribue selon les termes de la licence GPL
    
    	*/


	/**

	* file: corriger.php
	* @Repertoire: echanges/
	*/
?>

<script language=javascript>
// definition alerte GLOBALE ??
// ****************************

/**
* Test si un choix sur le type de correction a ete fait
* @language Javascript
* @Parametres 
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif (f) {
if ((f.global.checked) || (f.perso.checked))
 return true;
else 
 alert("Choisir au moins l'un des types de correction");  
 return false;
}

/**
* Passe du mode visible au mode invisible
* @language Javascript
* @Parametres
* @Return 
*/

function change(ele) {
var el=document.getElementById(ele).style;
if (el.visibility=="hidden")
 { el.visibility="visible"; }
else
if (el.visibility=="visible")
 { el.visibility="hidden"; }
}

/**
* Cache un element
* @language Javascript
* @Parametres
* @Return 
*/
function cache(ele) {
document.getElementById(ele).style.visibility="hidden";
}

/**
* Montre un  element
* @language Javascript
* @Parametres
* @Return
*/

function montre(ele) {
document.getElementById(ele).style.visibility="visible";
}
</script> 

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


echo "<body >
<h1>".gettext("Envois de corrig&#233;s de devoirs")." </h1>
<hr>";

// recherche des devoirs du prof, A CORRIGER
$req =" SELECT * FROM $table WHERE id_prof='$login' AND etat ='F' order by date_distrib,date_recup ";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $req);
$nb_devoirs=mysqli_num_rows($resultat);
// $nb_devoirs=2;
if ($nb_devoirs ==0) {
 echo "$login ".gettext("n'a pas de corrig&#233;s de devoirs &#224; envoyer en ce moment");
 }
 else 
// sinon affichage de la table (compl&#232;te) des devoirs
echo "<table width='100%' border=2>
  <tr><th rowspan=2 width='15%'>".gettext("Devoir")."</th>
  <th rowspan=2>".gettext("date retour")."</th>
  <th colspan=2>".gettext("Choisir au moins un type de corrig&#233;")."</th><th rowspan=2 align='center' width='15%'>".gettext("correction")."</th></tr>
  <tr><th>".gettext("les devoirs corrig&#233;s")."</th><th>".gettext("un corrig&#233;-mod&#232;le")."</th></tr>";

for ($i=0;$i<$nb_devoirs;$i++) {
  echo "<form name='formu1' action='correction.php' method='post' enctype=\"multipart/form-data\">";
  $ligne=mysqli_fetch_array($resultat);
  list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_dev,$liste_retard, $etat) = $ligne; 
  echo "<tr><td>$id_devoir</td>";
  echo "<td>".affiche_date($date_retour)."</td>";
  
  echo "<td align='center'><input type='checkbox' name='perso'></td>
        <td><input type='checkbox' name='global' onclick='change(\"choix$i\");'>".gettext(" choisir")." 
            <input type='file' name='fich' size='25' align='right' id='choix$i' style=\"VISIBILITY: hidden;\">
            <INPUT TYPE='hidden' name='MAX_FILE_SIZE' value=50000></td>";

  echo "<td align='center' >
       <input type='button'  value='Envoi' onclick=\"if (verif(this.form)) this.form.submit();\">   
       <input type='hidden' name='id' value='$id'> 
       <input type='hidden' name='id_devoir' value='$id_devoir'></td>
       </tr></form>";
}
echo "</table>";

include("pdp.inc.php");
?>

