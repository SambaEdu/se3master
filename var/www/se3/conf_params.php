<?php

   /**
   
   * Permet configurer le serveur
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Philippe Chadefaux
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: conf_params.php

  */	



require ("entete.inc.php");

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


//aide 
$_SESSION["pageaide"]="L\'interface_web_administrateur#Configuration_g.C3.A9n.C3.A9rale";

if (ldap_get_right("se3_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BO
DY></HTML>");

$action = $_GET['action'];

// Change dans la base
if ($action == "change") {

	if ($_GET['varb'] == "proxy") {
		system("/usr/bin/sudo /usr/share/se3/scripts/modifProxy.sh ".$_GET['valeur']);
	} else {
                //$resultat=mysql_query("INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('$default_page_dem', '$name_params', 'homepage $userGroups', '1')");
                    
		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params (`value`, `name`, `descr`, `cat`) VALUES ('".$_GET['valeur']."','".$_GET['varb']."','".$_GET['descr']."','".$_GET['cat']."')");
		if ($resultat == FALSE) {
			mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$_GET[valeur]' where name='".$_GET['varb']."';");
		}
	}
	if ($_GET['varb'] == "corbeille") {
		system("sudo /usr/share/se3/sbin/update-smbconf.sh");
	}
	if ($_GET['varb'] == "defaultintlevel") {
		setintlevel($_GET['valeur']);
		echo "<SCRIPT LANGUAGE='JavaScript'>";
		echo "setTimeout('top.location.href=\"index.html\"',\"10\")";
		echo "</SCRIPT>";
		exit;
	}	

	if ($_GET['varb'] == "defaultshell") {
		$shell_orig=$defaultshell;
		$shell_mod=$_GET['valeur'];
		exec ("/usr/share/se3/sbin/changeShellAllUsers.pl $shell_orig $shell_mod",$AllOutPut,$ReturnValue);
	
	}

	if ($_GET['varb'] == "autoriser_part_pub") {
		$sql="SELECT 1=1 FROM params WHERE name='autoriser_part_pub';";
		$test=mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		if(mysqli_num_rows($test)==0) {
			$sql="INSERT INTO params SET name='autoriser_part_pub', value='".$_GET['valeur']."', descr='Autoriser partage public', cat='1';";
			$insert=mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		}
		else {
			$sql="UPDATE params SET value='".$_GET['valeur']."' WHERE name='autoriser_part_pub';";
			$update=mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		}
		exec ("/usr/bin/sudo /usr/share/se3/scripts/autoriser_partage_public.sh autoriser=".$_GET['valeur'],$AllOutPut,$ReturnValue);
	}

	exec('/usr/bin/sudo /usr/share/se3/scripts/refresh_cache_params.sh');
}



require ("config.inc.php");


echo "<h1>".gettext("Param&#233;trage du serveur")."</H1>\n";



// Affichage du form de mise a jour des paramatres
echo "<br><br>";
echo "<center>";
echo "<TABLE border=\"1\" width=\"80%\">\n";
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Param&#233;trage de l'interface SambaEdu");
echo "</TD></TR>\n";

// Niveau d'interface
echo "<TR><TD>".gettext("Niveau d'interface")."</TD><TD align=\"center\">";
if ($action=="modif_intlevel") {
//	echo "<form method=\"get\" action=\"chlevel.php\">";

	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"defaultintlevel\">";
	echo "<select name =\"valeur\" ONCHANGE=\"this.form.submit();\">";
	echo "<option"; if ($defaultintlevel=="1") { echo " selected"; } echo " value=\"1\">".gettext("D&#233;butant")."</option>";
	echo "<option"; if ($defaultintlevel=="2") { echo " selected"; } echo " value=\"2\">".gettext("Interm&#233;diaire")."</option>";
	echo "<option"; if ($defaultintlevel=="3") { echo " selected"; } echo " value=\"3\">".gettext("Confirm&#233;")."</option>";
	echo "<option"; if ($defaultintlevel=="4") { echo " selected"; } echo " value=\"4\">".gettext("Exp&#233;rimental")."</option>";
	echo "</select>\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de s&#233;lectionner le niveau de l\'interface Se3.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u>";
	echo "</form>\n";
} else {
	if ($defaultintlevel=="1") { $intlevel="D&#233;butant"; }
	if ($defaultintlevel=="2") { $intlevel="Interm&#233;diaire"; }
	if ($defaultintlevel=="3") { $intlevel="Confirm&#233;"; }
	if ($defaultintlevel=="4") { $intlevel="Exp&#233;rimental"; }
	echo "<u onmouseover=\"return escape".gettext("('Mode d\'affichage')")."\">";
	echo "<a href=conf_params.php?action=modif_intlevel>$intlevel</a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Adresse de l'interface 
echo "<TR><TD>".gettext("Adresse de l'interface SambaEdu")."</TD><TD align=\"center\">";
if ($action == "modif_urlse3") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"urlse3\">";
	echo "<input type=\"text\" name=\"valeur\"  value=\"$urlse3\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici l\'adresse de votre Serveur SambaEdu.<br>Cela peut &#234;tre le nom DNS si vous disposez d\'un serveur DNS interne, ou son adresse IP.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
	echo "</form>";
} elseif ($defaultgid)  {
	echo "<u onmouseover=\"return escape".gettext("('Adresse de l\'interface SambaEdu. Si vous ne disposez pas de serveur DNS interne, vous devez indiquer l\'adresse IP.<br><br><b>Ex : http://adresse:909</b>')")."\">";
	echo "<a href=conf_params.php?action=modif_urlse3>$urlse3</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Vous devez obligatoirement mettre une adresse pour pouvoir acc&#233;der &#224; votre interface.')")."\">";
	echo "<a href=conf_params.php?action=modif_urlse3><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\"  alt=\"Disabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Affichage page Etat
echo "<TR><TD>".gettext("Affiche la page d'&#233;tat")."</TD><TD align=\"center\">";
if ($affiche_etat=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'afficher l\'&#233;tat du serveur &#224; chaque d&#233;marrage de l\'interface')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=affiche_etat&amp;valeur=1&amp;cat=6><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet de ne plus faire afficher l\'interface de l\'&#233;tat du serveur &#224; chaque lancement de l\'interface d\'administration du serveur.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=affiche_etat&amp;valeur=0&amp;cat=6><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Cle d'authentification
echo "<TR><TD>".gettext("Cl&#233; d'authentification")."</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('La cl&#233; d\'authentification correspond &#224; la cl&#233; de cryptage entre le serveur Se3 et le navigateur, pour le cryptage des &#233;changes.<br>Par d&#233;faut, tous les serveurs ont la m&#234;me cl&#233;, cliquer ici pour la modifier.<br>Ne pas oublier de vider votre navigateur pour pouvoir vous reconnecter.')")."\">";
echo "<a href=setup_keys.php?cat=2><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
echo "</u>";
echo "</td></tr>\n";


// Gestion des comptes utilisateur
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Configuration des comptes utilisateurs");
echo "</TD></TR>\n";

// uidPolicy
echo "<TR><TD>".gettext("Format des logins")."</TD><TD align=\"center\">";
if ($action=="modif_uidP") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"uidPolicy\">";
	echo "<select name =\"valeur\" ONCHANGE=\"this.form.submit();\">";
	//echo "<option"; if ($uidPolicy=="0") { echo " selected"; } echo " value=\"0\">".gettext("prenom.nom")."</option>";
	echo "<option"; if ($uidPolicy=="1") { echo " selected"; } echo " value=\"1\">".gettext("prenom.nom (tronqu&#233; &#224; 19)")."</option>";
	echo "<option"; if ($uidPolicy=="2") { echo " selected"; } echo " value=\"2\">".gettext("pnom (tronqu&#233; &#224; 19)")."</option>";
	echo "<option"; if ($uidPolicy=="3") { echo " selected"; } echo " value=\"3\">".gettext("pnom (tronqu&#233; &#224; 8)")."</option>";
	echo "<option"; if ($uidPolicy=="4") { echo " selected"; } echo " value=\"4\">".gettext("nomp (tronqu&#233; &#224; 8)")."</option>";
	echo "<option"; if ($uidPolicy=="5") { echo " selected"; } echo " value=\"5\">".gettext("nomprenom (tronqu&#233; &#224; 18)")."</option>";
	echo "</select>\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de choisir le type de login.<br> Cette option ne modifie pas les comptes existants, mais les comptes qui seront cr&#233;&#233;s.<br><br>Si vous avez un Slis vous devez choisir nomp (tronqu&#233; &#224; 8)')")."\">";
	echo "<img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\">";
	echo "</u></form>";
} else {
	if ($uidPolicy=="0") { $uidP=gettext("prenom.nom"); }
	if ($uidPolicy=="1") { $uidP=gettext("prenom.nom (tronqu&#233; &#224; 19)"); }
	if ($uidPolicy=="2") { $uidP=gettext("pnom (tronqu&#233; &#224; 19)"); }
	if ($uidPolicy=="3") { $uidP=gettext("pnom (tronqu&#233; &#224; 8)"); }
	if ($uidPolicy=="4") { $uidP=gettext("nomp (tronqu&#233; &#224; 8)"); }
	if ($uidPolicy=="5") { $uidP=gettext("nomprenom (tronqu&#233; &#224; 18)"); }
	echo "<u onmouseover=\"return escape".gettext("('Permet de modifier le format de login.<br> Ce changement ne modifie pas les comptes d&#233;j&#224; cr&#233;&#233;s.<br><br>Les types disponibles sont :<br>prenom.nom<br>prenom.nom (tronqu&#233; &#224; 19)<br>pnom (tronqu&#233; &#224; 19)<br>pnom (tronqu&#233; &#224; 8)<br>nomp (troqu&#233; &#224; 8).<br>nomprenom (tronqu&#233; &#224; 18).<br><br>Si vous avez un Slis, vous devez choisir nomp (troqu&#233; &#224; 8). ')")."\">";
	echo "<a href=conf_params.php?action=modif_uidP>$uidP</a>";
	echo "</u>";
}	
echo "</td></tr>\n";


// pwdPolicy
echo "<TR><TD>".gettext("Mots de passe par d&#233;faut")."</TD><TD align=\"center\">";
if ($action=="modif_pwdP") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"pwdPolicy\">";
	echo "<select name =\"valeur\" ONCHANGE=\"this.form.submit();\">";
	echo "<option"; if ($pwdPolicy=="0") { echo " selected"; } echo " value=\"0\">".gettext("bas&#233; sur la date de naissance")."</option>";
	echo "<option"; if ($pwdPolicy=="1") { echo " selected"; } echo " value=\"1\">".gettext("semi-al&#233;atoire (6 car.)")."</option>";
	echo "<option"; if ($pwdPolicy=="2") { echo " selected"; } echo " value=\"2\">".gettext("al&#233;atoire (8 car.)")."</option>";
	echo "</select>\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de choisir le format des mots de passe.')")."\">";
	echo "<img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\">";
	echo "</u></form>";
} else {
	if ($pwdPolicy=="0") { $pwdP=gettext("bas&#233; sur la date de naissance"); }
	if ($pwdPolicy=="1") { $pwdP=gettext("semi-al&#233;atoire (6 car.)"); }
	if ($pwdPolicy=="2") { $pwdP=gettext("al&#233;atoire (8 car.)"); }
	echo "<u onmouseover=\"return escape".gettext("('Permet de choisir le format des mots de passe. ')")."\">";
	echo "<a href=conf_params.php?action=modif_pwdP>$pwdP</a>";
	echo "</u>";
}	
echo "</td></tr>\n";


// Bash
echo "<TR><TD>".gettext("Shell par d&#233;faut")."</TD><TD align=\"center\">";
if ($defaultshell=="/bin/false") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Pas de shell (/bin/false)</b><br><br>Cliquer sur le bouton pour permettre aux utilisateurs d\'avoir un shell. <br>Cela est n&#233;cessaire pour les clients Linux.<br><br>Attention: Cela s\'applique pour tous les comptes d&#233;j&#224; cr&#233;&#233;s et pour les comptes qui seront cr&#233;&#233;s.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=defaultshell&amp;valeur=/bin/bash><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Shell (/bin/bash) </b><br><br>Cliquer sur le bouton, pour que les utilisateurs ne disposent pas d\'un shell.<br>Il est n&#233;cessaire d\'avoir un shell, si vous avez des clients Linux.<br><br>Attention: Cela s\'applique pour les comptes d&#233;j&#224; cr&#233;&#233;s et pour les comptes qui seront cr&#233;&#233;s.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=defaultshell&amp;valeur=/bin/false><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Autologon
echo "<TR><TD>".gettext("Connexion automatique &#224; l'interface")."</TD><TD align=\"center\">";
if ($autologon=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'avoir une connexion automatique &#224; l\'interface sambaEdu sans avoir besoin de se r&#233;authentifier')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=autologon&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet d\'avoir une connexion automatique &#224; l\'interface sambaEdu sans avoir besoin de se r&#233;authentifier')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=autologon&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Corbeille
echo "<TR><TD>".gettext("Corbeille r&#233;seau")."</TD><TD align=\"center\">";
if ($corbeille=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b>')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=corbeille&amp;valeur=1&amp;cat=4><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b>')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=corbeille&amp;valeur=0&amp;cat=4><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Gid 
echo "<TR><TD>".gettext("Groupe par defaut des nouveaux utilisateurs (gidNumber)")."</TD><TD align=\"center\">";
if ($action == "modif_gid") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"defaultgid\">";
	echo "<input type=\"text\" name=\"valeur\"  value=\"$defaultgid\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le GID du groupe par defaut pour tous les nouveaux utilisateurs.<br>Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.<br><br>Si vous avez un Slis, vous devez mettre 600. ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u> ";
	echo "</form>";
} elseif ($defaultgid)  {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le GID du groupe par defaut pour tous les nouveaux utilisateurs.<br>Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.<br><br>Si vous avez un Slis, vous devez mettre 600. ')")."\">";
	echo "<a href=conf_params.php?action=modif_gid>$defaultgid</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le GID du groupe par defaut pour tous les nouveaux utilisateurs.<br>Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.<br><br>Si vous avez un Slis, vous devez mettre 600. ')")."\">";
	echo "<a href=conf_params.php?action=modif_gid><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Affichage ou non script de login

echo "<TR><TD>".gettext("Masquage du script &#224; l'ouverture de session windows")."</TD><TD align=\"center\">";
if ($hide_logon=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Le script de connexion de windows est actuellement visible des utilisateurs.<br><br> Cliquez sur le bouton si vous voulez le masquer.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=hide_logon&amp;valeur=1&amp;cat=4><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Le script de connexion de windows est actuellement masqu&#233;.<br><br>Cliquez sur le bouton si vous voulez le rendre visible des utilisateurs. ')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=hide_logon&amp;valeur=0&amp;cat=4><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Configuration du serveur smtp 
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Configuration de l'exp&#233;dition des messages syst&#232;me");
echo "</TD></TR>
";
// domaine 
echo "<TR><TD>".gettext("Domaine")." </TD><TD align=\"center\">";
if ($action == "modif_domain") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"domain\">";
	echo "<input type=\"text\" name=\"valeur\"  value=\"$domain\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le domaine, qui sera ajout&#233; aux mails. Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u>";
	echo "</form>";
} elseif ($domain!="") {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le domaine, qui sera ajout&#233; aux  mails. Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.')")."\">";
	echo "<a href=conf_params.php?action=modif_domain>$domain</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le domaine, qui sera ajout&#233; aux  mails. Ce changemant n\'affectera que les nouveaux comptes cr&#233;&#233;s.')")."\">";
	echo "<a href=conf_params.php?action=modif_domain><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// SMTP 
echo "<TR><TD>".gettext("Exp&#233;dition des messages syst&#232;me")."</TD><TD align=\"center\">";
if (file_exists("/etc/ssmtp/ssmtp.conf")) {
	echo "<u onmouseover=\"return escape".gettext("('Permet de configurer l\'exp&#233;dition des mails, en indiquant le serveur smtp de votre provider.')")."\">";
	echo "<a href=conf_smtp.php><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
	echo "</form>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Attention : </b> Ce serveur ne peut pas envoyer de messages, cela permet d\'&#234;tre inform&#233; d\'un disfonctionnement.<br> Il est souhaitable de le configurer.')")."\">";
	echo "<a href=conf_smtp.php><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}	
echo "</td></tr>\n";



// Configuration des serveurs 
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Configuration pour les mises &#224; jour ");
echo "</TD></TR>\n";


// Adresse du Proxy
$prox=exec("cat /etc/profile | grep http_proxy= | cut -d= -f2");
if ($prox != "") {
        preg_match("/http:\/\/(.*)\"/i",$prox,$rest);
	$proxy = $rest[1];
}	
echo "<TR><TD>".gettext("Adresse du proxy")."</TD><TD align=\"center\">";
if ($action == "modif_proxy")  {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"proxy\">";
	echo "<input type=\"text\" name=\"valeur\"  value=\"$proxy\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer, si vous en utilisez un pour vos connexions &#224; internet, le proxy et son port.<br><br>Ex : 172.16.0.1:8080 ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u>";
	echo "</form>";
} elseif ($proxy!="") {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer, si vous en utilisez un pour vos connexions &#224; internet, le proxy et son port.<br><br>Ex : 172.16.0.1:8080 ')")."\">";
	echo "<a href=conf_params.php?action=modif_proxy>$proxy</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Pas de proxy, connexion directe &#224; internet</b><br><br>Indiquer, si vous en utilisez un pour vos connexions &#224; internet, le proxy et son port.<br><br>Ex : 172.16.0.1:8080 ')")."\">";
	echo "<a href=conf_params.php?action=modif_proxy><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}	
echo "</td></tr>\n";


// Serveur de temps
echo "<TR><TD>".gettext("Serveur de temps")."</TD><TD align=\"center\">";
if ($action == "modif_ntp")  {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"ntpserv\">";
	echo "<input type=\"text\" name=\"valeur\"  value=\"$ntpserv\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de temps &#224; utiliser.<br><br>Si vous avez un Slis, indiquer son adresse IP')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u>";
	echo "</form>";
} elseif ($ntpserv!="") {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de temps &#224; utiliser.<br><br>Si vous avez un Slis, indiquer son adresse IP')")."\">";
	echo "<a href=conf_params.php?action=modif_ntp>$ntpserv</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de temps &#224; utiliser.<br><br>Si vous avez un Slis, indiquer son adresse IP')")."\">";
	echo "<a href=conf_params.php?action=modif_ntp><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}	
echo "</td></tr>\n";

// Serveur de mises &#224; jour 
echo "<TR><TD>".gettext("Adresse des scripts de mises &#224; jour")."</TD><TD align=\"center\">";
if ($action=="modif_urlmaj") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"urlmaj\">";
	echo "<input type=\"text\" name=\"valeur\" size=\"40\" value=\"$urlmaj\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de mises &#224; jour de votre serveur Se3. ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u>";
	echo "</form>";
} elseif ($urlmaj!="") {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de mises &#224; jour de votre serveur Se3. ')")."\">";
	echo "<a href=conf_params.php?action=modif_urlmaj>$urlmaj</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur de mises &#224; jour de votre serveur Se3. ')")."\">";
	echo "<a href=conf_params.php?action=modif_urlmaj><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a></u>";
}
echo "</td></tr>\n";

// Serveur de mises &#224; jour 
echo "<TR><TD>".gettext("Adresse de t&#233;l&#233;chargement de mises &#224; jour")."</TD><TD align=\"center\">";
if ($action=="modif_ftpmaj") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"ftpmaj\">";
	echo "<input type=\"text\" name=\"valeur\" size=\"40\" value=\"$ftpmaj\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur ftp de mises &#224; jour de votre serveur Se3. ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u>";
	echo "</form>";
} elseif ($ftpmaj!="") {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur ftp de mises &#224; jour de votre serveur Se3. ')")."\">";
	echo "<a href=conf_params.php?action=modif_ftpmaj>$ftpmaj</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer le serveur ftp de mises &#224; jour de votre serveur Se3. ')")."\">";
	echo "<a href=conf_params.php?action=modif_ftpmaj><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a></u>";
}
echo "</td></tr>\n";


// Configuration des serveurs 
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Configuration de l'annuaire")." ($ldap_base_dn) ";
echo "</TD></TR>\n";


// PHPLDAPADMIN
$se3_pam=exec("dpkg -l|grep 'ii  se3-pla'|wc -l");

echo "<TR><TD>".gettext("Droit d'&#233;criture dans l'annuaire")." (phpldapadmin)</TD><TD align=\"center\">";
if ($se3_pam) {
	if ($yala_bind=="0") {
		echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin de pouvoir activer la possibilit&#233; d\'&#233;crire directement dans l\'annuaire.<br>Cette possibilit&#233; est &#224; utiliser avec prudence.<br>Normalement, vous ne devriez pas &#224; avoir besoin d\'&#233;crire directement dans l\'annuaire.')")."\">";
		echo "<a href=conf_params.php?action=change&amp;varb=yala_bind&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
		echo "</u>";
	} else {
		echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de pouvoir d&#233;sactiver la possibilt&#233; d\'&#233;crire directement dans l\'annuaire LDAP.<br>Cette possibilit&#233; est &#224; utiliser avec prudence.')")."\">";
		echo "<a href=conf_params.php?action=change&amp;varb=yala_bind&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
		echo "</u>";
	}
} else {
	echo "le module se3-pla n'est pas install&#233;";
}
echo "</td></tr>\n";


// Sauvegarde distance 
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Sauvegarde");
echo "</TD></TR>\n";


// Sauvegarde

// test si rsync est actif
$rsync_actif = exec("netstat -na | grep 0.0.0.0:873  > /dev/null && echo ok");
			
echo "<TR><TD>".gettext("Sauvegarde rsyncd locale ou distante")."</TD><TD align=\"center\">";
if ($rsync_actif!="ok") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin de pouvoir activer la possibilit&#233; de sauvegarder en mode rsyncd, soit en local sur le se3 ou depuis un autre serveur (Se3 ou Lcs) disposant de backuppc.')")."\">";
	echo "<a href=conf_rsync.php><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de pouvoir d&#233;sactiver la possibilt&#233; de sauvegarder ce serveur soit en local sur le se3 ou depuis un autre serveur disposant de backuppc, un se3 ou bien un serveur LCS.<br> Si vous n\'utiliser pas cette fonctionnalit&#233;, il est souhaitable de ne pas l\'activer.')")."\">";
	echo "<a href=conf_rsync.php><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
	echo "</u>";
}

echo "</td></tr>\n";
echo "<tr><td>".gettext("Sauvegarde hebdomadaire")."</td><td align=\"center\">";
if ($svgsyst_cnsv_hebdo=="1") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de d&#233;sactiver une sauvegarde hebdomadaire. <br><b>Attention</b> Si vous utilisez backuppc (sauvegarde int&#233;gr&#233;e &#224; Se3) cette option est d&#233;conseill&#233;e.<br> Utiliser cela si vous ne souhaitez pas utiliser de syst&#232;me de sauvegarde. Voir la documentation pour plus d\'explication.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_cnsv_hebdo&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin d\'activer une sauvegarde journali&#232;re. <br><b>Attention</b> Si vous utilisez backuppc (sauvegarde int&#233;gr&#233;e &#224; Se3) cette option est d&#233;conseill&#233;e.<br> Utiliser cela si vous ne souhaitez pas utiliser de syst&#232;me de sauvegarde. Voir la documentation pour plus d\'explication.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_cnsv_hebdo&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}
echo "</td></TR>\n";

echo "<tr><td>".gettext("Sauvegarde Samba")."</td><td align=\"center\">";

if ($svgsyst_varlibsamba=="1") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de d&#233;sactiver une sauvegarde journali&#232;re. <br><b>Attention</b> Si vous utilisez backuppc (sauvegarde int&#233;gr&#233;e &#224; Se3) cette option est d&#233;conseill&#233;e.<br> Utiliser cela si vous ne souhaitez pas utiliser de syst&#232;me de sauvegarde. Voir la documentation pour plus d\'explication.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_varlibsamba&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin d\'activer une sauvegarde journali&#232;re de /var/lib/samba. <br><b>Attention</b> Si vous utilisez backuppc (sauvegarde int&#233;gr&#233;e &#224; Se3) cette option est d&#233;conseill&#233;e.<br> Utiliser cela si vous ne souhaitez pas utiliser de syst&#232;me de sauvegarde. Voir la documentation pour plus d\'explication.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_varlibsamba&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}

echo "</td></TR>\n";

echo "<tr><td>".gettext("Sauvegarde ACL de /var/se3")."</td><td align=\"center\">";

if ($svgsyst_aclvarse3=="1") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin d&#233;sactiver la  sauvegarde des ACL de /var/se3. <br>Cela permet de remettre les ACL en cas de probl&#232;me.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_aclvarse3&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin d\'activer une sauvegarde des ACL /var/se3.')")."\">";
	echo "<a href=conf_params.php?action=change&amp;varb=svgsyst_aclvarse3&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}

echo "</td></TR>\n";


// Serveurs de com
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Configuration des serveurs de communication");
echo "</TD></TR>
";


// Serveur Slis
echo "<TR><TD>".gettext("Adresse IP de votre serveur Slis (optionnel)")."</TD><TD align=\"center\">";
if ($action=="add_slis") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"slisip\">";
	echo "<input type=\"text\" name=\"valeur\" size=\"25\" value=\"$slisip\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer l\'adresse IP de votre serveur Slis  ou ne rien mettre pour le d&#233;sactiver.')")."\"> <img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"></u>";
	echo "</form>";
} elseif ($slisip!="") {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer sur l\'adresse pour modifer. <br><br>Cette adresse correspond &#224; l\'adresse de votre serveur Slis.')")."\">";
	echo "<a href=conf_params.php?action=add_slis>$slisip</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer  ici afin de pouvoir indiquer l\'adresse de votre serveur de communication SLIS.')")."\">";
	echo "<a href=conf_params.php?action=add_slis><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a></u>";
}
echo "</td></tr>\n";

// Serveur Lcs 
echo "<TR><TD>".gettext("Adresse IP du serveur Lcs (optionnel)")."</TD><TD align=\"center\">";
if ($action=="add_lcs") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"lcsIp\">";
	echo "<input type=\"text\" name=\"valeur\" size=\"25\" value=\"$lcsIp\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer l\'adresse IP de votre serveur Lcs ou ne rien mettre pour le d&#233;sactiver.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"> </u>";
	echo "</form>";
} elseif ($lcsIp!="") {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer sur l\'adresse pour modifer. <br><br>Cette adresse correspond &#224; l\'adresse de votre serveur LCS.')")."\">";
	echo "<a href=conf_params.php?action=add_lcs>$lcsIp</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer  ici afin de pouvoir indiquer l\'adresse de votre serveur de communication LCS.')")."\">";
	echo "<a href=conf_params.php?action=add_lcs><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Acces a l'interface du serveur de communication 
echo "<TR><TD>".gettext("Adresse de l'interface de votre serveur de communication")."</TD><TD align=\"center\">";
if ($action=="add_com") {
	echo "<form method=\"get\" action=\"conf_params.php\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"change\">";
	echo "<input type=\"hidden\" name=\"varb\" value=\"slis_url\">";
	echo "<input type=\"text\" name=\"valeur\" size=\"25\" value=\"$slis_url\"><input type=\"submit\" value=\"Ok\"> ";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer l\'adresse de votre serveur de communication (Lcs - Slis).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"Help\"> </u>";
	echo "</form>";
} elseif ($slis_url!="") {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer sur l\'adresse pour modifer. <br><br>Cette adresse correspond &#224; l\'adresse de votre serveur de communication.')")."\">";
	echo "<a href=conf_params.php?action=add_com>$slis_url</a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Cliquer  ici afin de pouvoir indiquer l\'adresse de votre serveur de communication LCS - Slis.')")."\">";
	echo "<a href=conf_params.php?action=add_com><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Partages
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Partages");
echo "</TD></TR>
";


echo "<tr><td>".gettext("Purge journali&#232;re de la ressource public ")."</td><td align=\"center\">";

if ($purge_public=="1") {
        echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de d&#233;sactiver la purge automatique du partage public. <br>Cela permet de supprimer automatiquement toutes les nuits les fichiers dans la ressource public.')")."\">";
        echo "<a href=conf_params.php?action=change&amp;varb=purge_public&amp;valeur=0&amp;cat=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
        echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin d\'activer une purge automatique de la ressource public.')")."\">";
        echo "<a href=conf_params.php?action=change&amp;varb=purge_public&amp;valeur=1&amp;cat=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}

echo "</td></tr>\n";

echo "<tr><td>".gettext("Autoriser l'acc&#232;s &#224; la ressource public ")."</td><td align=\"center\">";

if ($autoriser_part_pub=="y") {
        echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de d\'interdire l\'acc&#232;s au dossier /var/se3/Docs/public')")."\">";
        echo "<a href=conf_params.php?action=change&amp;varb=autoriser_part_pub&amp;valeur=n&amp;cat=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
        echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin de d\'autoriser l\'acc&#232;s au dossier /var/se3/Docs/public')")."\">";
        echo "<a href=conf_params.php?action=change&amp;varb=autoriser_part_pub&amp;valeur=y&amp;cat=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}

echo "</td></TR>
";

echo "</table></center>";

echo "</table></center>";

include("pdp.inc.php");
?>
