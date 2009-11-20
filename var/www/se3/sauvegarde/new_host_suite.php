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
   * file: new_host_suite.php

  */	


include ("fonction_backup.inc.php");

	
// if ((is_admin("system_is_admin",$login)=="Y") or (is_admin("inventaire_can_read",$login)=="Y")) {
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

// Test si le nom donn&#233; n'est pas deja utilis&#233;

if ($HostServer != "") {
        if (HostExist($HostServer)=="true") { 
		$err="1"; 
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&Share=$Share&defo=$defo&Compte=$Compte&PassWord=$PassWord&AclName=$AclName&LdapName=$LdapName&MysqlName=$MysqlName&Secrets=$Secrets&dhcp=$dhcp&BackupFilesExclude=$BackupFilesExclude&TypeServerOld=$TypeServerOld&err=$err\">";
	exit;
	}
}

if (($HostServer != "") and ($XferMethod=="archive") and ($ArchiveDest!="")) {
	if ($ArchiveSplit=="") { $ArchiveSplit="0"; }
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host_valid.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&ArchiveDest=$ArchiveDest&ArchiveSplit=$ArchiveSplit&dhcp=$dhcp&TypeServerOld=$TypeServerOld\">";
	exit;
}


if (($HostServer == "") or ($TypeServer == "") or ($XferMethod == "") or ($dhcp == "") or ($Share == "") or ($defo == "")) {
	$err="4";
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&Share=$Share&defo=$defo&Compte=$Compte&PassWord=$PassWord&AclName=$AclName&LdapName=$LdapName&MysqlName=$MysqlName&Secrets=$Secrets&dhcp=$dhcp&BackupFilesExclude=$BackupFilesExclude&TypeServerOld=$TypeServerOld&err=$err\">";
	exit;
}	


// On teste si le compte et mot de passe sont remplis
if (($XferMethod == "smb") or ($XferMethod == "rsyncd")) {
	if (($Compte == "") or ($PassWord == "")) {
		$err = "2";
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&Share=$Share&defo=$defo&Compte=$Compte&PassWord=$PassWord&AclName=$AclName&LdapName=$LdapName&MysqlName=$MysqlName&Secrets=$Secrets&dhcp=$dhcp&BackupFilesExclude=$BackupFilesExclude&TypeServerOld=$TypeServerOld&err=$err\">";
		exit;
		}
}	
/*
if ($XferMethod == "rsync") {
	if ($Compte == "") {
		$err = "3";
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&Share=$Share&defo=$defo&Compte=$Compte&PassWord=$PassWord&AclName=$AclName&LdapName=$LdapName&MysqlName=$MysqlName&Secrets=$Secrets&dhcp=$dhcp&BackupFilesExclude=$BackupFilesExclude&TypeServerOld=$TypeServerOld&err=$err\">";
	exit;
	}
}
*/

/********************* Tout est Ok  Suite de la conf ***************************************/

if ($defo=="N") { // Dans le cas ou on veut faire une conf particuli&#233;re	
	include "entete.inc.php";
  	require ("config.inc.php");
  	require ("ldap.inc.php");
  	require ("ihm.inc.php");

  	require_once("lang.inc.php");
  	bindtextdomain('sauvegarde',"/var/www/se3/locale");
  	textdomain ('sauvegarde');
	
	// Rechercche les valeurs par defaut
	$WakeupSchedule = variables(WakeupSchedule,config);
        $FullPeriod = variables(FullPeriod,config);
	$IncrPeriod = variables(IncrPeriod,config);
	$FullKeepCnt = variables(FullKeepCnt,config);
	$FullKeepCntMin = variables(FullKeepCntMin,config);
	$IncrKeepCnt = variables(IncrKeepCnt,config);
	$IncrKeepCntMin = variables(IncrKeepCntMin,config);
	$FullAgeMax = variables(FullAgeMax,config);
	$IncrAgeMax = variables(IncrAgeMax,config);
	$EMailAdminUserName = variables(EMailAdminUserName,config);
	$hourBegin = variables(hourBegin,config);
	$hourEnd = variables(hourEnd,config);
	$weekDays = variables(weekDays,config);

  	echo "<P><h1>".gettext("Configuration sp&#233;cifique &#224; la machine")." $HostServer</h1></P>";
	echo "<form method=\"get\" action=\"new_host_valid.php\" >";
  	echo "<br><br>\n";
  	echo "<table align=center width=\"70%\" border=1 cellspacing=\"1\" cellpadding=\"0\">\n";
  	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Nombre de jours entre deux Sauvegardes")."</td></tr>\n";
  	echo "<tr><td>".gettext("Entre deux sauvegardes compl&#232;tes :")."</td>";
  	echo "<td><input name=\"FullPeriod\" type=\"text\" size=\"30\" value=\"$FullPeriod\" >&nbsp;<u onmouseover=\"return escape".gettext("('Pr&#233;ciser ici le nombre de jours entre deux sauvegardes compl&#233;tes.<br><br>D&#233;faut 6.97 (Soit 7 jours).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  	echo "<tr><td>".gettext("Entre deux sauvegardes incr&#233;mentales :")."</td>";
  
  	echo "<td><input name=\"IncrPeriod\" type=\"text\" size=\"30\" value=\"$IncrPeriod\" >&nbsp;<u onmouseover=\"return escape".gettext("('Pr&#233;ciser ici le nombre de jours entre deux sauvegardes incr&#233;mentales.<br><br>D&#233;faut 0,97 (Soit 1 jour).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
  	echo "</tr></table>\n";
  
  	echo "<br><br>";

  	echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
  	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">Nombre de sauvegardes conserv&#233;es&nbsp;<u onmouseover=\"return escape".gettext("('Indiquer ici le nombre de sauvegardes &#224; conserver, lorsque tout est normal, ou au minimum.<br><br>Les valeurs par d&#233;faut sont en temps normal : 1 compl&#233;te, 6 incr&#233;mentales.<br>Et au minimum, 1 compl&#233;te, et 1 incr&#233;mentale. ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  	echo "<tr><td>";
  	echo "<table align=center width=\"100%\" border=1>\n";
  	echo "<tr><td colspan=2 bgcolor=#E0E0E0>".gettext("En temps normal")."</td><td colspan=2 bgcolor=#E0E0E0>".gettext("Au minimum")."</td></tr>";
  	echo "<tr><td>".gettext("Complete :")."</td><td><input name=\"FullKeepCnt\" type=\"text\" size=\"10\" value=\"$FullKeepCnt\"  ></td>\n";
  	echo "<td>".gettext("Complete :")."</td><td><input name=\"FullKeepCntMin\" type=\"text\" size=\"10\" value=\"$FullKeepCntMin\"  ></td>\n";  
  	echo "</tr><tr><td>".gettext("Incr&#233;mentale :")."</td><td><input name=\"IncrKeepCnt\" type=\"text\" size=\"10\" value=\"$IncrKeepCnt\"  ></td>\n";
  	echo "<td>".gettext("Incr&#233;mentale :")."</td><td><input name=\"IncrKeepCntMin\" type=\"text\" size=\"10\" value=\"$IncrKeepCntMin\"  ></td>";
  	echo "</tr></table>\n";
  	echo "</td></tr></table>\n";

  	echo "<br><br>";

  	echo "<table align=center width=\"70%\" border=1 cellspacing=\"1\" cellpadding=\"0\">\n";
  	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Suppression des anciennes sauvegardes")."&nbsp;<u onmouseover=\"return escape".gettext("('Vous permet de pr&#233;ciser la dur&#233;e de conservation des sauvegardes. Toutefois les valeurs minimales seront toujours conserv&#233;es.<br><br>D&#233;faut 90 jours pour les compl&#233;tes,<br>30 pour les incr&#233;mentales')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  	echo "<tr><td>".gettext("Nb de jours apres lesquels les sauvegardes<br> compl&#232;tes seront supprim&#233;es")."</td>";
  	echo "<td><select name=\"FullAgeMax\">\n";
  	echo "<option value=$FullAgeMax>$FullAgeMax<option value=1>1<option value=2>2<option value=3>3<option value=4>4<option value=5>5<option value=6>6<option value=7>7<option value=8>8<option value=9>9<option value=10>10<option value=11>11<option value=12>12<option value=13>13<option value=14>14<option value=15>15<option value=16>16<option value=17>17<option value=18>18<option value=19>19<option value=20>20</select></td></tr>\n";
  	echo " <tr><td>".gettext("Nb de jours apres lesquel les sauvegardes<br> incr&#233;mentielles seront supprim&#233;es")."</td>\n";
  	echo "<td><select name=\"IncrAgeMax\"><option value=$IncrAgeMax>$IncrAgeMax<option value=1>1<option value=2>2<option value=3>3<option value=4>4<option value=5>5<option value=6>6<option value=7>7<option value=8>8<option value=9>8<option value=10>10<option value=11>11<option value=12>12<option value=13>13<option value=14>14<option value=15>15<option value=16>16<option value=17>17<option value=18>18<option value=19>19<option value=20>20</select></td>\n";
  	echo "</tr></table>\n";
  
  	echo "<br><br>";

  	echo "<table align=center width=\"70%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
  	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">Gestion des alertes&nbsp;<u onmouseover=\"return escape".gettext("('Indiquqer ici, l\'adresse mail de la personne qui doit recevoir les alertes de la sauvegarde. <br>Cette information peut &#234;tre remplie par machine.<br><br>Remarque : ne pas oublier de configurer Se3 afin de pouvoir envoyer des mails.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
  	echo "<tr><td>";
  	echo gettext("Mail envoy&#233; &#224; :")." </td><td><input type=\"text\" name=\"EMailAdminUserName\" size=\"50\" value=\"$EMailAdminUserName\"></td></tr>";
  	echo "</table>\n";
  
  	echo "<br><br>";

  	echo "<input type=\"hidden\" name=\"HostServer\" value=\"$HostServer\" />";
  	echo "<input type=\"hidden\" name=\"TypeServer\" value=\"$TypeServer\" />";
  	echo "<input type=\"hidden\" name=\"XferMethod\" value=\"$XferMethod\" />";
  	echo "<input type=\"hidden\" name=\"Share\" value=\"$Share\" />";
  	echo "<input type=\"hidden\" name=\"Compte\" value=\"$Compte\" />";
  	echo "<input type=\"hidden\" name=\"PassWord\" value=\"$PassWord\" />";
  	echo "<input type=\"hidden\" name=\"AclName\" value=\"$AclName\" />";
  	echo "<input type=\"hidden\" name=\"LdapName\" value=\"$LdapName\" />";
  	echo "<input type=\"hidden\" name=\"MysqlName\" value=\"$MysqlName\" />";
  	echo "<input type=\"hidden\" name=\"Secrets\" value=\"$Secrets\" />";
  	echo "<input type=\"hidden\" name=\"dhcp\" value=\"$dhcp\" />";
  	echo "<input type=\"hidden\" name=\"BackupFilesExclude\" value=\"$BackupFilesExclude\" />";

  	echo "<center><u onmouseover=\"return escape".gettext("('La validation va cr&#233;er le fichier de configuration par d&#233;faut, et relancer le serveur de sauvegarde.<br>Les champs vides, seront automatiquement compl&#233;t&#233;s, avec les valeurs par d&#233;faut.<br><br>Si celui-ci ne se relance pas, cela implique tr&#232;s probablement une erreur dans le fichier.<br><br>Si vous savez pas comment remplir les champs, vous pouvez les laisser libre et simplement valider')")."\"><input type=\"submit\"  value=\"".gettext("Valider")."\"></u></center>";
  	echo "</form>\n";

  	require ("pdp.inc.php");

} elseif ($_GET[defo] == "Y") {
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=new_host_valid.php?HostServer=$HostServer&TypeServer=$TypeServer&XferMethod=$XferMethod&Share=$Share&defo=$defo&Compte=$Compte&PassWord=$PassWord&AclName=$AclName&LdapName=$LdapName&MysqlName=$MysqlName&Secrets=$Secrets&dhcp=$dhcp&BackupFilesExclude=$BackupFilesExclude\">";
}

?>			  
  
