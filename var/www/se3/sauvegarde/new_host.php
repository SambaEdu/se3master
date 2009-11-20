<?php


   /**
   
   * Permet configurer la sauvegarde (Backuppc)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: sauvegarde
   * file: new_host.php

  */	



include "entete.inc.php";
require ("config.inc.php");
require ("ldap.inc.php");
require ("ihm.inc.php");
include ("fonction_backup.inc.php");

require_once("lang.inc.php");

bindtextdomain('sauvegarde',"/var/www/se3/locale");
textdomain ('sauvegarde');

// Verifie les droits
if (is_admin("system_is_admin",$login)=="Y") {
	
	$HostServer = $_GET['HostServer'];
	$TypeServer = $_GET['TypeServer'];
	$XferMethod = $_GET['XferMethod'];
	$dhcp = $_GET['dhcp'];
	$Share = $_GET['Share'];
	$defo = $_GET['defo'];
	$Compte = $_GET['Compte'];
	$PassWord = $_GET['PassWord'];
	$AclName = $_GET['AclName'];
	$LdapName = $_GET['LdapName'];
	$MysqlName = $_GET['MysqlName'];
	$Secrets=$_GET['Secrets'];
	$BackupFilesExclude=$_GET['BackupFilesExclude'];
	$TypeServerOld=$_GET['TypeServerOld'];
	$err=$_GET['err'];
	$ArchiveDest=$_GET['ArchiveDest'];
	$ArchiveSplit=$_GET['ArchiveSplit'];


	if ($Share != "") {
		$Share = stripslashes($Share);
	}	
	if ($BackupFilesExclude != "") {
	        $BackupFilesExclude = stripslashes($BackupFilesExclude);
	}


	$pass="0";
	if ($TypeServer == $TypeServerOld) {
		$pass = "1";
	}	
	$TypeServerOld = "$TypeServer";

	
	/****************************************************************************************/
	echo "<P><h1>".gettext("Ajout d'une machine &#224; sauvegarder")."</h1></P>\n";
	echo "<br><br>";

	/***************************** Erreurs **************************************************/
	echo "<center>";
	if ($err == "1") {
		echo "<font color=\"red\">".gettext("Attention : le nom que vous avez donn&#233; existe d&#233;j&#224;, vous ne pouvez pas donner ce nom")."</font>"; 
		echo "<br><br>";
	}
	if ($err == "2") {
		echo "<font color=\"red\">".gettext("Attention : Vous devez indiquer le compte ou le mot de passe pour la connexion")." $XferMethod</font>";
		echo "<br><br>";
	}	
	if ($err == "3") {
		echo "<font color=\"red\">".gettext("Attention : Vous devez indiquer la cl&#233; pour la connexion")." $XferMethod</font>";
		echo "<br><br>";
	}	

	if ($err == "4") {
		echo "<font color=\"red\">".gettext("Attention : Certains champs obligatoires ne sont pas remplis.<br> Vous devez commencer par le Type de machine.")." </font>";
		echo "<br><br>";
	}	

	/************************************************************************/

	echo "<form method=\"get\" action=\"new_host_suite.php\" >";

	echo "<input type=\"hidden\" name=\"TypeServerOld\" value=\"$TypeServerOld\">";
	echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">
      	<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Machine &#224; sauvegarder")."</td></tr>\n";

	if ($TypeServer!="Archive") {
  		echo "<tr><td width=\"40%\">".gettext("Type de machine :")."</td>
      		<td><select name=\"TypeServer\" ONCHANGE=\"this.form.submit();\">
      		<option value=\"\">".gettext("S&#233;lectionner")."</option>";
  		echo "<option"; if ($TypeServer=="Local") { echo " selected"; } echo " VALUE=\"Local\">Se3 ".gettext("local")."</option>";
  		echo "<option"; if ($TypeServer=="Se3") { echo " selected"; } echo " VALUE=\"Se3\">Se3 ".gettext("distant")."</option>";
  		echo "<option"; if ($TypeServer=="Slis") { echo " selected"; } echo ">Slis</option>";
  		echo "<option"; if ($TypeServer=="Lcs") { echo " selected"; } echo ">Lcs</option>";
  		echo "<option"; if ($TypeServer=="WinXP") { echo " selected"; } echo " VALUE=\"WinXP\">Windows (smb)</option>";
  		echo "<option"; if ($TypeServer=="WinRsync") { echo " selected"; } echo " VALUE=\"WinRsync\">Windows (rsyncd)</option>";
  		echo "<option"; if ($TypeServer=="Autre") { echo " selected"; } echo ">".gettext("Autre")."</option>";
  		echo "</select>&nbsp;<u onmouseover=\"return escape".gettext("('S&#233;lectionner le type de machine que vous souhaitez sauvegarder.<br><br> - Si la machine &#224; sauvegarder est la machine sur laquelle tourne le serveur de sauvegarde, s&#233;lectionner Se3 local.<br><br> - Pour un Se3, sur une autre machine s&#233;lectionner Se3 distant.<br><br> - Pour sauvegarder une machine Windows avec le protocole samba s&#233;lectionner Windows (smb)<br><br> - Pour une machine Windows en utilisant rsyncd, s&#233;lectionner Windows (rsync). Cela n&#233;cessite d\'installer rsyncd sur la machine windows &#224; sauvegarder.<br><br>En fonction du choix que vous faites, des configurations vous seront propos&#233;es. Il vous est toujours possible de faire un autre choix en s&#233;lectionnant Autre.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	}     

  
	// Indique ici les choix imposes ou conseilles. Uniquement au premier passage
	if ($pass=="0") {
		// si type est Se3 alors le protocole est smb
		if ($TypeServer=="WinXP") {
			$XferMethod="smb";
	                $BackupFilesExclude = "";
	                $Share="'C:\BCDI'";
			$dhcp="1";
		}

		if ($TypeServer=="WinRsync") {
			$XferMethod="rsyncd";
	                $BackupFilesExclude = "";
	                $Share="'BCDI'";
			$dhcp="1";
		}
		// Si la machine est local et de type Se3 on propose tar
		if ($TypeServer=="Local") { 
			$dhcp = "0";
			$XferMethod = "tar";
			$Model = "Se3";
			$HostServer="localhost";
		}
		if ($TypeServer=="Lcs") {
			$XferMethod = "rsync";
			$Share="'/'";
			$BackupFilesExclude = "'/var/spool/squid','/var/mail','/var/spool/mail','/tmp','/var/cache/apt/archives','/proc','/mnt','/var/lib/backuppc','/usr/share/doc'";
		}
		if ($TypeServer=="Se3") {
			$XferMethod = "rsyncd";
			$BackupFilesExclude = "";
			$Share="'var','home','etc'";
		}
	
		if ($TypeServer=="Slis") {
			$XferMethod = "rsync";
			$BackupFilesExclude = "";
			$Share="";
		}

		if ($TypeServer=="Autre") {
			$XferMethod = "";
			$BackupFilesExclude = "";
			$Share="";
		}
		if ($TypeServer=="Archive") {
			$XferMethod = "archive";
		}	
	}

	// On impose le choix du type serveur en premier
	if ($TypeServer=="") { 
		$HostServer = "";
		$XferMethod = "";
		$BackupFilesExclude = "";
		$Share = "";
	}	

	if ($TypeServer=="Archive") {

		echo "<tr><td>".gettext("Nom de l'archive")."</td><td><input type=text name=\"HostServer\" value=\"$HostServer\" size=\"45\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer ici le nom de l\'archive, par exemple cdArchiv ou TapeArchiv, ou en fonction du nom de la machine dont vous souhaitez archiver les sauvegardes.<br>Eviter les caract&#233;res particuliers.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>";
	  	echo "</table>\n";
	  	echo "<br><br>";
  
	  	echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
	  	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Support d'archivage")."</td></tr>\n";
	  	echo "<tr><td>".gettext("Destination de l'archive")."</td><td><input type=\"text\" name=\"ArchiveDest\" value=\"$ArchiveDest\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer le support sur lequel archiver. /tmp pour le mettre dans le r&#233;pertoire tmp.<br><br>Pour archiver sur une bande indiquer /dev/st0 (&#224; v&#233;rifier en fonction de votre machine).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
		echo "<tr><td>".gettext("Taille de l'archive")."</td><td><input type=\"text\" name=\"ArchiveSplit\" value=\"$ArchiveSplit\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer la taille de l\'archive. Par exemple pour archiver sur un CD, en utilisant un graveur, indiquer 650. l\'archive sera alors coup&#233;e en plusieurs fichiers de 650.<br><br>Si vous laissez vide, aucune taille limite ne sera donn&#233;e (0 pas d&#233;faut).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	  	echo "</table>";
	  	echo "<input type=\"hidden\" name=\"XferMethod\" value=\"archive\" />";
	  	echo "<input type=\"hidden\" name=\"TypeServer\" value=\"Archive\" />";
	  	echo "<input type=\"hidden\" name=\"dhcp\" value=\"1\" />";
	} else {
  		if (($TypeServer != "Local") && ($HostServer == "localhost")) { $HostServer = ""; }
  		echo "<tr><td>".gettext("Nom de la machine")."</td><td><input type=text name=\"HostServer\" value=\"$HostServer\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer le nom de la machine &#224; sauvegarder.<br><br> - Dans le cas d\'une machine windows, indiquer son nom netbios.<br>Sinon indiquer son nom DNS si elle est indiqu&#233;e dans un serveur de nom (cela peut &#234;tre lyc&#233;ee.ac-acad&#233;mie.fr).<br><br>Pour v&#233;rifier, vous pouvez faire un ping avec ce nom afin de v&#233;rifier que la machine &#224; sauvegarder est bien vu.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>";
  		echo "<tr><td>".gettext("Nom DNS")."</td><td><select name=\"dhcp\">";
  		echo "<option value=\"0\""; if ($dhcp=="0") echo " selected"; echo ">".gettext("Trouvable par son nom (ip fixe)")."</option>";
  		echo "<option value=\"1\""; if ($dhcp=="1") echo " selected"; echo ">".gettext("Non trouvable par son nom (Pas d'ip fixe)")."</option>";
  		echo "</select>&nbsp;<u onmouseover=\"return escape".gettext("('Si votre machine n\'est pas dans un serveur de nom, vous devez indiquer Pas d\'ip fixe. Ce cas est le plus fr&#233;quent dans un &#233;tablissement scolaire.<br>Ne pas oublier dans la configuration g&#233;n&#233;rale d\'indiquer la plage d\'adresses pour la recherche des machines.<br>Ce cas concerne toutes les machines clientes<br>Si votre machine est trouvable par son nom, indiquer Ip fixe.<br><br>Comment savoir si une machine est visible par le serveur de sauvegarde ?<br>Essayer depuis celui-ci de la pinguer avec le nom que vous avez indiqu&#233;.<br>Les machines windows sont recherch&#233;es avec leur nom netbios.<br><br>Attention : les firewall sur les machines &#224; sauvegarder, peuvent bloquer la recherche de la machine.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  		echo "</table>";

  		echo "<br><br>";

		// Type de sauvegarde
  		echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
  		echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Type de Sauvegarde")."</td></tr>\n";
  		echo "<tr><td width=\"50%\">".gettext("Type de sauvegarde :")."</td>";
  		if ($TypeServer == "WinXP") { 
  			echo "<td> smb</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"smb\" />";
		}
  		elseif ($TypeServer == "WinRsync") { 
  			echo "<td> rsyncd</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"rsyncd\" />";
		}
  		elseif ($TypeServer == "Se3") { 
  			echo "<td> rsyncd</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"rsyncd\" />";
		}
  		elseif ($TypeServer == "Lcs") { 
  			echo "<td> rsync</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"rsync\" />";
		}
  		elseif ($TypeServer == "Local") { 
  			echo "<td> tar</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"tar\" />";
		}
  		elseif ($TypeServer == "Slis") {
  			echo "<td> rsync</td></tr>"; 
   			echo "<input type=\"hidden\" name=\"XferMethod\" value=\"rsync\" />";
		}
  		else {
      			echo "<td><select name=\"XferMethod\" ONCHANGE=\"this.form.submit();\">
      			<option value=\"\">".gettext("S&#233;lectionner")."</option>
      			<option";
  			if ($XferMethod=="smb") { echo " selected"; }
  		   	echo ">smb</option><option";
  			if ($XferMethod=="rsync") { echo " selected"; }
        		echo ">rsync</option><option";
  			if ($XferMethod=="tar") { echo " selected"; }
        		echo ">tar</option><option";
  			if ($XferMethod=="rsyncd") { echo " selected"; }
            		echo ">rsyncd</option></select>\n";
  			echo "&nbsp;<u onmouseover=\"return escape".gettext("('S&#233;lectionner le type de protocole &#224; utiliser pour faire les sauvegardes.<br><br> - smb : &#224; utiliser pour sauvegarder les machines windows. Vous devez fournir un compte et un mot de passe pour se connecter. Il faut donner les droits n&#233;cessaires afin de pouvoir faire les sauvegardes.<br><br> - rsync : est utilis&#233; pour faire une sauvegarde sur une machine distante, via un tunnel crypt&#233; SSH. Vous devez fournir la cl&#233; que vous avez g&#233;n&#233;r&#233; sur le serveur de sauvegarde &#224; la machine &#224; sauvegarder afin de pouvoir vous y connecter. Voir la documentation.<br><br> - tar : disponible que si vous sauvegardez le serveur de sauvegarde lui m&#234;me.<br><br> - rsyncd : Vous devez mettre en place rsyncd sur la machine que vous souhaitez sauvegarder. Vous devrez indiquer le compte plus mot de passe. rsyncd peut aussi &#234;tre utilis&#233;, pour sauvegarder des machines Windows. Voir la documentation.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>";
  		}

   		if(($XferMethod=="smb") or ($XferMethod=="rsyncd")) {
  	    		echo "<tr><td>";
            		echo "<TR><td>".gettext("Compte de connexion")."</td><td><input type=text name=\"Compte\" value=\"$Compte\"></td></tr>";
            		echo "<tr><td>".gettext("Mot de passe")."</td><td><input type=text name=\"PassWord\" value=\"$PassWord\"></td></tr>";
  		}

  		echo "</table>\n";
  		echo "<br><br>\n";

  		if ($TypeServer!="") {
       			echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
       			echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">";
       			if($XferMethod!="rsyncd") {
       				echo gettext("R&#233;pertoires &#224; Sauvegarder");
			} else {
				echo gettext("Modules de sauvegarde");
			}	
			echo "</td></tr>\n";
  		}

  		if (($Model=="Se3") and (($XferMethod=="rsync") or ($XferMethod == "tar"))) {
			echo "<input type=\"hidden\" name=\"Model\" value=\"Se3\" />";
			echo "<tr><td>";
			echo "ACL ".gettext("des r&#233;pertoires")." /var/se3 </td><td align=\"center\"><input type=\"checkbox\" name=\"AclName\" checked disabled>";
  			echo "&nbsp;<u onmouseover=\"return escape".gettext("('Les ACL de /var/se3 sont sauvegard&#233;es automatiquement toutes les nuits, dans /var/se3/save, les ACL de home, n\'ont pas besoin de l\'&#234;tre car reconstruite automatiquement.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
			echo "</td></tr>";
			echo "<tr><td>";
			echo gettext("Annuaire LDAP")." </td><td align=\"center\"><input type=\"checkbox\" name=\"LdapName\" checked disabled>";
  			echo "&nbsp;<u onmouseover=\"return escape".gettext("('L\'annuaire LDAP est sauvegard&#233; automatiquement toutes les nuits dans /var/se3, en sauvegardant ce r&#233;pertoire vous les sauvegardez donc aussi.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
			echo "</td></tr>";
			echo "<tr><td>";
			echo gettext("Base MySQL")."</td><td align=\"center\"><input type=\"checkbox\" name=\"MysqlName\"";
			if($MysqlName=="on") {echo " checked"; }
			if($pass=="0") {echo " checked"; }
			echo ">";

  			echo "&nbsp;<u onmouseover=\"return escape".gettext("('Une partie des bases MySQL (se3db et mysql) sont sauvegard&#233;es automatiquement. En cliquant sur sauvegarder les bases MySQL, vous allez sauvegarder les autres (Inventory ...).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
			echo "</td></tr>";
			$Share = "'/home','/var/se3'";
  		} 

  		if (($Model=="Slis") and (($XferMethod=="rsync") or ($XferMethod=="tar"))) {
			echo "<input type=\"hidden\" name=\"Model\" value=\"Slis\" />";
			echo "<tr><td>";
        		echo gettext("R&#233;pertoire")." /home</td><td align=\"center\"><input type=\"checkbox\" name=\"ShareName1\">";
        		echo "</td></tr>";
        		echo "<tr><td>";
        		echo "Logs</td><td align=\"center\"><input type=\"checkbox\" name=\"ShareName2\">";
        		echo "</td></tr>";
        		echo "<tr><td>";
        		echo gettext("Base Postgres")."</td><td align=\"center\"><input type=\"checkbox\" name=\"PgsqlName\">";
        		echo "</td></tr>";
        		echo "<tr><td>";
        		echo gettext("Annuaire LDAP")."</td><td align=\"center\"><input type=\"checkbox\" name=\"LdapName\">";
        		echo "</td></tr>";
  		}

  		if($TypeServer!="") {
       			if($XferMethod!="rsyncd") {
				echo "<tr><td width=\"40%\">".gettext(" R&#233;pertoires &#224; sauvegarder :")."</td>";
			} else {
				echo "<tr><td width=\"40%\">".gettext("Modules &#224; sauvegarder :")."</td>";
			}	
        	 	echo "<td><input type=\"text\" name=\"Share\" value=\"$Share\" size=\"40\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer les r&#233;pertoires &#224; sauvegarder. Vous devez mettre des \' et des virgules entre chaque r&#233;pertoire.<br>Par exemple \'/home\',\'/var/se3\'<br><br> - Si la m&#233;thode de sauvegarde est rsyncd, vous devez indiquer le nom des modules, pas les r&#233;pertoires. Exemple \'module1\',\'module2\'. Les r&#233;pertoires &#224; sauvegarder sont &#224; indiquer dans le fichier rsyncd.conf se trouvant sur la machine &#224; sauvegarder.<br><br> - Pour les machines Windows indiquer \'C:\BCDI\'. Les fichiers syst&#232;mes en utilisation, ne peuvent pas &#234;tre sauvegard&#233;s.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
        		echo "<tr><td width=\"40%\">".gettext(" Exclusions :")."</td>";
			echo "<td><input type=\"text\" name=\"BackupFilesExclude\" value=\"$BackupFilesExclude\" size=\"40\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer ici les exclusions. Celle-ci se font par rapport aux r&#233;pertoires &#224; sauvegarder.<br><br>Par exemple, si vous avez d&#233;cid&#233; de sauvegarder le r&#233;pertoire /home et que vous ne souhaitez pas sauvegarder le sous r&#233;pertoire ssh indiquer ici \'ssh\' sans indiquer le r&#233;pertoire parent.<br><br>Pour les machines Windows, si vous avez indiqu&#233; C$ et que vous ne souhaitez pas sauvegarder le r&#233;pertoire windows, indiquer ce r&#233;pertoire dans cet espace.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  		}

  		echo "</table>";
  		echo "<br><br>";

  		echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
  		echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Configuration par d&#233;faut")."</td></tr>\n";
  		echo "<tr><td>".gettext("Souhaitez vous utiliser <br>la configuration par d&#233;faut ?")." </td>\n";
  		echo "<td align=\"center\">".gettext(" Oui : ")."<input type=radio name=\"defo\" value=\"Y\"";
  		if ($defo=="Y") { echo " checked"; }
  		echo ">".gettext(" Non :")."<input type=radio name=\"defo\" value=\"N\"";
  		if ($defo=="N") { echo " checked"; }
  		echo ">&nbsp;<u onmouseover=\"return escape".gettext("('Si vous souhaitez une configuration particuli&#232;re par machine, par rapport &#224; la configuration g&#233;n&#233;rale qui s\'applique &#224; toutes les machines.<br> Cela n\'a un sens que si vous sauvegardez plusieurs machines.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr></table>\n";
	} // fin de non archive	

	
	echo "<br><br>";
	echo "<center><input name=\"formsauv\" type=\"submit\"  value=\"".gettext("Suite")."\"></center>";
	echo "</form>\n";
	require ("pdp.inc.php");

}
?>
