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

	###################################################################################
	# Fix Bpcmedia
	###################################################################################
	$sql2 = "UPDATE `params` SET `value` = '3' WHERE `params`.`name` ='bpcmedia' ;";
	$c2 = mysql_query($sql2) or die("ERREUR: $sql2");

	
	###################################################################################


			$drive='/var/lib/backuppc/'.$NAS_mntsuffix;
			
			echo "<table align=\"center\" width=\"80%\" border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
			echo "<tr><td>&nbsp;";
			echo gettext("Etat connexion au disque NAS :  ");
			if (exec ('sudo /usr/share/se3/sbin/testbackup.sh')==false) {
				$msg = 'La connexion au serveur de sauvegarde est fonctionnelle<BR>En cliquant sur ce bouton, vous deconnecterez le disque NAS afin de pouvoir proc&#233;der par exemple &#224; son &#233;change.';
			       echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('$msg')"."\"  href=sauv.php?action=umountUSB><IMG id=\"status_media\" style=\"border: 0px solid;\" SRC=\"../elements/images/enabled.png\" ></a>"; 
	
				echo "</td><td align=\"center\">";
				echo "<span onmouseout=\"UnTip()\" onmouseover=\"Tip('Taille disponible en Gb du disque sur lequel se trouve votre sauvegarde.')"."\">";
				#echo round(diskfreespace("$drive")/1024/1024/1024,2); 
				
				#echo " Gb ";
				exec ("sudo /usr/share/se3/scripts/dfbck.sh",$output);
				echo $output[0]."</span></td></tr>";
				$test_button = 'disabled="disabled"';
						                        
			} else {
				echo "</td><td align=\"center\">";
				$msg = gettext("Acc&#232;s au NAS impossible.<BR />En cliquant sur ce bouton, <BR /> SE3 tentera de s\'y reconnecter.");
				echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('$msg')"."\"  href=sauv.php?action=restoreUSB><IMG id=\"status_media\" style=\"border: 0px solid;\" SRC=\"../elements/images/disabled.png\" ></a>";
				echo "</td></tr>";
				$test_button = '';
                        }
		
			
		$msg3 = 'Il faut songer &agrave; d&#233;connecter le disque <BR />pour rendre ce bouton accessible.';
		echo "<tr class=\"nas_config\"><td colspan=\"2\" align=\"center\" >&nbsp;<strong>".gettext("Configuration du NAS")."</strong></td></tr>";
		

		//params d�j� positionn�s ?
		$sql = "select * from `params` where cat='7'";
		$c = mysql_query($sql) or die("ERREUR $sql");
		for ($x=0;$x<mysql_num_rows($c);$x++) {
			$R = mysql_fetch_object($c);
			if ($R->name == 'NAS_protocol')
				 $NAS_protocol= $R->value;
			if ($R->name == 'NAS_ip')
				 $NAS_ip= $R->value;
			if ($R->name == 'NAS_share')
				 $NAS_share= $R->value;
			if ($R->name == 'NAS_login')
				 $NAS_login= $R->value;
			if ($R->name == 'NAS_pass')
				 $NAS_pass= $R->value;

		}
		
		//if (!isset($NAS_protocol))
		//	$NAS_protocol='nfs';
		if (!isset($NAS_ip))
			$NAS_ip='192.168.234.10';
		if (!isset($NAS_share))
			$NAS_share='wawa_share';
		if (!isset($NAS_login))
			$NAS_login='wawa';
		if (!isset($NAS_pass))
			$NAS_pass='wawa';

		if (!isset($NAS_mntsuffix))
			$NAS_mntsuffix ="";

		if ($NAS_protocol == 'cifs') {
			$test = 'checked';
			$test2 = '';
		} elseif ($NAS_protocol == 'nfs') {
			$test = '';
			$test2 = 'checked';
		}

		

		$choixProtocole =  "<input type=\"radio\"  id=\"NAS_protocol1\" name=\"NAS_protocol\" value=\"cifs\" $test>CIFS</input>";
 		$choixProtocole .= "&nbsp;&nbsp;<img onmouseover=\"Tip('Ce choix convient pour le montage d\'un partage samba.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";
		$choixProtocole .= "<BR /><input type=\"radio\" id=\"NAS_protocol2\"name=\"NAS_protocol\" value=\"nfs\" $test2>NFS</input>";
		$choixProtocole .= "&nbsp;&nbsp; <img onmouseover=\"Tip('Ce protocole est fortement recommand&#233;.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";

	
		$choixIP = "<input id=\"NAS_ip\" value=\"$NAS_ip\" />";
		$choixNomPartage = "<input id=\"NAS_share\" value=\"$NAS_share\" />";
		$choixLogin = "<input id=\"NAS_login\" value=\"$NAS_login\" />";
		$choixPasse = "<input type=\"password\" id=\"NAS_pass\" value=\"$NAS_pass\" />";

		if ($NAS_mntsuffix == '') {
			$test = 'checked';
			$test2 = '';
		} elseif ($NAS_mntsuffix == 'pc') {
			$test = '';
			$test2 = 'checked';
		}


		$choixSufX = "<input type=\"radio\"  id=\"NAS_suf1\" name=\"NAS_mountsuffix\" value=\"\" $test>/var/lib/backuppc</input>";
		$choixSufX .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img onmouseover=\"Tip('Convient dans la plupart des cas.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";
		
		$choixSufX .= "<BR /><input type=\"radio\" id=\"NAS_suf2\"name=\"NAS_mountsuffix\" value=\"pc\" $test2>/var/lib/backuppc/pc</input>";
		$choixSufX .= "&nbsp;&nbsp; <img onmouseover=\"Tip('N�cessaire pour certains NAS.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";
	

		echo "<tr class=\"nas_config\"><td width=\"66%\">&nbsp;&nbsp;".gettext("Protocole:")."</td><td align=\"center\" >$choixProtocole</td></tr>";
		echo "<tr class=\"nas_config\"><td width=\"66%\">&nbsp;&nbsp;".gettext("Point de montage:")."</td><td align=\"center\" >$choixSufX</td></tr>";
		echo "<tr class=\"nas_config\"><td>&nbsp;&nbsp;".gettext("Adresse IP du NAS:")."</td><td align=\"center\" >$choixIP</td></tr>";
		echo "<tr class=\"nas_config\"><td>&nbsp;&nbsp;".gettext("Nom du partage:")."</td><td align=\"center\" >$choixNomPartage</td></tr>";
		echo "<tr id=\"ligne_nas_user\" class=\"nas_config\"><td>&nbsp;&nbsp;".gettext("Login:")."</td><td align=\"center\" >$choixLogin</td></tr>";
		echo "<tr id=\"ligne_nas_passe\" class=\"nas_config\"><td>&nbsp;&nbsp;".gettext("Mot de passe:")."</td><td align=\"center\" >$choixPasse</td></tr>";
		echo "<tr class=\"nas_config\"><td colspan=\"2\" align=\"center\"><input id=\"wantSave\" type=\"button\" value=\"Enregistrer\" $test_button >"
		."<span  onmouseout=\"UnTip()\" onmouseover=\"Tip('$msg3')"."\" > <IMG valign=\"center\" style=\"border: 0px solid;\" SRC=\"../elements/images/system-help.png\" ></span>"
		."</td></tr>";

		echo "</table>";

?>
