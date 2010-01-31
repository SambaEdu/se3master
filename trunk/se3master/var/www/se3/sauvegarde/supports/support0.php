<?php
	@session_start();
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
	$sql2 = "UPDATE `params` SET `value` = '0' WHERE `params`.`name` ='bpcmedia' ;";
	$c2 = mysql_query($sql2) or die("ERREUR: $sql2");
	

	###################################################################################
	# Fix nas suffix
	###################################################################################
	$sql_suffix = "UPDATE params set value='' where name='NAS_mntsuffix' ;";
	$res_suffix = mysql_query($sql_suffix) or die("ERREUR: $sql_suffix");
	
	###################################################################################

	echo "<table align=\"center\" width=\"80%\" \" border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
	echo "<td>";
	echo gettext("Espace de sauvegarde")."</td><td align=\"center\">";
	if (file_exists("/tmp/move_backuppc.lock")) {
		echo "<u onmouseover=\"return escape".gettext("('Vous avez mis en oeuvre un d&#233;placement du r&#233;pertoire de sauvegarde.<br>Vous devez attendre que cette op&#233;ration soit termin&#233;e.<br>Cela peut prendre un certain temps.')")."\">";
			echo "<font color=\"red\">".gettext("Copie en cours")."</font>";
			echo "</u>";
	} else {		
	  if (is_link("/var/lib/backuppc")) {
		$drive=readlink('/var/lib/backuppc');
		if ($_SESSION['action']=="change") {
			echo "<form method=\"get\" action=\"sauv.php\">";
			echo "<input type=\"hidden\" name=\"drive\" value=\"$drive\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"modif\">";
			echo "<input type=\"text\" name=\"space\" value=\"$drive\" >";
			$msg10 = "Pour changer le r&#233;pertoire ou disque de la sauvegarde indiquer sont chemin.<br>Le disque doit &#234;tre mont&#233; avant.<br><b> Ne pas oublier de donner les droits &#224; backuppc sur le r&#233;pertoire de sauvegarde.<br>chown -R ww-se3.root <br></b><br>Attention, cette op&#233;ration est extr&#233;mement longue en fonction des sauvegardes existantes.'";
			echo "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('$msg10');\">";
				echo "<input type=\"submit\" value=\"Ok\">\n";
			echo "</span>";
			echo "</form>";
		} else {	
			echo "<a href=sauv.php?action=change>";
			$msg11 = "R&#233;pertoire ou se trouve la sauvegarde.<br><br>Pour le changer cliquer sur le lien.<br><br>Attention, cette op&#233;ration est extr&#233;mement longue en fonction des sauvegardes existantes.<br><br><b>Attention : Ne pas oublier de donner les droits au r&#233;pertoire parent, ou vous avez d&#233;placer votre sauvegarde. chown -R backuppc /mon_repertoire_de_sauvegarde.</b>";
			echo "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('$msg11');\">";
				echo readlink('/var/lib/backuppc');
			echo "</span>";
			echo "</a>";
		}	
	  } else {
		$drive="/var/lib/backuppc";
		if ($_SESSION['action']=="change") {
			echo "<form method=\"get\" action=\"sauv.php\">";
			echo "<input type=\"hidden\" name=\"drive\" value=\"$drive\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"modif\">";
			echo "<input type=\"text\" name=\"space\" value=\"$drive\" >";
			echo "<u onmouseover=\"return escape".gettext("('Pour changer le r&#233;pertoire ou disque de la sauvegarde cliquer indiquer sont chemin. par exemple /mnt/usb. Ne pas indiquer  / &#224; la fin.<br>Le disque doit &#234;tre mont&#233; avant.<br>Attention, cette op&#233;ration est extr&#233;mement longue en fonction des sauvegardes existantes.<br>Il est donc conseill&#233; de faire cela avant de lancer une sauvegarde.')")."\">";
			echo "<input type=\"submit\" value=\"Ok\">\n";
			echo "</u>";
			echo "</form>";
		} else {
		 	echo "<a href=sauv.php?action=change>";
			echo "<u onmouseover=\"return escape".gettext("('Attention, la sauvegarde va se faire sur la partition /var.<br><br>Il est vivement conseill&#233; de changer cela et de faire votre sauvegarde, soit sur un autre disque, soit sur un disque externe USB.<br><br>Vous devez penser &#224; donner les droits pour backuppc au r&#233;pertoire parent.')")."\">";
			echo"<a href=sauv.php?action=change><font color=\"red\">/var/lib/backuppc</font></a>";
			echo "</u>";
			echo "</a>";
		}
	  }	
	}
	echo "</td></tr>";
	
	echo "<tr><td>";
		echo gettext("Espace disponible")."</td><td align=\"center\">";
		echo "<u onmouseover=\"return escape".gettext("('Taille disponible en Gb du disque sur lequel se trouve votre sauvegarde.')")."\">";
		echo round(diskfreespace("$drive")/1024/1024/1024,2); echo " Gb </td></tr>";

	echo "</table>";

?>
