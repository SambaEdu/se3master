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
   * file: conf_host.php

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
if (is_admin("system_is_admin",$login)=="Y") {


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
  


} // Fin du premier passage		

/**********************************************************************/
echo "<P><h1>".gettext("Param&#233;trage de la machine ")."$HostServer</h1></P>";
echo "<br><br>";

echo "<table align=center width=\"60%\" border=1 cellspacing=\"0\" cellpadding=\"0\">
      <tr><td colspan=\"2\" bgcolor=#E0E0E0 align=\"center\">".gettext("Configuration de la machine &#224; sauvegarder")."</td></tr>\n";
	      
echo "<tr><td>";

// Indique ici les choix impos&#233;s ou conseill&#233;s
if (($TypeServer=="WinRsync") and ($XferMethod=="rsyncd")) {
	echo "<b>".gettext("Installer cygwin pour Windows")."</b><br>";
	echo gettext("Commencer par le t&#233;l&#233;charger")." <a href=\"http://sourceforge.net/project/showfiles.php?group_id=34854&package_id=88133\">".gettext("ici")."</a>.";
	$ip=exec('cat /etc/network/interfaces | grep address | cut -d" " -f 2');
        echo "<br><br><b>".gettext("Cr&#233;er le fichier")." rsyncd.conf :</b><br>
		pid file = C:/rsyncd/rsyncd.pid <br>
		lock file = C:/rsyncd/rsyncd.lock<br>
		syslog facility=local5<br>
		auth user=$Compte<br>
		secrets file=C:/rsyncd/rsyncd.secrets<br>
		strict modes = false<br>
		hosts allow=$ip<br>
		read only=yes<br>
		list = false<br>
		<I>".gettext("Partie &#224; adapter")."</I><br>";
		$share1 = explode(",", $Share);
		for ($i = 0;$i < sizeof($share1); $i++) {
		   $module = preg_replace("/\'/","",$share1[$i]);
		   echo "[$module]<br>";
		   echo "  comment = ".gettext("ce que vous voulez")."<br>";
		   echo "  path = C:/repertoire/a/sauvegarder<br>";
		}
		echo"<i>".gettext("Mettre no &#224; read only, quand vous souhaitez restaurer (le yes assure une s&#233;curit&#233;).")."</I>
		     <br><br><b>".gettext("Cr&#233;er le fichier")." C:/rsyncd/rsyncd.secrets</b><br>";
		echo gettext("Placez dedans")." $Compte:$PassWord<br>";
		echo gettext("Vous devez r&#233;duire les droits en faisant un")." chmod 400 /etc/rsyncd.secrets<br><br>";
		echo "<b>".gettext("Lancer le script rsync.bat pour lancer rsync comme un service")."</b><br>";
		echo "<br>".gettext("Ne pas oublier de lire le README qui se trouve dans le paquet");
}			
elseif ($XferMethod=="rsyncd") {
	$ip=exec('cat /etc/network/interfaces | grep address | cut -d" " -f 2');
	echo gettext("Configuration de rsyncd sur une machine Linux")."<br><b>";
	echo gettext("Installer rsyncd")."</b><br>";
	echo gettext("Vous devez installer rsync, sur une debian faire un apt-get install rsync.")."<br><br><b>";
	echo gettext("Cr&#233;er le fichier")." /etc/rsyncd.conf :</b><br>";
	echo "
	uid=root<br>
	gid=root<br>
	use chroot=no <br>
	syslog facility=local5<br>
	auth user=$Compte<br>
	secrets file=/etc/rsyncd.secrets<br>
	hosts allow=$ip<br>
	read only=yes<br>
	<I>".gettext("Partie &#224; adapter")."</I><br>";
	$share1 = explode(",", $Share);
	for ($i = 0;$i < sizeof($share1); $i++) {
		$module = preg_replace("/\'/","",$share1[$i]);
		echo "[$module]<br>";
		echo "	comment = ".gettext("ce que vous voulez<br>");
		echo "	path = /repertoire/a/sauvegarder<br>";
	}	
	echo"<i>".gettext("Mettre no &#224; read only, quand vous souhaitez restaurer (le yes assure une s&#233;curit&#233;).")."</I>
	<br><br><b>".gettext("Cr&#233;er le fichier")." /etc/rsyncd.secrets</b><br>
	echo  \"$Compte:$PassWord\" > /etc/rsyncd.secrets<br>";
	echo gettext("Vous devez r&#233;duire les droits en faisant un")." chmod 400 /etc/rsyncd.secrets<br><br><b>";
	echo gettext("Lancer")." rsyncd</b><br>";
	echo gettext("Ajouter la ligne suivante dans")." /etc/inetd.conf<br>
	rsync	stream	tcp	nowait	root	/usr/bin/rsync rsyncd.conf --daemon<br>";
	echo gettext("Relancer alors inetd en faisant un")." killall -HUP inetd.<br>";
	echo gettext("rsync doit alors &#234;tre &#224; l'&#233;coute, pour v&#233;rifier faire un")." netstat -na | grep 873.<br><br>";
	echo gettext("Lancer une sauvegarde pour tester.");
	
}	
 
elseif ($XferMethod=="smb") {
	echo gettext("Configuration de SMB sur une machine Windows")."<br><br>";
	echo gettext("Vous devez cr&#233;er un compte")." $Compte ".gettext("avec comme mot de passe")." $PassWord ".gettext(" sur la machine Windows &#224; sauvegarder et donner les droits sur le r&#233;pertoire que vous souhaitez sauvegarder &#224; ce compte.")."<br><br>".gettext(" La machine doit avoir comme nom netbios")." $HostServer."; 
}

elseif (($XferMethod=="tar") && ($HostServer == "localhost")) {
	echo gettext("Le type de sauvegarde est tar sur la machine localhost")."<br><br>";
	echo gettext("Vous n'avez rien &#224; faire sur la machine. Vous &#234;tes dans le cas ou cette machine est un serveur  Se3. Si vous avez activ&#233; la sauvegarde de LDAP, MySQL et les ACL, ils seront plac&#233;s avant dans le r&#233;pertoire")." /etc/sove."; 
}

elseif ($XferMethod=="tar") {
	echo gettext("Configuration de tar sur la machine");
	echo gettext("Attention, il n'est pas possible d'utiliser tar sur une machine distante.")."<br><br>".gettext("Vous devez utiliser soit rsync, soit rsyncd pour les machines linux.")."<br><br>".gettext("Pour une machine Windows vous pouvez utiliser rsync ou rsyncd.");
	
} 

elseif ($XferMethod=="rsync") {
	echo "<b>";
	echo gettext("Installer rsync sur la machine &#224; sauvegarder.")."</b><br>".gettext("Sur une Debian apt-get install rsync.");
	echo "<br><br><b>";
	echo gettext("Copier la cl&#233;");
	echo "</b><br>";
	echo gettext("Copier la cl&#233; publique qui se trouve sur le serveur Se3 dans")." /var/remote_adm/.ssh/id_rsa.pub,".gettext(" sur la machine que vous souhaitez sauvegarder, dans le r&#233;pertoire")." /root/.ssh/ ".gettext(" et la renomer en authorized_keys. R&#233;duire les droits en faisant un")." chmod 400 /root/.ssh/authozed_keys.";
	echo "<br><br><b>".gettext("Tester")."</b><br>".gettext("Connectez vous depuis ce serveur vers la machine &#224; sauvegarder, pour cela faites su www-se3, puis ssh root@machine_a_sauvegarder")."<br>".gettext("Vous devez &#234;tre connect&#233; sans avoir &#224; taper un mot de passe.")."<br>";
}

echo "</td></tr>";
require ("pdp.inc.php");

}
?>
