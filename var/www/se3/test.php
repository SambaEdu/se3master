<?php

   /**

   * Page qui teste les differents services
   * @Version $Id$
   * @Projet LCS / SambaEdu
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposées par Sébastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
   * Modification du système d'infos bulles.(Nouvelle version de wz-tooltip) Ancienne version incompatible avec ajax
   * Externalisation des messages contenus dans les infos-bulles
   * Fonctions Tip('msg') et UnTip();
   * Nouvelle organisation de l'arborescence.
   * Migration des messages dans un script php

   */

   /**

   * @Repertoire: /
   * file: test.php
   */



	require ("config.inc.php");
	require ("entete.inc.php");

	//require_once("lang.inc.php");
	//bindtextdomain('se3-core',"/var/www/se3/locale");
	//textdomain ('se3-core');
	$prefix = "tests";
	require_once("$prefix/messages/$lang/".$prefix."_messages.php");

	//aide
	$_SESSION["pageaide"]="Informations_syst%C3%A8me#Diagnostic";

	// Si pas se3_is_admin
	if (ldap_get_right("se3_is_admin",$login)=="Y")  {

		if ($_GET[action] == "setadminse3smbpass") {
			exec('/usr/bin/sudo /usr/share/se3/scripts/change_adminse3_smbpass.sh');
		}

		//if ($_GET[action] == "updatesystem") {
		//	exec('/usr/bin/sudo /usr/share/se3/scripts/se3_update_system.sh --auto');
		//    unset($action);
		// }
		if ($_GET[action] == "updatesystem") {
		$info_1 = gettext("Mise &#224; jour syst&#232;me lanc&#233;e, ne fermez pas cette fen&#234;tre avant que le script ne soit termin&#233;. vous recevrez un mail r&#233;capitulatif de tout ce qui sera effectu&#233;...");
		echo $info_1;
		system('sleep 1; /usr/bin/sudo /usr/share/se3/scripts/se3_update_system.sh --auto &');
		unset($action);
	}
	if ($_GET[action] == "settime") {
		exec('/usr/bin/sudo /usr/share/se3/sbin/settime.sh');
	}
	if ($_GET[action] == "startsamba") {
		exec('/usr/bin/sudo /usr/share/se3/scripts/services.sh samba restart');
	}
	if ($action == "exim_mod") {
		$fichier = "/etc/ssmtp/ssmtp.conf";
		$fp=fopen("$fichier","w+");
		$DEFAUT = "
		root=$dc_root
		mailhub=$dc_smarthost
		rewriteDomain=$dc_readhost
		";
		fwrite($fp,$DEFAUT);
		fclose($fp);
		$action="mail_test";
	}


	if ($_GET[action] == "mail_test") {
       	$dc_root=exec('cat /etc/ssmtp/ssmtp.conf | grep root= | cut -d= -f2');
	       $subject = gettext("Test de la configuration de votre serveur Se3");
        	$message = gettext("Message envoy&#233; par le serveur Se3");
	       mail ($dc_root, $subject, $message);
       	unset($action);
	}



?>
	<script language="JavaScript">
	<!--
	   svbg=""
	   function chng(obj,i) {
	   if(i==0) obj.setAttribute("BGCOLOR", "#A8A8A8", false)
	   if(i==1)
	      if(obj==svbg) obj.setAttribute("BGCOLOR", "#CDCDCD", false)
	    else obj.setAttribute("BGCOLOR", "#CDCDCD", false)
	   if(i==2) {
	      if(svbg!="") svbg.setAttribute("BGCOLOR", "white", false)
	      svbg=obj
	      obj.setAttribute("BGCOLOR", "lime", false)
	   }
	 }
	//--></script>
	<script type="text/javascript" src="/elements/js/wz_tooltip_new.js"></script>
	<script type="text/javascript" src="/tests/js/tests_messages_ajax.php"></script>
	<script type="text/javascript" src="/tests/js/gest_messages.js"></script>
	<script type="text/javascript" src="/tests/js/tests.js"></script>

	<?php
	/********** Test de la conf du serveur **********************/
	echo "<H1>".gettext("Etat du serveur")."</H1>";
	$phpv2=preg_replace("/[^0-9\.]+/","",phpversion());
	$phpv=$phpv2-0;

	/*******************************************************/

	// =======================================
	// Affichage d'un lien de rafraichissement du cadre.
	if(file_exists('/etc/se3/temoin_test_refresh.txt')){
		echo "<div style='position:fixed; top:5px; left:5px; width:20px; height:20px; border:1x solid black;'>\n";
		echo "<a href='".$_SERVER['PHP_SELF']."'><img src='elements/images/rafraichir.png' width='16' height='16' border='0' alt='Rafraichir' /></a>\n";
		echo "</div>\n";
	}
	// =======================================


	// Verifie la connexion a internet si ligne_internet = 0% alors on a internet
	$PING_INTERNET="195.98.246.50";
	if ($phpv>=4.2) {
		$PING="ping -c 1 -w 1 $PING_INTERNET | awk '/packet/ {print $6}'";
	} else {
		$PING="ping -c 1 $PING_INTERNET | awk '/packet/ {print $7}'";
	}

	$ligne_internet=exec("$PING",$test,$testretour);
	if ($ligne_internet != "0%") { // on teste sur un autre serveur
	   $PING_INTERNET="www.free.fr";
	   if ($phpv>=4.2) {
		$PING="ping -c 1 -w 1 $PING_INTERNET | awk '/packet/ {print $6}'";
	   } else {
		$PING="ping -c 1 $PING_INTERNET | awk '/packet/ {print $7}'";
	   }
	   $ligne_internet=exec("$PING",$test,$testretour);
	}

	// leb 30sept2007
	if ($ligne_internet != "0%") { // test acces http
	   //$http=exec("cd /tmp; wget -q ---tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
	   $http=exec("cd /tmp; wget -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
	   if ($retour=="0") {
	       $ligne_internet = "0%";
	   }
	}
	// fin-leb 30sept2007
	// Verifie si proxy defini
	$proxy=exec("cat /etc/profile | grep http_proxy= | cut -d= -f2");
	if ($proxy != "") {
		preg_match("/http:\/\/(.*)\"/i",$proxy,$rest);
		putenv("http_proxy=$rest[1]");
	}



	$os=exec("cat /etc/debian_version");
	$vers=exec("dpkg -s se3|grep Version|cut -d ' ' -f2");
?>

	<center><TABLE border="1" width="80%"><TR><TD colspan="3" align="center" class="menuheader">
	Version SambaEdu</TD></TR><TR><TD>Version OS</TD><TD align="center" colspan="2">
<?php
	if ($os=="3.1") { echo "Sarge"; } elseif ($os=="4.0") { echo "Etch"; } else { echo "Lenny"; } echo "<I> <img src=\"../elements/images/debian.png\">($os)</I></TD></TR>\n";
?>
	</TD>
	</TR>
	<TR>
		<TD>Mise &#224; jour de votre serveur Se3 <I>(Version actuelle <?php echo $vers; ?>)</I></TD>
		<TD align="center">
			<a id=link_maj href="#"><IMG id="check_maj" style="border: 0px solid ;" SRC="../elements/images/info.png" ></a>
		</TD>
		<TD align="center">
			<a id="help_maj_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>
	<TR>
		<TD>Importation des cl&#233;s</TD>
		<TD align="center">
			<a id=link_keys href="#"><IMG id="check_keys" style="border: 0px solid ;" SRC="../elements/images/info.png" /></A>
		</TD>
		<TD align="center">
			<A id="help_keys_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD>Contr&#244;le la pr&#233;sence des VBS</TD>
		<TD align="center">
			<a id=link_vbs href="#"><IMG id="check_vbs" style="border: 0px solid ;" SRC="../elements/images/info.png" /></a>
		</TD>
		<TD align="center">
			<A id="help_vbs_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD colspan="3" align="center" class="menuheader">
			V&#233;rification des connexions</TD></TR><TR><TD>V&#233;rifie la connexion &#224; la passerelle <I>(
	<?php
	// Ping passerelle
	$PING_ROUTEUR=`cat /etc/network/interfaces | grep gateway | grep -v broadcast | cut -d" " -f 2`;
	$PING_ROUTEUR=trim($PING_ROUTEUR);
	echo $PING_ROUTEUR;
	?>
	)</I>
		</TD>
		<TD align="center"><IMG id="check_gateway" style="border: 0px solid ;" SRC="../elements/images/info.png"></TD>
		<TD align="center">
			<a id="help_gateway_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>

	<TR>
		<TD>V&#233;rification de la connexion &#224; internet</TD>
		<TD align="center">
			<IMG id="check_internet" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<a id="help_net_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>
	<TR>
		<TD>V&#233;rification de la r&#233;solution de nom (DNS)</TD>
		<TD align="center">
			<IMG id="check_dns" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<a id="help_dns_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>
	<TR>
		<TD>V&#233;rification du nom DNS du serveur Se3 <span id="urlse3" style="font-style: italic;">(<?php echo $urlse3 ?>)</span></TD>
		<TD align="center"><IMG id="check_dns_se3" style="border: 0px solid ;" SRC="../elements/images/info.png"></TD>
		<TD align="center">
			<a id="help_dns2_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>
	<TR>
		<TD>Connexion au serveur FTP de mises &#224; jour <I>(<?php echo $ftpmaj ?>)</I></TD>
		<TD align="center"><IMG id="check_ftp" style="border: 0px solid ;" SRC="../elements/images/info.png"></TD>
		<TD align="center">
			<a id="help_ftp_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>

	<TR>
		<TD>V&#233;rifie l'acc&#232;s au web</TD>
		<TD align="center"><IMG id="check_web" style="border: 0px solid ;" SRC="../elements/images/info.png"></TD>
		<TD align="center">
			<a id="help_web_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>
	<TR>
		<TD>V&#233;rifie la connexion au serveur de temps <span id="ntp_server" style="font-style: italic;">(<?php echo $ntpserv ?>)</span></TD>
		<TD align="center"><IMG id="check_ntp" style="border: 0px solid ;" SRC="../elements/images/info.png"></TD>
		<TD align="center">
			<a id="help_ntp_se3"><img name="action_image2"  src="../elements/images/system-help.png"></a>
		</TD>
	</TR>

	<TR><TD colspan="3" align="center" class="menuheader">Contr&#244;le des services</TD></TR>

	<?php
		$la=date("G:i:s d/m/Y");
	?>
	<TR id="ligne_date" style="display: none;">
		<TD>Contr&#244;le la date et l'heure du serveur <I>(date actuelle <?php echo $la; ?>)</I></TD>
		<TD align="center">
			<A id="link_time"><img id="check_time" style="border: 0px solid ;" SRC="../elements/images/info.png"></A>
		</TD>
		<TD align="center">
			<A id="help_time_se3"><img iname="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>


	<TR>
		<TD>Configuration de l'exp&#233;dition des mails</TD>
		<TD align="center">
			<IMG id="check_mail" style="border: 0px solid ;" id="check_mail" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_mail_se3"><img  name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD>Etat du serveur Samba Version: <span id="smb_version" style="font-style: italic;">(<?php echo $smbversion ?>)</span></TD>
		<TD align="center">
			<a id="link_samba" href="#"><IMG id="check_smb" style="border: 0px solid ;" SRC="../elements/images/info.png"></a>
		</TD>
		<TD align="center">
			<a id="help_samba_se3"><img src="../elements/images/system-help.png"></a>
		</TD>
	</TR>

	<TR>
		<TD>Controle du SID samba</TD>
		<TD align="center">
			<IMG id="check_sid" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_sid_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD>Etat de la base MySQL</TD>
		<TD align="center">
			<IMG id="check_mysql" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_mysql_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>

		<TR id="ligne_dhcp" style="display: none;">

			<TD>Etat du serveur DHCP</TD>
			<TD align="center">
				<IMG id="check_dhcp" style="border: 0px solid ;" SRC="../elements/images/info.png">
			</TD>
			<TD align="center">
				<A id="help_dhcp_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
			</TD>
		</TR>


	<TR>
		<TD>Onduleur</TD>
		<TD align="center">
			<A id="link_ondul"><IMG id="check_ondul" style="border: 0px solid ;" SRC="../elements/images/info.png"></A>
		</TD>
		<TD align="center">
			<A id="help_ondul_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR><TD colspan="3" align="center" class="menuheader">Etat des disques</TD></TR>

	<TR>
		<TD>Partition : / <span id="space_disk1"><span><br></TD>
		<TD align="center">
			<IMG id="check_disk1" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">

			<A id="help_disk1"><img  src="../elements/images/system-help.png" /></A>

	</TD>
	</TR>
	<TR>
		<TD>Partition : /var/se3 <span id="space_disk2"><span></TD>
		<TD align="center">
			<IMG id="check_disk2" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_disk2"><img src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD>Partition : /home <span id="space_disk3"><span><br></TD>
		<TD align="center">
			<IMG id="check_disk3" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_disk3"><img src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR>
		<TD>Partition : /var <span id="space_disk4"><span><br></TD>
		<TD align="center">
			<IMG id="check_disk4" style="border: 0px solid ;" SRC="../elements/images/info.png">
		</TD>
		<TD align="center">
			<A id="help_disk4"><img src="../elements/images/system-help.png"></A>
		</TD>
	</TR>

	<TR><TD colspan="3" align="center" class="menuheader">S&#233;curit&#233;</TD></TR>
	<TR>
		<TD>Mises &#224; jour de s&#233;curit&#233; Debian</TD>
		<TD align="center">
			<A id="link_secu" ><IMG id="check_secu" style="border: 0px solid ;" SRC="../elements/images/info.png"></A>
		</TD>
		<TD align="center">
			<A id="help_secu_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>
	<TR><TD colspan="3" align="center" class="menuheader">Clients</TD></TR>
	<TR>
		<TD>V&#233;rifie le compte d'int&#233;gration des clients</TD>
		<TD align="center">
			<A id="link_client_lien_hs_voir_pourkoi"><IMG id="check_client" style="border: 0px solid ;" SRC="../elements/images/info.png"></A>
		</TD>
		<TD align="center">
			<A id="help_client_se3"><img name="action_image2"  src="../elements/images/system-help.png"></A>
		</TD>
	</TR>

	</TABLE></center>


	<!-- //Menu pour mail. -->
	<div  id="mail_menu" style="width: 100%; display: none;" ><table width=100%><tr><td colspan=2 align=center bgcolor=#6699CC><font face=Verdana size=-1  color=#000000><b>Menu</b></font></td></tr><tr><td><IMG width=15 height=15 SRC=../elements/temp/command.png></td><td onmouseover=chng(this,0) onmouseout=chng(this,1)><a href=conf_smtp.php><font face=Verdana size=-1  color=#000000>Tester envoi</font></a></td></tr><td><IMG width=15 height=15 SRC=../elements/temp/comment.gif></td><td  onmouseover=chng(this,0) onmouseout=chng(this,1)><a href=../conf_smtp.php><font face=Verdana size=-1  color=#000000>Configurer</font></a></td></tr></table></div>


<?php
	echo "</center>";
	require ("pdp2.inc.php");
	} // fin de pas se3_is_admin

?>
