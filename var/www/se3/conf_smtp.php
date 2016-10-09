<?php


   /**
   
   * Permet de configurer le smtp pour expedier les messages ssmtp
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: conf_smtp.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


/**
* Fonction pour obtenir les valeurs deja definies dans ssmtp.conf

* @Parametres $name 
* @Return Retourne  la valeur de name contenue dans ssmtp.conf
	
*/

function variable ($Name) { // retourne la valeur de Name
	if (file_exists("/etc/ssmtp/ssmtp.conf")) {
		$lignes = file("/etc/ssmtp/ssmtp.conf");
		foreach ($lignes as $num => $ligne) {
			if (preg_match("/$Name=(.*)/",$ligne,$reg)) {
				$var = trim($reg[1]);
				return $var;
			}
		}
	}	
} // fin function
		

echo "<H1>".gettext("Configure le SMTP")."</H1>\n";

//Aide
//aide
$_SESSION["pageaide"]="L%27interface_web_administrateur#Partie_:_Configuration_de_l.27exp.C3.A9dition_des_messages_syst.C3.A8me";

if (is_admin("system_is_admin",$login)=="Y") {
	
	// Creation du fichier de conf de ssmtp
	if ($_GET[action] == "exim_mod") {
		$fichier = "/etc/ssmtp/ssmtp.conf";
  		$fp=fopen("$fichier","w+");
$DEFAUT = "
# Genere par l'interface de Se3
root=$_GET[dc_root]
mailhub=$_GET[dc_smarthost]
rewriteDomain=$_GET[dc_readhost]
hostname=$_GET[dc_readhost]
";
if (@$_GET[dc_AuthUser])
	$DEFAUT .= "AuthUser=$_GET[dc_AuthUser]
";
if (@$_GET[dc_AuthPass])
	$DEFAUT .= "AuthPass=$_GET[dc_AuthPass]
";
if (@$_GET[dc_UseTLS]=="YES")
	$DEFAUT .= "UseTLS=$_GET[dc_UseTLS]
";
if (@$_GET[dc_UseSTARTTLS]=="YES")
	$DEFAUT .= "UseSTARTTLS=$_GET[dc_UseSTARTTLS]
";
		fwrite($fp,$DEFAUT);
		fclose($fp);

		$subject = gettext("Test de la configuration de votre serveur Se3");
		$message = gettext("Message envoye par le serveur Se3");
		mail ($_GET[dc_root], $subject, $message);

		unset($action);
	}

	// test la presence du paket
	$ssmtp = exec("dpkg -l | grep ssmtp > /dev/null && echo ok");
	// Si deja installe
	if ($ssmtp == "ok") {
		echo "<form method=\"get\" action=\"conf_smtp.php\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"exim_mod\">";
		echo "<br><br>";
		echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\" >\n";
		echo "<tr><td colspan=\"3\" align=\"center\"  class=\"menuheader\" height=\"30\">".gettext("Configuration de la messagerie")."</td></tr>\n";
		$dc_readhost = variable ("rewriteDomain");
		if ($dc_readhost == "") { $dc_readhost = "$domain"; }
		echo "<tr>";
     		echo "<td>".gettext("Domaine :")."</td>";
      		echo "<td><input name=\"dc_readhost\" type=\"text\" size=\"40\" value=\"$dc_readhost\"  ></td>\n";
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici le domaine de votre &#233;tablissement. Par exemple lyc&#233;e.ac-acad&#233;mie.fr<br>Si vous n\'avez pas d\'IP fixe vous ne poss&#233;dez pas de domaine, vous risquez alors de ne pas pouvoir envoyer de messages<br>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
      	echo "</tr>\n";
		      
		echo "<tr>\n";
        	echo "<td>".gettext("Serveur SMTP")." :</td>";
        	$dc_smarthost = variable ("mailhub");
        	if ($dc_smarthost == "") { $dc_smarthost = "$slisip"; }
        	echo "<td><input name=\"dc_smarthost\" type=\"text\" size=\"40\" value=\"$dc_smarthost\"  ></td>\n";
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici le serveur qui vous permet d\'exp&#233;dier les messages.<br><br> Pour un port particulier, le rajouter apr&#232;s l\'adresse du serveur smtp (Ex. messagerie.ac-versailles.fr:465).<br><br> - Si vous avez un Slis ou un Lcs, indiquer son adresse IP.<br> - Si vous n\'avez pas un serveur de ce type indiquer le smtp de votre provider. (smtp.free.fr par exemple). ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
        echo "</tr>\n";
			
		echo "<tr>\n";
        	echo "<td>".gettext("Identifiant de connexion au smtp (si n&#233;cessaire)")." :</td>";
        	$dc_AuthUser = variable ("AuthUser");
        	echo "<td><input name=\"dc_AuthUser\" type=\"text\" size=\"40\" value=\"$dc_AuthUser\"  ></td>\n";
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici l\'identifiant de connexion au smtp.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
        echo "</tr>\n";
			
		echo "<tr>\n";
        	echo "<td>".gettext("Mot de passe de connexion au smtp (si n&#233;cessaire)")." :</td>";
        	$dc_AuthPass = variable ("AuthPass");
        	echo "<td><input name=\"dc_AuthPass\" type=\"password\" size=\"40\" value=\"$dc_AuthPass\"  ></td>\n";
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer ici le mot de passe de connexion au smtp.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
        echo "</tr>\n";
			
		echo "<tr>\n";
        	echo "<td>".gettext("Utiliser le mode sécurisé TLS")." :</td>";
        	$dc_UseTLS = variable ("UseTLS");
        	echo "<td><select name=\"dc_UseTLS\">";
			echo "<option value=\"NO\">Non</option>";
			echo "<option value=\"YES\"";
			if ($dc_UseTLS=="YES")
				echo " selected";
			echo ">Oui</option>";
			echo "</select></td>\n";			
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('A utiliser en cas de mode s&#233curis&#233;')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
        echo "</tr>\n";
			
		echo "<tr>\n";
        	echo "<td>".gettext("Utiliser le mode sécurisé STARTTLS")." :</td>";
        	$dc_UseSTARTTLS = variable ("UseSTARTTLS");
        	echo "<td><select name=\"dc_UseSTARTTLS\">";
			echo "<option value=\"NO\">Non</option>";
			echo "<option value=\"YES\"";
			if ($dc_UseSTARTTLS=="YES")
				echo " selected";
			echo ">Oui</option>";
			echo "</select></td>\n";			
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('A utiliser en cas de mode s&#233curis&#233;')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
        echo "</tr>\n";
		
		echo "<tr>\n";
        	echo "<td>".gettext("Boite de r&#233;ception")." :</td>";
			$dc_root = variable ("root");
       		echo "<td><input name=\"dc_root\" type=\"text\" size=\"40\" value=\"$dc_root\" ></td>";
			echo "<td align=\"center\"><u onmouseover=\"return escape".gettext("('Indiquer l\'adresse qui va recevoir les mails g&#233;n&#233;r&#233;s par le syst&#232;me.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
      	echo "</tr>\n";
		

		echo "</table>\n";
		echo "<br><br>";
		echo "<center><input type=\"submit\"  value=\"".gettext("Valider")."\"></center>";
		echo "</form>\n";      
	} else {
		echo gettext("Le paquet ssmtp ne semble pas install&#233; sur la machine");
		echo "<BR><BR>";
		echo gettext("Vous devez d'abord ex&#233;cuter sur le serveur un apt-get install ssmtp");
	}	
} else echo gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page...");

include ("pdp.inc.php");
?>
