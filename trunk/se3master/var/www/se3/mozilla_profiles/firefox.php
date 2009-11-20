<?php

   /**
   
   * Deploiement et modification des profils firefox des postes clients 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  franck.molle@ac-rouen.fr
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: mozilla_profiles
   * file: firefox.php

  */	




require("entete.inc.php");

//Verification existence utilisateur dans l'annuaire
require("config.inc.php");
require("ldap.inc.php");

//permet l'autehtification is_admin
require("ihm.inc.php");

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-mozilla',"/var/www/se3/locale");
textdomain ('se3-mozilla');

//AUTHENTIFICATION
if (is_admin("computer_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


//aide
$_SESSION["pageaide"]="Gestion_Mozilla#Mozilla_Firefox";


$choix=$_POST['choix'];
$config=$_GET['config'];
$autres_gr=$_POST['autres_gr'];
$classe_gr=$_POST['classe_gr'];
$equipe_gr=$_POST['equipe_gr'];
$home=$_POST['home'];
$page_dem=$_POST['page_dem'];
$user=$_POST['user'];

/*
echo "valeur de choix : $choix";
echo "valeur de config : $config";*/

// Titre
echo "<h1>".gettext("D&#233;ploiement mozilla firefox")."</h1>";

//EVALUE SI UNE SAISIE A ETE EFFECTUEE: AUTO-APPEL DE LA PAGE APRES FORMULAIRE REMPLI
if ($config==""||$config=="init") {
	//echo "valleur de config : $config<br>";
	//echo "valleur de choix : $choix<br>";
	//echo "valleur de config : $config<br>";
	
	// echo "<body>";
	
	if (file_exists("/var/se3/unattended/install/packages/firefox/firefox-config.bat") or file_exists("/usr/share/se3/logonpy/logon.py")) {
		echo "<a href=\"/mozilla_profiles/firefox-se3-NG.php\">Configuration des profils firefox</a>";  
	}
	
	$form = "<form action=\"firefox.php?config=init\" method=\"post\">\n";
	// Form de selection d'actions
	$form .="<H3>".gettext("D&#233;ploiement ou  modification  des profils Mozilla Firefox :")." </H3>\n";
	$form .= "<SELECT name=\"choix\" onchange=submit()>\n";
	$form .= "<OPTION VALUE='choix'>-------------------------------".gettext(" Choisir ")."-------------------------------</OPTION>\n";

//	$choix=$_POST['choix'];
	if($choix=="deploy_nosave")  {$form .= "<OPTION SELECTED VALUE='deploy_nosave'>".gettext("D&#233;ployer et remplacer les profils existants")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='deploy_nosave'>".gettext("D&#233;ployer et remplacer les profils existants")."</OPTION>\n";}


	if($choix=="deploy_save")  {$form .= "<OPTION SELECTED VALUE='deploy_save'>".gettext("D&#233;ployer et remplacer les profils mais conserver les bookmarks")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='deploy_save'>".gettext("D&#233;ployer et remplacer les profils mais conserver les bookmarks")."</OPTION>\n";}

	if($choix=="modif")  {$form .= "<OPTION SELECTED VALUE='modif'>".gettext("Modifier la page de d&#233;marrage pour un groupe ou un utilisateur")."</OPTION>\n";}
	else {$form .= "<OPTION VALUE='modif'>".gettext("Modifier la page de d&#233;marrage pour un groupe ou un utilisateur")."</OPTION>\n";}

	if($choix=="modif_skel")  {$form .= "<OPTION SELECTED VALUE='modif_skel'>".gettext("Modifier la page de d&#233;marrage de l'utilisateur mod&#232;le")." \"skeluser\"</OPTION>\n";}
	else {$form .= "<OPTION VALUE='modif_skel'>".gettext("Modifier la page de d&#233;marrage de l'utilisateur mod&#232;le")." \"skeluser\"</OPTION>\n";}

	$form .= "</SELECT>\n";
	$form.="</form>\n";
	echo $form;
	echo "<br>";


	if($choix=="modif") {
		echo "<form action=\"firefox.php?config=suite\" name=\"form2\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"$choix\">";

		// Etablissement des listes des groupes disponibles
		$list_groups=search_groups("(&(cn=*) $filter )");
		// Etablissement des sous listes de groupes :
		$j =0; $k =0;
		$m = 0;
		for ($loop=0; $loop < count ($list_groups) ; $loop++) {
			// Classe
			if ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) ) {
				$classe[$j]["cn"] = $list_groups[$loop]["cn"];
				$classe[$j]["description"] = $list_groups[$loop]["description"];
				$j++;}
			// Equipe
			elseif ( preg_match ("/Equipe_/", $list_groups[$loop]["cn"]) ) {
				$equipe[$k]["cn"] = $list_groups[$loop]["cn"];
				$equipe[$k]["description"] = $list_groups[$loop]["description"];
				$k++;}
			// Autres
			elseif (!preg_match ("/^overfill/", $list_groups[$loop]["cn"]) &&
				!preg_match ("/^lcs-users/", $list_groups[$loop]["cn"]) &&
				!preg_match ("/^admins/", $list_groups[$loop]["cn"]) &&
				!preg_match ("/Cours_/", $list_groups[$loop]["cn"]) &&
				!preg_match ("/Matiere_/", $list_groups[$loop]["cn"]) &&
				!preg_match ("/^machines/", $list_groups[$loop]["cn"])) {
				$autres[$m]["cn"] = $list_groups[$loop]["cn"];
				$autres[$m]["description"] = $list_groups[$loop]["description"];
				$m++;
			}
		}

		// Affichage des boites de s&#233;lection des groupes sur lesquels fixer les quotas + choix d'un user sp&#233;cifique
		echo "
		<table align=\"left\" border=\"0\" cellspacing=\"10\">
		<tr>
		<td>".gettext("Classes")."</td>
		<td>".gettext("Equipes")."</td>
		<td>".gettext("Autres")."</td>
		<td>".gettext("Utilisateur sp&#233;cifique")."</td>
		</tr>
		<tr>
		<td valign=\"top\">

		<select name= \"classe_gr[]\" size=\"8\" multiple=\"multiple\">\n";
		for ($loop=0; $loop < count ($classe) ; $loop++) {
			echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"];
		}
		echo "</select>";
		echo "</td>\n";
		echo "<td valign=\"top\">\n";
		echo "<select name= \"equipe_gr[]\" size=\"8\" multiple=\"multiple\">\n";
		for ($loop=0; $loop < count ($equipe) ; $loop++) {
			echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"];
		}
		echo "</select></td>\n";
		echo "<td valign=\"top\">";
		echo "<select name=\"autres_gr[]\" size=\"8\" multiple=\"multiple\">";
		for ($loop=0; $loop < count ($autres) ; $loop++) {
			echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"];
		}
		echo "</select></td>\n";
		echo "<td valign=\"top\"><INPUT TYPE=\"TEXT\" NAME=\"user\" size=20></td>\n";
		echo "</tr></table>\n\n";
		echo "<br><br><br><br><br><br><br><br><br><br>\n";
		echo "<h3>".gettext("Nouvelle page de d&#233;marrage pour Mozilla Firefox :")." </h3>\n";
		echo "<INPUT TYPE=\"TEXT\" NAME=\"page_dem\" size=50><br><br>\n";

		echo "
		<h3>".gettext("Cr&#233;er les espaces personnels s'ils n'existent pas sur la partition")." /home ?</h3>\n
		<INPUT TYPE=RADIO NAME=option value=\"create_homes\" checked > Oui <br>\n
		<INPUT TYPE=RADIO NAME=option value=\"no_create\">".gettext(" Non")." <BR><BR>\n";
	
		echo "<input type=\"submit\" value=\"".gettext("valider")."\">\n
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\">\n";

		//echo "<input type=\"text\" name=\"choix\" value=\"$choix\" size=\"30\" />";



		echo "</form>\n";

	}
	elseif($choix=="modif_skel")
	{
		echo "<form action=\"firefox.php?config=suite \" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"modif_skel\">";
		echo "<h3>".gettext("Nouvelle page de d&#233;marrage pour Mozilla Firefox :")." </h3><INPUT TYPE=\"TEXT\" NAME=\"page_dem\" size=50><br>";
		echo "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		//echo "<input type=\"text\" name=\"config\" value=\"\" size=\"30\" />";
		echo "</form>\n";

	}
	elseif($choix=="deploy_nosave")	{
		echo "<form action=\"firefox.php?config=suite \" name=\"form2\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_nosave\">";
		echo "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		echo "</form>\n";

		echo gettext("si vous fonctionnez avec un slis, v&#233;rifier que son ip est bien d&#233;fini sur cette ");
		echo "<a href=\"../conf_params.php?cat=1\">".gettext("page")."</a>\n";
	}
	elseif($choix=="deploy_save")
	{
		echo "<form action=\"./firefox.php?config=suite\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"choix\" value=\"deploy_save\">";
		echo "<div align='left'><input type=\"submit\" value=\"".gettext("valider")."\">
		<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\"></div>";
		echo "</form>";


		echo gettext("si vous fonctionnez avec un slis, v&#233;rifier que son ip est bien d&#233;fini sur cette ");
		echo "<a href=\"../conf_params.php?cat=1\">".gettext("page")."</a>\n";

	}


	// echo "</body></html>";
} else {

	$nomscript=date("Y_m_d_H_i_s");
	$nomscript="tmp_firefox_$nomscript.sh";
	$nbr_user=0;
	system ("echo \"#!/bin/bash\n\" > /tmp/$nomscript");

    $option=isset($_POST['option']) ? $_POST['option'] : "";
    
	if($choix=="modif_skel") {
		exec("sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh skeluser $page_dem");
		echo "<h4>".gettext("Modification de la page de d&#233;marrage de Mozilla Firefox pour l'utilisateur model")." \"Skeluser\":</h4>";
		echo gettext("la page de d&#233;marrage a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B>,".gettext(" tous les nouveaux comptes utiliseront donc cette page par d&#233;faut")."<br>";
	}



	elseif($choix=="modif") {
		echo "<h4>".gettext("Modification de la page de d&#233;marrage de Mozilla Firefox pour le ou les groupes suivants :")."</h4>";
		//On change la page pour les groupe ou le user selectionne
		if (count($classe_gr) ) {
			foreach ($classe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
	
				echo gettext("La page de d&#233;marrage pour le groupe Classe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A>".gettext(" a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B><br>";
	
				system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");

			}
		}

		if (count($equipe_gr) ) {
			foreach ($equipe_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				echo gettext("La page de d&#233;marrage pour le groupe Equipe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A>";gettext(" a &#233;t&#233; fix&#233;e &#224; ")."<B>\"$page_dem\"</B><br>";
	
				system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");
			}
		}
		if (count($autres_gr) ) {
			foreach ($autres_gr as $grp){
				$uids = search_uids ("(cn=".$grp.")");
				$people = search_people_groups ($uids,"(sn=*)","cat");
				$nbr_user=$nbr_user+count($people);
				echo gettext("La page de d&#233;marrage pour tout le groupe")." <A href=\"/annu/group.php?filter=$grp\">$grp</A>".gettext(" a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B><br>";
				system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $grp $page_dem $option \n\" >> /tmp/$nomscript");

			}
		}

		//teste si utilisateur saisi pour recherche dans ldap
		if ($user!=""&&$user!="skeluser")
		{

			//recherche dans ldap si $user est valide
			$tabresult=search_people("uid=$user");
			if(count($tabresult)!=0)
			{
				$nbr_user=$nbr_user+1;
				echo gettext("La page de d&#233;marrage pour l'utilisateur")." $user ".gettext("a &#233;t&#233; fix&#233;e &#224;")." <B>\"$page_dem\"</B><br>";
				system("echo \"sudo /usr/share/se3/scripts/modif_profil_mozilla_ff.sh $user $page_dem $option \n\" >> /tmp/$nomscript");
			}
			else
			{
				echo "<h4>".gettext(" Erreur").", \"$user\" ".gettext("n'existe pas !")."<h4>";
			}
		}
// 			else
// 			{echo "<h4> Erreur, votre s&#233;lection est vide !<h4>";}

		//le script se supprime a la fin de son exec
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);
		
		if($nbr_user>10){
			//execution differee d'une minute pour ne pas attendre la page trop longtemps
			echo "<h4>".gettext("Requ&#234;te lanc&#233;e en arri&#232;re-plan d'ici &#224; 1mn")."</h4>";
			exec("at -f /tmp/$nomscript now + 1 minute");
		}
		else {
			//execution immediate du script
			exec("/tmp/$nomscript");
		}
	}

	elseif($choix=="deploy_nosave")
	{
		echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla Firefox dans les espaces personnels existants lanc&#233; en arri&#232;re-plan !")."</h4>";
		system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh\n\" >> /tmp/$nomscript");
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);
		exec("at -f /tmp/$nomscript now + 1 minute");
	
		#=========================================================================
		# Ajout: Creation du fichier d'information.
		# Il est modifie par la suite par le script /usr/share/se3/scripts/deploy_mozilla_ff_final.sh
		# Il faut que le dossier /var/www/se3/tmp existe et que www-se3 ait le droit d'y ecrire.
		$fichier_info=fopen('/var/www/se3/tmp/recopie_profils_firefox.html','w+');
		fwrite($fichier_info,'<html>
<meta http-equiv="refresh" content="2">
<html>
<body>
<h1 align="center">Traitement des profils</h1>
<p align="center">Le traitement va demarrer dans la minute qui vient...<br></p>
</body>
</html>');
		fclose($fichier_info);
	
		# Ouverture d'une fenetre popup:
		echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_firefox.html','Suivi_recopie_profils_Firefox','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
		#=========================================================================
	}
	elseif($choix=="deploy_save")
	{
		echo "<h4>".gettext("Red&#233;ploiement du profil Mozilla Firefox dans les espaces personnels existants lanc&#233; !")."<br>
		".gettext("S'il existe des fichiers bookmarks.html dans les profils, ceux-ci seront conserv&#233;s.")."</h4>";
		system("echo \"sudo /usr/share/se3/scripts/deploy_mozilla_ff_final.sh sauve_book\n\" >> /tmp/$nomscript");
		system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
		chmod ("/tmp/$nomscript",0700);
		exec("at -f /tmp/$nomscript now + 1 minute");

		#=========================================================================
		# Ajout: Creation du fichier d'information.
		# Il est modifie par la suite par le script /usr/share/se3/scripts/deploy_mozilla_ff_final.sh
		# Il faut que le dossier /var/www/se3/tmp existe et que www-se3 ait le droit d'y ecrire.
		$fichier_info=fopen('/var/www/se3/tmp/recopie_profils_firefox.html','w+');
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
		echo "\n<script language=\"JavaScript\">\nwindow.open('../tmp/recopie_profils_firefox.html','Suivi_recopie_profils_Firefox','width=300,height=200,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');\n</script>\n";
		#=========================================================================
	}

}

include("pdp.inc.php");
?>
