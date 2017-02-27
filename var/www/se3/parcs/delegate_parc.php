<?php


   /**
   
   * permet d'attribuer la gestion d'un template a un utilisateur
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs sandrine dangreville matice creteil aout 2005

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: delegate_parc.php

  */	




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


/**

* Met a jour le fichier /var/se3/unattended/install/wpkg/droits.xml pour wpkg

* @Parametres
* @Return 
*/


function update_wpkg() {
	// Met a jour le fichier /var/se3/unattended/install/wpkg/droits.xml pour wpkg
	$wpkgDroitSh="/usr/share/se3/scripts/update_droits_xml.sh";
	if (file_exists($wpkgDroitSh)) exec ("$wpkgDroitSh");
}


/**

* Affiche un bouton retour vers la page delegate_parc.php

* @Parametres
* @Return
*/

function retour_delegate() {
	echo "<p><a href=\"delegate_parc.php?action=liste\">".gettext("Voir la liste des d&#233;l&#233;gations en cours")."</a></p>\n";
}



/**

* test si $salles a un template et retourn true

* @Parametres
* @Return  true si on a bien un template
*/

function is_template($salles) {
	if(is_dir("/home/templates/".$salles)) return true;
}



if (is_admin("computers_is_admin",$login)=="Y") {

	// Aide
	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#D.C3.A9l.C3.A9gation_de_parc";

	// Titre
	echo "<h1>".gettext("D&#233;l&#233;gation de parc")."</h1>";

	// On teste si il existe des parcs

	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
	if ( count($list_parcs)==0) {
		echo "<br><br>";
		echo gettext("Il n'existe aucun parc. Vous devez d'abord cr&#233;er un parc");
		exit;
	}	

	if (!(is_admin("computers_is_admin",$login)=="Y") and (!is_admin("parc_can_manage",$login)=="Y")) {
		echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");
		exit;
	}

	//************************Definition des variables*******************
	$user=isset($_GET['nouveau']) ? $_GET['nouveau'] : NULL;
	$salles=isset($_POST['salles']) ? $_POST['salles'] : (isset($_GET['salles']) ? $_GET['salles'] : NULL);
	$action=isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : "");
	$nouveau=isset($_GET['nouveau']) ? $_GET['nouveau'] : NULL;

	$template=is_template($salles);

	//test pour savoir si on a choisi un parc ou un template ( pour affichage)
	if ($template) {
		$aide= aide(gettext("Si vous avez choisi un template et une d&#233;l&#233;gation forte, ceci implique que vous voulez donnez acc&#232;s au fichier logon.bat et au menu Clients Windows &#224; cet utilisateur."),"<img src=\"../elements/images/system-help.png\" alt=\"\" />");
		$mot="template";
	} else { $mot="parc"; }

	$parcs=array();

	switch ($action) {
	//************Voir les delegations en cours***************************
	default:
		if ((is_admin("computers_is_admin",$login)=="N") and (is_admin("parc_can_manage",$login)=="N")) { exit; }

		echo "<h3>".gettext("D&#233;l&#233;gations en cours")." </h3>";
	
		if (is_admin("computers_is_admin",$login)=="Y") {
			$query="select * from delegation order by login asc"; 
		} else {
			$parc_du_login=list_parc_delegate($login);
		
			foreach ($parc_du_login as $parc) {
				if ($ajout_parc) {
					$ajoutparc=$ajoutparc." and parc='$parc' "; 
				} else { $ajoutparc=" parc='$parc' "; }
			}
			$query="select * from delegation  where $ajoutparc order by login asc;";
			// echo $query;
		}

		$result=mysqli_query($GLOBALS["___mysqli_ston"], $query) or die("Impossible d'acc&#233;der &#224; la table");
		$ligne=mysqli_num_rows($result);

		if ($ligne==0) {
			echo gettext("Aucune d&#233;l&#233;gation en cours");
		} else {
			echo "<table border=1 align=center width=\"50%\">\n";
			echo "<tr><td align=center class=menuheader colspan=\"6\" height=\"30\" >".gettext("R&#233;capitulatif des d&#233;l&#233;gations par parc/template")."</td>\n";
			echo "</tr>\n";
			echo "<tr><td class=menuheader height=\"30\" align=center>".gettext("Utilisateur")."</td>\n";
			echo "<td class=menuheader align=center>".gettext("Parc/Template")."</td>\n";
			echo "<td class=menuheader colspan=3 align=center>".aide(gettext("Deux niveaux sont possibles par parc: <ul><li>D&#233;l&#233;gation forte: on peut agir sur le parc</li><li>D&#233;l&#233;gation faible: on peut voir des informations sur le parc</li></ul>"),gettext("Niveau de d&#233;l&#233;gation"))."</td></tr>\n";
			$liste_delegate = array();;

			//$last_user="";
			while ($row=mysqli_fetch_row($result)) {
				if ((isset($last_user)) and ($last_user) and ($last_user<>$row[1])) { echo "<tr><td class=menuheader colspan=\"6\"></td></tr>\n";}
				array_push($liste_delegate,$row[1]);
				echo "<tr><td align=center>".$row[1]."</td>\n";
				echo "<td align=center>".$row[2]."</td>\n";
				echo "<td align=center>";
				if ($row[3]=="manage") { 
					echo gettext("D&#233;l&#233;gation forte")."</td>"; 
					if (is_admin("computers_is_admin",$login)=="Y") {
						echo "<td align=center><a href=\"delegate_parc.php?nouveau=$row[1]&salles=$row[2]&action=view\">";
						echo "<img src=\"../elements/images/stock_bottom.png\" alt=\"".gettext("Diminuer le niveau de d&#233;l&#233;gation")."\" title=\"".gettext("Diminuer le niveau de d&#233;l&#233;gation")."\" width=\"16\" height=\"16\" border=\"0\" /></a></td>\n";
						echo "</td>\n";
					}
				}

				if ($row[3]=="view") { 
					echo gettext("D&#233;l&#233;gation faible")."</td>\n";
					
					if (is_admin("computers_is_admin",$login)=="Y") { 
						echo"<td align=center><a href=\"delegate_parc.php?nouveau=$row[1]&salles=$row[2]&action=manage\">";
						echo "<img src=\"../elements/images/stock_top.png\" alt=\"".gettext("Augmenter le niveau de d&#233;l&#233;gation")."\" title=\"".gettext("Augmenter le niveau de d&#233;l&#233;gation")."\" width=\"16\" height=\"16\" border=\"0\" /></a></td>\n";
						echo "</td>\n";
					}
				}

				if ((is_admin("computers_is_admin",$login)=="Y") or ((this_parc_delegate($login,$row[2],"manage")) and ($row[3]=="view"))) {
					echo "<td align=center><a href=\"delegate_parc.php?nouveau=$row[1]&salles=$row[2]&action=nodelegate\">";
					echo "<img src=\"../elements/images/edittrash.png\" alt=\"".gettext("Supprimer cette d&#233l&#233gation")."\" title=\"".gettext("Supprimer cette d&#233;l&#233;gation")."\" width=\"16\" height=\"16\" border=\"0\" /></a></td>\n";
					echo "</td>\n";
				} else { echo "<td>&nbsp;</td>\n"; }

				//echo "<td align=center><a href=\"delegate_parc.php?nouveau=$row[1]&salles=$row[2]&action=manage\"><img src=\"../elements/images/stock_top.png\" alt=\"\" title=\"Augmenter le niveau de d&#233;l&#233;gation\" width=\"16\" height=\"16\" border=\"0\" /></a></td></td>";
				//echo "<td align=center><a href=\"delegate_parc.php?nouveau=$row[1]&salles=$row[2]&action=view\"><img src=\"../elements/images/stock_bottom.png\" alt=\"\" title=\"Diminuer le niveau de d&#233;l&#233;gation\" width=\"16\" height=\"16\" border=\"0\" /></a></td></td>";

				echo"</tr>";
				$last_user=$row[1];
			}
			echo "</table><br><br>\n";
		}
		include ("pdp.inc.php");
		//break;

		//*******************Choix d'une salle********************************
		if (!$salles) {
			//if (is_admin("computers_is_admin",$login)=="Y") { echo "<h1>D&#233;l&#233;gations</h1><a href=\"delegate_parc.php?action=liste\">Voir les d&#233;l&#233;gations en cours</a>"; }

			//choix du template
			echo "<h3>".gettext("Choisir un nouveau parc &#224; d&#233;l&#233;guer")." </h3>";

			$list_parcs=search_machines("objectclass=groupOfNames","parcs");
			sort($list_parcs);

			echo "<FORM method=\"post\" action=\"delegate_parc.php\">\n";
			echo "<input type=\"hidden\" name=\"action\" value=\"new\" />";	
			echo "<SELECT NAME=\"salles\" SIZE=\"1\" onchange=submit()>";
			echo "<option value=\"\">S&#233;lectionner</option>";
			if ( count($list_parcs)>0) {
				for ($loop=0; $loop < count($list_parcs); $loop++) {
					if ((is_admin("computers_is_admin",$login)=="Y") or (this_parc_delegate($login,$list_parcs[$loop]["cn"],"manage"))) {
						array_push($parcs,$list_parcs[$loop]["cn"]); 
						echo "<option value=\"".$list_parcs[$loop]["cn"]."\"";
						if ((isset($parc))&&($parc==$list_parcs[$loop]["cn"])) { echo " selected"; }
						echo ">".$list_parcs[$loop]["cn"]."\n";
						echo "</option>";
					}
				}
			}

			echo "</SELECT>&nbsp;&nbsp;\n";
			
			echo "<u onmouseover=\"return escape".gettext("('Un parc correspond &#224; un groupe de machines. <br>Il peut vous servir &#224; regrouper vos machines (par salle par exemple).<br>Une machine peut appartenir &#224; plusieurs parcs en fonction de vos besoins. Il peut &#234;tre li&#233; &#224; un <font color=#FF0000>template</font> du m&#234;me nom si vous avez cr&#233;&#233; un r&#233;pertoire correspondant dans le r&#233;pertoire Admhomes/templates. Vous pourrez alors agir sur les machines du parc en utilisant les possibilit&#233;s des templates.')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";

			echo "</form>\n";
			
			// Affiche les templates a deleguer
			if (is_admin("computers_is_admin",$login)=="Y")  {
				echo "<h3>".gettext("Choisir un template &#224; d&#233;l&#233;guer")."</h3>";
				$handle=opendir('/home/templates');
				
				echo "<FORM method=\"post\" action=\"delegate_parc.php\">\n";
				echo "<input type=\"hidden\" name=\"action\" value=\"new\" />";
				echo "<SELECT NAME=\"salles\" SIZE=\"1\" onchange=submit()>";
				echo "<option value=\"\">S&#233;lectionner</option>";

				while ($file = readdir($handle)) {
					if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser') {
						if  ((is_admin("computers_is_admin",$login)=="Y") and (!in_array($file,$parcs))) { 
							echo "<option value=\"$file\">$file</option>";
							$test_affiche++;
						}
					}
				}
				echo "</select>\n";
				echo "<u onmouseover=\"return escape".gettext("('Un template peut correspondre &#224; un groupe de machines, un groupe de personnes.Il permet d\'agir sur le login et sur le bureau de l\'utilisateur. Les templates s\'appliquent dans l\'ordre suivant: <ul><li>base</li><li> groupe</li><li> parcs</li></ul>. Il est possible de faire des doubles templates de type:<ul><li> utilisateur@@machine</li><li> utilisateur@@parc_machine</li><li> groupe_utilisateur@@machine</li><li> groupe_utilisateur@@parc_machine</li></ul> N\'h&#233;sitez pas &#224; consulter la documentation sur le site sambaedu.org , rubrique Point de vue de l\'administrateur, Le r&#233;pertoire templates.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
				echo "</form>\n";
				closedir($handle); 
			}
		}
		break;

	//*********************Choix d'un utilisateur******************************
	case "new":
		if (!$nouveau) {   //choix du user
			echo "<h3>".gettext("Vous avez choisi de d&#233;l&#233;guer la gestion de")." $salles $aide";
			echo "</h3><form action=\"delegate_parc.php\" name=\"visu\" method=\"get\">\n";
			echo gettext("Indiquez un utilisateur : ");
			echo "<input type=\"text\" name=\"nouveau\" value=\"$nouveau\" size=\"20\" >\n";
			echo "<input type=\"hidden\" name=\"salles\" value=\"$salles\" />";

			echo "<input type=\"button\" value=\"Recherche \" onclick=\"popuprecherche('search_user.php','popuprecherche','width=500,height=500');\">";
			echo "<br><br>  ";
			
			if (is_admin("computers_is_admin",$login)=="Y") {
				echo "<input type=\"radio\" name=\"action\" id=\"action_manage\" value=\"manage\"  >\n";
				echo "<label for='action_manage'>".gettext("D&#233;l&#233;guer totalement la gestion de")." <b> $salles </b></label><br>\n";
			}

			if ((is_admin("computers_is_admin",$login)=="Y") or (this_parc_delegate($login,$salles,"manage")))  {
				//pour les templates, on est automatiquement dans une delegation forte !!!!!!!!!!!!
				//en effet, la delegation sur les templates donne acces a logon.bat et a clients windows
				//des qu'un parc a un template associe il est considere comme un template
				//if (is_parc($salles)) {
				echo "<input type=\"radio\" name=\"action\" id=\"action_view\" value=\"view\" CHECKED />\n";
				echo "<label for='action_view'>".gettext("Permettre &#224; l'utilisateur de suivre le parc ( pas d'action possible)")."</label>";
				//}
				echo "<br><input type=\"radio\" name=\"action\" id=\"action_nodelegate\" value=\"nodelegate\">\n";
				echo "<label for='action_nodelegate'>".gettext("Retirer la d&#233;l&#233;gation de")." <b> $salles </b> ".gettext("pour cet utilisateur")."</label>";
			}

			echo"<br><br><input type=\"submit\" name=\"submit\" value=\"".gettext("Envoyer")."\" />\n";
			echo "</form>\n";
		}
		break;

	//**********************************action manage***********************
	case "manage":
		//********************************action commun a: manage, view, nodelegate
		if (!($nouveau) or (!$salles)) { 
			echo gettext("Vouz devez choisir un")." $mot ".gettext("et un utilisateur");
			retour_delegate(); 
			exit ;
		}

		echo "<h2>".gettext("D&#233l&#233gation du")." $mot $salles $aide</h2>\n";
		
		if (is_admin("computers_is_admin",$user)=="Y") { 
			echo "<font color=#FF0000>".gettext("Cet utilisateur b&#233;n&#233;ficie du  droit computers_is_admin , il a d&#233;j&#224; tous les droits sur les parcs et les templates")."</font><br>";
		}

		if (is_admin("computers_is_admin",$login)=="N") { exit; }
		
		if(@is_dir("/home/$user")) {
		}  else  {
			echo "<font color=#FF0000>".gettext("Cet utilisateur n'a pas de r&#233;pertoire personnel. <br> Il est impossible de placer le raccourci du")." $mot ".gettext("sur son bureau.<BR> Demandez &#224; cet utilisateur de se connecter au moins une fois sur le domaine.")."</font>";
			exit;
		}

		if (is_admin("parc_can_manage",$user)=="Y") {
			echo "<p>".gettext("Cet utilisateur b&#233n&#233ficie d&#233;j&#224; d'une d&#233;l&#233;gation forte")."</p>";
		} else {
			$right="parc_can_manage";
			$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
			$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";
			$pDndel="cn=parc_can_view,$rightsRdn,$ldap_base_dn";
			exec ("/usr/share/se3/sbin/groupAddEntry.pl \"$cDn\" \"$pDn\"");
			//   exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDndel\"");

			echo gettext("La d&#233;l&#233;gation forte (parc_can_manage) est prise en compe: Plus d'&#233;l&#233;ments du menu sont accessibles &#224;")." <b>$user </b> ".gettext("dans")." <b>$salles</b><br>\n";
			echo "<ul><li>".gettext("Clients Windows")."</li>\n";
			echo "<li>".gettext("Inventaire")."</li>\n";
			echo "<li>.....</li></ul><br>";
			// echo "Le droit delegation faible ( parc_can_view) a ete retire";
		}


		//ajout dans la table delegation si necessaire
		$query="select parc from delegation where login='$user' and parc='$salles';";
		$result= mysqli_query($GLOBALS["___mysqli_ston"], $query);
	
		if ($result) { 
			$ligne= mysqli_num_rows($result);
			if ($ligne>0) {
				if (is_template($salles)) { 
				exec ("/usr/bin/sudo /usr/share/se3/scripts/delegate_parc.sh \"$salles\" \"$user\" \"nodelegate\""); 
			}

				echo "<p><font color=#FF0000>".gettext("L'utilisateur")." <b>$user</b> ".gettext("avait d&#233;j&#224; une d&#233;l&#233;gation sur ce")." $mot</font></p>\n";
				$query_suppr="delete from delegation where login='$user' and parc='$salles';";
				$resul_suppr=mysqli_query($GLOBALS["___mysqli_ston"], $query_suppr);
			}
	
			$query_verif="select parc from delegation where login='$user' and niveau='view';";
			$result_verif= mysqli_query($GLOBALS["___mysqli_ston"], $query_verif);
	
			if ($result_verif) {
				$ligne_verif= mysqli_num_rows($result_verif);
				if ($ligne_verif==0) {
					$right="parc_can_view";
					$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
					$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";
					exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\""); 
				}
			}
		}
		$query_insert="Insert into delegation (ID,login,parc,niveau) VALUES ('','$user','$salles','manage');";
		$result_insert=mysqli_query($GLOBALS["___mysqli_ston"], $query_insert) or die ("Erreur d'&#233;criture dans la table");

		if ($template) {
			//echo "/usr/bin/sudo /usr/share/se3/scripts/delegate_parc.sh \"$salles\" \"$user\" \"delegate\"";
			exec ("/usr/bin/sudo /usr/share/se3/scripts/delegate_parc.sh \"$salles\" \"$user\" \"delegate\"");
			echo "<br><h3><b>$user</b>".gettext("verra &#233;galement appara&#238;tre dans Mes Documents/D&#233;l&#233;gation le r&#233;pertoire")." <b> $salles </b> ".gettext("pour pouvoir g&#233;rer les scripts de d&#233;marrage et les ic&#244;nes des machines du parc")."  $salles .</h3><br>";
		}
		update_wpkg();
		retour_delegate();
		break;


	//**************************************action nodelegate********************************************
	case "nodelegate":

	//********************************action commun a: manage, view, nodelegate
		if (!($nouveau) or (!$salles)) { 
			echo gettext("Vouz devez choisir un")." $mot ".gettext("et un utilisateur");
			retour_delegate();
			exit ;
		}

		echo "<h2>".gettext("D&#233l&#233gation de parcs")."</h2>\n";
			
		if (is_admin("computers_is_admin",$user)=="Y") { 
			echo "<font color=#FF0000>".gettext("Cet utilisateur b&#233;n&#233;ficie du  droit computers_is_admin , il a d&#233;j&#224; tous les droits sur les parcs et les templates")."</font><br>";
		}

		//retirer le champ dans la table
		$query="select parc from delegation where login='$user' and parc='$salles';";
		$result= mysqli_query($GLOBALS["___mysqli_ston"], $query);
	
		if ($result) { 
			$ligne= mysqli_num_rows($result);
			if ($ligne==1) {
				//suppression pour ce parc
				$query_suppr="delete from delegation where login='$user' and parc='$salles';";
				$resul_suppr=mysqli_query($GLOBALS["___mysqli_ston"], $query_suppr);
				echo "<p>".gettext("La d&#233;l&#233;gation est supprim&#233; pour")." <b> $user </b> ".gettext("sur le")." $mot <b> $salles</b></p>\n";
			}
		}
	
		//verification qu'il n'y a pas d'autres delegations en cours

		$query="select parc,niveau from delegation where login='$user';";
		$result= mysqli_query($GLOBALS["___mysqli_ston"], $query);
		
		if ($result) { 
			$ligne= mysqli_num_rows($result);
			if ($ligne>0) {
				//une autre delegation est en cours, on laisse les droits tel quel
				echo "<br><table border=1>\n";
				echo "<tr><td class=menuheader height=\"30\" colspan=\"2\" align=center width=25%>".gettext("Droits restants")."</td></tr>\n";
				echo "<tr><td class=menuheader height=\"30\" align=center>Parc</td><td class=menuheader align=center>".gettext("Droit")."</td></tr>\n";
					
				while ($row=mysqli_fetch_row($result)) {
						echo "<tr><td align=center>$row[0]</td>\n";
					echo "<td align=center>\n";
						
					if ($row[1]=="manage") { echo gettext(" D&#233;l&#233;gation forte"); $fort++;}

					if ($row[1]=="view") { echo gettext("D&#233;l&#233;gation faible"); $faible++;}
					echo "</td></tr>\n";
				}
					
				echo "</table>\n";
			}
		}
	
		//retirer le droit (potentiellement les deux droits) si ce user n'a plus aucun parc delegue !!
		if (((!isset($fort))||(!$fort)) and (is_admin("parc_can_manage",$user)=="Y")) {
			echo "<h3>".gettext("Suppression du droit `D&#233;l&#233;gation forte(parc_can_manage)`")." </h3>\n";
			$right="parc_can_manage";
			$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
			$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";
			exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
		}

		if (((!isset($faible))||(!$faible)) and (is_admin("parc_can_view",$user)=="Y")) {
			echo "<h3>".gettext("Suppression du droit `D&#233;l&#233;gation faible (parc_can_view)`")." </h3>";

			$right="parc_can_view";
			$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
			$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";
			exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
		}

		if (!this_parc_delegate($user,$salles,"manage")) { 
			exec ("/usr/bin/sudo /usr/share/se3/scripts/delegate_parc.sh \"$salles\" \"$user\" \"nodelegate\""); 
		}

		// echo "Commande prise en compte";
		update_wpkg();
		retour_delegate();

		break;

	//*******************************action view*******************************
	case "view":
		//********************************action commun a: manage, view, nodelegate
		if (!($nouveau) or (!$salles)) { 
			echo gettext("Vouz devez choisir un")." $mot ".gettext("et un utilisateur"); 
			retour_delegate(); 
			exit ;
		}

		echo "<h2>".gettext("D&#233l&#233gation du")." $mot: </h2>\n";
	
		if (is_admin("computers_is_admin",$user)=="Y") { 
			echo "<font color=#FF0000>".gettext("Cet utilisateur b&#233;n&#233;ficie du  droit computers_is_admin , il a d&#233;j&#224; tous les droits sur les parcs et les templates")."</font><br>";
		}

		//ajout dans ldap du droit parc_can_view si le droit n'est pas deja mis
			$right="parc_can_view";

		if (is_admin($right,$user)=="Y") {
			echo gettext("Cet utilisateur b&#233n&#233ficie d&#233j&#224 d'une d&#233l&#233gation faible")."<br>";
		} else {
			$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
			$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";

			exec ("/usr/share/se3/sbin/groupAddEntry.pl \"$cDn\" \"$pDn\"");
		}
				
		if (is_template($salles)) { 
			exec ("/usr/bin/sudo /usr/share/se3/scripts/delegate_parc.sh \"$salles\" \"$user\" \"nodelegate\""); 
		}

		// echo "/usr/share/se3/sbin/groupAddEntry.pl \"$cDn\" \"$pDn\"";
		echo gettext("La d&#233;l&#233;gation est prise en compte: Certains &#233;l&#233;ments du menu sont accessibles &#224;")." <b> $user </b> <br>";
		echo "<br><h3>".gettext("L'utilisateur <B> $user</B> b&#233;n&#233;ficie d'une d&#233;l&#233;gation faible, il ne pourra pas modifier vos r&#233;glages.")."</h3>";
		
		//ajout dans la table delegation si necessaire
		$query="select parc from delegation where login='$user' and parc='$salles';";
		$result= mysqli_query($GLOBALS["___mysqli_ston"], $query);
			
		if ($result) { 
			$ligne= mysqli_num_rows($result);
			if ($ligne>0) {
				echo "<p><font color=#FF0000>".gettext("L'utilisateur")." <B> $user</B> ".gettext("avait d&#233j&#224 une d&#233l&#233gation sur le")." $mot <b>$salles</b></font></p>\n";
				$query_suppr="delete from delegation where login='$user' and parc='$salles';";
				$resul_suppr=mysqli_query($GLOBALS["___mysqli_ston"], $query_suppr);
			}
			$query_verif="select parc from delegation where login='$user' and niveau='manage';";
			$result_verif= mysqli_query($GLOBALS["___mysqli_ston"], $query_verif);
			
			if ($result_verif) {
				$ligne_verif= mysqli_num_rows($result_verif);
				if ($ligne_verif==0){
					$right="parc_can_manage";
					$cDn = "uid=$user,$peopleRdn,$ldap_base_dn";
					$pDn = "cn=$right,$rightsRdn,$ldap_base_dn";
					exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
				}                 	
			}
		}

		$query_insert="Insert into delegation (ID,login,parc,niveau) VALUES ('','$user','$salles','view');";
		$result_insert=mysqli_query($GLOBALS["___mysqli_ston"], $query_insert) or die ("Erreur d'&#233;criture dans la table");
		
		update_wpkg();
		retour_delegate();
		
		break;
		// fin case "view":
	}  // fin du switch($action)

}
include ("pdp.inc.php");
?>
