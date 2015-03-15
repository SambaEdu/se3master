<?php


   /**
   
   * Interface de gestion des acl
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: acls
   * file: valide.php

  */	


include "entete.inc.php";
include "ihm.inc.php";
include "ldap.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-acls',"/var/www/se3/locale");
textdomain ('se3-acls');

  
if (is_admin("se3_is_admin",$login)=="Y") {
  
   	// Aide
      	$_SESSION["pageaide"]="ACL#En_utilisant_l.27interface_SambaEdu";

   	$noms = $_POST['noms'];
	$propagation = $_POST['propagation'];
	$choix = $_POST['choix'];
	$nouveau = $_POST['nouveau'];
	$nomformulaire = $_POST['nomformulaire'];
	$repertoire = $_POST['repertoire'];
	$type_fich = $_POST['type_fich'];


	$nom = explode (",",$noms);
    	$valeur = 0;
    
    	if ($propagation == "oui") $propagation="-R";
 
    	for ($loop=0; $loop < count ($nom) ; $loop++){
		$tri=explode (" ",$nom[$loop]);
		if ($nomformulaire[$valeur]== "oui") $lecture="r";
		else $lecture="-";
		$valeur = $valeur + 1;
		if ($nomformulaire[$valeur]== "oui") $ecriture="w";
		else $ecriture="-";
		$valeur = $valeur + 1;
		if ($nomformulaire[$valeur]== "oui") $execution="x";
		else $execution="-";
		$valeur = $valeur + 1;
	
       		if ($tri[0] != "Heritage") { 
	    		$defaut="non";
	    		if ($tri[0]=="Utilisateur") $type="u";
	    		elseif ($tri[0]=="Groupe") $type="g";
	    		elseif ($tri[0]=="Autres") $type="o";
	    		elseif ($tri[0]=="Proprietaire") $type="u";
	    	
		if ($nomformulaire[$valeur]== "oui") $effacer="eff";
	    	else $effacer="-m";
	    	$valeur = $valeur + 1;
	    	$nom1 = $tri[1];
	    	if ($tri[0]=="Autres" ||  $tri[0] == "Proprietaire" || $tri[1] == "proprietaire") {
			$nom1="x";
	    	}
	    	
		exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation");
	    
	}
	
	if ($tri[0] == "Heritage") { 
	    $defaut = "oui";
	    if ($tri[1]=="utilisateur") $type="u";
	    elseif ($tri[1]=="groupe") $type="g";
	    elseif ($tri[1]=="autres") $type="o";
	    elseif ($tri[1]=="proprietaire") $type="u";
	    if ($nomformulaire[$valeur]== "oui") $effacer="effd";
	    else $effacer="-m";
	    $valeur = $valeur + 1;
	    $nom1 = $tri[2];
	    if ($tri[1]=="autres" ||  $tri[1] == "proprietaire" || $tri[2] == "proprietaire") {
		$nom1="x";
	    }
	    exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation");
	}
	
    }//for ($loop=0; $loop < count ($nom) ; $loop++){
    
    if ($nouveau != "") {
	$defaut = "non";
	$effacer="-m";
	if ($_POST['nouveaulecture'] == "oui") $lecture = "r";
	else $lecture="-";
	if ($_POST['nouveauecriture'] == "oui") $ecriture="w";
	else $ecriture="-";
	if ($_POST['nouveauexecution'] == "oui") $execution="x";
	else $execution="-";
	$type=$choix;
	$nom1=$nouveau;
	exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation");
	if ( $nouveauheritage == "oui") {
		$defaut = "oui";
	    	exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation"); 
	}
    }
    echo gettext(" Les acls ont &#233;t&#233; modifi&#233;es ");    
    
}//fin is_admin
else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");

?>
		
