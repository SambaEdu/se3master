<?php
	require ("config.inc.php");
   	require_once ("functions.inc.php");
	require ("ldap.inc.php");
	require ("ihm.inc.php");
	include ("fonction_backup.inc.php");

	require_once("lang.inc.php");
	bindtextdomain('sauvegarde',"/var/www/se3/locale");
	textdomain ('sauvegarde');


	// Verifie les droits
	$login =isauth();
	if (ldap_get_right("system_is_admin",$login)!="Y")
       	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	
	$sql="Select value from params where name='usbdisk';";
	$query=mysql_query($sql);
	$row=mysql_fetch_row($query);
	$usbdisk=$row[0];

	###################################################################################
	# Fix Bpcmedia
	###################################################################################
	$sql2 = "UPDATE `params` SET `value` = '1' WHERE `params`.`name` ='bpcmedia' ;";
	$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	###################################################################################
	# Fix nas suffix
	###################################################################################
	$sql_suffix = "UPDATE params set value='' where name='NAS_mntsuffix' ;";
	$res_suffix = mysql_query($sql_suffix) or die("ERREUR: $sql_suffix");

	###################################################################################
	echo "<table align=\"center\" width=\"80%\" border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
	
	$drive="/var/lib/backuppc";
		echo "<tr><td>&nbsp;";
		echo gettext("Etat de la connexion au disque USB:")."</td><td align=center>";
		if (exec ('sudo /usr/share/se3/sbin/testbackup.sh')==false) {

			$msg5 = 'La connexion au disque est fonctionnelle<BR>En cliquant sur ce bouton, vous deconnecterez le disque USB afin de pouvoir proc&#233;der par exemple &#224; son &#233;change.';
			echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('$msg5')"."\"  href=sauv.php?action=umountUSB><IMG id=\"status_media\" style=\"border: 0px solid;\" SRC=\"../elements/images/enabled.png\" ></a>"; 
	
	
		} else { 
				$msg6 = gettext("Acc&#232;s au disque USB impossible<BR />En cliquant sur ce bouton, SE3 tentera de reconnecter le disque USB.");
				echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('$msg6')"."\"  href=sauv.php?action=restoreUSB><IMG id=\"status_media\" style=\"border: 0px solid;\" SRC=\"../elements/images/disabled.png\" ></a>";
		}
		echo "</td>\n"; 
		echo "<td align=center>";
		exec("/usr/share/se3/sbin/diskdetect.sh", $disks);
		echo "<form method=\"get\" action=\"sauv.php\">\n";
		echo "<select id=\"usbdisk\" name=\"usbdisk\" onchange=\"this.form.submit();\">";
		echo "<option>Choisir le disque USB</option>";
		for ($i=0; $i<count($disks); $i++) {
			echo "<option value=$disks[$i]";
			if ($disks[$i]==$usbdisk) echo " selected";
			echo ">".$disks[$i]."</option>\n";
		}
		if (count($disks) == 0) echo "<option selected>Pas de disque!</option>";
		echo "</select><td>";
		echo "</form>\n";

		echo "<tr><td>&nbsp;";
		echo gettext("Espace disponible")."</td><td align=\"center\">";
		$msg7= gettext("Taille disponible en Gb du disque sur lequel se trouve votre sauvegarde.");
		echo "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('".$msg7."');\" >";
		echo round(diskfreespace("$drive")/1024/1024/1024,2); echo " Gb </span></td><td></td></tr>";
		echo "</table>";
		echo "<br/><center><a href=\"sauv.php?action=format&usbdisk=$usbdisk\" onclick=\"return getformatconfirm();\">Formater disque USB</a></center>\n";

?>
