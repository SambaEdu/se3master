<?php


   /**
   
   * Affiche le trombinoscope
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux
   * @auteurs Setphane Boireau

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: replica_log.php
   */



   
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if ((is_admin("annu_can_read",$login)=="Y")||(is_admin("sovajon_is_admin",$login)=="Y") || (is_admin("Annu_is_admin",$login)=="Y"))  {
	$filter=$_GET['filter'];
	$group=search_groups ("(cn=".$filter.")");
  	$uids = search_uids ("(cn=".$filter.")");
  	$people = search_people_groups ($uids,"(sn=*)","cat");
 

/********************* Indiquer le nombre de photos par page**************/ 
// Pour permettre le changement des variables
// Il suffit de creer un fichier conf_trombin.inc.php et de mettre les variables

// Ne pas changer ici
$nbr_foto="5";        // Nombre de colonnes
$largeur_foto="130";  // Taille en largeur
$hauteur_foto="180";  // Taille en hauteur 
$rep_trombine="/var/se3/Docs/trombine/";

/* Si vous souhaitez changer le nombre de photos par ligne,  la taille vous devez creer un fichier conf_trombin.inc.php avec les variables ci dessus afin que cela ne soit pas ecrase au moment des mises a jour.
*/

if (file_exists("/var/www/se3/annu/conf_trombin.inc.php")) {
  include ("/var/www/se3/annu/conf_trombin.inc.php");
}

/*************************************************************************/


function affiche_img_redim($compte,$type){

	/**

	* Fonction pour dimensionner les photos
  	* Function: affiche_img_redim


	* @Parametres $compte - login de l'utilisateur 
	* @Parametres $type - format de l'image (gif,png,jpg,jpeg) 

	* @Return	Affiche le HTML pour afficher la photo

	*/
	
        global $largeur_foto;
        global $hauteur_foto;
        global $rep_trombine;
	//$dimimg=getimagesize("$image");
	$dimimg=getimagesize("$rep_trombine/$compte.$type");
	$largimg=$largeur_foto;
	if($dimimg[0]!=0){
		$hautimg=round($dimimg[1]*$largeur_foto/$dimimg[0]);
	}
	else{
		$hautimg=$hauteur_foto;
	}
	echo "<img src=\"trombine/$compte.$type\" width=\"$largimg\" height=\"$hautimg\">\n";
}


$tab_type=array("gif","png","jpg","jpeg");
//===================================================

if (count($people)) {
    // affichage des r?sultats
    // Nettoyage des _ dans l'intitul? du groupe
    $intitule =  strtr($filter,"_"," ");
    echo "<H1><U>".gettext("Groupe")."</U> : $intitule <font size=\"-2\">".$group[0]["description"]."</font></H1>\n";
    echo "<br><br>";
    echo "<TABLE border=1>\n";
    
    $i="0";
    // Si ondemande plus de photo qu'il n'y a de personne dans le groupe
    if($nbr_foto>=count($people)) {
	$nbr_foto=count($people);
    }


   $nbr_user=count($people);
   // Pour supprimer la photo du prof des groupes Cours
   for ($loop=0; $loop < count($people); $loop++) {
 	if (preg_match("/Cours_/i", "$filter")) {
		if ($people[$loop]["prof"]==1) {
			$nbr_user=$nbr_user-1;	
		} 
	}
   }		


for ($loop=0; $loop < $nbr_user; $loop++) {
    echo "<tr>";
 
    if($loop!="0") {
    	$nbr=$nbr_foto+$loop; 
	
     } else { 
     	$nbr="$nbr_foto";
    }


 // echo "nbr $nbr<br>";    
 // echo "loop avant boucle-2 $loop et i $i<br>";   
    for ($loop="$i"; $loop < "$nbr"; $loop++) {
 // echo "loop dans boucle-2 $loop<br>";    

 // Pour supprimer la photo du prof des groupes Cours
 if (preg_match("/Cours_/i", "$filter")) {
	if ($people[$loop]["prof"]==1) {
		$i++;
		$nbr = $nbr+1;
		continue;
	}
   }   	

	echo "<td  width=\"$largeur_foto\" height=\"$hauteur_foto\">\n";    
	echo "<table><tr><td align=\"center\">";
	$image_trouvee=0;
	for($j=0;$j<count($tab_type);$j++){
		if($image_trouvee==0){
			$photo="$rep_trombine".$people[$loop]["uid"].".".$tab_type[$j];
			// Supprime le 0 devant s'il existe
			$employeeNumber_gepi = preg_replace('/^[0]/','',$people[$loop]["employeeNumber"],1);
			$photo_employeeNumber="$rep_trombine"."$employeeNumber_gepi".".".$tab_type[$j];
			// $photo_employeeNumber="$rep_trombine".$people[$loop]["employeeNumber"].".".$tab_type[$j];
			if(file_exists("$photo")){
				//affiche_img_redim("$photopng",$people[$loop]["uid"],$tab_type[$j]);
				affiche_img_redim($people[$loop]["uid"],$tab_type[$j]);
				$image_trouvee=1;
			} elseif (file_exists("$photo_employeeNumber")) {
				// affiche_img_redim($people[$loop]["employeeNumber"],$tab_type[$j]);
				affiche_img_redim($employeeNumber_gepi,$tab_type[$j]);
				$image_trouvee=1;
			}	
			
		}
	}
        echo "</td></tr>\n";
        echo "<tr><td valign=\"bottom\" width=\"$largeur_foto\" align=\"center\" >";
    
	echo $people[$loop]["fullname"];
//	echo "EN".$people[$loop]["employeeNumber"];
	echo "</TD>";

	echo "</td></tr></table>\n";
	echo "</td>\n";
	$i++;
    }
    $loop=$loop-1;
 //   echo "loop fin de boucle-2 $loop i $i nbr $nbr<br>";
    echo "</tr>";    
}
echo "</TABLE>\n";
} else {
    echo " <STRONG>".gettext("Pas de membres")."</STRONG> ".gettext(" dans le groupe")." $filter.<BR>";
}

} else {
	exit;
}	
  include ("pdp.inc.php");
?>
