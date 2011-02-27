<?php


   /**
   
   * Desactive des utilisateurs 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Sandrine Dangreville ( academie de creteil )

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: peoples_desac.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
?>

<SCRIPT type="text/javascript">

/**
* Check tous les boutons radio
* @language Javascript
* @Parametres
* @return 
*/


function checkAll(nombre)
{
for (var j = 0; j < nombre; j++)
    {
    box = eval("document.desactive.desac" + j);
      if (box.checked == false)
    box.checked = true;
       }
}

/**
* UnCheck tous les boutons radio
* @language Javascript
* @Parametres
* @return 
*/


function uncheckAll(nombre)
{
    for (var j = 0; j < nombre; j++)
    {
    box = eval("document.desactive.desac" + j);
    if (box.checked == true) box.checked = false;
    }
}

</script>
<?php


require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

//Aide
$_SESSION["pageaide"]="Annuaire";

if (is_admin("Annu_is_admin",$login)=="Y") {
	echo "<h1>".gettext("Annuaire")."</h1>\n";
  
  	// Convertion en utf_8
  	$act=$_POST['action'];
  	$phase_en_cours=$_POST['phase'];
	// foreach ($_POST as $cle=>$val) {
	//	$$cle = $val;
	// }	
	if ($act=="") { $act=$_GET['action']; }


//	aff_trailer ("1");

	switch ($phase_en_cours) {

  	default:
  		if ($act=="activ") { 
  			$titre .= gettext("Activer les comptes d'une classe")."\n"; 
  		}  else { 
    			$titre .= gettext("D&#233;sactiver les comptes d'une classe"). "\n"; }
    			$texte = "<form action=\"peoples_desac.php\" method = post>\n";
    			$texte .= "<input type=\"hidden\" name=\"phase\" value=\"1\" />\n";
    			$texte .= "<input type=\"hidden\" name=\"action\" value=\"$act\" />\n";
    			$texte .= "<table>\n";
    			$texte .= "<tbody>\n";
    			$texte .= "<tr>\n";
    			$texte .= "<td>".gettext("Classe")." :</td>\n";
    			$texte .= "<td>\n";
    			$texte .= "<select name=\"priority_classe\">\n";
    			$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
    			$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
    			$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
    			$texte .= "</select>\n";
    			$texte .= "</td>\n";
    			$texte .= "<td><input type=\"text\" name=\"classe\"></td>\n";
    			$texte .= "</tr>\n";
    			$texte .= "</tbody>\n";
    			$texte .= "</table>\n";
    			$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>";
    			$texte .= "</form>\n";
   
    			echo "<BR>";
    			mktable($titre,$texte);
			break;

	
	case '1':
		$classe = $_POST[classe];
		if ($classe) {
       		$act=$_POST['action'];
       		// Recherche des classes et equipes dans la branche groups de l'annuaire
       		if ($_POST[priority_classe]=="contient") {
       		$filter_classe="(cn=Classe_*$classe*)";
       		} elseif($_POST[priority_classe]=="commence") {
        		$filter_classe="(cn=Classe_$classe*)";
       		} else {
         		$filter_classe="(cn=Classe_*$classe)";
       		}
       		
		// Affichage menu haut de page
//       		aff_trailer("3");
       		if ("$smbversion"=="samba3") { $acctname="sambaAcctFlags"; } else { $acctname="acctFlags"; }
       		$uids = search_uids ($filter_classe);
       		if ($act=="activ") {   
			$filter_people="($acctname=[UD          ])";
       			echo "<h3>".gettext(" Vous avez choisi d'activer le(s) compte(s) suivant(s)")."</h3>"; 
		} else { 
			$filter_people="($acctname=[U           ])";
       			echo "<h3>".gettext(" Vous avez choisi de d&#233;sactiver le(s) compte(s) suivant(s)")."</h3>";
		}
      		
		// $filter_people="(acctFlags=[U     
	       $people = search_people_groups ($uids,$filter_people,"group");
       		#$TimeStamp_1=microtime();
       		#############
       		# DEBUG     #
       		#############
       		# echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
       		#############
       		# DEBUG     #
       		#############
       		if (count($people)) {
         		if (count($people)==1) {
           			echo "<p><STRONG>".count($people)."</STRONG>".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche.")."</p>\n";
         		} else {
           			echo "<p><STRONG>".count($people)."</STRONG>".gettext(" utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche.")."</p>\n";
         		}
         		
			// affichage des resultats
         		echo "<form action=\"peoples_desac.php\" name=\"desactive\" method=\"post\">\n
         		<input type=\"hidden\" name=\"phase\" value=\"2\" />\n
         		<input type=\"hidden\" name=\"action\" value=\"$act\" />\n
         		<input type=\"button\" name=\"javascript\" value=\"".gettext("Tout selectionner")."\" onclick=\"checkAll(".count($people).")\" />\n";
           		
			if ($act=="activ") {
       				echo "<input type=\"submit\" name=\"submit\" value=\"".gettext("Activer les comptes s&#233;lectionn&#233;s")."\" /><br>\n";
         		} else  {
	 			echo "<input type=\"submit\" name=\"submit\" value=\"".gettext("D&#233;sactiver les comptes s&#233;lectionn&#233;s")."\" /><br>\n"; 
			}
     
         		for ($loop=0; $loop < count($people); $loop++) {
           			if (( $people[$loop]["group"] != $people[$loop-1]["group"])||($loop==0)) {
             				echo "<U>Classe</U> : ".$people[$loop]["group"]."<BR>\n";
           			}

           			if ($people[$loop]["cat"] == "Equipe") {
           		    		echo "<img src=\"images/gender_teacher.gif\" width=18 height=18 hspace=1 border=0 alt=\"Equipe\">\n";
           			} else {
             				if ($people[$loop]["sexe"]=="F") {
               					echo "<img src=\"images/gender_girl.gif\" width=14 height=14 hspace=3 border=0 alt=\"Fille\">\n";
             				} else {
               					echo "<img src=\"images/gender_boy.gif\" width=14 height=14 hspace=3 border=0 alt=\"Gar&#231;on\">\n";
             				}
           			}
         			
				//  $test=people_get_variables ($people[$loop]["uid"], true);
          			// echo
           			echo "<input type=\"checkbox\" name=\"desac".$loop."\" value=\"".$people[$loop]["uid"]."\" />".$people[$loop]["fullname"]."&nbsp;&nbsp;(".$people[$loop]["group"].")<BR>\n";
           			//echo "<A href=\"people.php?uid=".$people[$loop]["uid"]."\">".$people[$loop]["fullname"]."</A><BR>\n";

         		}
         		
			echo"<input type=\"hidden\" name=\"count_people\" value=\"".count($people)."\" />";
         		echo "</form>\n";

       		} else {
           		echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>
                  	".gettext("Retour au")." <A href=\"annu.php\">".gettext("formulaire de recherche")."</A>...<BR>\n";
       		}
  	} else {
       		// Aucun criteres de recherche
       		echo " <STRONG>".gettext("Pas de r&#233;sultats !")."</STRONG><BR>";
       		echo gettext("Veuillez compl&#233;ter au moins l'un des trois champs (nom, pr&#233;nom, classe) du")." <A href=\"annu.php\">".gettext("formulaire de recherche")."</A> !<BR>\n";
  	}
  	
	break;

  	case '2':
  		$count=$_POST['count_people'];
  		$act =$_POST['action'];
  		if  ($count) {
  			for ($loop=0; $loop < $count; $loop++) {
  				$uids=$_POST["desac".$loop.""];
  				if ($uids) {
  					echo $uids."&nbsp;";
  					userDesactive($uids,$act);
    					echo "<br>";
  				}
  			}
  		} else {
  			echo gettext("Aucun utilisateur s&#233;lectionn&#233;");
  		}

  	break;

	}

} else {
        echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");
?>
