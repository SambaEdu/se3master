<?php


   /**
   
   * Permet configurer la sauvegarde (Backuppc)
   * Ajout du support de NAS
   * Ajaxification de la page
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux Wawa MrT

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo /usr/share/se3/sbin/testbackup.sh

   */

   /**

   * @Repertoire: sauvegarde
   * file: sauv.php

  */	



   include "entete.inc.php";   
   require ("config.inc.php");
   require ("ldap.inc.php");
   require ("ihm.inc.php");
   include ("fonction_backup.inc.php");

   require_once("lang.inc.php");
   bindtextdomain('sauvegarde',"/var/www/se3/locale");
   textdomain ('sauvegarde');


###############################################################################
# Octobre 2008
# Ajout de parametrage en Ajax pour NAS Mrt@EquipeTice.
# Scripts systeme de wawa
###############################################################################
?>

<script type="text/javascript">

	var bck_user;

	function IsNumeric(sText)
	{
   		var ValidChars = "0123456789.";
		var IsNumber=true;
		var Char;

 		for (i = 0; i < sText.length && IsNumber == true; i++) { 
		      Char = sText.charAt(i); 
      		      if (ValidChars.indexOf(Char) == -1) {
         			IsNumber = false;
         		}
      		}
   	return IsNumber;
   	}


	function saveNAS(){
		var params ='?';
		var proto;
		var sufX;
		if ($('NAS_protocol1').checked)
			proto ='cifs';
		if ($('NAS_protocol2').checked)
			proto = 'nfs';

		if ($('NAS_suf1').checked)
			sufX ='rien';
		if ($('NAS_suf2').checked)
			sufX = 'pc';


		params += 'NAS_protocol='+proto;
		params += '&NAS_ip='+$('NAS_ip').value;
		params += '&NAS_share='+$('NAS_share').value;
		params += '&NAS_login='+$('NAS_login').value;
		params += '&NAS_pass='+$('NAS_pass').value;
		params += '&NAS_mntsuffix='+sufX;
		
		var url = './supports/saveNAS.php';
		var ajax2 = new Ajax.Request(url,{ method: 'get', parameters: params, onComplete: function(requester) {
			alert(requester.responseText);	
		}});
		
		
	}

	function bckSave(){
		if ($('bck_user1').checked)
				bck_user = $('bck_user1').value;
		if ($('bck_user2').checked)
				bck_user = $('bck_user2').value;

		var bck_uidnumber=$('bck_uidnumber').value;
		
		if (IsNumeric(bck_uidnumber)) {
			var params='?bck_user='+bck_user+'&bck_uidnumber='+bck_uidnumber;
			
			var url = 'saveBackupPcSettings.php';
			var ajax16 = new Ajax.Request(url,{ method: 'post', parameters: params, onComplete: function(requester) {
				alert(requester.responseText);
			}});
		}
	}

	function manageSave(){
		var choix = $('bpcmediaNew').value;
		
		var url = './supports/support'+choix+'.php';
				
		var ajax1 = new Ajax.Updater("bidon",url,{ method: 'post', onComplete: function(requester) {
			
			//alert(requester.responseText);
			
			var source = $('status_media').src;
			var reg = new RegExp("enabled.png", "i");
			var supp = $('bpcmediaNew').value;
			
			var source2 = $('bck_status').src;
			var reg2= new RegExp("enabled.png", "i");
			

			if ($('NAS_protocol2')) {

				if ($('NAS_protocol2').checked) {
	
					Element.hide('ligne_nas_user');
					Element.hide('ligne_nas_passe');
					$('NAS_suf2').disabled = true;
					$('NAS_suf1').checked = true;

				}						


				$('NAS_protocol2').onclick = function(){
					$('NAS_suf2').disabled = true;
					$('NAS_suf1').checked = true;
					Element.hide('ligne_nas_user');
					Element.hide('ligne_nas_passe');						
				}
			}

			if ($('NAS_protocol1')) {

				if ($('NAS_protocol1').checked) {
	
					Element.show('ligne_nas_user');
					Element.show('ligne_nas_passe');
				}						


				$('NAS_protocol1').onclick = function(){
					$('NAS_suf2').disabled = false;
					Element.show('ligne_nas_user');
					Element.show('ligne_nas_passe');						
	
				}
			}



			if ( $('bck_user1').checked) {
				bck_user = $('bck_user1').value;
				Element.hide('ligne_uid');
				}
			if ( $('bck_user2').checked) {
				
				bck_user = $('bck_user2').value;
				if (!reg2.exec(source2)) {
					Element.show('ligne_uid');
					
					}
				}
			
			var saved_uid ='<?php echo $bck_uidnumber; ?>';
			var params ='?user='+bck_user;
			var ajax15 = new Ajax.Request('search_uidnumber.php',{ method: 'post', parameters: params, onComplete: function(requester) {
				var rep = requester.responseText;
				if ( rep != '-1') {
					if (saved_uid && bck_user == 'backuppc')
						$('bck_uidnumber').value = saved_uid;
					else
						$('bck_uidnumber').value = rep;
					if (saved_uid == rep)
						$('chk_uidnumber').src = '../elements/images/recovery.png';
					else
						$('chk_uidnumber').src = '../elements/images/warning.png';

					$('chk_uidnumber').onmouseover = function() {
							UnTip();
							Tip('Le syst&#234me a trouv&#233, l\'uidnumber '+rep+' pour l\'utilisateur '+bck_user);
							this.onmouseout=function() {UnTip();}
						}

				}
			}});

			//

			
			


			if (reg.exec(source) == null)   {
				$('bpcmediaNew').style.display = 'block';
				Event.observe('wantSave','click',saveNAS, true);	
				$('info').innerHTML = '';
				


			} else {
				var liste = document.getElementsByClassName('nas_config');
				for (var i = 0; i < liste.length; i++) {
					$(liste[i]).style.display='none';
				}
				$('bpcmediaNew').style.display = 'none';
				
				if (supp == 0)
					$('info').innerHTML = '<strong>Autre</strong>';
				if (supp == 1)
					$('info').innerHTML = '<strong>USB</strong>';
				if (supp == 2)
					$('info').innerHTML = '<strong>Disque Dur</strong>';
				if (supp == 3)
					$('info').innerHTML = '<strong>NAS</strong>';

			}
			
			//tt_Init();

		
		}});
		
	}
	
	function checkUidNumber() {
		var test_uid = $('bck_uidnumber').value;
		var params='?uidnumber='+test_uid;
		var url= 'valid_uidnumber.php';
		if (IsNumeric(test_uid)) {
			var ajax2 = new Ajax.Request(url,{ method: 'post', parameters: params, onComplete: function(requester) {
				if (requester.responseText != 'erreur') { 
					if (requester.responseText == '') { 
						$('chk_uidnumber').src = '../elements/images/recovery.png';
						$('chk_uidnumber').onmouseover = function() {
							UnTip();
							Tip('UidNumber libre');
							this.onmouseout=function() {UnTip();}
						}

					} else {
						$('chk_uidnumber').src = '../elements/images/critical.png';
						$('chk_uidnumber').onmouseover = function() {
							UnTip();
							Tip(requester.responseText);
							this.onmouseout=function() {UnTip();}
						}
					}
				} else {
						$('chk_uidnumber').src = '../elements/images/info.png';
						$('chk_uidnumber').onmouseover = function() {
							UnTip();
							Tip('Ce bouton v&#233;rifie la disponibilit&#233; de l\'UidNumber.');
							this.onmouseout=function() {UnTip();}
						}
				
				}	
			}});
		} else {
						$('chk_uidnumber').src = '../elements/images/info.png';
						$('chk_uidnumber').onmouseover = function() {
							UnTip();
							Tip('Merci de ne saisir que des chiffres');
							this.onmouseout=function() {UnTip();}
						}
				
				}	

	}

	function init() {
		
		Event.observe('bpcmediaNew','change',manageSave, true);
		
		$('bpcmediaNew').value = <?php echo $bpcmedia; ?>;
		
		manageSave();
		Event.observe('bck_uidnumber','keyup',checkUidNumber,true);
		
		var source = $('bck_status').src;
		var reg = new RegExp("enabled.png", "i");
			if (reg.exec(source) != null)   {
				Element.hide('ligne_user');
				Element.hide('ligne_uid');
				Element.hide('ligne_bouton');

			}
		
		Event.observe('save_backup','click',bckSave, true);
		Event.observe('bck_user1','click',manageSave, true);
		Event.observe('bck_user2','click',manageSave, true);
		

	}
	
	

	Event.observe(window,'load',init,false);
	
</script>
<script type="text/javascript" src="../elements/js/wz_tooltip_new.js"></script>
<?php
################################################################################
  


// Verifie les droits
if (ldap_get_right("system_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

//aide 
$_SESSION["pageaide"]="Sauvegarde Backuppc";

$drive=$_GET['drive'];
$space=$_GET['space'];
$_SESSION['action'] = $_GET['action'];
$bpcmediaNew=$_GET['bpcmediaNew'];
					       
/***************************************************************************************************/
if (isset($_GET['usbdisk']) and ! isset ($_GET['action'])) {
	$sql="Delete from params where name='usbdisk';";
	mysql_query($sql);
	$sql="Insert into params values ('', 'usbdisk', '".$_GET['usbdisk']."', '5', '0', 'Disque de sauvegarde');";
	mysql_query($sql);
	mysql_close();
	system("sudo /usr/share/se3/scripts/udev_disk_rule.sh ".$_GET['usbdisk']);
	umountUSB ();
}

if ($_GET['action'] == "format") {
	$return=system("sudo /usr/share/se3/scripts/format_disk.sh ".$_GET['usbdisk']);
	echo $return;
}

if ($_GET['action'] == "start") {
	if (file_exists("/etc/backuppc/restore.lck")) {
	  unlink("/etc/backuppc/restore.lck");
	}
	startBackupPc();
}
if ($_GET['action'] == "stop") {
	if (!file_exists("/etc/backuppc/restore.lck")) {
	  touch("/etc/backuppc/restore.lck");
	}
	stopBackupPc();
	
}	
	
if ($_GET['action'] == "key") {
	CreeKey();
}	

if ($_GET['action'] == "modif") {
	if ($drive!=$space) {
		MoveRep($drive,$space);
	}	
}	

if ($_GET[action] == "disk") {
	if ($bpcmediaNew=="") { $bpcmediaNew="0"; }
	$authlink = mysql_connect($dbhost,$dbuser,$dbpass);
	@mysql_select_db($dbname) or die(gettext("Impossible de se connecter a la base"));
	$resultat=mysql_query("UPDATE params set value='$bpcmediaNew' where name='bpcmedia'");
	$bpcmedia=$bpcmediaNew;
}	

/******************** Affichage de la page ******************************************/
echo "<P><h1>".gettext("Gestion des sauvegardes")."</h1></P>";
if ($_GET[action] == "restoreUSB") {
	if (file_exists("/etc/backuppc/restore.lck")) {
	  unlink("/etc/backuppc/restore.lck");
	}
	echo "<PRE class=code>";
	restoreUSB();
	echo "</PRE>";
}
if ($_GET[action] == "umountUSB") {
	if (!file_exists("/etc/backuppc/restore.lck")) {
	  touch("/etc/backuppc/restore.lck");
	}
	echo "<PRE class=code>";
	umountUSB();
	echo "</PRE>";
}

echo "<br><br>";

/*********************************** Affichage des archives ******************************/
if ($_GET[action] == "list") {
	$rep=variables(ArchiveDest,$HostServer);
	if (file_exists($rep)) {

		echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
		echo "<tr  bgcolor=#E0E0E0 align=\"center\"><td align=\"center\">".gettext("Archive")."</td><td>".gettext("Taille")."</td><td>".gettext("Date")."</td></tr>";
		$list = glob("$rep/*.*");
		if (is_array($list)) {
			foreach ($list as $filename) {
				echo "<tr><td>".$filename."</td><td align=\"right\"> " . filesize($filename) ."</td><td align=\"right\">". date ("d F Y H:i:s", filemtime($filename)) ."</td></tr>\n";
			}
		}
	echo "</table>";	
	}
	exit;
}

/***********************************************************************************/

	echo "
	<table align=center width=\"80%\" border=0 cellspacing=\"0\" cellpadding=\"0\">
        <tr><td><H3>".gettext("Configuration g&#233;n&#233;rale")."</H3></td></tr>
	</table><br>\n";
	
	echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"4\">
        <tr><td width=\"66%\">";
	echo gettext("Configuration par d&#233;faut")."</td><td align=center>";
	if (file_exists('/etc/backuppc/config.pl')) { 
		echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('Un fichier de configuration par d&#233;faut existe.<br /> Si vous souhaitez le modifier cliquer sur ce bouton')"."\"href=\"config_defaut.php\"><IMG style=\"border: 0px solid;\" SRC=\"../elements/images/enabled.png\"></a>"; 
		} else { 
		echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('Aucun fichier de configuration n\'existe<br />. Vous devez obligatoirement en cr&#233;er un avant de <br />pouvoir lancer des sauvegardes')"."\"  href=config_defaut.php><IMG style=\"border: 0px solid;\" SRC=\"../elements/images/disabled.png\" ></a>"; }
	
	echo "</td></tr>\n";
	echo "<tr><td>".gettext(" Etat du serveur de sauvegarde")."</td><td align=\"center\">"; 
	
	if ($_GET['action'] == 'stop')
			if (EtatBackupPc() == 1) {
				stopBackupPc();
				sleep(1);
	
			}

	if (EtatBackupPc()== "1") { 
		echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('Pour stopper et d&#233;sactiver le serveur<br /> de sauvegarde cliquer sur le bouton')"."\"  href=sauv.php?action=stop><IMG id=\"bck_status\" style=\"border: 0px solid;\" SRC=\"../elements/images/enabled.png\" ></a>"; 
	}
	 else { 
		echo "<a  onmouseout=\"UnTip()\" onmouseover=\"Tip('Pour d&#233;marrer le serveur de sauvegarde,<br /> cliquer sur le bouton')"."\"  href=sauv.php?action=start><IMG id=\"bck_status\" style=\"border: 0px solid;\" SRC=\"../elements/images/disabled.png\" ></a>"; 
	} 
	
	echo "</td></tr>\n";

	if ($bck_user == 'www-se3' ) {
		$test = 'checked';
		$test2= '';
	} else {
		$test ='';
		$test2 = 'checked';
	}

	$choixUser =  "<input type=\"radio\"  id=\"bck_user1\" name=\"bck_user\" value=\"www-se3\" $test>&nbsp;www-se3</input>";
 	$choixUser .= "&nbsp;&nbsp;<img onmouseover=\"Tip('Ce choix convient un disque USB.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";
	$choixUser .= "<BR /><input type=\"radio\" id=\"bck_user2\"name=\"bck_user\" value=\"backuppc\" $test2>backuppc</input>";
	$choixUser .= "&nbsp;&nbsp;<img onmouseover=\"Tip('Ce choix convient pour un serveur de sauvegarde NAS.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";


	echo "<tr id=\"ligne_user\" >";
	echo "<td>".gettext("Utilisateur de BackupPc: ")."</td><td align=\"center\">";
	echo $choixUser;
	echo "</td>";
	
	echo "</tr>\n";
	echo "<tr id=\"ligne_uid\">";
	echo "<td style= \"vertical-align: middle\" >".gettext("UidNumber associ&#233; :")."</td><td align=\"center\">";

	echo "<input style=\"width: 50px;\" id=\"bck_uidnumber\" value=\"$backup_uidnumber\" />";
	echo "&nbsp;&nbsp;<img style= \"vertical-align: middle\" id=\"chk_uidnumber\" onmouseover=\"Tip('Ce bouton v&#233;rifie la disponibilit&#233; de l\'UidNumber.')\" onmouseout=\"UnTip()\" src=\"../elements/images/info.png\"></img>";
	echo  "&nbsp;&nbsp;<img style= \"vertical-align: middle\" onmouseover=\"Tip('Vous devez renseigner l\'UidNumber associ&#233;<br /> &#224; l\'utilisateur d&#233;clar&#233; ci-dessus.<br />Un script Ajax verifiera la disponibilit&#233; de ce dernier.')\" onmouseout=\"UnTip()\" src=\"../elements/images/system-help.png\"></img>";
	



	echo "</td>";
	echo "</tr>\n";

	echo "<tr id=\"ligne_bouton\"><td align=\"center\" colspan=\"2\"><div><input type=\"button\" id=\"save_backup\" value=\"Enregistrer\" /></div></td></tr>";
	echo "</table><br>";

	

// Espace de sauvegarde

	$prompt = "Attention, pour configurer un support de sauvegarde les m&#233;dias doivent &ecirc;tre d&#233;mont&#233;s !<br />"
		."Pour cel&agrave;, rendez vous au niveau de la ligne &#233;tat de la connexion.<BR />";
		

	echo "<table align=center width=\"80%\" border=0 cellspacing=\"0\" cellpadding=\"0\">
        <tr><td><H3>".gettext("Espace de sauvegarde")."</H3></td></tr>
	</table><br>\n";
	echo "<table align=center style=\"background:#a5d6ff;\" width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
	echo "<tr><td  width=\"66%\">&nbsp;<strong>";
	echo gettext("Support de sauvegarde");
	echo "</strong>";
	echo "&nbsp; <span  onmouseout=\"UnTip();\" onmouseover=\"Tip('$prompt');\".'\'>"; 
	echo "<img name=\"action_image2\"  src=\"../elements/images/system-help.png\">";
	echo "</span>";
	echo "</td><td align=\"center\"><center><span id=\"info\" style=\"text-align: center;\"></span></center>";
	echo "<form method=\"get\" action=\"sauv.php\">";
	
	####
	#echo "<select name=\"bpcmediaNew\" ONCHANGE=\"this.form.submit();\">";
	####
	echo "<select id=\"bpcmediaNew\" name=\"bpcmediaNew\" >";
	####
	echo "<option"; if ($bpcmedia=="0") { echo " selected"; } echo " value=\"0\">".gettext("Autre")."</option>";
	echo "<option"; if ($bpcmedia=="1") { echo " selected"; } echo " value=\"1\">".gettext("Disque USB")."</option>";
//	echo "<option"; if ($bpcmedia=="2") { echo " selected"; } echo " value=\"2\">Disque dur</option>";
	echo "<option"; if ($bpcmedia=="3") { echo " selected"; } echo " value=\"3\">NAS</option>";
	echo "</select> ";

	$prompt2 ="Pour un disque USB branch&#233; sur le serveur Se3, s&#233;lectionner Disque USB.<br />"
		  ."Utilisez NAS pour la sauvegarde sur un serveur de sauvegarde";

	echo "<td align=\"center\"><span  id=\"bulles_media\" onmouseout=\"UnTip();\" onmouseover=\"Tip('$prompt2');\".'\'>"; 
	echo "<img name=\"action_image2\"  src=\"../elements/images/system-help.png\">";
	echo "</span></td>";

	


	echo "<input type=\"hidden\" name=\"action\" value=\"disk\">";
	
//	echo "? ";
	echo "</form>";
	echo "</td></tr>\n";
	########################################################################################
	# a placer en Ajax !
	echo "</table>";
	echo "<div id=\"bidon\">&nbsp;</div>";
	############################### BRIQUE AJAX

	

		// Sauvegarde sur bande
		echo "<br /><table align=center width=\"80%\" border=0 cellspacing=\"0\" cellpadding=\"0\">
	        <tr><td><H3>".gettext("Gestion des archives")."</H3></td></tr>
		</table><br />\n";
	

echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
echo "<tr><td>&nbsp;".gettext("Cr&#233;er une nouvelle archive")."</td><td align=center>";
$msg4 = "Cr&#233;er une archive &#224; partir de ce lien.<br />Qu\'est ce qu\'une archive ?<br/>Une archive est le moyen de sauvegarder sur une bande ou de graver sur CD ou DVD, la fusion des derni&#232;res sauvegardes d\'une machine ou de toutes les machines sauvegard&#233;es.";
echo "<a onmouseout=\"UnTip();\" onmouseover=\"Tip('$msg4');\" href=\"new_host.php?TypeServer=Archive\">".gettext("Ajouter")."</a></td></tr>";




echo "</table>";

echo "<br>";

$dir = "/etc/backuppc";
$i="0";
if(is_dir($dir)) {
        if ($liste = opendir($dir)) {
	  while (($file = readdir($liste)) != false) {
	     if ((preg_match("/.pl$/",$file)) and ($file != "config.pl")) {
	        $Host = substr ("$file",0,-3);
                // recherche le type de sauvegarde
                if (GetTypeServer($Host) == "Archive") {
			if ($i=="0") {	
				echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
				echo "<tr><td colspan=\"5\" bgcolor=#E0E0E0 align=\"center\">".gettext("Archives")."</td></tr>\n";
			}	
		    $i="1";
                    echo "<tr";
		    // verifie si tout est ok
		    if (HostExist($Host) == "true") { $im = "recovery.png"; } else { $im="critical.png"; }
		    $msg8 = "Si le bouton est vert, tout est normal. Cliquez dessus pour &#233;ventuellement modifier la sauvegarde.<bR>Si le bouton est rouge, il y a un prol&#232;me.";
		    echo "<td><a onmouseout=\"UnTip();\" onmouseover=\"Tip('Modifier la configuration.');\" href=modif_host.php?HostServer=$Host>$Host</a></td>";
		    echo "<td align=center><a onmouseout=\"UnTip();\" onmouseover=\"Tip('$msg8');\" href=\"modif_host.php?HostServer=$Host\"><img style=\"border: 0px solid;\"src=\"../elements/images/$im\"></a></td>";
		    echo "<td align=center><a onmouseout=\"UnTip();\" onmouseover=\"Tip('Permet de lancer l\'archivage.');\" href=../backuppc/index.cgi?host=$Host><img style=\"border: 0px solid;\"src=\"../elements/images/zoom.png\"></a></td>";
		    echo "<td align=center><a onmouseout=\"UnTip();\" onmouseover=\"Tip('Permet de parcourir les archives.');\" href=\"sauv.php?action=list&HostServer=$Host\"><img style=\"border: 0px solid;\"src=\"../elements/images/logrotate.png\"></a></td>";
		    echo "<td align=center><a onmouseout=\"UnTip();\" onmouseover=\"Tip('Permet de supprimer cette archive.<br>Les archives existantes ne seront pas d&#233;truites.');\" href=\"sauvhost.php?HostServer=$Host&action=del\"><img style=\"border: 0px solid;\"src=\"../elements/images/edittrash.png\"></a></td></tr>";
		}
	     }
	}
    }
}

echo "</table>";

echo "<br><br>";
require ("pdp2.inc.php");


?>
