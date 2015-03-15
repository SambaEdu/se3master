<?php

   /**

   * Deploiement et modification des profils thunderbird des postes clients
   * @Version $Id$


   * @Projet LCS / SambaEdu

   * @auteurs  franck.molle@ac-rouen.fr

   * @Licence Distribue selon les termes de la licence GPL

   * @note

   */

   /**

   * @Repertoire: mozilla_profiles
   * file: thunderbird.php

  */



require("entete.inc.php");

//Verification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'autehtification is_admin
require("ihm.inc.php");
require_once ("lang.inc.php");
bindtextdomain('se3-mozilla',"/var/www/se3/locale");
textdomain ('se3-mozilla');

//AUTHENTIFICATION
if (is_admin("computer_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

//aide
$_SESSION["pageaide"]="Gestion_Mozilla#Mozilla_Thunderbird";


$choix=$_POST['choix'];
$config=$_GET['config'];
$autres_gr=$_POST['autres_gr'];
$classe_gr=$_POST['classe_gr'];
$equipe_gr=$_POST['equipe_gr'];
$home=$_POST['home'];
$page_dem=$_POST['page_dem'];
$user=$_POST['user'];

$option=isset($_POST['option']) ? $_POST['option'] : "";


// Titre
echo "<h1>".gettext("D&#233;ploiement mozilla thunderbird")."</h1>\n";


//EVALUE SI UNE SAISIE A ETE EFFECTUEE :
if ($config==""||$config=="init") {


	$form = "<form action=\"thunderbird.php?config=init\" method=\"post\">\n";
	// Form de selection d'actions
	$form .="<H3>".gettext("Deploiement des profils Mozilla Thunderbird :")." </H3>\n";
	$form .= "<SELECT name=\"choix\" onchange=submit()>\n";
	$form .= "<OPTION VALUE='choix'>-----------------------------".gettext(" Choisir ")."---------------------------------</OPTION>\n";

	if($choix=="deploy_all")  {$form .= "<OPTION SELECTED VALUE='deploy_all'>".gettext("D&#233;ployer les profils dans tous les espaces personnels existants")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='deploy_all'>".gettext("D&#233;ployer les profils dans tous les espaces personnels existants")."</OPTION>\n";}

	if($choix=="deploy_grp")  {$form .= "<OPTION SELECTED VALUE='deploy_all'>".gettext("D&#233;ployer les profils dans certains espaces personnels")." </OPTION>\n";}
	else {$form .= "<OPTION VALUE='deploy_grp'>".gettext("D&#233;ployer les profils dans certains espaces personnels")." </OPTION>\n";}


	$form .= "</SELECT>\n";
	$form.="</form>\n";
	echo $form;
	echo "<br>";


	if($choix=="deploy_grp") {
		echo "<form action=\"thunderbird.php?config=suite\" name=\"form2\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_grp\">";

		// Etablissement des listes des groupes disponibles
		affiche_all_groups(left, user);

// 		echo "<h3>Nouvelle page de d&#233;marrage pour Mozilla thunderbird : </h3><INPUT TYPE=\"TEXT\" NAME=\"page_dem\" size=50><br><br>";
//
		
		echo "
		<h3>".gettext("Ecraser les profils Thunderbird m&#234;me s'ils contiennent des donn&#233;es ?")." </h3>
		<INPUT TYPE=\"RADIO\" NAME=\"option\" value=\"force_move\" >".gettext(" Oui ")."<br>
		<INPUT TYPE=\"RADIO\" NAME=\"option\" value=\"no_force\" checked >".gettext(" Non ")."<BR><BR>";

		echo gettext("Par d&#233;faut les profils contenant des donn&#233;es sont ignor&#233;s, mais si vous le d&#233;sirez, vous pouvez forcer leur ecrasement. <br>Une sauvegarde sera alors effectue&#233; dans le r&#233;pertoire profil/appdata de l'espace personnel de l'utilisateur")."<br><br>";

		echo "<input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\">";

		//echo "<input type=\"text\" name=\"choix\" value=\"$choix\" size=\"30\" />";

		echo "</form>";

	}
	elseif($choix=="deploy_all")
	{
		echo "<form action=\"thunderbird.php?config=suite \" name=\"form2\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_all\">";
		echo "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		echo "</form>";
	}


}  else {

	$nomscript=date("Y_m_d_H_i_s");
	$nomscript="tmp_thunderbird_$nomscript.sh";
	$nbr_user=0;
	system ("echo \"#!/bin/bash\n\" > /tmp/$nomscript");


	if($choix=="deploy_all") {
		echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla thunderbird dans les espaces personnels existants :")."</h4>";
		echo "<h4>".gettext("La requ&#234;te sera lanc&#233;e en arri&#232;re-plan dans une minute")."</h4>";
		system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_tb_final.sh all \n\" >> /tmp/$nomscript");
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);
		exec("at -f /tmp/$nomscript now + 1 minute");
	} elseif($choix=="deploy_grp") 	{
		echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla thunderbird dans les espaces personnels s&#233;lectionn&#233;s :")." </h4>";
		//On change la page pour les groupe ou le user selectionne
		if (count($classe_gr) ) {
			foreach ($classe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				echo gettext("Groupe Classe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A> <br>";
				system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_tb_final.sh $grp $option \n\" >> /tmp/$nomscript");
			}
		}

		if (count($equipe_gr) ) {
			foreach ($equipe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				echo gettext("Groupe Equipe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A> <br>";

				system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_tb_final.sh $grp $option \n\" >> /tmp/$nomscript");
			}
		}
		if (count($autres_gr) ) {
			foreach ($autres_gr as $grp){
				echo gettext("Groupe")."<A href=\"/annu/group.php?filter=$grp\">$grp</A> <br>";
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_tb_final.sh $grp $option \n\" >> /tmp/$nomscript");
			}
		}

		//teste si utilisateur saisi pour recherche dans ldap
		if ($user!="")
		{
			//recherche dans ldap si $user est valide
			$tabresult=search_people("uid=$user");
			if(count($tabresult)!=0) {
				//echo "- L'utilisateur $user <br>";
				$nbr_user=$nbr_user+1;
				system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_tb_final.sh $user $option \n\" >> /tmp/$nomscript");
			} else {
				echo "<h4>".gettext("Erreur,")." \"$user\" ".gettext("n'existe pas !")."<h4>";
			}
		}

		//le script se supprime a la fin de son exec
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);

		if($nbr_user>20){
		//execution differee d'une minute pour ne pas attendre la page trop longtemps
			echo "<h4>".gettext("La requ&#234;te sera lanc&#233;e en arri&#232;re-plan dans une minute")."</h4>";
			exec("at -f /tmp/$nomscript now + 1 minute");
			#=========================================================================
			# Ajout: Creation du fichier d'information.
			# Il est modifie par la suite par le script /usr/share/se3/scripts/deploy_mozilla_tb_final.sh
			# Il faut que le dossier /var/www/se3/tmp existe et que www-se3 ait le droit d'y ecrire.
			$fichier_info=fopen('/var/www/se3/tmp/recopie_profils_thunderbird.html','w+');
			fwrite($fichier_info,'<html>
<meta http-equiv="refresh" content="2">
<html>
<body>
<h1 align="center">Traitement des profils</h1>
<p align="center">Le traitement va d&#233;marrer dans la minute qui vient...<br></p>
</body>
</html>');
			fclose($fichier_info);

			# Ouverture d'une fenetre popup:
			echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_thunderbird.html','".gettext("Suivi_recopie_profils_Thunderbird")."','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
			#=========================================================================

		}
		else {
			//execution immediate du script
			system("/tmp/$nomscript");
		}

	}

}
include("pdp.inc.php");
?>
