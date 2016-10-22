<?php


   /**

   * Cree un nouveau parc
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs Equipe Tice acad&#233;mie de Caen

   * @Licence Distribue selon les termes de la licence GPL

   * @note

   */

   /**

   * @Repertoire: parcs/
   * file: create_parc.php

  */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');



$parc=$_POST['parc'];
if ($parc==""){ $parc=$_GET['parc']; }
$newparc=$_POST['newparc'];
$create_parc=$_POST['create_parc'];
$createtemplateparc=$_POST['createtemplateparc'];
$new_computers=$_POST['new_computers'];
if($new_computers=="") { $new_computers=$_GET['new_computers']; }
$creationdossiertemplate=$_POST['creationdossiertemplate'];
if ($creationdossiertemplate=="") { $creationdossiertemplate=$_GET['creationdossiertemplate']; }


if (is_admin("computers_is_admin",$login)=="Y") {

	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Ajout_de_machines";

	// Titre
	echo "<h1>".gettext("Ajout - Cr&#233;ation ")."</h1>";

	// Affichage du formulaire de s&#233;lection de parc
	if ((!isset($parc))&&(!isset($newparc))) {
		// Ajout de nouvelles machines dans les parcs
		echo "<H3>".gettext("S&#233;lection du parc &#224; alimenter")."</H3>";
		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if ( count($list_parcs)>0) {
			echo "<FORM method=\"post\" action=\"create_parc.php\">\n";
			echo "<SELECT NAME=\"parc\" SIZE=\"1\">";
			for ($loop=0; $loop < count($list_parcs); $loop++) {
				echo "<option value=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."\n";
			}
			echo "</SELECT>&nbsp;&nbsp;\n";

			echo "<input type=\"hidden\" name=\"create_parc\" value=\"$create_parc\">\n";
			for ($loop=0; $loop < count($new_computers); $loop++) {
				echo "<input type=\"hidden\" name=\"new_computers[]\" value=\"".$new_computers[$loop]."\">\n";
			}

			echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "<u onmouseover=\"return escape".gettext("('S&#233;lectionner un parc dans lequel vous souhaitez ajouter des machines.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
			echo "</FORM>\n";
		}
		echo "<BR>";


		// Cr&#233;ation de nouveau parc

		// on ne propose pas de creer un nouveau parc si la page d'origine est recherche
		if ($new_computers == "") {
			echo "<H3>".gettext("Cr&#233;ation d'un nouveau parc")."</H3>";
			echo "<FORM method=\"post\" action=\"create_parc.php\">\n";
			echo "<INPUT TYPE=\"text\" SIZE=\"10\" name=\"newparc\">\n";

			echo "<u onmouseover=\"return escape".gettext("('Indiquer le nom du nouveau parc que vous souhaitez cr&#233;er. Ne pas utiliser de caract&#232;res &#233;tranges.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";

			//=============================================================
			//AJOUT
			echo gettext("Cr&#233;er le dossier de template pour ce parc ?");
			echo "<u onmouseover=\"return escape".gettext("('La cr&#233;ation du template associ&#233;, permet de mettre en place des scripts de connexion pour ce parc. Il est toujours possible de le cr&#233;er apr&#232;s.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
			echo gettext("Oui:")."<INPUT TYPE=\"radio\" VALUE=\"yes\" name=\"createtemplateparc\"> - \n";
			echo "<INPUT TYPE=\"radio\" VALUE=\"no\" name=\"createtemplateparc\" CHECKED>:".gettext("Non")."<br>\n";
			//=============================================================

			echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "</FORM>\n";


			// Creation des templates si pas deje crees
			echo "<br>";
			echo "<H3>".gettext("Cr&#233;er les dossiers de template pour les parcs existants")."</H3>";
			$list_parcs=search_machines("objectclass=groupOfNames","parcs");
			if ( count($list_parcs)>0) {
				echo "<FORM method=\"post\" action=\"create_parc.php\">\n";
				echo "<table>\n";
				echo "<tr><td valign=\"top\">".gettext("Cr&#233;er les dossiers pour les parcs:")." </td>\n";
			        if (count($list_parcs)>10) $size=10; else $size=count($list_parcs);
				echo "<td><SELECT NAME=\"parc[]\" SIZE=\"$size\" multiple=\"multiple\">";
				$cpt_verif=0;
				for ($loop=0; $loop < count($list_parcs); $loop++) {
					$tmpparc=$list_parcs[$loop]["cn"];
					if(!file_exists("/home/templates/$tmpparc")){
						echo "<option value=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."</option>\n";
						$cpt_verif++;
					}
				}

				echo "</SELECT></td></tr>\n";
				echo "</table>\n";
				echo "<input type=\"hidden\" name=\"creationdossiertemplate\" value=\"oui\">\n";
				// echo "<input type=\"hidden\" name=\"parc\" value=\"temoin\">\n";
				echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
				echo "</FORM>\n";
				if($cpt_verif==0){
					echo "<p>".gettext("Tous les parcs existants ont leur dossier de template cr&#233;&#233;.")."</p>\n";
				}
				//echo "<p>Dans le cas o&#151; le dossier de template existe d&#233;j&#224;, il n'est pas modifi&#233; et rien n'est ajout&#233;.</p>";
			}
			/**********************************************************************************/
		}

	// Debut du traitement
	} else {
		// Affichage du formulaire de remplissage du parc
	  	if ($creationdossiertemplate!="oui") {
			if ( !$create_parc) {
				if (isset($newparc)) {

					//=============================================================
					//PROPOSITION:
					//Passer d'autorit&#233; en minuscules le nom du parc
					$newparc=strtolower($newparc);


					if(strlen($newparc)==0){
						echo "<p>".gettext("Le nom du parc ne doit pas &#234;tre vide.")."</p>\n";
						include ("pdp.inc.php");
						exit;
					}

					//On pourrait meme ajouter un test ereg plus haut pour exclure les caracteres speciaux
					//(je suppose que le test est aussi fait au niveau du script PERL plus bas;o)
					if(strlen(preg_replace("/[0-9a-z_]/","",$newparc))!=0){
						echo "<p>".gettext("Le nom du parc propos&#233; comporte des caract&#232;res non valides.")."<br>\n";
						echo gettext("Veuillez n'utiliser que des caract&#232;res alphanum&#233;riques en minuscules (<i>surtout les chiffres;o</i>) et &#233;ventuellement le caract&#232;re '_'.")."</p>\n";
						echo "<br><center>";
						echo "<a href=create_parc.php>Retour</a>";
						echo "</center>";
						include ("pdp.inc.php");
						exit;
					} else {
						//=============================================================
						// Cr&#233;ation d'un nouveau parc
						echo gettext("Cr&#233;ation du parc ").$_POST['newparc'];
						echo "<br>";
						echo gettext("Vous devez obligatoirement ajouter une machine dedans.");
						echo "<u onmouseover=\"return escape".gettext("('Vous devez obligatoirement mettre une machine dans le nouveau parc, sinon il ne sera pas cr&#233;&#233;.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
						echo "<BR>\n";
						$parc=$newparc;
						//=============================================================
					}
				}

				$filtrecomp=isset($_POST['filtrecomp']) ? $_POST['filtrecomp'] : "";

				echo "<H3>".gettext("Alimentation du parc")." <U>$parc</U></H3>";
				// Filtrage des noms
				echo "<FORM action=\"create_parc.php\" method=\"post\">\n";
				echo "<P>".gettext("Lister les noms contenant: ");
				echo "<INPUT TYPE=\"text\" NAME=\"filtrecomp\"\n VALUE=\"$filtrecomp\" SIZE=\"8\">";

				echo "<input type=\"hidden\" name=\"createtemplateparc\" value=\"$createtemplateparc\">\n";
				if (isset($newparc)) {
					echo "<input type=\"hidden\" name=\"newparc\" value=\"$newparc\">\n";
				}
				if (isset($parc)) {
					echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\">\n";
				}
				echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
				echo "</FORM>\n";
				// Initialisation:
				$list_new_machines=array();
				// Lecture des membres du parc
				$mp=gof_members($parc,"parcs",1);
				// Creation d'un tableau des nouvelles machines &#224; int&#233;grer
				if ($filtrecomp == '') $filtrel = '*';
						else $filtrel = "*$filtrecomp*";
				$list_machines=search_machines("(&(cn=$filtrel)(objectClass=ipHost))","computers");
				// tri des machines d&#233;ja pr&#233;sentes dans le parc
				$lmloop=0;
				$mpcount=count($mp);
				for ($loop=0; $loop < count($list_machines); $loop++) {
					$loop1=0;
					$mach=$list_machines[$loop]["cn"];
					while (("$mp[$loop1]" != "$mach") && ($loop1 < $mpcount)) $loop1++;
					if ("$mp[$loop1]" != "$mach") $list_new_machines[$lmloop++]=$mach;
				}
				// Affichage menu de s&#233;lection des machines &#224; ajouter au parc
				if  ( count($list_new_machines)>15) $size=15; else $size=count($list_new_machines);
				if ( count($list_new_machines)>0) {
					sort($list_new_machines);
					$form = "<form action=\"create_parc.php\" method=\"post\">\n";
					$form.="<p>".gettext("S&#233;lectionnez les nouvelles machines &#224; int&#233;grer au parc:")."</p>\n";
					$form.="<p><select size=\"".$size."\" name=\"new_computers[]\" multiple=\"multiple\">\n";
					echo $form;
					for ($loop=0; $loop < count($list_new_machines); $loop++) {
						if ("$list_new_machines[$loop]" != "$netbios_name") echo "<option value=\"".$list_new_machines[$loop]."\">".$list_new_machines[$loop];
					}
					$form="</select></p>\n";

					$form.="<input type=\"hidden\" name=\"createtemplateparc\" value=\"$createtemplateparc\">\n";
					$form.="<input type=\"hidden\" name=\"newparc\" value=\"$newparc\">\n";
					$form.="<input type=\"hidden\" name=\"create_parc\" value=\"true\">\n";
					$form.="<input type=\"hidden\" name=\"parc\" value=\"$parc\">\n";
					$form.="<input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">\n";
					$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
					$form.="</form>\n";
					echo $form;
				} else {
					$message =  gettext("Il n'y a pas de nouvelles machines &#224; ajouter !");
					echo $message;
				}
			} else {

				// Remplissage du parc dans l'annuaire
				// Et &#233;ventuellement cr&#233;ation
				// plus cr&#233;ation du template
				echo "<H3>".gettext("Alimentation du parc")." <U>$parc</U></H3>";
				echo "<P>".gettext("Vous avez s&#233;lectionn&#233; "). count($new_computers).gettext(" machine(s)")."<BR>\n";
				if((count($new_computers)=="0") && ($_POST['newparc']!="")) {
					echo "<br>";
					echo gettext("Vous n'avez pas s&#233;lectionn&#233; au moins une machine.<br>Le parc ne sera pas cr&#233;&#233;.");
				}
				for ($loop=0; $loop < count($new_computers); $loop++) {
					$computer=$new_computers[$loop];
					$cDn = "cn=".$computer.",".$computersRdn.",".$ldap_base_dn;
					$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;

					// Cr&#233;ation du template au premier passage uniquement
					if(($_POST['createtemplateparc']=="yes") && $loop==0) {
						echo "<p>".gettext("Cr&#233;ation du dossier du template de parc ");
						echo $_POST['newparc'];
						echo "</p>";
						exec ("/bin/bash /usr/share/se3/scripts/createtemplateparc.sh \"$newparc\"");
					}

					//echo gettext("Ajout de l'ordinateur")." $computer ".gettext("au parc")." <U>$parc</U><BR>";
					echo gettext("Ajout de l'ordinateur")."<a href='show_histo.php?selectionne=2&mpenc=$computer' title='Voir les connexions'> $computer </a>".gettext("au parc")." <U><a href='show_parc.php?parc=$parc' title='Voir les machines du parc.'>$parc</a></U> <a href='action_parc.php?parc=$parc' title='Action sur les stations du parc'><img src='../elements/images/magic.png' width='22' height='24' /></a><br />";

					// Si on est en train de cr&#233;er un nouveau parc
					if ($newparc!="") {
						exec ("/usr/share/se3/sbin/entryAdd.pl \"cn=$newparc,$parcsRdn,$ldap_base_dn\" \"cn=$newparc\" \"objectClass=groupOfNames\" \"member=$cDn\"");
						exec ("/usr/share/se3/sbin/printers_group.pl");

						 // Lance le script pour wpkg
						 $script_wpkg="/usr/share/se3/scripts/update_hosts_profiles_xml.sh";
						if (file_exists($script_wpkg)) {
							exec ("/bin/bash /usr/share/se3/scripts/update_hosts_profiles_xml.sh ou=Computers ou=Parcs $ldap_base_dn");
							exec ("/bin/bash /usr/share/se3/scripts/update_droits_xml.sh");
						}
						$newparc="";
					} else {
						// Sinon on ajoute simplement
						exec ("/usr/share/se3/sbin/groupAddEntry.pl \"$cDn\" \"$pDn\"");

						// NJ 10-2004 reconstruction des partages imprimantes par parc
						exec ("/usr/share/se3/sbin/printers_group.pl");

						// On relance le script pour italc
						exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");

						// Lance le script pour wpkg
						$script_wpkg="/usr/share/se3/scripts/update_hosts_profiles_xml.sh";
						if (file_exists($script_wpkg)) {
							exec ("/bin/bash /usr/share/se3/scripts/update_hosts_profiles_xml.sh ou=Computers ou=Parcs $ldap_base_dn");
							exec ("/bin/bash /usr/share/se3/scripts/update_droits_xml.sh");
						}
						echo "<BR>";
					}
				}

				echo "<BR><BR><CENTER>\n";
				echo "<A HREF=\"show_parc.php?parc=$parc\">Retour</A>";
			}
		}

		// Cr&#233;ation des templates apres
		if($creationdossiertemplate=="oui") {
			echo "<H3>".gettext("Cr&#233;ation des dossiers de parc")."</H3>\n";
			if(count($parc)==0){
				echo "<p><b>".gettext("Erreur").":</b> ".gettext("Vous n'avez pas s&#233;lectionn&#233; de parc;o).")."</p>";
			} else {
				for($loop=0;$loop<count($parc);$loop++){
					echo "<p>".gettext("Cr&#233;ation du dossier de template pour le parc")." $parc[$loop]</p>\n";
					exec ("/bin/bash /usr/share/se3/scripts/createtemplateparc.sh \"$parc[$loop]\"");
				}
			}

		}



	}
}

include ("pdp.inc.php");
?>
