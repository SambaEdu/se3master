<?php


	/** 

	* Retour correction des devoirs et corrections 
	
	* @Version $Id$ 
   	* @Auteurs Jean Gourdin
   	
   	* @Projet LCS-SE3
	* @Licence Distribue selon les termes de la licence GPL
    
    	*/
	
	/**

	* file: correction.php
	* @Repertoire: echanges/
	*/


require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');


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
$id_prof=$login;
$now =date("Y-m-d");
$table="devoirs";
$fichiers= array();


// requete pour avoir le detail de ce devoir $id
$req = "SELECT * FROM $table WHERE id = '$id'";
$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $req);
$ligne= mysqli_fetch_array($resultat);
list($id,$id_prof,$id_devoir,$nom_devoir,$date_distrib,$date_retour,$description,$liste_distrib, $liste_retard) = $ligne; 

// liste complete des eleves beneficiaires du devoir 
$liste_distrib=preg_replace("/\|$/","",$liste_distrib);
$tab_distrib = liste_tab($liste_distrib);       // tableau associatif : nom_classe --> uid1#uid2#..
 
echo "<body >
<h1>".gettext("Correction du devoir")." <em>$id_devoir</em></h1>
<hr>\n";

////  distribution CORRIGE du PROF pour TOUS les eleves  ////
if ($global) {
// recuperation du fichier uploade
  if (move_uploaded_file($_FILES['fich']['tmp_name'], $_FILES['fich']['name'])) {
	// print_r ($_FILES);
	if ($f = @fopen( $_FILES['fich']['name'], "r")) {
  		$taille=$_FILES['fich']['size'];
  		// echo "ouverture du fichier $fich de taille $taille";
 	 	$contenu=fread($f, $taille);
 	 	$donnees=addslashes($contenu);
 	 	$nom_fichier = $_FILES['fich']['name'] ;
 	 	//$chemin="/tmp/$nom_fichier";
 		system("mkdir -p /tmp/$login");
 		$chemin="/tmp/$login/$nom_fichier";
 		$f1 = fopen($chemin,"w");
 		if ($f1)
 		   echo "<h4>".gettext("Distribution du corrig&#233;")." <em>$nom_fichier</em><br> ".
 		   gettext("(renomm&#233; CORRIGE) aux &#233;l&#232;ves :")."</h4>\n";
 	   	fputs($f1, $contenu);
 	   	fclose($f1);
  
  		foreach ($tab_distrib as $classe => $liste_distrib_classe) {
   			$liste_distrib_classe=preg_replace("/#$/","",$liste_distrib_classe);
   			$tab_eleves_classe=preg_split("/#/",$liste_distrib_classe);
   			$nb_eleves_classe=count($tab_eleves_classe);
   			$liste_classe_retard ="";          // liste eleves en retard par classe

  			// boucle sur tous les eleves de la classe/groupe
  			for ($p=0; $p < $nb_eleves_classe; $p++) {
   				$uid_eleve = $tab_eleves_classe[$p];
   				$param=params_eleve($uid_eleve);
   				$cla=classe_eleve($uid_eleve);              // $cla est la VRAIE classe de l'eleve (# $classe si $classe d&#233;signe un groupe !)
          
   				$rep= "/var/se3/Classes/$cla/$uid_eleve/".inverse_login($id_devoir); 
   				//$ch ="/usr/bin/sudo /usr/share/se3/scripts/copie_corrige_distrib.sh $uid_eleve $rep $nom_fichier $login";  
   				$ch ="/usr/bin/sudo /usr/share/se3/scripts/copie_corrige_distrib.sh $uid_eleve \"$rep\" \"$nom_fichier\" $login";  
   				//echo "<p>ch=$ch</p>";
   				$cr=exec($ch);                             //  echo "$ch --> $cr<br>";
   				if ($cr) {
     					echo ($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
       					"<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
     					echo $param["nom"]." - ".$param["classe"]."<br>\n";
   				} else        
   					echo "--> ".gettext(" &#233;chec pour")." $uid_eleve<br>";      
  			}
 		}
 	}
  	//Nettoyage en fin de distribution du corrige-type:
  	//echo "<p>Suppression du fichier $chemin</p>";
  	if(file_exists("$chemin")){
  		  unlink("$chemin");
  	}
} else 
  die (gettext("Pas de fichier-corrig&#233; choisi (ou fichier vide ..)\nrecommencez .."));     // si aucun fichier n'a &#233;t&#233; choisi
}  // fin corrige global --> if ($global)

////   CORRIGE PERSONNEL pour les eleves qui ont rendu leur devoir ;-) ////
if ($perso) {
  $tab_eleves_corrige=array();
  
/// recherche des eleves a jour
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
  
  for ($p=0; $p < $nb_eleves_classe; $p++) {
   // $tab_eleves_classe[$p] se trouve t-il dans $tab_retard[$classe]  ? 
    if (preg_match("/$tab_eleves_classe[$p]#/", $tab_retard[$classe])) {  // -->  OUI --> pas de corrige !
    $tab_eleves_retard[$classe][]=$tab_eleves_classe[$p];
    $nb_eleves_retard++;   
    }
    else {        //  --> NON --> envoyer le corrige de leur devoir
    $tab_eleves_retour[$classe][]=$tab_eleves_classe[$p];
    $nb_eleves_retour++;
   }
 }
}

if ($nb_eleves_retour == 0) 
 echo "<h4>".gettext("Aucun &#233;l&#232;ve n'a rendu son devoir !")."</h4>\n";
    
else {  // des eleves ont rendu leur devoir !
  echo "<h4>".gettext("Distribution des corrig&#233;s personnels du devoir")." <em>$id_devoir</em><br>".
  gettext("(sous le nom <em>DEVOIR-CORRIGE</em>) aux &#233;l&#232;ves :")."</h4>\n";

// boucle sur la liste de TOUS les eleves qui ont rendu leur devoir !
 foreach ($tab_eleves_retour as $classe => $tab_eleves) {
  if (preg_match("/^Classe_/", $classe))
    echo gettext("Classe")." <b>".$classe."</b><br>\n";  
  else   
    echo gettext("Groupe")." <b>".$classe."</b><br>\n";  
  for ($p=0; $p < count($tab_eleves); $p++) {
   $uid_eleve=$tab_eleves[$p];
   $param=params_eleve($uid_eleve);
   $cla=classe_eleve($uid_eleve);      // $cla est la VRAIE classe de l'eleve (# $classe si $classe designe un groupe !)
   //$ch ="/usr/bin/sudo  /usr/share/se3/scripts/copie_corrige.sh $login $id_devoir $uid_eleve $cla ";
   $ch ="/usr/bin/sudo  /usr/share/se3/scripts/copie_corrige.sh $login \"$id_devoir\" ".inverse_login($uid_eleve)." $cla ";
   //echo "<p>$ch</p>";
   $cr=exec($ch);  
   if ($cr) {
    echo ($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
       "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
    echo $param["nom"]." - ".$param["classe"]."<br>\n";
   }         
   else        
    echo "--> ".gettext("&#233;chec de la remise du devoir de")." $uid_eleve<br>\n";      
  } // fin boucle sur les eleves
 } // fin boucle classes
} // fin else 
} // fin corrige perso
include ("pdp.inc.php");
?>

