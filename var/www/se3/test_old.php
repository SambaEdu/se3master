<?php

   /**
   
   * Page qui test les differents services
   * @Version $Id: test.php 3002 2008-05-30 12:58:43Z keyser $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux  

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: test.php
   */




require ("config.inc.php");
require ("entete.inc.php");

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

//aide
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Diagnostic";


// Si pas se3_is_admin
if (ldap_get_right("se3_is_admin",$login)=="Y")  {

if ($_GET[action] == "setrootsmbpass") {
	exec('/usr/bin/sudo /usr/share/se3/scripts/change_root_smbpass.sh');
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
<script language="JavaScript"><!--
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
   $http=exec("cd /tmp; wget -q ---tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
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

// $ligne_internet="1%";

//######################### MISES A JOUR ######################################## ##/


	// Ajout popup d'alerte
	include("fonc_outils.inc.php");
	
	entree_table_param_exist(url_popup_alert,"http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Alerte_popup.html",4,"Url du popup alerte");
	entree_table_param_exist(tag_popup_alert,0,4,"Tag du popup alerte");
	// On relit la table
	require ("config.inc.php");
	system("cd /tmp; wget -q --tries=1 --connect-timeout=1 $url_popup_alert");
   	if (file_exists("/tmp/Alerte_popup.html")) {
        	$lines = file("/tmp/Alerte_popup.html");
	        foreach ($lines as $line_num => $line) {
			$line=trim($line);
			if(preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/","$line",$matche)) {
				// test la persence du tag precedent
				$tag_alerte=$matche[1].$matche[2].$matche[3];
				if ($tag_alerte==$tag_popup_alert) {
					$ok_alert="0";
				} else {	
	                        	$ok_alert="1";
				}	
	                }
	        }
	}												
	@unlink("/tmp/Alerte_popup.html");	
	if ($ok_alert=="1") {
		echo "<SCRIPT LANGUAGE=JavaScript>";
		echo "window.open(\"$url_popup_alert\",\"PopUp\",\"width=500,height=350,location=no,status=no,toolbars=no,scrollbars=no,left=100,top=80\")";
		echo "</SCRIPT>";
		
		// require ("functions.inc.php");
		setparam("tag_popup_alert",$tag_alerte);
	}
	// Fin popup
	
	



// Version
echo "<center>";
echo "<TABLE border=\"1\" width=\"80%\">";
echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Version SambaEdu");
echo "</TD></TR>";
$os=exec("cat /etc/debian_version");
echo "<TR><TD>".gettext("Version OS")."</TD><TD align=\"center\" colspan=\"2\">";
if ($os=="3.1") { echo "Sarge"; } else { echo "Etch"; } echo "<I> ($os)</I></TD></TR>\n";
// echo "<TR><TD>Version php</TD><TD  align=\"center\">$phpv</TD><TD></TD></TR>";

// Verifie si le serveur est a jour
echo "<TR><TD>";
echo gettext("Mise &#224; jour de votre serveur Se3")." <I>(".gettext("Version actuelle")." $version)</I>";
echo "</TD><TD align=\"center\">";

if($ligne_internet != "0%") { //si pas de connexion a internet
	echo "<u onmouseover=\"return escape".gettext("('Impossible de v&#233;rifier les mises &#224; jour, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>\n";
} else {
   system("cd /tmp; wget -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/test.php?majnbr=".$majnbr."\&testver=1");
   if (file_exists("/tmp/test.php?majnbr=".$majnbr."&testver=1")) {
     $lines = file("/tmp/test.php?majnbr=".$majnbr."&testver=1");
        foreach ($lines as $line_num => $line) {
                $line=trim($line);
                if(preg_match("/OK/i","$line")) {
                        $ok="1";
                }
        }
		unlink("/tmp/test.php?majnbr=".$majnbr."&testver=1");
   }
   else
   {
   echo "<u onmouseover=\"return escape".gettext("('Impossible de v&#233;rifier l'&#233;tat des mises &#224; jour sur http://wawadeb.crdp.ac-caen.fr')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>\n";
   }
   if ($ok=="1") {
	echo "<u onmouseover=\"this.T_WIDTH=140;return escape".gettext("('Etat : serveur &#224; jour')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\" ></u>\n";
   } else {
	echo "<a href=\"../majphp/majtest.php\"><u onmouseover=\"return escape".gettext("('Cliquer ici pour mettre &#224; jour')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></u></a>\n";
   }
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('V&#233;rifie si votre serveur est &#224; jour.<br>Si ce n\'est pas le cas, vous pouvez le mettre &#224; jour &#224; partir <a href=../majphp/majtest.php>d\'ici</a>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
echo "</TD></TR>\n";


// Controle l'installation des cles
echo "<TR><TD>";
echo gettext("Importation des cl&#233;s");
echo "</TD><TD align=\"center\">";
$authlink = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die(gettext("Impossible de se connecter &#224; la base")." $dbname.");
$query="select * from corresp";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
$ligne=mysqli_num_rows($resultat);

if($ligne == "0") { // si aucune cle dans la base SQL
	if ($ligne_internet == "0%") { // si connection a internet on peut proposer l'import
		echo "<u onmouseover=\"this.T_WIDTH=140;return escape".gettext("('Cliquer ici pour importer les cl&#233;s')")."\"><a href=\"../registre/gestion_interface.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a></u>\n";
	} else { // sinon on ne peut pas proposer tant que pas de connexion
		 echo "<u onmouseover=\"return escape".gettext("('Impossible de mettre &#224; jour les cl&#233;s, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\" ></u>\n";
	}
} else {
	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\" >\n";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Vous n\'avez pas install&#233; les cl&#233;s des registres,<br>Pour cela vous devez aller dans <a href=\'../registre/gestion_interface.php\'>Gestion des clients Windows</a> et cliquer sur effectuer la mise &#224; jour de la base des cl&#233;s')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
echo "</TD></TR>\n";


// Controle l'installation des vbs
echo "<TR><TD>";
echo gettext("Contr&#244;le la pr&#233;sence des VBS");
echo "</TD><TD align=\"center\">";
$DIR_VBS="/var/se3/Progs/install/installdll/rejoin_se3_XP.vbs";
if(@is_dir("/var/se3/Progs/install/installdll")) {
	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\" >\n";
} else {
	if ($ligne_internet == "0%") { // si connection a internet on peut proposer l'import
		echo "<u onmouseover=\"this.T_WIDTH=140;return escape".gettext("('Cliquer ici pour installer les scripts VBS')")."\"><a href=\"../registre/gestion_interface.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a></u>";
	} else { // sinon on ne peut pas proposer tant que pas de connexion
		 echo "<u onmouseover=\"return escape".gettext("('Impossible de mettre &#224; jour les scripts VBS, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
	}
}
echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Les scripts VBS sont les scripts qui permettent de configurer vos clients Windows afin qu\'ils int&#233;grent facilement le domaine. <br><br>Vous devez installer ces scripts avant d\'ajouter une machine au domaine<br><br>Une fois les scripts install&#233;s, pour ajouter une machine XP, connectez vous en administrateur local sur la machine, puis recherchez le serveur SambaEdu. Puis allez dans /Progs/install/installdll/ et lancer le script rejoins_XP.<br><br>La gestion des scripts se fait dans <a href=\'../registre/gestion_interface.php\'>Gestion des clients Windows</a>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";


//########################### CONNEXIONS ################################################/

// Verification des connexions
echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo gettext("V&#233;rification des connexions");
echo "</TD></TR>";

// Ping passerelle
$PING_ROUTEUR=`cat /etc/network/interfaces | grep gateway | grep -v broadcast | cut -d" " -f 2`;
$PING_ROUTEUR=trim($PING_ROUTEUR);
if ($phpv>=4.2) {
	$PING="ping -c 1 -w 1 $PING_ROUTEUR | awk '/packet/ {print $6}'";
} else {
	$PING="ping -c 1 $PING_ROUTEUR | awk '/packet/ {print $7}'";
}
$ligne=exec("$PING",$test,$testretour);
$ok="0";
if (($ligne_internet == "0%") && ($ligne == "0%")) {
	$ok="1";
} elseif (($ligne_internet != "0%") && ($ligne != "0%")) {
	$ok="1";
} elseif (($ligne_internet != "0%") && ($ligne == "0%")) {
	$ok="1";
} elseif (($ligne_internet == "0%") && ($ligne != "0%")) {
	$ok="0";
}
if ($ok=="1") {
   	echo "<TR><TD>";
   	echo gettext("V&#233;rifie la connexion &#224; la passerelle")." <I>($PING_ROUTEUR)</I>";
   	echo "</TD><TD align=\"center\">";
   	if ($ligne == "0%") {  echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">"; } else { echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">"; }
   	echo "</TD><TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Test si la passerelle est joignable.<br> Si la r&#233;ponse est n&#233;gative, cela peut vouloir dire que votre routeur n\'est pas pingable, ou que celui-ci est mal configur&#233;.<br>La passerelle est le routeur ou machine qui est le passage obligatoire pour aller sur internet. Si celui-ci est en erreur, mais que vous pouvez vous connecter &#224; internet ne pas tenir compte de ce test.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>\n";
}

// Ping internet
echo "<TR><TD>";
echo gettext("V&#233;rification de la connexion &#224; internet");
echo "</TD><TD align=\"center\">";
if ($ligne_internet == "0%") {  echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">"; } else { echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">"; }
echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('Test si une machine sur internet est joignable.<br><br> Si la r&#233;ponse est n&#233;gative, vous devez v&#233;rifier votre connexion internet.<br><br> - Si la connexion &#224; votre routeur &#233;tait en erreur, vous devez commencer par corriger la route par defaut puis retester <br><br> - Si vous avez un Slis devant ne pas oublier de laisser internet accessible depuis cette machine<br><br> - Ne pas oublier de d&#233;clarer le proxy si vous en avez un, pour acc&#232;der &#224; internet.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";

// Verifie DNS
echo "<TR><TD>";
echo gettext("V&#233;rification de la r&#233;solution de nom (DNS)");
echo "</TD><TD align=\"center\">";
if($ligne_internet == "0%") {
   $IP_WAWA=@gethostbyname('wawadeb.crdp.ac-caen.fr');
   if ($IP_WAWA=="193.49.66.139") {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
   } else {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">";
  }
} else {
   echo "<u onmouseover=\"return escape".gettext("('Test de la r&#233;solution DNS impossible, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('V&#233;rifie si la r&#233;solution DNS est correcte<br>Si vous avez une erreur, vous devez v&#233;rifier que le fichier /etc/resolv.conf est bien configur&#233;.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";

echo "</TD></TR>\n";

// Verification de la conf dns
$authlink = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die(gettext("Impossible de se connecter &#224; la base")." $dbname.");
$query="select urlse3 from params where name='urlse3'";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
if ($resultat) {
	while ($r=mysqli_fetch_array($resultat)) {
		$urlse3=$r[0];
	}
}
preg_match("/^(http:\/\/)?([^\:]+)/i","$urlse3",$adress);

echo "<TR><TD>";
echo gettext("V&#233;rification du nom DNS du serveur Se3")." <I> ($urlse3)</I>";
echo "</TD><TD align=\"center\">";
$com="/usr/bin/host -t A $adress[2]";
$fp2=exec("$com",$out,$log);

if ($log=="0") {  echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">"; } else { echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">"; }
echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Le nom DNS que vous avez donn&#233; &#224; votre serveur Se3")." ($urlse3) ".gettext("ne peut &#234;tre trouv&#233;. Sans un nom correct, vous ne pourrez pas faire la mise &#224; jour des cl&#233;s des registres. Vous pouvez soit ajouter dans le DNS de votre Slis ou LCS le serveur Se3, soit mettre l\'adresse IP &#224; la place, par exemple http://172.16.0.2:909. Pour cela  <a href=\'../conf_params.php?cat=1\'>modifier le champ urlse3</a>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";

// Contact serveur de mise a jour ftp
$FTP="wawadeb.crdp.ac-caen.fr";
echo "<TR><TD>";
echo gettext("Connexion au serveur FTP de mises &#224; jour")." <I>($FTP)</I>";
echo "</TD><TD align=\"center\">";
if ($ligne_internet == "0%") {
  $CONNECT_FTP=@ftp_connect("$FTP",0,30);

  if($CONNECT_FTP) {  echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
  } else { echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">";
	@ftp_close($FTP);
  }
} else { // pas de connexion internet
	echo "<u onmouseover=\"return escape".gettext("('Impossible de tester la connexion au FTP des mises &#224; jour, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('Test une connexion au serveur ftp de mises &#224; jour.<br><br>Si la r&#233;ponse est n&#233;gative, et que les pr&#233;c&#233;dentes r&#233;ponses &#233;taient positives, v&#233;rifier d\'abord que le serveur ftp r&#233;pond bien &#224; partir d\'un simple navigateur.<br><br>Il se peut que celui-ci soit ne soit pas joignable (panne...!).')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";

echo "</TD></TR>\n";

// Verifie l'acces au serveur web pour la maj des cles
echo "<TR><TD>";
echo gettext("V&#233;rifie l'acc&#232;s au web");
echo "</TD><TD align=\"center\">";
if($ligne_internet == "0%") {
   $http=exec("cd /tmp; wget -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
   if ($retour=="0") {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
   } else {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">";
   }
} else {
	  echo "<u onmouseover=\"return escape".gettext("('Impossible de tester la connexion au web, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('Test si une machine sur internet est joignable sur le port 80 (Web).<br><br>Si la r&#233;ponse est n&#233;gative, vous devez v&#233;rifier votre connexion internet.<br><br>Si vous avez un Slis ou un autre proxy devant ne pas oublier de laisser internet accessible depuis cette machine et si vous n\'avez pas activ&#233; le proxy transparent, v&#233;rifier que dans /etc/profile le proxy est bien renseign&#233;.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";

echo "</TD></TR>\n";


// Verification de la connexion au serveur de temps
echo "<TR><TD>";
echo gettext("V&#233;rifie la connexion au serveur de temps")." <I>($ntpserv)</I>";
echo "</TD><TD align=\"center\">";
if ($ligne_internet=="0%") {
   $authlink = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
   @((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die(gettext("Impossible de se connecter &#224; la base")." $dbname.");
   $query="select ntpserv  from params";
   $resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
   if ($resultat) {
	while ($r=mysqli_fetch_array($resultat)) {
		$ntpserv=$r[0];
	}
   }
//   $ok_ntp=fsockopen("udp://$ntpserv",123,&$errno,&$errstr,5);
  $ok_ntp=system("/usr/sbin/ntpdate -q $ntpserv >/dev/null", $retval);
  if ($retval=="0") {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
   } else {
   	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">";
  }
} else {
 	 echo "<u onmouseover=\"return escape".gettext("('Impossible de tester l\'acc&#232;s au serveur de temps, sans connexion &#224; internet')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si le serveur de temps que vous avez indiqu&#233; ")." ($ntpserv) ".gettext("n\'est pas joingnable et si votre connexion internet semble correcte,<br><b> v&#233;rifier :</b><br><br> - Si vous avez un Slis de bien avoir comme serveur de temps le Slis lui m&#234;me (par exmple 172.16.0.1).<br> - Que votre proxy (routeur...etc) laisse passer en sorti, les connexions vers le port 123 UDP.<br><br>La modification s\'effectue <a href=../conf_params.php?cat=1>ici</a>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";

echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Contr&#244;le des services");
echo "</TD></TR>";

//######################## CONTROLE LES SERVICES ##################################//
// Controle le temps de la machine
$la=date("G:i:s d/m/Y");

if ($retval=="0") { // que si la connexion au serveur de temps est Ok
  echo "<TR><TD>";
  echo gettext("Contr&#244;le la date et l'heure du serveur")." <I>(".gettext("date actuelle")." $la)</I>";
  echo "</TD><TD align=\"center\">";
  $voir = exec("/usr/sbin/ntpdate -q $ntpserv | grep ntpdate | cut -d\" \" -f11");
  if($voir < 60) {
  	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
  } else {
  	echo "<u onmouseover=\"return escape".gettext("('Cliquer ici pour mettre &#224; l\'heure votre serveur')")."\"><a href=\"../test.php?action=settime\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
  }
  echo "</TD><TD align=\"center\">";
  echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('V&#233;rifie si votre serveur est &#224; l\'heure par rapport au serveur")." $ntpserv.<br>".gettext("La diff&#233;rence est actuellement de $voir sec. Cette diff&#233;rence doit rester inf&#233;rieure &#224; 60 sec')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  echo "</TD></TR>\n";
}

// Controle si le fichier ssmtp a ete configure
$ssmtp = exec("dpkg -l | grep ssmtp > /dev/null && echo 1");
if ($ssmtp == "1") {
  echo "<TR><TD>";
  echo gettext("Configuration de l'exp&#233;dition des mails");
  echo "</TD><TD align=\"center\">";
if(file_exists("/etc/ssmtp/ssmtp.conf")) {
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;this.T_WIDTH=140;this.T_BGCOLOR=&quot;#CDCDCD&quot;;this.T_FONTCOLOR=&quot;#000000&quot;;return escape('<table width=100%><tr><td colspan=2 align=center bgcolor=#6699CC><font face=Verdana size=-1  color=#000000><b>".gettext("Menu")."</b></font></td></tr><tr><td><IMG width=15 height=15 SRC=../elements/temp/command.png></td><td onmouseover=chng(this,0) onmouseout=chng(this,1)><a href=&quot;conf_smtp.php&quot;><font face=Verdana size=-1  color=#000000>".gettext("Tester envoi")."</font></a></td></tr><td><IMG width=15 height=15 SRC=../elements/temp/comment.gif></td><td  onmouseover=chng(this,0) onmouseout=chng(this,1)><a href=../conf_smtp.php><font face=Verdana size=-1  color=#000000>".gettext("Configurer")."</font></a></td></tr></table>')\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
} else {
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Cliquer ici pour configurer l\'exp&#233;dition de mail')")."\"><a href=\"../conf_smtp.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>"; }
	echo "</TD><TD align=\"center\">";
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('V&#233;rifie si votre serveur est configur&#233; pour vous exp&#233;dier des mails en cas de probl&#232;me.<BR>Si ce n\'est pas le cas vous devez <a href=../conf_smtp.php>renseigner les informations permettant d\'envoyer des mails</a>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>\n";
}

// Test le serveur smb
  $domaine = exec('cat /etc/samba/smb.conf | grep workgroup | cut -d" " -f 3');
  $smb = exec("smbclient -L localhost -N | grep -i $domaine >/dev/null && echo 1");
  echo "<TR><TD>";
  echo gettext("Etat du serveur Samba");
  if ($smbversion != "") { echo "<I> (Version : $smbversion)</I>"; }
  echo "</TD><TD align=\"center\">";

  if ($smb == "1") {
  	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
  } else {
  	echo "<u onmouseover=\"return escape".gettext("('Cliquer ici pour essayer de relancer samba')")."\"><a href=\"../test.php?action=startsamba\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a></u>"; }
  echo "</TD><TD align=\"center\">";
  echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Teste une connexion au domaine")." $domaine.<br>".gettext("Si celui-ci est en Echec, v&#233;rifiez qu\'il est bien d&#233;marr&#233;. Pour le d&#233;marrer /etc/init.d/samba start')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  echo "</TD></TR>\n";
  
// Test le sid samba et la presence d'un eventuel doublon de sid
  $testsid = exec('sudo /usr/share/se3/scripts/testSID.sh');
  
  echo "<TR><TD>";
  echo gettext("Controle du SID samba");
  echo "</TD><TD align=\"center\">";

  if ($testsid == "") {
  	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
  } else {
  	echo "<u onmouseover=\"return escape".gettext("('Attention : des sid diff&#233;rents sont d&#233;clar&#233;s dans l\'annuaire, mysql et le secrets.tdb')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a></u>"; 
	}
  echo "</TD><TD align=\"center\">";
  echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Teste la pr&#233;sence d\'&#233;ventuels doublons de SID.<br><br>Lancez la commande <b>/usr/share/se3/scripts/correctSID.sh</b> pour identifier et r&#233;soudre le probl&#232;me de SID.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  echo "</TD></TR>\n";
  
  

// Test la base MySQL
  $mysql = exec('sudo /usr/share/se3/sbin/testMySQL.sh',$out,$err);
  echo "<TR><TD>";
  echo gettext("Etat de la base MySQL");
  echo "</TD><TD align=\"center\">";
  if ($err == "0") {
  	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
  } else {
  	echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">"; }
  echo "</TD><TD align=\"center\">";
  echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Teste l\'int&#233;grit&#233; de votre base MySQL, par rapport &#224; ce qu\'elle devrait avoir.<br><br>Si cela est en erreur, lancer la commande <b>/usr/share/se3/sbin/testMySQL -v</b> afin de connaitre la cause du probl&#232;me.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  echo "</TD></TR>\n";

// Controle si le dhcp tourne si celui-ci a ete installe
$dhcp_install = exec("dpkg -l | grep dhcp3 > /dev/null && echo 1");

if (($dhcp_install == "1") && ($dhcp =="1")) {
	echo "<TR><TD>";
  	echo gettext("Etat du serveur DHCP");
  	echo "</TD><TD align=\"center\">";
  	$dhcp_state=exec("sudo /usr/share/se3/scripts/makedhcpdconf state");
	if($dhcp_state==1) {
		echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;this.T_WIDTH=140;return escape('Serveur DHCP actif')\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	} else {
		echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;this.T_WIDTH=140;return escape('Serveur DHCP inactif')\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></u>";
	}

  	echo "</TD><TD align=\"center\">";
 	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Test l\'&#233;tat du serveur DHCP.<br> Pour l\'activer ou le d&#233;sactiver aller sur <a href=dhcp/config.php>la page suivante</a>.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>\n";
}

// Test la presence d'un onduleur
  $ups = exec("upsc myups@localhost");
  $ups_charge = exec("upsc myups@localhost battery.charge");
  echo "<TR><TD>";
  echo gettext("Onduleur");
  if ($ups_charge != "") {
  	$ups_mfr = exec("upsc myups@localhost ups.mfr");
	$ups_model = exec("upsc myups@localhost ups.model");
	echo " <I> ( $ups_mfr $ups_model )</I>";
  }
  echo "</TD><TD align=\"center\">";
  if ($ups_charge != "") {  echo "<u onmouseover=\"return escape".gettext("('Etat de l\'onduleur')")."\"><a href=\"../cgi-bin/nut/upsstats.cgi\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
        echo "</TD><TD align=\"center\">";
        echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('<a href=../ups/ups.php>".gettext("Etat de l\'onduleur")."</A>')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  } else {
  	echo "<u onmouseover=\"this.T_WIDTH=140;return escape".gettext("('Configurer un onduleur')")."\"><a href=\"../ups/ups.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
        echo "</TD><TD align=\"center\">";
        echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Test la pr&#233;sence et l\'&#233;tat d\'un onduleur<BR><BR>Il n\'y a pas d\'onduleur d&#233;tect&#233; sur ce serveur.<br>Cela peut provoquer la perte des donn&#233;es. On vous conseille d\'en installer un.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  }
echo "</TD></TR>\n";


//################################### DISQUES #########################################################//
// Disques
echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Etat des disques");
echo "</TD></TR>";

// Partition root
echo "<TR><TD>".gettext("Partition")." : /";
$df_t=disk_total_space("/");
$df_f=disk_free_space("/");
$freespace=$df_f / 1048576;
$totalspace=$df_t / 1048576;
$usedspace=$totalspace - $freespace;
$pourcent=$usedspace / $totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
echo " <I> (".gettext("pourcentage occup&#233;")." $pourc %)</I><br>";
echo "</TD>";
$usedspace = $usedspace / 1024;
$usedspace = round($usedspace,2);
$totalspace = $totalspace / 1024;
$totalspace = round($totalspace,2);
$freespace = $freespace / 1024;
$freespace = round($freespace,2);
if($pourcent < 0.96) {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></TD><TD align=\"center\">";
} else {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></TD><TD align=\"center\">";
}
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('".gettext("Partition root /<br>Espace total")." <b>$totalspace Go</b><br>".gettext("Espace occup&#233;")." <b>$usedspace Go</b><br>".gettext("Espace disponible")." <b>$freespace Go</b>')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>";

// Partition /var/se3
echo "<TR><TD>".gettext("Partition")." : /var/se3";
$df_t=disk_total_space("/var/se3");
$df_f=disk_free_space("/var/se3");
$freespace=$df_f / 1048576;
$totalspace=$df_t / 1048576;
$usedspace=$totalspace - $freespace;
$pourcent=$usedspace / $totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
echo " <I> (".gettext("pourcentage occup&#233;")." $pourc %)</I><br>";
echo "</TD>";
$usedspace = $usedspace / 1024;
$usedspace = round($usedspace,2);
$totalspace = $totalspace / 1024;
$totalspace = round($totalspace,2);
$freespace = $freespace / 1024;
$freespace = round($freespace,2);
if($pourcent < 0.96) {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></TD><TD align=\"center\">";
} else {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></TD><TD align=\"center\">";
}
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('".gettext("Partition root /var/se3<br>Espace total")." <b>$totalspace Go</b><br>".gettext("Espace occup&#233;")." <b>$usedspace Go</b><br>".gettext("Espace disponible")." <b>$freespace Go</b>')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>";

// Partition /home
echo "<TR><TD>".gettext("Partition")." : /home";
$df_t=disk_total_space("/home");
$df_f=disk_free_space("/home");
$freespace=$df_f / 1048576;
$totalspace=$df_t / 1048576;
$usedspace=$totalspace - $freespace;
$pourcent=$usedspace / $totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
echo " <I> (".gettext("pourcentage occup&#233;")." $pourc %)</I><br>";
echo "</TD>";
$usedspace = $usedspace / 1024;
$usedspace = round($usedspace,2);
$totalspace = $totalspace / 1024;
$totalspace = round($totalspace,2);
$freespace = $freespace / 1024;
$freespace = round($freespace,2);
if($pourcent < 0.96) {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></TD><TD align=\"center\">";
} else {
	echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></TD><TD align=\"center\">";
}
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('".gettext("Partition root /home<br>Espace total")." <b>$totalspace Go</b><br>".gettext("Espace occup&#233;")." <b>$usedspace Go</b><br>".gettext("Espace disponible")." <b>$freespace Go</b>')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
	echo "</TD></TR>";

// Partition /var
echo "<TR><TD>".gettext("Partition")." : /var";
$df_t=disk_total_space("/var");
$df_f=disk_free_space("/var");
$freespace=$df_f / 1048576;
$totalspace=$df_t / 1048576;
$usedspace=$totalspace - $freespace;
$pourcent=$usedspace / $totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
echo " <I> (".gettext("pourcentage occup&#233;")." $pourc %)</I><br>";
echo "</TD>";
$usedspace = $usedspace / 1024;
$usedspace = round($usedspace,2);
$totalspace = $totalspace / 1024;
$totalspace = round($totalspace,2);
$freespace = $freespace / 1024;
$freespace = round($freespace,2);
if($pourcent < 0.96) {
echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></TD><TD align=\"center\">";
} else {
echo "<TD align=\"center\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></TD><TD align=\"center\">";
}
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('".gettext("Partition root /var<br>Espace total")." <b>$totalspace Go</b><br>".gettext("Espace occup&#233;")." <b>$usedspace Go</b><br>".gettext("Espace disponible")." <b>$freespace Go</b>')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";

// Securite
echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo "S&#233;curit&#233;";
echo "</TD></TR>\n";


// Mises a jour de secu debian
echo "<TR><TD>";
echo gettext("Mises &#224; jour de s&#233;curit&#233; Debian");
echo "</TD><TD align=\"center\">";
if($ligne_internet=="0%") {
$secu = exec('/usr/bin/sudo /usr/share/se3/scripts/update-secu.sh');
if ($secu == "1") {
echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
} else {
echo "<u onmouseover=\"return escape".gettext("('Cliquez sur le lien pour lancer la mise &#224; jour syst&#232;me via l\'interface. Vous pouvez aussi effectuer la mise &#224; jour en ligne de commande en lancant le script <b>se3_update_system.sh</b> :")." ')\"><a href=\"../test.php?action=updatesystem\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
}
} else {
echo "<u onmouseover=\"return escape('".gettext("Impossible de tester les mises &#224; jour de s&#233;curit&#233; Debian, sans connexion &#224; internet")."')\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
}

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape('".gettext("Teste si ce serveur est bien &#224; jour par rapport au serveur de s&#233;curit&#233; de Debian.<br><br>Pour mettre &#224; jour votre serveur, utilisez l\'interface ou lancez le script <b>se3_update_system.sh</b> dans une console<br><br>Attention, cela entraine aussi la mise &#224; jour des paquets Se3.")."')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
echo "</TD></TR>\n";


// Clients
echo "<TR><TD colspan=\"3\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Clients");
echo "</TD></TR>";

// Verifie le passe root pour ldap
echo "<TR><TD>";
echo gettext("V&#233;rifie le compte d'int&#233;gration des clients");
echo "</TD><TD align=\"center\">";
$compte=exec("cat /var/se3/Progs/install/installdll/confse3.ini | grep password_ldap_domain | cut -d= -f2",$out,$retour);
	$cmd_smb="smbclient -L localhost -U root%$compte && echo \$?";
	$samba_root=exec("$cmd_smb",$out,$retour2);
// echo "$cmd_smb";
	if ($retour2 == "0") {
		echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\">";
	} else {
        echo "<u onmouseover=\"return escape".gettext("('Le mot de passe ne correspond pas avec le contenu de confse3.ini, Cliquer ici pour corriger le probl&#232;me')")."\"><a href=\"../test.php?action=setrootsmbpass\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\"></a></u>";
	   	#echo "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/critical.png\">";
	}
//} else {
//	  echo "<u onmouseover=\"return escape('Impossible de tester la connexion au web, sans connexion &#224; internet')\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/info.png\"></u>";
// }

echo "</TD><TD align=\"center\">";
echo "<u onmouseover=\"return escape('".gettext("V&#233;rifie que le mot de passe contenu dans /var/se3/Progs/install/installdll/confse3.ini est correct.<br><br>Si ce n\'est pas le cas, vous ne pourrez pas int&#233;grer de nouvelles machines.<br><br>Dans ce cas pour reforcer ce mot de passe, aller dans /var/se3/Progs/install/installdll/confse3.ini pour connaitre le mot de passe &#224; mettre et taper la commande : <br><br><b>smbpasswd -a root</b><br><br>Puis taper le mot de passe qui correspond &#224; la ligne <b>password_ldap_domain</b>.")."')\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";

echo "</TD></TR>\n";


echo "</TABLE>";
echo "</center>";
require ("pdp.inc.php");

} // fin de pas se3_is_admin

?>
