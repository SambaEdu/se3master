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
   * file: confif_defaut.php

  */	

include "entete.inc.php";
require ("config.inc.php");
require ("ldap.inc.php");
require ("ihm.inc.php");
include ("fonction_backup.inc.php");

require_once("lang.inc.php");

bindtextdomain('sauvegarde',"/var/www/se3/locale");
textdomain ('sauvegarde');

//aide
$_SESSION["pageaide"]="Sauvegarde Backuppc";

// Verifie les droits
if (is_admin("system_is_admin",$login)=="Y")  {



/*************** Affiche la fin du traitement *************************/
if($_GET['action']=="modif") {

	$WakeupSchedule=$_GET['WakeupSchedule'];
	$FullPeriod=$_GET['FullPeriod'];
	$IncrPeriod=$_GET['IncrPeriod'];
	$FullKeepCnt=$_GET['FullKeepCnt'];
	$FullKeepCntMin=$_GET['FullKeepCntMin'];
	$IncrKeepCnt=$_GET['IncrKeepCnt'];
	$IncrKeepCntMin=$_GET['IncrKeepCntMin'];
	$FullAgeMax=$_GET['FullAgeMax'];
	$IncrAgeMax=$_GET['IncrAgeMax'];
	$hourBegin=$_GET['hourBegin'];
	$hourEnd=$_GET['hourEnd'];
	$ipAddrBase=$_GET['ipAddrBase'];
	$first=$_GET['first'];
	$last=$_GET['last'];
	$EMailAdminUserName=$_GET['EMailAdminUserName'];
	$Lundi=$_GET['Lundi'];
	$Mardi=$_GET['Mardi'];
	$Mercredi=$_GET['Mercredi'];
	$Jeudi=$_GET['Jeudi'];
	$Vendredi=$_GET['Vendredi'];
	$Samedi=$_GET['Samedi'];
	$Dimanche=$_GET['Dimanche'];
	
	// On v&#233;rifie que les variables sont remplies, sinon on met les valeurs par d&#233;faut
	if ($WakeupSchedule == "") { $WakeupSchedule = "1..23"; }
	if ($FullPeriod == "") { $FullPeriod = "6.97"; }
	if ($IncrPeriod == "") { $IncrPeriod = "0.97"; }
	if ($FullKeepCnt == "") { $FullKeepCnt = "1"; }
	if ($FullKeepCntMin == "") { $FullKeepCntMin = "1"; }
	if ($IncrKeepCnt == "") { $IncrKeepCnt = "6"; }
	if ($IncrKeepCntMin == "") { $IncrKeepCntMin = "1"; }
	if ($FullAgeMax == "") { $FullAgeMax = "90"; }
	if ($IncrAgeMax == "") { $IncrAgeMax = "30"; }
	if ($hourBegin == "") { $hourBegin = "6.0"; }
	if ($hourEnd == "") { $hourEnd = "20.0"; }

	$weekDays = "[$Lundi,$Mardi,$Mercredi,$Jeudi,$Vendredi,$Samedi,$Dimanche]";
	$weekDays = preg_replace("/,,|,,,|,,,,|,,,,,|,,,,,,/",",",$weekDays);
	$weekDays = preg_replace("/\[,","[/",$weekDays);
	$weekDays = preg_replace("/,\]","]/",$weekDays);
	

	// On ouvre le fichier
	$fp=fopen("/etc/backuppc/config.pl","w+");
	
$IncrLevels="[1";
for ($i=2; $i <= $IncrKeepCnt; $i++) {
        $IncrLevels = $IncrLevels.", $i";
}
$IncrLevels = $IncrLevels."]";
$DEFAULT = "
#============================================================= -*-perl-*-
###########################################################################
# General server configuration
###########################################################################\n
\$ENV{'PATH'} = '/bin:/usr/bin';
delete @ENV{'IFS', 'CDPATH', 'ENV', 'BASH_ENV'};
\$Conf{ServerHost} = `hostname`;
chomp(\$Conf{ServerHost});

\$Conf{ServerPort} = -1;
\$Conf{ServerMesgSecret} = '';
\$Conf{MyPath} = '/bin';
\$Conf{UmaskMode} = 027;
\$Conf{WakeupSchedule} = [$WakeupSchedule];
\$Conf{MaxBackups} = 4;
\$Conf{MaxUserBackups} = 4;
\$Conf{MaxPendingCmds} = 10;
\$Conf{MaxBackupPCNightlyJobs} = 2;
\$Conf{BackupPCNightlyPeriod} = 1;
\$Conf{MaxOldLogFiles} = 14;
\$Conf{DfPath} = '/bin/df';
\$Conf{DfCmd} = '$dfPath $topDir';
\$Conf{SplitPath} = '/usr/bin/split';
\$Conf{ParPath}   = '/usr/bin/par2';
\$Conf{CatPath}   = '/bin/cat';
\$Conf{GzipPath}  = '/bin/gzip';
\$Conf{Bzip2Path} = '/usr/bin/bzip2';
\$Conf{DfMaxUsagePct} = 95;
\$Conf{TrashCleanSleepSec} = 300;";
// On traite ici si la plage d'adresse DHCP est ok
if (($ipAddrBase == "") or ($first == "") or ($last == "")) {
$DEFAULT .= "
\$Conf{DHCPAddressRanges} = [];";
} else {
$DEFAULT .= "
\$Conf{DHCPAddressRanges} = [
	{
		ipAddrBase => '$ipAddrBase',
		first => '$first',
		last => '$last',
	},	
];";
}
$DEFAULT .= "
\$Conf{BackupPCUser} = 'www-se3';
\$Conf{CgiDir}       = '/usr/share/backuppc/cgi-bin';
\$Conf{InstallDir}   = '/usr/share/backuppc';
\$Conf{BackupPCUserVerify} = 1;
\$Conf{HardLinkMax} = 31999;
\$Conf{SmbShareName} = 'C$';
\$Conf{SmbShareUserName} = '';
\$Conf{SmbSharePasswd} = '';
\$Conf{TarShareName} = '/';
\$Conf{FullPeriod} = $FullPeriod;
\$Conf{IncrPeriod} = $IncrPeriod;
\$Conf{FullKeepCnt} = $FullKeepCnt;
\$Conf{FullKeepCntMin} = $FullKeepCntMin;
\$Conf{FullAgeMax}     = $FullAgeMax;
\$Conf{IncrKeepCnt} = $IncrKeepCnt;
\$Conf{IncrKeepCntMin} = $IncrKeepCntMin;
\$Conf{IncrLevels} = $IncrLevels;
\$Conf{IncrAgeMax}     = $IncrAgeMax;
\$Conf{PartialAgeMax} = 3;
\$Conf{IncrFill} = 0;
\$Conf{RestoreInfoKeepCnt} = 10;
\$Conf{ArchiveInfoKeepCnt} = 10;
\$Conf{BackupFilesOnly} = undef;
\$Conf{BackupFilesExclude} = undef;
\$Conf{BlackoutBadPingLimit} = 3;
\$Conf{BlackoutGoodCnt}      = 7;
\$Conf{BlackoutPeriods} = [
    {
	hourBegin =>  $hourBegin,
	hourEnd   => $hourEnd,
	weekDays  => $weekDays,
    },
];
\$Conf{BackupZeroFilesIsFatal} = 1;
###########################################################################
# General per-PC configuration settings
# (can be overridden in the per-PC config.pl)
###########################################################################
\$Conf{XferLogLevel} = 1;
\$Conf{SmbClientPath} = '/usr/bin/smbclient';
\$Conf{SmbClientFullCmd} = '\$smbClientPath \\\\\$host\\\$shareName'
	    . ' \$I_option -U \$userName -E -N -d 1'
            . ' -c tarmode\\ full -Tc\$X_option - \$fileList';
\$Conf{SmbClientIncrCmd} = '\$smbClientPath \\\\\$host\\\$shareName'
	    . ' \$I_option -U \$userName -E -N -d 1'
	    . ' -c tarmode\\ full -TcN\$X_option \$timeStampFile - \$fileList';
\$Conf{SmbClientRestoreCmd} = '\$smbClientPath \\\\\$host\\\$shareName'
            . ' \$I_option -U \$userName -E -N -d 1'
            . ' -c tarmode\\ full -Tx -';
\$Conf{TarClientCmd} = '\$sshPath -q -x -n -l root \$host'
                    . ' /usr/bin/env LC_ALL=C \$tarPath -c -v -f - -C \$shareName+'
                    . ' --totals';
\$Conf{TarFullArgs} = '\$fileList+';
\$Conf{TarIncrArgs} = '--newer=\$incrDate+ \$fileList+';
\$Conf{TarClientRestoreCmd} = '\$sshPath -q -x -l root \$host'
		   . ' /usr/bin/env LC_ALL=C \$tarPath -x -p --numeric-owner --same-owner'
		   . ' -v -f - -C \$shareName+';
\$Conf{TarClientPath} = '/bin/tar';
\$Conf{RsyncClientPath} = '/usr/bin/rsync';
\$Conf{RsyncClientCmd} = '\$sshPath -q -x -l root \$host \$rsyncPath \$argList+';
\$Conf{RsyncClientRestoreCmd} = '\$sshPath -q -x -l root \$host \$rsyncPath \$argList+';
\$Conf{RsyncdClientPort} = 873;
\$Conf{RsyncdUserName} = '';
\$Conf{RsyncdPasswd} = '';
\$Conf{RsyncdAuthRequired} = 1;
\$Conf{RsyncCsumCacheVerifyProb} = 0.01;
\$Conf{RsyncArgs} = [
            '--numeric-ids',
            '--perms',
            '--owner',
            '--group',
            '--devices',
            '--links',
            '--times',
            '--block-size=2048',
            '--recursive',
];
\$Conf{RsyncRestoreArgs} = [
	    '--numeric-ids',
	    '--perms',
	    '--owner',
	    '--group',
	    '--devices',
	    '--links',
	    '--times',
	    '--block-size=2048',
	    '--relative',
	    '--ignore-times',
	    '--recursive',
];
\$Conf{ArchiveDest} = '/tmp';
\$Conf{ArchiveComp} = 'gzip';
\$Conf{ArchivePar} = 0;
\$Conf{ArchiveSplit} = 0;
\$Conf{ArchiveClientCmd} = '\$Installdir/bin/BackupPC_archiveHost'
	. ' \$tarCreatePath \$splitpath \$parpath \$host \$backupnumber'
	. ' \$compression \$compext \$splitsize \$archiveloc \$parfile *';

\$Conf{SshPath} = '/usr/bin/ssh';

\$Conf{NmbLookupPath} = '/usr/bin/nmblookup';
\$Conf{NmbLookupCmd} = '\$nmbLookupPath -A \$host';
\$Conf{NmbLookupFindHostCmd} = '\$nmbLookupPath \$host';
\$Conf{FixedIPNetBiosNameCheck} = 0;
\$Conf{PingPath} = '/bin/ping';
\$Conf{PingCmd} = '\$pingPath -c 1 \$host';
\$Conf{ServerInitdPath} = '';
\$Conf{ServerInitdStartCmd} = '';
\$Conf{CompressLevel} = 3;
\$Conf{PingMaxMsec} = 20;
\$Conf{ClientTimeout} = 7200;
\$Conf{MaxOldPerPCLogFiles} = 12;
\$Conf{DumpPreUserCmd}     = undef;
\$Conf{DumpPostUserCmd}    = undef;
\$Conf{RestorePreUserCmd}  = undef;
\$Conf{RestorePostUserCmd} = undef;
\$Conf{ArchivePreUserCmd}  = undef;
\$Conf{ArchivePostUserCmd} = undef;
\$Conf{ClientNameAlias} = undef;
\$Conf{PerlModuleLoad}     = undef;

###########################################################################
# Email reminders, status and messages
# (can be overridden in the per-PC config.pl)
###########################################################################
\$Conf{SendmailPath} = '/usr/sbin/sendmail';
\$Conf{EMailNotifyMinDays} = 2.5;
\$Conf{EMailFromUserName} = 'backuppc';
\$Conf{EMailAdminUserName} = '$EMailAdminUserName';
\$Conf{EMailNotifyOldBackupDays} = 7.0;
\$Conf{EMailNoBackupRecentSubj} = undef;
\$Conf{EMailNoBackupRecentMesg} = undef;
\$Conf{EMailNotifyOldOutlookDays} = 5.0;
\$Conf{EMailOutlookBackupSubj} = undef;
\$Conf{EMailOutlookBackupMesg} = undef;

###########################################################################
# CGI user interface configuration settings
# (can be overridden in the per-PC config.pl)
###########################################################################
\$Conf{CgiAdminUserGroup} = 'backuppc';
\$Conf{CgiAdminUsers}     = 'www-se3';
\$Conf{CgiURL} = 'http://'.\$Conf{ServerHost}.'/backuppc/index.cgi';
\$Conf{Language} = 'fr';
\$Conf{CgiUserHomePageCheck} = '';
\$Conf{CgiUserUrlCreate}     = 'mailto:%s';
\$Conf{CgiDateFormatMMDD} = 0;
\$Conf{CgiNavBarAdminAllHosts} = 1;
\$Conf{CgiSearchBoxEnable} = 1;
\$Conf{CgiNavBarLinks} = [
    {
        link  => \"?action=view&type=docs\",
        lname => \"Documentation\",    
    },
    {
        link  => \"http://backuppc.sourceforge.net/faq\",
        name  => \"FAQ\",              
    },
    {
        link  => \"http://backuppc.sourceforge.net\",
        name  => \"SourceForge\",      
    },
];
\$Conf{CgiStatusHilightColor} = {
    Reason_backup_failed           => '#ffcccc',
    Reason_backup_done             => '#ccffcc',
    Reason_no_ping                 => '#ffff99',
    Reason_backup_canceled_by_user => '#ff9900',
    Status_backup_in_progress      => '#66cc99',
};
\$Conf{CgiHeaders} = '<meta http-equiv=\"pragma\" content=\"no-cache\">';
\$Conf{CgiImageDir} = '/usr/share/backuppc/image';
\$Conf{CgiExt2ContentType} = { };
\$Conf{CgiImageDirURL} = '/backuppc/image';
\$Conf{CgiCSSFile} = 'BackupPC_stnd.css';
";

fwrite($fp,$DEFAULT);
fclose($fp);

// test si on peut ecrire dans le repertoire parent
if ($bpcmedia != "1") {
	if (is_link("/var/lib/backuppc")) {
		$drive=readlink('/var/lib/backuppc');
	} else {
		$drive="/var/lib/backuppc";
	}
   
   	$droits_ok=TestEcrire($drive);
   	if ($droits_ok!="1") {
        	echo "<center><font color=\"red\">";
		echo gettext("Attention, la sauvegarde n'a pas les droits n&#233;cessaires sur le r&#233;pertoire parent.<br>Vous devez modifer les droits en faisant un chown -R www-se3")." $drive.";
		echo "</center>";
         	echo "<br><br>";
        }
}

// reload la conf
if (EtatBackupPc ()=="1") {
	reloadBackuPpc();
} else {
	startBackupPc();
}

}

/****************************************************************************************/
// Relit le fichier pour avoir les variables
if(file_exists("/etc/backuppc/config.pl")) {
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
  	$ipAddrBase = variables(ipAddrBase,config);
  	
	if (preg_match("/'(.*)'/",$ipAddrBase,$reg)) {
  		$ipAddrBase=trim($reg[1]);
  	}	
  
  	$first = variables(first,config);
  	if (preg_match("/'(.*)'/",$first,$reg)) {
  		$first=trim($reg[1]);
  	}	
  
  	$last = variables(last,config);
  	if (preg_match("/'(.*)'/",$last,$reg)) {
  		$last=trim($reg[1]);
  	}	
} else {
	//valeurs par defaut
	if ($WakeupSchedule == "") { $WakeupSchedule = "1..23"; }
	if ($FullPeriod == "") { $FullPeriod = "6.97"; }
	if ($IncrPeriod == "") { $IncrPeriod = "0.97"; }
	if ($FullKeepCnt == "") { $FullKeepCnt = "1"; }
	if ($FullKeepCntMin == "") { $FullKeepCntMin = "1"; }
	if ($IncrKeepCnt == "") { $IncrKeepCnt = "6"; }
	if ($IncrKeepCntMin == "") { $IncrKeepCntMin = "1"; }
	if ($FullAgeMax == "") { $FullAgeMax = "90"; }
	if ($IncrAgeMax == "") { $IncrAgeMax = "30"; }
	if ($hourBegin == "") { $hourBegin = "6.0"; }
	if ($hourEnd == "") { $hourEnd = "20.0"; }
	$weekDays = "[1,2,3,4,5,,]";
	$weekDays = preg_replace("/,,|,,,|,,,,|,,,,,|,,,,,,/",",",$weekDays);
	$weekDays = preg_replace("/\[,/","[",$weekDays);
	$weekDays = preg_replace("/,\]/","]",$weekDays);
}	
	
/***********************************************************************/
echo "<P><h1>";
echo gettext("Configuration par d&#233;faut de Backuppc");
echo "</h1></P>";

if ($_GET['action'] == "modif") {
	if (EtatBackupPc() == "1") {
		echo "<center><h3>";
	  	echo gettext("Relecture du fichier de conf. Modifications prises en compte");
	  	echo "</h3></center>";
	}
	
	if (EtatBackupPc() == "0") {
		echo "<center><h3>";
	  	echo gettext ("Impossible de relancer BackupPc. Vous devez avoir introduit une erreur dans votre fichier de configuration. Veuillez la corriger");
	  	echo "</h3></center>";
	}  
}	


echo "<form method=\"get\" action=\"config_defaut.php\" >";
echo "<br><br>";
echo "<input type=\"hidden\" name=\"action\" value=\"modif\" />";
echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\" >";
echo "<tr><td class='menuheader' height=\"30\" colspan=\"2\" align=\"center\" >";
echo gettext("Heures des sauvegardes&nbsp;");
echo "<u onmouseover=\"return escape";
echo gettext ("('Indique l\'heure de r&#233;veille du serveur de sauvegarde, afin de v&#233;rifier s\'il doit lancer une sauvegarde.<br><br> - Pour 2 heure du matin, indiquer 2.0, pour 2h30 indiquer 2.50<br> - Pour tester toutes les heures sauf &#224; minuit, indiquer 1..23<br> - Pour tester toutes les 2 heures, 2,4,6,8,10,14,16,18,20,22<br><br>D&#233;faut : 1..23')");
echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp; </td></tr>\n";
echo "<tr><td>";
echo gettext("Heure ou sera test&#233;e si la sauvegarde doit &#234;tre lanc&#233;e :");
echo "</td>";
echo "<td><input name=\"WakeupSchedule\" type=\"text\" size=\"30\" value=\"$WakeupSchedule\"  ></td>
      </tr>
</table>\n";

//Periode de blackout
echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
echo "<tr><td class='menuheader' height=\"30\" colspan=\"4\" align=\"center\">";
echo gettext("P&#233;riodes sans sauvegarde&nbsp;");
echo "<u onmouseover=\"return escape";
echo gettext("('Vous permet de pr&#233;ciser les heures et les jours ou aucune sauvegarde ne peut &#234;tre &#233;ffectu&#233;e. Cette option peut &#234;tre modifi&#233;e par machine.<br><br>D&#233;faut : Aucune sauvegarde entre 7h et 20H tous les jours sauf le dimanche, afin d\'&#233;viter les sauvegarde en pleine journ&#233;e.')");
echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp; ";
echo "</tr>\n";
echo "<tr><td>";
echo gettext("Heure du d&#233;but");
echo "</td><td><input type=\"text\" name=\"hourBegin\" size=\"8\" value=\"$hourBegin\"></td>";
echo "<td>". gettext("Heure de fin")."</td><td><input type=\"text\" name=\"hourEnd\" size=\"8\" value=\"$hourEnd\"></td></tr>\n";
echo "<tr><td colspan=\"4\" align=\"center\">";
echo gettext("Lun")."<input type=\"checkbox\" name=\"Lundi\" value=\"1\""; 
if (preg_match ("/1/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Mar")." <input type=\"checkbox\" name=\"Mardi\" value=\"2\"";
if (preg_match ("/2/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Mer")." <input type=\"checkbox\" name=\"Mercredi\" value=\"3\"";
if (preg_match ("/3/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Jeu")."<input type=\"checkbox\" name=\"Jeudi\" value=\"4\"";
if (preg_match ("/4/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Ven")."<input type=\"checkbox\" name=\"Vendredi\" value=\"5\"";
if (preg_match ("/5/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Sam")." <input type=\"checkbox\" name=\"Samedi\" value=\"6\"";
if (preg_match ("/6/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo gettext(" Dim")."<input type=\"checkbox\" name=\"Dimanche\" value=\"7\"";
if (preg_match ("/7/",$weekDays,$reg)) { echo " checked"; } echo "> ";
echo "</td></tr></table><br><br>";



	// Nombre de jours entre deux sauvegardes
	echo "
	<table align=center width=\"80%\" border=1 cellspacing=\"1\" cellpadding=\"0\">";

	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Nombre de jours entre deux Sauvegardes")."&nbsp;<u onmouseover=\"return escape";
	echo gettext("('Pr&#233;ciser ici le nombre de jours entre deux sauvegardes. <br>Cette option peut &#234;tre donn&#233;e par machine.<br><br>D&#233;faut 6.97 (Soit 7 jours) pour les compl&#233;tes<br>0,97 (soit 1 jour pour les incr&#233;mentales. ')");
	echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	echo "<tr><td>";
	echo gettext("Entre deux sauvegardes compl&#232;tes :");
	echo "</td><td><input name=\"FullPeriod\" type=\"text\" size=\"30\" value=\"$FullPeriod\"  > </td>
	</tr>
	<tr>
    		<td>".gettext("Entre deux sauvegardes incr&#233;mentales :")."</td>
    		<td><input name=\"IncrPeriod\" type=\"text\" size=\"30\" value=\"$IncrPeriod\"  > </td>
	</tr>
	</table>
	<br><br>";
	
	
	// Nombre de sauvegardes a conserver
	echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Nombre de sauvegardes conserv&#233;es")."&nbsp;<u onmouseover=\"return escape";
	echo gettext("('Indiquer ici le nombre de sauvegardes &#224; conserver, lorsque tout est normal, ou au minimum.<br><br>Les valeurs par d&#233;faut sont en temps normal : 1 compl&#233;te, 6 incr&#233;mentales.<br>Et au minimum, 1 compl&#233;te, et 1 incr&#233;mentale.<br><br>Cette option peut &#234;tre d&#233;finie par machine.')");
	echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	echo "<tr><td>
		<table align=center width=\"100%\" border=1>
		<tr>
       	 <td colspan=2 bgcolor=#E0E0E0>".gettext("En temps normal")."</td>
       	 <td colspan=2 bgcolor=#E0E0E0>".gettext("Au minimum")."</td>
	</tr>
	<tr>
	 <td>".gettext("Complete :")."</td><td><input name=\"FullKeepCnt\" type=\"text\" size=\"10\" value=\"$FullKeepCnt\"  ></td>
	 <td>".gettext("Complete :")."</td><td><input name=\"FullKeepCntMin\" type=\"text\" size=\"10\" value=\"$FullKeepCntMin\"  ></td>	
 	</tr><tr>
	 <td>".gettext("Incr&#233;mentale :")."</td><td><input name=\"IncrKeepCnt\" type=\"text\" size=\"10\" value=\"$IncrKeepCnt\"  ></td>
	 <td>".gettext("Incr&#233;mentale :")."</td><td><input name=\"IncrKeepCntMin\" type=\"text\" size=\"10\" value=\"$IncrKeepCntMin\"  ></td>
 	</tr>
	</table></td></tr></table>
	<br>";


	// Suppression des anciennes sauvegardes
	echo "<table align=center width=\"80%\" border=1 cellspacing=\"1\" cellpadding=\"0\">";
	echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Suppression des anciennes sauvegardes")."&nbsp;<u onmouseover=\"return escape";
	echo gettext("('Vous permet de pr&#233;ciser la dur&#233;e de conservation des sauvegardes. Toutefois les valeurs minimales seront toujours conserv&#233;es.<br><br>D&#233;faut 90 jours pour les compl&#233;tes,<br>30 pour les incr&#233;mentales')");
	echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
	echo "<tr>
     	  <td>".gettext("Nb de jours apr&#232;s lesquels les sauvegardes compl&#232;tes <br>seront supprim&#233;es")."</td>
	  <td><select name=\"FullAgeMax\">
	  		<option value=$FullAgeMax>$FullAgeMax
	  		<option value=1>1
	  		<option value=2>2
	  		<option value=3>3
	  		<option value=4>4
	  		<option value=5>5
	  		<option value=6>6
	  		<option value=7>7
	  		<option value=8>8
	  		<option value=9>9
	  		<option value=10>10
	  		<option value=11>11
	  		<option value=12>12
	  		<option value=13>13
	  		<option value=14>14
	  		<option value=15>15
	  		<option value=16>16
	  		<option value=17>17
	  		<option value=18>18
	  		<option value=19>19
	  		<option value=20>20
	  		<option value=30>30
			<option value=60>60
			<option value=90>90
			<option value=120>120
		</select></td>
	
	
	</tr>
	<tr>
       	  <td>".gettext("Nb de jours apr&#232;s lesquel les sauvegardes incr&#233;mentielles<br> seront supprim&#233;es")."</td>
	  <td><select name=\"IncrAgeMax\">
	  		<option value=$IncrAgeMax>$IncrAgeMax
	  		<option value=1>1
	  		<option value=2>2
	  		<option value=3>3
	  		<option value=4>4
	  		<option value=5>5
	  		<option value=6>6
	  		<option value=7>7
	  		<option value=8>8
	  		<option value=9>8
	  		<option value=10>10
	  		<option value=11>11
	  		<option value=12>12
	  		<option value=13>13
	  		<option value=14>14
	  		<option value=15>15
	  		<option value=16>16
	  		<option value=17>17
	  		<option value=18>18
	  		<option value=19>19
	  		<option value=20>20
		</select></td>
	  
	</tr>
</table>\n";
echo "<br><br>";


// Recherche par DHCP
echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\"  align=\"center\">".gettext("Plage d'adresses de recherche des machines")."&nbsp;<u onmouseover=\"return escape";
echo gettext("('Lorsqu\'une machine n\'a pas une adresse IP fixe (n\'est pas dans un DNS), entre autre les clients Windows, vous devez indiquer au serveur dans quelle plage d\'adresses les rechercher.<br>Pour cela indiquer le d&#233;but de l\'adresse, par exemple 172.16.0 dans le champ adresse de base, et la fin de la premi&#232;re  adresse IP dans adresse de d&#233;but et la fin de la derni&#232;re dans adresse de fin.<br>Pour rechercher dans 172.16.0.10 jusqu\'&#224; 172.16.0.128 indiquer respectivement 172.16.0 dans le premier champ, 10 dans le deuxi&#232;me et 128 dans le dernier.')");
echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
echo "<tr>
      <td>".gettext("Adresse de base (forme 172.16.0) :")."</td>
      <td><input name=\"ipAddrBase\" type=\"text\" size=\"40\" value=\"$ipAddrBase\"  > </td>
      </tr>
      <tr><td>".gettext("Adresse de d&#233;but :")."</td><td><input type=\"text\" name=\"first\" value=\"$first\"></td></tr>		
      <tr><td>".gettext("Adresse de fin :")."</td><td><input type=\"text\" name=\"last\" value=\"$last\"></td></tr></table>\n";		
echo "<br><br>";


// Mail de l'administrateur devant recevoir les alertes de sauvegarde
echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
echo "<tr><td colspan=\"2\" class='menuheader' height=\"30\" align=\"center\">".gettext("Gestion des alertes")."&nbsp;<u onmouseover=\"return escape";
echo gettext("('Indiquqer ici, l\'adresse mail de la personne qui doit recevoir les alertes de la sauvegarde. <br>Cette information peut &#234;tre remplie par machine.<br><br>Remarque : ne pas oublier de configurer Se3 afin de pouvoir envoyer des mails.')");
echo "\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td></tr>\n";
echo "<tr><td>";
echo gettext("Mail envoy&#233; par d&#233;faut &#224; :");
echo "</td><td><input type=\"text\" name=\"EMailAdminUserName\" size=\"50\" value=\"$EMailAdminUserName\"></td></tr>";
echo "</table>\n";
echo "<br><br>";

echo "<center><u onmouseover=\"return escape";
echo gettext("('La validation va cr&#233;er le fichier de configuration par d&#233;faut, et relancer le serveur de sauvegarde.<br>Les champs vides, seront automatiquement compl&#233;t&#233;s, avec les valeurs par d&#233;faut.<br><br>Si celui-ci ne se relance pas, cela implique tr&#232;s probablement une erreur dans le fichier.')");
echo "\"><input type=\"submit\"  value=\"Valider\"></u></center>
</form>\n";

require ("pdp.inc.php");

}
?>
