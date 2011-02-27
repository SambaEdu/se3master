<?php


   /**
   
   * Creation des repertoires classes et mise en place des ACL
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux, denis bonnenfant

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: partages/
   * file: rep_classes.php

  */	



  
  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-partages',"/var/www/se3/locale");
  textdomain ('se3-partages');

//aide
$_SESSION["pageaide"]="Ressources_et_partages";


$texte_alert="Vous allez supprimer tout le repertoire classe. Voulez vous vraiment continuer ?";
?>
<script type="text/javascript">

/**
* Affiche une boite de dialogue pour demander confirmation
* @language Javascript
* @Parametres
* @return 
*/


function areyousure()
       	{
       	var messageb = "<?php echo "$texte_alert"; ?>";
       	if (confirm(messageb))
       		return true;
	else
	        return  false;
        }
</script>

<?php
if (is_admin("se3_is_admin",$login)=="Y") {

	echo "<h1>".gettext("Cr&#233;ation des r&#233;pertoires classes")."</h1>";
	
	// On ajoute les classes
	if($_POST[create_folders_classes]) {
		$new_folders_classes=$_POST[new_folders_classes];	
        	for ($loop=0; $loop < count($new_folders_classes); $loop++) {
			list($Classe,$Niveau)=preg_split("/Classe_/",$new_folders_classes[$loop]);
			system("/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau");
			$rep_niveau = "/var/se3/Classes/Classe_".$Niveau;
			
			$acl_group_profs_classes = exec("cd /var/se3/Classes; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1");
			if ($acl_group_profs_classes=="1") {
				system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"$rep_niveau\" non -R");
				system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"$rep_niveau\" oui -R");
			}
			if (is_dir("$rep_niveau")) {
				echo "R&#233;pertoire classe ".$Niveau. "cree.<br>\n";
			} else {
				echo "Echec : Cr&#233;ation du r&#233;pertoire classe ".$Niveau."<br>\n";
			 	echo "V&#233;rifier que le groupe Equipe correspondant &#224; la Classe exist<br>\n";	
			}	
		}
	}

	// On supprime les classes
	if($_POST[delete_folders_classes]) {
		$old_RessourcesClasses=$_POST[old_RessourcesClasses];	
        	for ($loop=0; $loop < count($old_RessourcesClasses); $loop++) {
			list($Classe,$Niveau)=preg_split("/Classe_/",$old_RessourcesClasses[$loop]);
			system ("/usr/bin/sudo /usr/share/se3/scripts/deleteClasses.sh $Niveau");
			$rep_niveau = "/var/se3/Classes/Classe_".$Niveau;
			
			if ( ! is_dir("$rep_niveau")) {
				echo "Suppression du r&#233;pertoire classe ".$Niveau."<br>\n";
			} else {
				echo "Echec : Suppression du r&#233;pertoire classe ".$Niveau."<br>\n";
			}	
		}
	}

	// On rafaichit on sait jamais, cela replace les acl
	if($_POST[refresh_folders_classes]) {
       		$dirClasses = dir ("/var/se3/Classes");
        	$indice=0;
        	while ( $Entry = $dirClasses ->read() ) {

        		if ( preg_match("/^Classe_/", $Entry) ) {

        			$RessourcesClasses[$indice] = $Entry;
				list($Classe,$Niveau)=preg_split("/Classe_/",$RessourcesClasses[$indice]);
				//echo "Rafraichissement du r&#233;pertoire classe ".$Niveau."<br>\n";
				system ("/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau");
        			$indice++;
        		}
        	}
		// Dans le cas ou on donne le droit a tous les profs sur les repertoires classes
		if ($_POST[acl_group_profs]) {
			system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"/var/se3/Classes\" non -R");
			system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"/var/se3/Classes\" oui -R");
		} else {
			system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh eff g Profs r w x \"/var/se3/Classes\" non -R");
			system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh effd g Profs r w x \"/var/se3/Classes\" oui -R");
		}
		echo "<br><br><center>";
		echo "<a href=rep_classes.php>Continuez</a>";
		echo "</center>";
		exit;
	}
	// On rafaichit les classes selectionnees
	if($_POST[refresh_classes]||$_POST[clean_classes]) {
		$refresh_RessourcesClasses=$_POST[old_RessourcesClasses];	
            if ( count($refresh_RessourcesClasses) > 0 ) {
	        for ($loop=0; $loop < count($refresh_RessourcesClasses); $loop++) {
       		    list($Classe,$Niveau)=preg_split("/Classe_/",$refresh_RessourcesClasses[$loop]);
		    if($_POST[refresh_classes]) {
			//echo "<b>rafraichissement de la classe : $Niveau</b><br>";
			system ("/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau ");
		
		        // Dans le cas ou on donne le droit a tous les profs sur les repertoires classes
		        if ($_POST[acl_group_profs]) {
			    system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"/var/se3/Classes/$refresh_RessourcesClasses[$loop]\" non -R");
			    system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh -m g Profs r w x \"/var/se3/Classes/$refresh_RessourcesClasses[$loop]\" oui -R");
	        	} else {
			    $acl_group_profs_classe = exec("cd /var/se3/Classes/$refresh_RessourcesClasses[$loop]; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1");
                            if ($acl_group_profs_classe=="1") {
                            echo "<b>rafraichissement de la classe : $Niveau</b><br>";
       		            system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh eff g Profs r w x \"/var/se3/Classes/$refresh_RessourcesClasses[$loop]\" non -R");
			    system ("/usr/bin/sudo /usr/share/se3/scripts/acls.sh effd g Profs r w x \"/var/se3/Classes/$refresh_RessourcesClasses[$loop]\" oui -R");
		            }
		        }
		    } elseif($_POST[clean_classes]) {
			echo "<b>nettoyage de la classe : $Niveau</b><br>";
			system ("/usr/bin/sudo /usr/share/se3/scripts/cleanClasses.pl $Niveau ");
		    }
		}
	     }
		echo "<br><br><center>";
		echo "<a href=rep_classes.php>Continuez</a>";
		echo "</center>";
		exit;
	}

	echo "<BR>";
      	// configuration mono serveur  : determination des parametres du serveur
      	$serveur=search_machines ("(l=maitre)", "computers");
      	$cn_srv= $serveur[0]["cn"];
      	$stat_srv = $serveur[0]["l"];
      	$ipHostNumber =  $serveur[0]["ipHostNumber"];

      	// Recherche de la liste des classes dans l'annuaire
       	$list_classes=search_groups("cn=Classe_*");
      	// Recherche des sous dossiers classes d&#233;ja existant sur le serveur selectionn&#233;
       
       	// Constitution d'un tableau avec les ressources deja existantes
       	$dirClasses = dir ("/var/se3/Classes");
        $indice=0;
        while ( $Entry = $dirClasses ->read() ) {
        	if ( preg_match("/^Classe_/", $Entry) ) {
        		$RessourcesClasses[$indice] = $Entry;
        		$indice++;
        	}
        }
      
      	// Creation d'un tableau des nouvelles ressources a cr&#233;er  par
      	// elimination des ressources deja existantes
      	$k=0;
      	for ($i=0; $i < count($list_classes); $i++ ) {
        	for ($j=0; $j < count($RessourcesClasses); $j++ ) {
          		if (  $list_classes[$i]["cn"] ==  $RessourcesClasses[$j])  {
            			$exist = true;
            			break;
          		} else { $exist = false; }
        	}
        	
		if (!$exist) {
          		$list_new_classes[$k]["cn"]= $list_classes[$i]["cn"];
          		$k++;
        	}
      	}
     	
	// Affichage de la table
	echo "<H3>Gestion des ressources classes</H3>\n";
	echo "<br>";
	echo "<table BORDER=1 CELLPADDING=3 CELLSPACING=1 RULES=COLS>\n";
	echo "<tr class=\"menuheader\" height=\"30\">";
	echo "<td align=\"center\">Classes &#224; cr&#233;er ";
	echo "<u onmouseover=\"return escape".gettext("('Classes disponibles dans l\'annuaire, mais dont le r&#233;pertoire n\'a pas encore &#233;t&#233; cr&#233;&#233; sur le serveur.<br><b>Remarque :</b> Un r&#233;pertoire peut &#234;tre cr&#233;&#233;, que si il existe une &#233;quipe correspondante.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u></td>";
	echo "<td align=\"center\">Classes cr&#233;&#233;es ";
        echo "</td></tr>";
        echo "<tr><td align=\"center\">\n";
      	// Affichage menu de s&#233;lection des sous-dossiers classes a cr&#233;er
      	if   ( count($list_new_classes)>15) $size=15; else $size=count($list_new_classes);
      	if ( count($list_new_classes)>0) {
        	echo "<form action=\"rep_classes.php\" method=\"post\">\n";
        	echo "<select size=\"".$size."\" name=\"new_folders_classes[]\" multiple=\"multiple\">\n";
        	for ($loop=0; $loop < count($list_new_classes); $loop++) {
          		echo "<option value=".$list_new_classes[$loop]["cn"].">".$list_new_classes[$loop]["cn"]."\n";
        	}
        	echo "</select><br>\n";
        	echo "<input type=\"hidden\" name=\"create_folders_classes\" value=\"true\">\n";
        	// echo "<input type=\"hidden\" name=\"cn_srv\" value=\"$cn_srv\">\n";
        	// echo "<input type=\"hidden\" name=\"ipHostNumber\" value=\"$ipHostNumber\">\n";

        	echo "<input type=\"submit\" value=\"".gettext("Cr&#233;er")."\">\n";
        	echo "</form>\n";
        
		// V&#233;rification selection d'au moins une classe
        	if ( $create_folders_classes && count($new_folders_classes)==0 ) {
          		echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
        	}
      	} else {
          	echo "<div class='error_msg'>".gettext("Pas de nouvelles classes !")."</div>\n";
      	}
        
	echo "</td><td align=\"center\">\n";
      	if   ( count($RessourcesClasses)>15) $size=15; else $size=count($RessourcesClasses);
      	if ( count($RessourcesClasses)>0) {
        	echo "<form action=\"rep_classes.php\" method=\"post\">\n";
        	echo "<select size=\"".$size."\" name=\"old_RessourcesClasses[]\" multiple=\"multiple\">\n";
        	for ($loop=0; $loop < count($RessourcesClasses); $loop++) {
          		echo "<option value=".$RessourcesClasses[$loop].">".$RessourcesClasses[$loop]."\n";
        	}
        	echo "</select><br>\n";
//        	echo "<input type=\"hidden\" name=\"refresh_classes\" value=\"true\">\n";
        	echo "<input type=\"submit\" name=\"refresh_classes\" value=\"".gettext("Rafraichir")."\">\n";
        	echo "<u onmouseover=\"return escape".gettext("('Choisir les classes que l\'on souhaite rafra&#238;chir,<br>par exemple suite &#224; l\'ajout de nouveaux &#233;l&#232;ves.<br> En Cas de migration d\'une ann&#233;e &#224; une autre, les dossiers de l\ann&#233;e pr&#233;c&#233;dente des &#233;l&#232;ves seront copi&#233;s dans un sous-dossier Archive dans le dossier de la nouvelle ann&#233;e.<br> Si l\'&#233;l&#232;ve n\'est plus dans l\'&#233;tablissement, son dossier est cach&#233;.<br>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
        	echo "<input type=\"submit\" name=\"clean_classes\" value=\"".gettext("Nettoyer")."\">\n";
        	echo "<u onmouseover=\"return escape".gettext("('Choisir les classes que l\'on souhaite nettoyer, par exemple suite &#224; l\'ajout de nouveaux &#233;l&#232;ves.<br> En Cas de migration d\'une ann&#233;e &#224; une autre, les dossiers de l\ann&#233;e pr&#233;c&#233;dente des &#233;l&#232;ves seront copi&#233;s dans un sous-dossier Archive dans le dossier de la nouvelle ann&#233;e.<br> Si l\'&#233;l&#232;ve n\'est plus dans l\'&#233;tablissement, son dossier est cach&#233;.<br>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
//        	echo "<input type=\"hidden\" name=\"delete_folders_classes\" value=\"true\">\n";
        	echo "<input type=\"submit\" name=\"delete_folders_classes\" onClick=\"return areyousure()\" value=\"".gettext("Supprimer")."\">\n";
        	echo "<u onmouseover=\"return escape".gettext("('<br><b>Attention, la suppression entrainera la perte des donn&#233;es de la classe.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
        	echo "</form>\n";
		// V&#233;rification selection d'au moins une classe
        	if ( ($refresh_classes || $delete_folders_classes || $clean_classes) && count($old_RessourcesClasses)==0 ) {
          		echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
        	}
	}
/*	echo "</td><td align=\"center\">\n";
      	if   ( count($RessourcesClasses)>15) $size=15; else $size=count($RessourcesClasses);
      	if ( count($RessourcesClasses)>0) {
        	echo "<form action=\"rep_classes.php\" method=\"post\">\n";
        	echo "<select size=\"".$size."\" name=\"refresh_RessourcesClasses[]\" multiple=\"multiple\">\n";
        	for ($loop=0; $loop < count($RessourcesClasses); $loop++) {
          		echo "<option value=".$RessourcesClasses[$loop].">".$RessourcesClasses[$loop]."\n";
        	}
        	echo "</select><br>\n";
        	echo "<input type=\"hidden\" name=\"refresh_classes\" value=\"true\">\n";
        	echo "<input type=\"submit\" value=\"".gettext("Rafraichir")."\">\n";
        	echo "</form>\n";
	}
*/
	echo "</td></tr>\n";
	echo "</table>\n";
	
	echo "<br>";
	echo "<H3>Rafraichir les r&#233;pertoires classes existants</H3>\n";
	echo "<form action=\"rep_classes.php\" method=\"post\">\n";
	$acl_group_profs_classes = exec("cd /var/se3/Classes; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1");
	if ($acl_group_profs_classes=="1") {
		$CHECKED="checked";
	}	
	echo "Droits du groupe Profs : ";
	echo "<input type=\"checkbox\" name=\"acl_group_profs\" $CHECKED>";
	echo "<u onmouseover=\"return escape".gettext("('Permet de donner tous les droits (ACL) sur les classes existantes &#224; tous les membres du groupe Profs. Tous les profs ont tous les droits sur toutes les classes.<br>Si vous souhaitez donner un droit au groupe Profs sur une classe particuli&#232;re, vous devez passer par <i>Droits sur fichiers</i><br><b>Attention : Le fonctionnement normal est d\'avoir la case non coch&#233;e. Les profs ont alors les droits uniquement sur leurs classes.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>";
        echo "<BR><BR>";
	echo "<input type=\"hidden\" name=\"refresh_folders_classes\" value=\"true\">\n";
        echo "<input type=\"submit\" onClick=\"alert('Cette op&#233;ration peut &#234;tre tr&#232;s longue !')\"  value=\"".gettext("Rafraichir toutes les classes")."\">\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de reforcer les droits (ACL) sur les classes existantes.<br><b>Attention : Cette op&#233;ration peut &#234;tre longue.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>";
        echo "</form>\n";


} // Fin if is_admin
include ("pdp.inc.php");
?>
