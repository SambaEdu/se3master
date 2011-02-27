<?php


   /**
   
   * Affiche les eleves
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: affichageleve.php
   */


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";


require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

 // Aide
$_SESSION["pageaide"]="Annuaire";

echo "<h1>".gettext("Annuaire")."</h1>";

if (is_admin("Annu_is_admin",$login)=="Y") {
	$cn=$_POST["cn"];
	$description=$_POST["description"];
	$action=$_POST["action"];
	$classe_gr=$_POST["classe_gr"];
	$equipe_gr=$_POST["equipe_gr"];
	$autres_gr=$_POST["autres_gr"];
	$matiere_gr=$_POST["matiere_gr"];
	aff_trailer ("1");
	for ($loop=0; $loop < count ($classe_gr) ; $loop++) {    
     		$filter[$loop]=$classe_gr[$loop];
	}
	$index=$loop;
	for ($loop=0; $loop < count ($equipe_gr) ; $loop++) {
    		$filter[$index+$loop]=$equipe_gr[$loop];
	}
	$index=$index+$loop;
	for ($loop=0; $loop < count ($autres_gr) ; $loop++) {
    		$filter[$index+$loop]=$autres_gr[$loop];
	}
	$index=$index+$loop;
	for ($loop=0; $loop < count ($matiere_gr) ; $loop++) {
		$filter[$index+$loop]=$matiere_gr[$loop];
	}
	if($action!='1') {
		// Message d'erreurs de saisie
		if ( $cn=="" || $description=="" ) {
    			echo "<div class=error_msg>".gettext("Vous devez saisir un nom de groupe et une description !")."</div><br>\n";
    			exit();
		}
		elseif (!verifDescription($description)) {
    			echo "<div class=error_msg>".gettext("Le champ description comporte des caract&#232;res interdits !")."</div><br>\n";
    			exit();
		}
		elseif (!verifIntituleGrp($intitule)) {
    			echo "<div class=error_msg>".gettext("Le champ intitul&#233; ne doit pas commencer ou se terminer par l'expresssion : Classe, Equipe ou Matiere !")."</div><br>\n";
    			exit();
		}
		elseif ( $filter=="") {
    			echo "<div class=error_msg>".gettext("Vous devez s&#233;lectionner au moins un groupe!")."</div><br>\n"; 
    			exit();
		}
	
		// Verification de l'existance du groupe    
		$groups=search_groups("(cn=$cn)");
		if (count($groups)) {
			echo "<div class='error_msg'>".gettext("Attention le groupe <font color='#0080ff'>$cn</font> est d&#233;ja pr&#233;sent dans la base, veuillez choisir un autre nom !")."</div><BR>\n";
			exit();
		} else {
			// Ajout du groupe
			$intitule = enleveaccents($intitule);
			exec ("/usr/share/se3/sbin/groupAdd.pl \"1\" $cn \"$description\"",$AllOutPut,$ReturnValue);
			if ($ReturnValue == "0") {
				echo "<div class=error_msg>".gettext("Le groupe <font color='#0080ff'>$cn</font> a &#233;t&#233; ajout&#233; avec succ&#232;s.")."</div><br>\n";
			} else {echo "<div class=error_msg>".gettext("Echec, le groupe <font color='#0080ff'>$cn</font> n'a pas &#233;t&#233; cr&#233;&#233; !")."\n";
				if ($ReturnValue) echo "(type d'erreur : $ReturnValue),&nbsp;";
echo "&nbsp;".gettext("Veuillez contacter</div> <A HREF='mailto:$MelAdminLCS?subject=PB creation groupe'>l'administrateur du syst&#232;me</A>")."<BR>\n";
				exit();
			}
    		}
	}
	echo "<B>".gettext("S&#233;lectionner les personnes &#224; mettre dans le groupe ci-dessus :")."</B><BR>";
	echo "<form action=\"constitutiongroupe.php\" method=\"post\">";
	echo "<table border=\"0\" cellspacing=\"10\">";    
	echo "<TR>";
	for ($loop=0; $loop < count($filter); $loop++) {
    		echo "<TD>$filter[$loop]</TD>";
	}
	echo "</TR>";    
	echo "<TR>";
	for ($filt=0; $filt < count($filter); $filt++) {
      		$uids=search_uids("(cn=".$filter[$filt].")");
      		$people=search_people_groups($uids,"(sn=*)","cat");
      		echo "<td valign=\"top\">";
      		//echo "<B>$filter[$filt]</B>";
      		echo "<select name=\"eleves[]\" size=\"10\"  multiple=multiple>";
      		for ($loop=0; $loop < count($people); $loop++) {
      			echo "<option value=".$people[$loop]["uid"].">".$people[$loop]["fullname"];
       		}
		echo"</select><td>";
	}
	echo "</TR>";    
	echo "</table>";    
	echo "<BR><BR>";    
	$CREER_REP=$_POST['CREER_REP'];
	echo "<input type=\"hidden\" name=\"cn\" value=\"$cn\">
      	      <input type=\"hidden\" name=CREER_REP value=\"$CREER_REP\">
      	      <input type=\"submit\" value=\"".gettext("valider")."\">
      	      <input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">";
	echo"</form>";

}//fin is_admin
	
else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");
include ("pdp.inc.php");
?>
	
