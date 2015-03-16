<?php

	/** 

	* Deploie des devoirs ou documents aux utilisateur
	
	* @Version $Id$
   	* @Projet LCS-SE3
   
   	* @Auteurs Jean Gourdin
   
   	* @Licence Distribue selon les termes de la licence GPL
    
    	*/


	/**

	* file: distribuer.php
	* @Repertoire: echanges
	*/

require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');

?>

<html>
  <head>
    <title><?php echo gettext("Distribution d'un texte aux &#233;l&#232;ves"); ?></title>
    <link href="style/style.css" type="text/css" rel="StyleSheet">

<script language=javascript>

/**
* Test si un choix sur le nombre et le type de devoir a ete fait
* @language Javascript
* @Parametres
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif (f) {
 n = false; typ=false; dev=false; alerte="";
for(i=1;i<f.nombre.length;i++)
if (f.nombre[i].selected)
    n=true;
if (! n) alerte += "<?php echo gettext("Choisir un nombre de fichiers &agrave; distribuer"); ?>\n";  
for(i=0;i<f.type.length;i++)
if (f.type[i].checked)
   typ=true;
if (! typ) alerte += "<?php echo gettext("Choisir un type de distribution"); ?>\n";    
for(i=0;i<f.devoir.length;i++)  
if (f.devoir[i].checked)
   dev=true;
if (! dev) alerte += "<?php echo gettext("Pr&#233;ciser s'il s'agit d'un devoir"); ?>\n";    

if ( n && typ && dev)
  return true;
else {
  alert(alerte);return false;
  }
}


/**
* Test si un choix d'une classe a ete fait
* @language Javascript
* @Parametres
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif2(f) {
// verif pour type = 2
var liste_classe="";
var indic =false;
for (i=0 ;i< f.classes.length; i++)
if( f.classes.options[i].selected) {
   indic=true;
   liste_classe += f.classes.options[i].value+"#";
 }
if (liste_classe=='') {
  alert("<?php echo gettext("Choisir au moins une classe"); ?>");return false;
  }
else
  f.liste_classe.value= liste_classe;  
return indic;
}

/**
* Test si un choix d'une classe  a ete fait
* @language Javascript
* @Parametres
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif1(f) {
// verif pour type = 1 ET NON devoir
 var liste_classe="";
 c =false; d=false ; alerte="";
for (i=0 ;i< f.classes.length; i++)
if( f.classes.options[i].selected) {
   c=true;
   liste_classe += f.classes.options[i].value+"#";
  }
if (liste_classe=="") 
  alerte += "<?php echo gettext("Choisir au moins une classe"); ?>\n"; 
else
  f.liste_classe.value= liste_classe;  
if ( c  )
  return true;
else {
  alert(alerte);return false;
  }
}

/**
* Test si identifiant, nom et date de retour sont corrects
* @language Javascript
* @Parametres
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif1d(f) {
// verif pour type = 1 ET devoir
 var liste_classe="";
 var alerte = "";
 c =false; d=false ; r=false; 
for (i=0 ;i< f.classes.length; i++)
if( f.classes.options[i].selected) {
   c=true;
   liste_classe += f.classes.options[i].value+"#";
  }
if (liste_classe=="") 
  alerte += "<?php echo gettext("Choisir au moins une classe"); ?>\n"; 
else
  f.liste_classe.value= liste_classe;  

if( f.id_devoir.value !='')
  d=true;
if (! d) alerte += "<?php echo gettext("Choisir un identifiant pour le devoir"); ?>\n";  

if( f.an_retour.value !='' || f.mois_retour.value !='' || f.jour_retour.value !='' )
  r=true;
if (! r) alerte += "<?php echo gettext("Choisir une date de retour du devoir"); ?>\n";  

if ( c && d && r )
  return true;
else {
  alert(alerte);return false;
  }
}

/**
* Test si le nom et la date du devoir sont corrects
* @language Javascript
* @Parametres
* @Return true si le choix est fait
* @Return false si pas de choix
*/

function verif_devoir(f) {
result=true;alerte='';
reg=/[\s\W]/;
if( f.id_devoir.value =='') { 
 result= false;
 alerte += "<?php echo gettext("Choisir un identifiant pour le devoir"); ?>\n";  
 }
if (reg.test(f.id_devoir.value) ) {
  result= false;
   alerte += "<?php echo gettext("Identifiant de devoir sans espace, ni caract&egrave;res sp&#233;ciaux"); ?>\n";  
 }
if( f.an_retour.value =='' || f.mois_retour.value =='' || f.jour_retour.value =='' ) {
  result= false;
  alerte += "<?php echo gettext("Choisir une date correcte de retour du devoir"); ?>\n";  
 }
 if (result==false) 
   alert(alerte);
 return result;
}
</script>
</head>

<?php

require("entete.inc.php");
require("ldap.inc.php");
require("fonc_outils.inc.php");

// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
  $$cle = $val;
}



//aide
$_SESSION["pageaide"]="L%27interface_prof#Ressources_et_partages";

$nbMax=5;
$self="distribuer.php";
$now =date("Y-m-d");
$tab_mois =array(9=>gettext("Septembre"),10=>gettext("Octobre"),11=>gettext("Novembre"),12=>gettext("D&#233;cembre"),1=>gettext("Janvier"),2=>gettext("F&#233;vrier"),3=>gettext("Mars"),4=>gettext("Avril"),5=>gettext("Mai"),6=>gettext("Juin"),7=>gettext("Juillet"),8=>gettext("Ao&#251;t"));
$lib_mois =array(9=>"Sept",10=>"Oct",11=>"Nov",12=>"D&#233;c",1=>"Jan",2=>"F&#233;v",3=>"Mars",4=>"Avr",5=>"Mai",6=>"Juin",7=>"Juil",8=>"Ao&#251;t");
$nbjours=array("09"=>30,"10"=>31,"11"=>30,"12"=>31,"01"=>31,"02"=>28,"03"=>31,"04"=>30,"05"=>31,"06"=>30,"07"=>31,"08"=>31) ;

// trouver les classes (et groupes) du prof
$login=isauth();
$classes=classes_prof($login);
$nb_classes= count($classes);


// affichage ECRAN 1 
echo "<body >
    <h1>".gettext("Distribution de document(s)")." <font size=-2>(<em>".gettext("par")." $login, ".gettext("le ").affiche_date($now)."</em>)</font></h1>\n";
 if ($nb_classes==0) {
   echo gettext("Attention distribution impossible :")." $login ".gettext("n'a pas de classes !"); exit;
   }
 if (! $nombre ) {
	$form1="<h2>".gettext("Pr&#233;paration")."</h2>\n\n";
	$form1.="<form method=\"post\" name='formu' action=\"$self\">\n";
	$form1.="<table width='80%'>\n";
	$form1.="<tr><th align=\"left\"><h3>".gettext("Choisir le nombre de fichiers")."</h3></th>\n";
	$form1.="<td><select name='nombre' >";
	// onchange='if(verif(this.form)) formu.submit()'
	$form1.="<option value='0' checked>".gettext("Choisir")."</option>";
	for ($i=1; $i<= $nbMax; $i++)
 		$form1.="<option value=$i>$i</option>";
	$form1.="</select></td></tr>\n";

	$form1.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>\n";

    $form1.="<tr><th align=\"left\" ><h3>".gettext("Choisir &#224; qui s'adressent les documents ")."<br>
	<ul><li>".gettext("classes enti&#232;res (ou sous-groupes)")."<br> 
	<li>".gettext("choix personnalis&#233; des &#233;l&#232;ves")."</ul></h3></th>\n";
	$form1.="<td><input type='radio' name='type' value=1 >".gettext("classe(s) enti&#232;re(s)")." <br>";
        // onclick='if(verif(this.form)) formu.submit()'>
	$form1.="<input type='radio' name='type' value=2 >".gettext("choix des &#233;l&#232;ves")."</td></tr>\n";
	$form1.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>\n";

    $form1.="<tr><th align=\"left\"><h3>".gettext("Distribution de documents :")."<br>
       <ul><li>".gettext("&#233;nonc&#233;s de devoir")." <br> 
       <li>".gettext("distribution sans retour ")."</ul></h3></th>\n";
	$form1.="<td><input type='radio' name='devoir' value=1 >".gettext("devoir")." <br>";
	$form1.="<input type='radio' name='devoir' value=0 >".gettext("simple distribution")."</td>\n";

    	$form1.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>\n";	
	$form1.="<tr><th align=\"left\"><h3>".gettext("Valider ces choix")." </h3></th>\n";
	$form1.="<td><input type='button' name='valider' value='".gettext("Valider")."' onclick='if(verif(this.form)) formu.submit()'> </td>\n";

	$form1.="</tr></table></form>\n\n";
	echo $form1;
	}

//////////////////////////   Debut traitement type = 1  --> Distrib classes entieres  //////////////////////////////

if ( $type==1 ) {

echo "<table width='100%'><tr><td>\n";  // debut tableau general

echo "<form method=\"post\" name='formu3' action=\"distribution.php\" enctype=\"multipart/form-data\">";
$form2 ="<h3>".gettext("Choisir les classes")."</h3>";
$form2.="<table width='80%'><tr>\n";

// $form2.="<td><select name='classes[]' multiple>";  <-- enlever les []
$form2.="<td><select name='classes' multiple>";
for ($c=0; $c< $nb_classes; $c++) {
   $form2.="<option value=".$classes[$c].">".$classes[$c]."</option>";
}
$form2.="</select></td>\n";
$form2.="</tr></table><p>\n";
echo $form2;

echo "<h3>".gettext(" S&#233;lectionner ").($nombre==1?gettext("le fichier"):gettext("les")." $nombre ".gettext("fichiers"))."</h3>";
echo "<table width='100%'>";
for ($i=1; $i<= $nombre; $i++) {
    $f="fich$i";
    echo "<tr><td align='left'><font size='-2'>".gettext("Fichier")." $i</font></td>\n";
    echo "<td><input type=\"file\" name=\"$f\" size='20'></td></tr><p>\n";
    echo "<INPUT TYPE='hidden' name='MAX_FILE_SIZE' value=20000000>";
}
echo "</table><p></p>\n\n";

// rangee de validation
 
echo "<table align='center' width=100%><tr><td align='center'>\n";

if ($devoir) {
  echo "<h3>".gettext("Validation des choix et envoi")."</h3>\n";
  echo "<input type=\"hidden\" name='devoir' value=$devoir>";
  }
 else 
  echo "<h3 align='center'>".gettext("Distribuer ").($nombre==1?gettext("le fichier"):gettext("les")." $nombre ".gettext("fichiers")).gettext(" aux &#233;l&#232;ves choisis")."</h3>\n";

if ($devoir)
 //  echo "<input type=\"button\" value=\"Envoyer\" onClick='if (verif_devoir(this.form)) formu3.submit()'>";
  echo "<input type=\"button\" value=\"".gettext("Distribuer le devoir")."\" onClick='if (verif1d(this.form)) formu3.submit()'>\n";
else
  echo "<input type=\"button\" value=\"".gettext("Envoyer")."\" onClick='if (verif1(this.form)) formu3.submit()'>\n";
  // <input type=\"submit\" value=\"Envoyer\"></td></tr>

echo "
  <input type=\"hidden\" name='nombre' value=$nombre>
  <input type=\"hidden\" name='liste_classe'>
  <input type=\"hidden\" name='type' value=$type>
  </table>\n\n";

// fin cellule de gauche
echo "</td>\n";


if ($devoir) {
  echo "<td>";
  echo "<h3>".gettext("Choisir les caract&#233;ristiques du devoir")."</h3>
  <table >
  <tr><td><font size='-1'>".gettext("Nom du devoir")."<br>".gettext("(identifiant unique)")."</font></td>  <td> <input type='text' name='id_devoir' value =''></td></tr>
  <tr><td><font size='-1'>".gettext("Fichier &#224; rendre")."<br>".gettext("(par d&#233;faut \"devoir\")")."</font></
  td>  <td> <input type='text' name='nom_devoir' value ='devoir'></td></tr>
  <tr><td><font size='-1'>".gettext("Date de retour")."</font></td> <td>";

  choix_date($now,"retour");   
   
  echo "</td></tr>
    <tr><td><font size='-1'>".gettext("Commentaire")."</font></td><td><textarea cols=30 rows=3 name=description ></textarea></td></tr>
    </table></td>";
 }  
  echo "</form></tr></td></table>";
}
///////////////////////////   fin type = 1 , debut type = 2           //////////////////////////////
//////////////////////////    Debut type = 2  -->  Choix des eleves   //////////////////////////////

if ( $type==2 ) {

 // d'abord choix des classes concernees //
  if (! $choix and ! isset($choix) ) {
	$form3 ="<form method=\"post\" name='formu2' action=\"distribuer.php\">";
	$form3.="<h3>".gettext(" Choisir les ").($choix?gettext("&#233;l&#232;ves"):gettext("classes"))."</h3>";
	$form3.="<table width='100%'><tr>";

   if ($nb_classes==0 )
    $form3.= " $login ".gettext("n'a pas de classes !");
   else {
	$form3.="<td width='40%'><select name='classes' multiple>";
	for ($c=0; $c< $nb_classes; $c++) {
		$form3.="<option value=".$classes[$c].">".$classes[$c]."</option>";
	 }
	$form3.="</select></td>";
	$form3 .="<td><input type=\"button\" value=\"".gettext("Valider")."\" onClick='if (verif2(this.form)) formu2.submit()'></td>
	 <input type=\"hidden\" name='nombre' value=$nombre>
	 <input type=\"hidden\" name='type' value=$type>
	 <input type=\"hidden\" name='devoir' value=$devoir>
	 <input type=\"hidden\" name='choix' value=1>
	 <input type=\"hidden\" name='liste_classe'></td>";
	$form3.="<td width='40%'>&nbsp;</td></tr></table></form>";
	echo $form3;
	}
  }
  // les classes ont ete choisies
   else {
   if (! empty($liste_classe)) {
	  $liste_classe=trim($liste_classe);
	  $liste_classe=preg_replace("/#$/","",$liste_classe);
	  $classes=preg_split("/#/",$liste_classe);
	 }
	$n=sizeof($classes);

	$form3 = "<form method=\"post\" name='formu3' action=\"distribution.php\" enctype=\"multipart/form-data\">";
	$form3.="<h3>".gettext(" Choisir les &#233;l&#232;ves dans les classes")." </h3>";
	$form3.="<table width='100%'>";
	for ($g=0; $g<$n; $g++) {
        	$filtres[$g]="cn=$classes[$g]";
		$eleves="eleves".$g."[]";
		$uids = search_uids ($filtres[$g]);
		$people = search_people_groups ($uids,"","group");
		$nb_people=sizeof($people);
		if ($g % 3==0) $form3.="<tr>";
		$form3.="<td align='center'>".$classes[$g]."<br>";
		$form3.="<select name=$eleves multiple size=8>";
  		for ($p=0; $p < $nb_people; $p++) {
 
            /// ici filtrer les profs s'il ne s'agit pas d'une classe
               if (! preg_match("/^Classe_/", $classes[$g])) {
            // echo $people[$p]["uid"]."-->".est_prof($people[$p]["uid"])."<br>";
                 if (est_prof($people[$p]["uid"])) continue;
               }
           /// fin modif
                $form3.="<option value=".$people[$p]["uid"].">".$people[$p]["fullname"]."</option>";
		}
		$form3.="</select></td>";
		if ($g % 3 == 2) $form3.="</tr>";
	}
	$form3.="</table><p>";
	echo $form3;

	// fin table haut , debut table a 2 colonnes
     echo "<table width='100%'><tr><td>";

	echo "<h3>".gettext(" S&#233;lectionner ").($nombre==1?gettext("le fichier"):gettext("les $nombre fichiers"))."</h3>";
	echo "<table width='80%'>";
	for ($i=1; $i<= $nombre; $i++) {
        $f="fich$i";
		echo "<tr><td align='left'<font size='-2'>Fichier $i</font></td> ";
		echo "<td><input type=\"file\" name=\"$f\" size='20'></td></tr><p>";
		echo "<INPUT TYPE='hidden' name='MAX_FILE_SIZE' value=20000000>";
	}
	echo "</table><p></p>";

if ($devoir) {
  echo "<h3>".gettext("Validation des choix et envoi")."</h3>";
  echo "<input type=\"hidden\" name='devoir' value=$devoir>";
  }
 else 
  echo "<h3 align='center'>".gettext("Distribuer").($nombre==1?gettext("le fichier"):gettext("les")." $nombre ".gettext("fichiers")).gettext(" aux &#233;l&#232;ves choisis")."</h3>";
	
    echo "<table  width=80%><tr><td align='center'>";
   // echo "<input type=\"button\" value=\"Envoyer\" onClick='if (verif3(this.form)) formu3.submit()'></td>";
   if ($devoir) 
      echo "<input type=\"submit\" value=\"".gettext("Distribuer le devoir")."\"></td>";
   else
      echo "<input type=\"submit\" value=\"".gettext("Envoyer")."\"></td>";
    echo "<input type=\"hidden\" name='nombre' value=$nombre>
	 <input type=\"hidden\" name='type' value=$type>
	 <input type=\"hidden\" name='n' value=$n>
 	 <input type=\"hidden\" name='liste_classe' value=$liste_classe>";
    echo "</tr></table>";
     
// fin cellule de gauche
echo "</td>";
if ($devoir) {
  echo "<td>";
  echo "<h3>".gettext("Choisir les caract&#233;ristiques du devoir")."</h3>
  <table >
  <tr><td><font size='-1'>".gettext("Nom du devoir")."<br>".gettext("(identifiant unique)")."</font></td>
      <td> <input type='text' name='id_devoir' value =''></td></tr>
  <tr><td><font size='-1'>".gettext("Fichier &#224; rendre")."<br>".gettext("(par d&#233;faut \"devoir\")")."</font></td>
      <td> <input type='text' name='nom_devoir' value ='".gettext("devoir")."'></td></tr>
  <tr><td><font size='-1'>".gettext("Date de retour")."</font></td> <td>";    
       choix_date($now,"retour");    
  echo "</td></tr> 
        <tr><td><font size='-1'>".gettext("Commentaire")."</font></td><td><textarea cols=30 rows=3 name=description ></textarea></td></tr></table>";
  } 
 echo "</td></tr></table></form>";
 } // fin du else 
} // fin cas type =2

include("pdp.inc.php");
?>
