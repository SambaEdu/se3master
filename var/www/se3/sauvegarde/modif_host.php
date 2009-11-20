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
   * file: modif_host.php

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
	$HostServer=$_GET['HostServer'];

if ($_GET[Share] != "") {
        $Share = stripslashes($Share);
}
if ($_GET[BackupFilesExclude] != "") {
        $BackupFilesExclude = stripslashes($BackupFilesExclude);
}
		
/****************************************************************************************/
// Relit le fichier pour avoir les variables

if ($pass == "") {
  $XferMethod = variables(XferMethod,$HostServer);
  $dhcp = GetDhcp($HostServer);
  $TypeServer = GetTypeServer($HostServer);
  if ($TypeServer =="") { $TypeServer="Autre"; }
  if ($XferMethod == "smb") {
	$Compte = variables(SmbShareUserName,$HostServer);
	$PassWord = variables(SmbSharePasswd,$HostServer);
	$Share = variables(SmbShareName,$HostServer);
  }	
  if ($XferMethod == "tar") {
	$Share = variables(TarShareName,$HostServer);
  }
  if ($XferMethod == "rsyncd") {
  	$Share = variables(RsyncShareName,$HostServer);
	$Compte = variables(RsyncdUserName,$HostServer);
	$PassWord = variables(RsyncdPasswd,$HostServer);
  }	
  if ($XferMethod == "rsync") {
  	$Share = variables(RsyncShareName,$HostServer);
  }	
  $BackupFilesExclude = variables(BackupFilesExclude,$HostServer);
  $FullPeriod = variables(FullPeriod,$HostServer);
  $IncrPeriod = variables(IncrPeriod,$HostServer);
  $FullKeepCnt = variables(FullKeepCnt,$HostServer);
  $FullKeepCntMin = variables(FullKeepCntMin,$HostServer);
  $IncrKeepCnt = variables(IncrKeepCnt,$HostServer);
  $IncrKeepCntMin = variables(IncrKeepCntMin,$HostServer);
  $FullAgeMax = variables(FullAgeMax,$HostServer);
  $IncrAgeMax = variables(IncrAgeMax,$HostServer);
  $EMailAdminUserName = variables(EMailAdminUserName,$HostServer);
  $hourBegin = variables(hourBegin,$HostServer);
  $hourEnd = variables(hourEnd,$HostServer);
  $weekDays = variables(weekDays,$HostServer);
  $ArchiveDest = variables(ArchiveDest,$HostServer);
  $ArchiveSplit = variables(ArchiveSplit,$HostServer);
  

// On traite si on doit afficher d'office la conf particuli&#232;re
  if (($FullPeriod != "") or ($IncrPeriod != "") or ($FullKeepCnt != "") or ($FullKeepCntMin != "") or ($IncrKeepCnt != "") or ($IncrKeepCntMin != "") or ($FullAgeMax != "") or ($IncrAgeMax != "") or ($EMailAdminUserName != "")) {
	$defo_conf = "Y";
  }	

} // Fin du premier passage		

/**********************************************************************/
echo "<P><h1>".gettext("Sauvegarde de la machine")." $HostServer</h1></P>";
echo "<br><br>";

echo "<form method=\"get\" action=\"modif_host_suite.php\" >\n";

echo "<input type=\"hidden\" name=\"HostServer\" value=\"$_GET[HostServer]\" />";
echo "<input type=\"hidden\" name=\"pass\" value=\"1\" />";
echo "<input type=\"hidden\" name=\"TypeServer\" value=\"$TypeServer\" />";

echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">
      <tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Machine &#224; sauvegarder")."</td></tr>\n";
	      
echo "<tr><td width=\"40%\">".gettext("Type de machine :")."</td>";
echo "<td>$TypeServer</td></tr>";

// Indique ici les choix impos&#233;s ou conseill&#233;s
	if (($TypeServer=="WinXP") and ($XferMethod=="")) {$XferMethod="smb";}
	if (($TypeServer=="WinXP") and ($Share=="")) {$Share="'C$'";}
 	if ((TypeMachine()=="Se3") and ($TypeServer=="Local")) 	{ 
		$dhcp = "0";
		$XferMethod = "tar";
		$Model = "Se3";
		$pass="0";
	}
	
	if ($TypeServer=="Archive") {
		$XferMethod = "archive";
	}	
	if ($TypeServer=="") { 
		$HostServer = "";
		$XferMethod = "";
	}

	if ($TypeServer=="Archive") {
//	  echo "<tr><td>Nom de l'archive</td><td><input type=text name=\"HostServer\" value=\"$HostServer\"></td></tr>";
	  echo "</table>\n";
	  echo "<br><br>";

	  echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
	  echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Support d'archivage")."</td></tr>\n";
	  echo "<tr><td>".gettext("Destination de l'archive")."</td><td><input type=\"text\" name=\"ArchiveDest\" value=\"$ArchiveDest\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer le support sur lequel archiver. /tmp pour le mettre dans le r&#233;pertoire tmp.")."<br><br>".gettext("Pour archiver sur une bande indiquer /dev/st0 (&#224; v&#233;rifier en fonction de votre machine).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	  echo "<tr><td>".gettext("Taille de l'archive")."</td><td><input type=\"text\" name=\"ArchiveSplit\" value=\"$ArchiveSplit\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer la taille de l\'archive. Par exemple pour archiver sur un CD, en utilisant un graveur, indiquer 650. l\'archive sera alors coup&#233;e en plusieurs fichiers de 650.<br><br>Si vous laissez vide, aucune taille limite ne sera donn&#233;e (0 pas d&#233;faut).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	  echo "</table><br><br>";
	  echo "<input type=\"hidden\" name=\"XferMethod\" value=\"archive\" />";
	  echo "<input type=\"hidden\" name=\"dhcp\" value=\"1\" />";
	} else {
			  
       echo "<tr><td>".gettext("Nom DNS")."</td><td><select name=\"dhcp\">";
       echo "<option value=\"0\""; if ($dhcp=="0") echo " selected"; echo ">".gettext("Trouvable par son nom (Ip fixe)")."</option>";
       echo "<option value=\"1\""; if ($dhcp=="1") echo " selected"; echo ">".gettext("Non trouvable par son nom (Pas d'ip fixe)")."</option>";
       echo "</select>&nbsp;<u onmouseover=\"return escape".gettext("('Si votre machine n\'est pas dans un serveur de nom, vous devez indiquer Pas d\'ip fixe. Ce cas est le plus fr&#233;quent dans un &#233;tablissement scolaire.<br>Ne pas oublier dans la configuration g&#233;n&#233;rale d\'indiquer la plage d\'adresses pour la recherche des machines.<br>Ce cas concerne toutes les machines clientes<br>Si votre machine est trouvable par son nom, indiquer Ip fixe.<br><br>Comment savoir si une machine est visible par le serveur de sauvegarde ?<br>Essayer depuis celui-ci de le pinguer avec le nom que vous avez indiqu&#233;.<br>Les machines windows sont recherch&#233;es avec leur nom netbios.<br><br>Attention : les firewall sur les machines &#224; sauvegarder, peuvent bloquer la recherche de la machine.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
       echo "</table>\n";

       echo "<br><br>";


       echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
       echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Type de Sauvegarde")."</td></tr>\n";
       echo "<tr>
             <td width=\"50%\">".gettext("Type de sauvegarde :")."</td>
             <td><select name=\"XferMethod\" ONCHANGE=\"this.form.submit();\">
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
	     echo "&nbsp;<u onmouseover=\"return escape".gettext("('S&#233;lectionner le type de protocole &#224; utiliser pour faire les sauvegardes.<br><br> - smb : &#224; utiliser pour sauvegarder les machines windows. Vous devez fournir un compte et un mot de passe pour se connecter. Il faut donner les droits n&#233;cessaires afin de pouvoir faire les sauvegardes.<br><br> - rsync : est utilis&#233; pour faire une sauvegarde sur une machine distante, via un tunnel crypt&#233; SSH. Vous devez fournir la cl&#233; que vous avez g&#233;n&#233;r&#233; sur le serveur de sauvegarde &#224; la machine &#224; sauvegarder afin de pouvoir vous y connecter. Voir la documentation.<br><br> - tar : disponible que si vous sauvegardez le serveur de sauvegarde lui m&#234;me.<br><br> - rsyncd : Vous devez mettre en place rsyncd sur la machine que vous souhaitez sauvegarder. Vous devrez indiquer compte plus mot de passe. Voir la documentation.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;";
            echo "<tr><td>";
      if(($XferMethod=="smb") or ($XferMethod=="rsyncd")) {
            echo "<TR><td>".gettext("Compte de connexion")."</td><td><input type=text name=\"Compte\" value=\"$Compte\"></td></tr>";
            echo "<tr><td>".gettext("Mot de passe")."</td><td><input type=text name=\"PassWord\" value=\"$PassWord\"></td></tr>";
      }


	echo "</table>\n";
      echo "<br><br>\n";

if ($TypeServer!="") {
       echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
       echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("R&#233;pertoires &#224; Sauvegarder")."</td></tr>\n";
}

if ($Model=="Se3") {
	echo "<tr><td>";
      	echo "ACL ".gettext("des r&#233;pertoires")." /var/se3 ".gettext("et")." /home </td><td align=\"center\"><input type=\"checkbox\" name=\"AclName\" checked disabled>";
	echo "&nbsp;<u onmouseover=\"return escape".gettext("('Les ACL de /var/se3 sont sauvegard&#233;es automatiquement toutes les nuits, dans /var/se3/save, les ACL de home, n\'ont pas besoin de l\'&#234;tre car reconstruite automatiquement.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
	echo "</td></tr>";
      	echo "<tr><td>";
      	echo gettext("Annuaire LDAP")."</td><td align=\"center\"><input type=\"checkbox\" name=\"LdapName\" checked disabled>";
        echo "&nbsp;<u onmouseover=\"return escape".gettext("('L\'annuaire LDAP est sauvegard&#233; automatiquement toutes les nuits dans /var/se3, en sauvegardant ce r&#233;pertoire vous les sauvegardez donc aussi.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
	echo "</td></tr>";
      	echo "<tr><td>";
      	echo gettext("Base")." MySQL</td><td align=\"center\"><input type=\"checkbox\" name=\"MysqlName\"";
        $sql="SELECT value FROM params WHERE name='mysql_all_save';";
        $result=mysql_query($sql);
        $row = mysql_fetch_row($result);
        mysql_close ();
        echo $row[0];
      	if($row[0]=="on") {echo " checked"; }
      	echo ">";
  	echo "&nbsp;<u onmouseover=\"return escape".gettext("('Une partie des bases MySQL (se3db et mysql) sont sauvegard&#233;es automatiquement. En cliquant sur sauvegarder les bases MySQL, vous allez sauvegarder les autres (Inventory ...).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;\n";
	echo "</td></tr>";
      } elseif ($Model=="Slis") {
             echo "<tr><td>";
             echo gettext("R&#233;pertoire")." /home</td><td align=\"center\"><input type=\"checkbox\" name=\"ShareName1\">";
             echo "</td></tr>";
             echo "<tr><td>";
             echo "Logs</td><td align=\"center\"><input type=\"checkbox\" name=\"ShareName2\">";
             echo "</td></tr>";
             echo "<tr><td>";
             echo gettext("Base")." Postgres</td><td align=\"center\"><input type=\"checkbox\" name=\"PgsqlName\">";
             echo "</td></tr>";
             echo "<tr><td>";
             echo gettext("Annuaire")." LDAP</td><td align=\"center\"><input type=\"checkbox\" name=\"LdapName\">";
             echo "</td></tr>";
     }
     if($TypeServer!="") {
             echo "<tr><td width=\"40%\">".gettext(" R&#233;pertoires &#224; sauvegarder :")."</td>";
             echo "<td><input type=\"text\" name=\"Share\" value=\"$Share\" size=\"35\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer les r&#233;pertoires &#224; sauvegarder. Vous devez mettre des \' et des virgules entre chaque r&#233;pertoire.<br>Par exemple \'/etc\',\'/var/se3\'<br><br>Pour les machines Windows indiquer \'C$\',\'D$\'.<br>Attention : les fichiers syst&#232;mes en utilisation, ne peuvent pas &#234;tre sauvegard&#233;s.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	     echo "<tr><td>".gettext("Exclusions :")." </td><td><input type=\"text\" name=\"BackupFilesExclude\" size=\"35\" value=\"$BackupFilesExclude\">&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer ici les exclusions. Celle-ci se font par rapport aux r&#233;pertoires &#224; sauvegarder.<br><br>Par exemple, si vous avez d&#233;cid&#233; de sauvegarder le r&#233;pertoire /etc et que vous ne souhaitez pas sauvegarder le sous r&#233;pertoire ssh indiquer ici \'ssh\' sans indiquer le r&#233;pertoire parent.<br><br>Pour les machines Windows, si vous avez indiqu&#233; C$ et que vous ne souhaitez pas sauvegarder le r&#233;pertoire windows, indiquer ce r&#233;pertoire dans cet espace.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>";
     }
     echo "</table>";
     echo "<br><br>";

     echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
     echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Configuration par d&#233;faut")."</td></tr>\n";
     if ($defo_conf == "Y") {
        echo "<tr><td align=\"center\" colspan=\"2\"><font color=\"orange\">".gettext("Vous aviez une configuration sp&#233;cifique pour cette machine.<br> Pour revenir &#224; la configuration par defaut, cliquer sur Oui,<br> pour la modifier cliquer sur Non.")."</font></td></tr>\n";
     }
     echo "<tr><td>".gettext("Souhaitez vous utiliser la configuration par d&#233;faut ?")." </td>\n";
     echo "<td align=\"center\">Oui : <input type=radio name=\"defo\" value=\"Y\"";
     if ($defo=="Y") { echo " checked"; }
     echo ">".gettext(" Non :")."<input type=radio name=\"defo\" value=\"N\"";
     if ($defo=="N") { echo " checked"; }
     echo ">&nbsp;<u onmouseover=\"return escape".gettext("('Si vous souhaitez une configuration particuli&#232;re par machine, par rapport &#224; la configuration g&#233;n&#233;rale qui s\'applique &#224; toutes les machines.<br> Cela n\'a un sens que si vous sauvegardez plusieurs machines.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr></table>\n";
     echo "<br><br>";
 } // fin du cas archive

     echo "<center><input name=\"formsauv\" type=\"submit\"  value=\"".gettext("Valider")."\"></center>
	   </form>\n";

require ("pdp.inc.php");

}
?>
