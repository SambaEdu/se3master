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
* file: visuacls.php

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
	
	$repertoire=isset($_POST['repertoire']) ? $_POST['repertoire'] : (isset($_GET['repertoire']) ? $_GET['repertoire'] : "");
	
	$type_fich=isset($_POST['type_fich']) ? $_POST['type_fich'] : (isset($_GET['type_fich']) ? $_GET['type_fich'] : "");
	
	$noms=isset($_POST['noms']) ? $_POST['noms'] : "";
	$propagation=isset($_POST['propagation']) ? $_POST['propagation'] : "";
	$choix=isset($_POST['choix']) ? $_POST['choix'] : "";
	$nouveau=isset($_POST['nouveau']) ? $_POST['nouveau'] : "";
	$nomformulaire=isset($_POST['nomformulaire']) ? $_POST['nomformulaire'] : array();
	$valide=isset($_POST['valide']) ? $_POST['valide'] : NULL;
	$nouveaulecture=isset($_POST['nouveaulecture']) ? $_POST['nouveaulecture'] : "";
	$nouveauecriture=isset($_POST['nouveauecriture']) ? $_POST['nouveauecriture'] : "";
	$nouveauexecution=isset($_POST['nouveauexecution']) ? $_POST['nouveauexecution'] : "";
	$nouveauheritage=isset($_POST['nouveauheritage']) ? $_POST['nouveauheritage'] : "";
	

	if (isset($valide)) {
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
		if ($nouveaulecture == "oui") $lecture = "r";
		else $lecture="-";
		if ($nouveauecriture == "oui") $ecriture="w";
		else $ecriture="-";
		if ($nouveauexecution == "oui") $execution="x";
		else $execution="-";
		$type=$choix;
		$nom1=$nouveau;
		exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation");
		if ( $nouveauheritage == "oui") {
		$defaut = "oui";
		exec ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh $effacer $type $nom1 $lecture $ecriture $execution \"$repertoire\" $defaut $propagation");
		}
	}
	echo "<H1>".gettext("Attribution d'acls")."</H1><P>\n";
	echo gettext(" Les acls sont maintenant :")." <p>";
	
	}
	
	exec ("/usr/bin/sudo /usr/share/se3/scripts/getfacl.sh \"$repertoire\"");
	$acl = file("/tmp/test.tmp");
	$a = 0;
	if (!isset($valide)) {	
	echo "<H1>".gettext("Attribution d'acls")."</H1><P>";    
	echo gettext("Acls du r&#233;pertoire")." $repertoire";
	echo "<form name=\"visu\" action=\"visuacls.php\" method=\"post\">\n";
	}
	echo "<table border=\"0\" cellspacing=\"\">
	<thead>
	<tr>
	<B><td>".gettext("Noms")."</td>
	<td>".gettext("Lecture")."</td>
	<td>".gettext("Ecriture")."</td>
	<td>".gettext("Ex&#233;cution")."</td>\n";
	if (!isset($valide)) {
	echo "<td>".gettext("Supprimer")."</td>\n";
	}
	echo "</B></tr>
	</thead>
	<tbody><tr>\n";
	
	$boucle = count ($acl) - 1;    
	for ($loop=1 ;$loop < $boucle; $loop++) {
	
	$test=explode(":",$acl[$loop]);
	
	for ($i=0 ; $i < count ($test) ; $i++) {
		$test[$i]=trim($test[$i]);
	}
	
	if ( ! ($test[0]=="# owner" || $test[0]=="# group" || $test[0]=="mask" || ($test[0]=="default"&&$test[1]=="mask"))) {
		echo "<td valign=\"top\">\n";
	}
	
	if ($test[0]=="# owner") {
		$proprio=$test[1];
	}
	if ($test[0]=="# group") {
		$groupeproprio=$test[1];
	}
	if ($test[0]=="other") {	 
		$nom[$loop]="Autres";
		echo "$nom[$loop]";
		$lecture=substr($test[2],0,1);
		$ecriture=substr($test[2],-2,1);
		$execution=substr($test[2],-1);
	}
	if ($test[0]=="user") {
		if ($test[1]!= "") {
			$nom[$loop]="Utilisateur ".$test[1];
			echo "$nom[$loop]";
			$lecture=substr($test[2],0,1);
			$ecriture=substr($test[2],-2,1);
			$execution=substr($test[2],-1);
		}
		if ($test[1] == "") {
			$nom[$loop]="Proprietaire ".$proprio;
			echo "$nom[$loop]"; 
			$lecture=substr($test[2],0,1);
			$ecriture=substr($test[2],-2,1);
			$execution=substr($test[2],-1);
		}
	}
	if ($test[0]=="group") {
		if ($test[1]!= "") {
			$nom[$loop]="Groupe ".$test[1];
			echo "$nom[$loop]";
			$lecture=substr($test[2],0,1);
			$ecriture=substr($test[2],-2,1);
			$execution=substr($test[2],-1);
		}
		if ($test[1] == "") {
			$nom[$loop]="Groupe proprietaire ".$groupeproprio;
			echo "$nom[$loop]";
			$lecture=substr($test[2],0,1);
			$ecriture=substr($test[2],-2,1);
			$execution=substr($test[2],-1);
		}
	}
	
	elseif ($test[0]=="default") {
		
		if ($test[1]=="other") {
			$nom[$loop]="Heritage autres";
			echo "$nom[$loop]";
			$lecture=substr($test[3],0,1);
			$ecriture=substr($test[3],-2,1);
			$execution=substr($test[3],-1);
		}
		if ($test[1]=="user") {
			if ($test[2]!= "") {
				$nom[$loop]="Heritage utilisateur ".$test[2];
				echo "$nom[$loop]";
				$lecture=substr($test[3],0,1);
				$ecriture=substr($test[3],-2,1);
				$execution=substr($test[3],-1);
			}
			if ($test[2] == "") {
				$nom[$loop]="Heritage proprietaire ".$proprio;
				echo "$nom[$loop]"; 
				$lecture=substr($test[3],0,1);
				$ecriture=substr($test[3],-2,1);
				$execution=substr($test[3],-1);
			}
		}
		if ($test[1]=="group") {
			if ($test[2]!= "") {
				$nom[$loop]="Heritage groupe ".$test[2];
				echo "$nom[$loop]";
				$lecture=substr($test[3],0,1);
				$ecriture=substr($test[3],-2,1);
				$execution=substr($test[3],-1);
			}
			if ($test[2] == "") {
				$nom[$loop]="Heritage groupe proprietaire ".$groupeproprio;
				echo "$nom[$loop]";
				$lecture=substr($test[3],0,1);
				$ecriture=substr($test[3],-2,1);
				$execution=substr($test[3],-1);
			}
		}
	}
		
	if ( ! ($test[0]=="# owner" || (($test[0]=="default")&&($test[1]=="mask")) || $test[0]=="# group" || $test[0]=="mask")) {
		echo "</td>\n";	 
		
		$nomformulaire[$a] = "lecture".$nom[$loop] ;
		if ($lecture =="r") {
		echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\" CHECKED>  </td>\n";
		}
		else  echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\"></td>\n";
		$a= $a +1;
		$nomformulaire[$a] = "ecriture".$nom[$loop];
		
		if ($ecriture =="w") {
			echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\" checked > </td>\n";
		}
		else echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\"></td>\n";
		
		$a = $a +1;    
		$nomformulaire[$a] = "execution".$nom[$loop];
		
		if ($execution =="x") {
			echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\" checked></td>\n";
		}
		else echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\"></td>\n";
		
		$a= $a + 1;
		$nomformulaire[$a] = "supprimer".$nom[$loop];    
		if (!(($test[0] == "user"&&$test[1]=="") || ($test[0] == "group"&& $test[1]=="") || $test[0]=="other"  || ($test[1] == "user"&&$test[2]=="") || ($test[1] == "group"&& $test[2]=="") || $test[1]=="other" )) {
		if (!isset($valide)) {
			echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nomformulaire[$a]\" VALUE=\"".gettext("oui")."\"></td>\n";
		}
		}
		$a= $a +1;	 
		echo  "</tr>\n";
	}
	}
	
	$noms = implode(",",$nom);	 
	echo "</tbody></table>\n";
	echo "<br /><br />\n";
	if (!isset($valide)) {
	echo "<TD><B>".gettext("Ajout d'un nouvel utilisateur ou groupe :")."</B></TD>\n";
	echo "<table border=\"0\">\n";
	echo "<TR><TD>".gettext("Nom")."</td> <td valign=\"top\"><input type=\"text\" name=\"nouveau\" value=\"$nouveau\" size=\"20\" ></TD>\n";
	echo "<TD><input type=\"button\" value=\"".gettext("Recherche dans l'annuaire")."\" onclick=\"popuprecherche('searchacls.php','popuprecherche','width=500,height=500');\"></TD></TR>\n";    
	echo "<TR><TD>".gettext("Utilisateur")."</td><td valign=\"top\"><input type=\"radio\" name=\"choix\" value=\"u\"></td></tr>
		<TR><TD>".gettext("Groupe")."</td><td valign=\"top\"><input type=\"radio\" name=\"choix\" value=\"g\"></td></tr>\n";
	
	echo "<TR><TD>".gettext("Lecture")."</td> <td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nouveaulecture\" VALUE=\"oui\"></td></tr>\n";
	echo "<TR><TD>".gettext("Ecriture")."</td> <td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nouveauecriture\" VALUE=\"oui\"></td></tr>\n";
	echo "<TR><TD>".gettext("Execution")."</td> <td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nouveauexecution\" VALUE=\"oui\"></td></tr>\n";
	/*
	//Stephane Boireau (21/03/2006)
	//Modification de la variable 'type' en 'type_fich'
	//parce que la variable 'type' est utilisee avec
	//plusieurs autres significations dans la page courante (visuacls.php)
	if ($type=="repertoire"){
	*/
	if ($type_fich=="repertoire"){
		echo "<TR><TD>".gettext("Propagation de l'acl par H&#233;ritage")."</td>\n";
		echo "<td valign=\"top\">\n<INPUT TYPE=\"checkbox\" NAME=\"nouveauheritage\" VALUE=\"oui\"></td></tr>\n";
	}
	echo "</table><br />\n";
	//if ($type=="r&#233;pertoire"){
	if ($type_fich=="repertoire"){
		echo "<B><br />".gettext("Appliquer les changements aux sous dossiers et fichiers d&#233;j&#224;  existants")." <B><INPUT TYPE=\"checkbox\" NAME=\"propagation\" VALUE=\"".gettext("oui")."\"><br /><br />\n";
	}
	echo "<input type=\"hidden\" name=\"noms\" value=\"$noms\">" ;
	echo "<input type=\"hidden\" name=\"repertoire\" value=\"$repertoire\">" ;
	echo "<input type=\"hidden\" name=\"type_fich\" value=\"$type_fich\">" ;
	echo "<input type=\"hidden\" name=\"valide\" value=\"$valide\">" ;
	echo "<input type=\"submit\" value=\"".gettext("valider les acls")."\">\n";
	echo "</form>\n";
	}
	
	if (isset($valide)) {
		/*
		//Stephane Boireau (21/03/2006)
		//Ajout de la variable 'type_fich' pour conserver la possibilite d'Heritage lors d'un clic sur 'Modifier a nouveau'
		//echo "<a  href=\"visuacls.php?repertoire=$repertoire\" > Modifier a nouveau </a>";   }
		*/
		echo "<a  href=\"visuacls.php?repertoire=$repertoire&type_fich=$type_fich\" >".gettext(" Modifier &#224; nouveau")." </a>";   
	}
	
}//fin is_admin
else echo gettext("Vous n'avez pas les droits necessaires pour ouvrir cette page...");
include ("pdp.inc.php");

?>
