<?php


   /**
   
   * Permet configurer la sauvegarde (Backuppc)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo /usr/share/se3/scripts/tarCreate -v -f - -C \$shareName+ --totals    
   */

   /**

   * @Repertoire: sauvegarde
   * file: new_host_valid.php

  */	



include ("fonction_backup.inc.php");
require ("config.inc.php");
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
	$Share = stripslashes2($Share);
}	
if ($BackupFilesExclude != "") {
	$BackupFilesExclude = stripslashes2($BackupFilesExclude);
}
/*************** Affiche la fin du traitement *************************/

// On ouvre le fichier
$fichier = "/etc/backuppc/".$HostServer.".pl";
$fp=fopen("$fichier","w+");

$HOSTFILE = "
#============================================================= -*-perl-*-
###########################################################################
# General per-PC configuration settings
# (can be overridden in the per-PC config.pl)
###########################################################################
\$Conf{XferMethod} = '$XferMethod';
\$Conf{XferLogLevel} = 1;";

if ($XferMethod=="archive") {
$HOSTFILE .= "
\$Conf{ArchiveDest} = '$ArchiveDest';
\$Conf{ArchiveComp} = 'gzip';
\$Conf{ArchivePar} = 0;
\$Conf{ArchiveSplit} = $ArchiveSplit;
\$Conf{ArchiveClientCmd} = '\$Installdir/bin/BackupPC_archiveHost'
        . ' \$tarCreatePath \$splitpath \$parpath \$host \$backupnumber'
	        . ' \$compression \$compext \$splitsize \$archiveloc \$parfile *';
\$Conf{ArchivePreUserCmd}  = undef;
\$Conf{ArchivePostUserCmd} = undef;
";
}
elseif ($XferMethod=="smb") {
$HOSTFILE .= "
\$Conf{SmbShareName} = [$Share];
\$Conf{SmbShareUserName} = '$Compte';
\$Conf{SmbSharePasswd} = '$PassWord';
\$Conf{SmbClientPath} = '/usr/bin/smbclient';
\$Conf{SmbClientFullCmd} = '\$smbClientPath \\\\\\\\\$host\\\\\$shareName'
	    . ' \$I_option -U \$userName -E -N -d 1'
            . ' -c tarmode\\ full -Tc\$X_option - \$fileList';
\$Conf{SmbClientIncrCmd} = '\$smbClientPath \\\\\\\\\$host\\\\\$shareName'
	    . ' \$I_option -U \$userName -E -N -d 1'
	    . ' -c tarmode\\ full -TcN\$X_option \$timeStampFile - \$fileList';
\$Conf{SmbClientRestoreCmd} = '\$smbClientPath \\\\\\\$host\\\\\$shareName'
            . ' \$I_option -U \$userName -E -N -d 1'
            . ' -c tarmode\\ full -Tx -';
\$Conf{BackupFilesExclude} = [$BackupFilesExclude];
";
} elseif($XferMethod=="tar") {
$HOSTFILE .= "
\$Conf{TarShareName} = [$Share];
";
if ($HostServer=="localhost") {
$HOSTFILE .= "
\$Conf{TarClientCmd} = '/usr/bin/sudo /usr/share/se3/scripts/tarCreate -v -f - -C \$shareName+ --totals';
";
} else {
$HOSTFILE .= "
\$Conf{TarClientCmd} = '\$sshPath -q -x -n -l root \$host'
                    . ' /usr/bin/env LC_ALL=C \$tarPath -c -v -f - -C \$shareName+'
                    . ' --totals';
";
}
$HOSTFILE .= "
\$Conf{TarFullArgs} = '\$fileList+';
\$Conf{TarIncrArgs} = '--newer=\$incrDate+ \$fileList+';
\$Conf{TarClientRestoreCmd} = '\$sshPath -q -x -l root \$host'
		   . ' /usr/bin/env LC_ALL=C \$tarPath -x -p --numeric-owner --same-owner'
		   . ' -v -f - -C \$shareName+';
\$Conf{TarClientPath} = '/bin/tar';
\$Conf{BackupFilesExclude} = [$BackupFilesExclude];
";
} elseif(($XferMethod=="rsync") or ($XferMethod=="rsyncd")) {
$HOSTFILE .= "
\$Conf{RsyncClientPath} = '/usr/bin/rsync';
\$Conf{RsyncClientCmd} = '\$sshPath -q -x -l root \$host \$rsyncPath \$argList+';
\$Conf{RsyncClientRestoreCmd} = '\$sshPath -q -x -l root \$host \$rsyncPath \$argList+';
\$Conf{RsyncShareName} = [$Share];
\$Conf{RsyncdClientPort} = 873;
\$Conf{RsyncdUserName} = '$Compte';
\$Conf{RsyncdPasswd} = '$PassWord';
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
\$Conf{BackupFilesExclude} = [$BackupFilesExclude];
";
}
$FullPeriod=$_GET['FullPeriod'];
if (($FullPeriod != "")&& ($FullPeriod != variables(FullPeriod,config))) {
$HOSTFILE .= "
\$Conf{FullPeriod} = $FullPeriod;
";
}
$IncrPeriod=$_GET['IncrPeriod'];
if (($IncrPeriod != "")&&($IncrPeriod != variables(IncrPeriod,config))) {
$HOSTFILE .= "
\$Conf{IncrPeriod} = $IncrPeriod;
";
}
$FullKeepCnt=$_GET['FullKeepCnt'];
if (($FullKeepCnt != "")&&($FullKeepCnt != variables(FullKeepCnt,config))) {
$HOSTFILE .= "
\$Conf{FullKeepCnt} = $FullKeepCnt;
";
}
$FullKeepCntMin=$_GET['FullKeepCntMin'];
if (($FullKeepCntMin != "")&&($FullKeepCntMin != variables(FullKeepCntMin,config))) {
$HOSTFILE .= "
\$Conf{FullKeepCntMin} = $FullKeepCntMin;
";
}
$IncrKeepCnt=$_GET['IncrKeepCnt'];
if (($IncrKeepCnt != "")&&($IncrKeepCnt != variables(IncrKeepCnt,config))) {
$HOSTFILE .= "
\$Conf{IncrKeepCnt} = $IncrKeepCnt;
";
}
$IncrKeepCntMin=$_GET['IncrKeepCntMin'];
if (($IncrKeepCntMin != "")&&($IncrKeepCntMin != variables(IncrKeepCntMin,config))) {
$HOSTFILE .= "
\$Conf{IncrKeepCntMin} = $IncrKeepCntMin;
";
}
$FullAgeMax=$_GET['FullAgeMax'];
if (($FullAgeMax != "")&&($FullAgeMax != variables(FullAgeMax,config))) {
$HOSTFILE .= "
\$Conf{FullAgeMax} = $FullAgeMax;
";
}
$IncrAgeMax=$_GET['IncrAgeMax'];
if (($IncrAgeMax != "")&&($IncrAgeMax != variables(IncrAgeMax,config))) {
$HOSTFILE .= "
\$Conf{IncrAgeMax} = $IncrAgeMax;
";
}
$EMailAdminUserName=$_GET['EMailAdminUserName'];
if (($EMailAdminUserName != "")&&($EMailAdminUserName != variables(EMailAdminUserName,config))) {
$HOSTFILE .= "
\$Conf{EMailAdminUserName} = '$EMailAdminUserName';
";
}

fwrite($fp,$HOSTFILE);
fclose($fp);

/* Code invalide, pas les droits de faire cela: A SUPPRIMER SI PAS MIEUX
chmod($fichier,0775);
chown($fichier,"backuppc");
chgrp($fichier,"www-data");
*/
/**********************************Creation des fichiers de sauvegarde ***********************/

if (($HostServer == "localhost") and ($XferMethod == "tar")) {
    $sql="Delete from params where name='mysql_all_save';";
   	mysql_query($sql);
   	$sql="Insert into params values ('', 'mysql_all_save', '".$MysqlName."', '5', '0', 'Sauvegarde de l ensemble des base SQL pour localhost');";
   	mysql_query($sql);
   	mysql_close();
} // fin de si localhost
/***********************************************************************************/
// Si archive on cr&#233;e le r&#233;pertoire.
if ($XferMethod == "archive") {
	$rep_host = "/var/lib/backuppc/pc/".$HostServer;
	if ( ! is_dir($rep_host)) {
		mkdir($rep_host,0750);
	}	
}	


// Ajout dans le fichier hosts
  if (HostExist($HostServer)) { // si la machine existe d&#233;ja dans le fichier hosts on la vire d'abord
	DeleteHost($HostServer);
  }
	
AjoutHosts($HostServer,$dhcp,$TypeServer);
reloadBackuPpc();

// revient sur la page des machines

if ($XferMethod == "archive") {
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=sauv.php\">";
} else {
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=sauvhost.php\">";
}

?>
