<?php
 /**

   * Action sur un parc (arret - start)
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs  Stephane Boireau - MrT Novembre 2008

   * @Licence Distribue selon les termes de la licence GPL

   * @note
   * Ajaxification des pings - script parc_ajax_lib.php sur une proposition de Stéphane Boireau
   * Gestion des infobulles nouvelle mouture Tip et UnTip
   * Modification des fonctions ts et vnc qui se trouvent desormais dans /var/www/se3/includes/fonc_parc.inc.php
   * Externalisation des messages dans messages/fr/action_parc_messages.php dans un hash global
   * 
   */

   /**

   * @Repertoire: parcs/
   * file: parcs_ajax_lib.php

  */

	require ("config.inc.php");
	require_once ("functions.inc.php");
	require_once ("lang.inc.php");

	include "ldap.inc.php";
	include "ihm.inc.php";

	
	include('fonc_parc.inc.php');
	$prefix = "action_parc";
	//$lang = "en";
	require_once("messages/$lang/".$prefix."_messages.php");


	//echo "<script type='text/javascript' src='position.js'></script>\n";

	
	function get_smbsess($mp_en_cours) {
		global $smbversion, $action_parc;
		//echo "\$smbversion=$smbversion<br />";

		if ("$smbversion"=="samba3") {
			$smbsess=exec ("smbstatus -b 2>/dev/null |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $mp_en_cours ' |cut -d' ' -f2 |head -n1");
			//echo "smbstatus |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $mp_en_cours ' |cut -d' ' -f2 |head -n1";
		}
		else {
			$smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4}' |grep ' $mp_en_cours ' |cut -d' ' -f3 |head -n1");
		}

		if ($smbsess=="") {
			$etat_session="<img type=\"image\" src=\"../elements/images/disabled.png\">\n";
		} else {
			if ("$smbversion"=="samba3") {
				$login = exec ("smbstatus -b 2>/dev/null | grep -v 'root' |gawk -F' ' '{print \" \"$5\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
				//$login=exec ("smbstatus -b 2>/dev/null | grep -v 'root' | sed -e \"s/ \{2,\}/ /g\" | grep ' $smbsess ' | cut -d" " -f2 | head -n1");
			}
			else {
				$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$4\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
			}

			$texte= $login.$action_parc['msgUserLogged'];
			$etat_session.="<img onmouseout=\"UnTip();\" onmouseover=\"Tip('".$texte."',WIDTH,250,SHADOW,true,DURATION,5000);\" src=\"../elements/images/enabled.png\" border=\"0\" />";

			}
		echo $etat_session;
	}

	//====================================================

	//ip=$ip_machine&nom=$nom_machine&mode=wake_shutdown_or_reboot&wake=$wake&shutdown_reboot=$shutdown_reboot
	function wake_shutdown_or_reboot($ip,$nom,$wake,$shutdown_reboot) {
		global $smbversion;
		//echo "\$smbversion=$smbversion<br />";

		/*
		echo "ip=$ip<br />";
		echo "nom=$nom<br />";
		echo "wake=$wake<br />";
		echo "shutdown_reboot=$shutdown_reboot<br />";
		*/

		if(fping($ip)) {
			if($shutdown_reboot=="wait1") {
				echo $action_parc['msgNoSignal'];
			}
			elseif($shutdown_reboot=="wait2") {
				if ("$smbversion"=="samba3") {
					$smbsess=exec ("smbstatus -b 2>/dev/null |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $nom ' |cut -d' ' -f2 |head -n1");
				}
				else {
					$smbsess=exec ("smbstatus -b |gawk -F' ' '{print \" \"$5\" \"$4}' |grep ' $nom ' |cut -d' ' -f3 |head -n1");
				}

				if($smbsess=="") {
					@exec("sudo /usr/share/se3/scripts/start_poste.sh $nom reboot");
					echo $action_parc['cmdSendReboot'];
				}
				else {
					if ("$smbversion"=="samba3") {
						$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$5\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
					}
					else {
						$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$4\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
					}
					echo $login.$action_parc['msgUserIsLogged'];
				}
			}
			elseif($shutdown_reboot=="reboot") {
				@exec("sudo /usr/share/se3/scripts/start_poste.sh $nom reboot");
				echo $action_parc['msgSendReboot'];
			}
		}
		else {
			if("$wake"=="y") {
				@exec("sudo /usr/share/se3/scripts/start_poste.sh $nom wol");
				echo $action_parc['msgSendWakeup'];
			}
		}
	}

	//====================================================

	if($_POST['mode']=='ping_ip'){
		$resultat=fping($_POST['ip']);
		if($resultat){
			//echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border='0' title='".$_POST['ip']."' title='".$_POST['ip']."' />";
			//echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";

			$nom_machine=isset($_POST['nom_machine']) ? $_POST['nom_machine'] : NULL;
			$parc=isset($_POST['parc']) ? $_POST['parc'] : NULL;
			if((isset($nom_machine))&&(isset($parc))) {
				//echo gettext($action_parc['msgStationIsOn']),
				echo "<a target=\"main\" href=\"action_machine.php?machine=$nom_machine&action=shutdown&parc=$parc&retour=action_parc\""
				. "onmouseout=\"UnTip();\" onmouseover=\"Tip('".$action_parc['msgStationIsOn']."',WIDTH,250,SHADOW,true,DURATION,5000);\""
 				. "onclick=\"if (window.confirm('".$action_parc['msgConfirmEteindreMachine']." $mp_en_cours ?')) {return true;} else {return false;}\"/>"
				."<img type=\"image\" border=\"0\" title=\"".$action_parc['msgStationIsOn']."\" src=\"../elements/images/enabled.png\"></a>\n";
			}
			else {
				echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";
			}

		}
		else{
			//echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border='0' title='".$_POST['ip']."' title='".$_POST['ip']."' />";
			//echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";

			$nom_machine=isset($_POST['nom_machine']) ? $_POST['nom_machine'] : NULL;
			$parc=isset($_POST['parc']) ? $_POST['parc'] : NULL;
			if((isset($nom_machine))&&(isset($parc))) {
				
				echo "<a target=\"main\" href=\"action_machine.php?machine=$nom_machine&action=wol&parc=$parc&retour=action_parc\" target='_blank' "
				. "onmouseout=\"UnTip();\" onmouseover=\"Tip('".$action_parc['msgStationIsOff']."',WIDTH,250,SHADOW,true,DURATION,5000);\" >"
				."<img type=\"image\" border=\"0\" title=\"".$action_parc['msgStationIsOff']."\" src=\"../elements/images/disabled.png\">"
				."</a>\n";
			}
			else {
				echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";
			}
		}
	}
	elseif($_POST['mode']=='session') {
		get_smbsess($_POST['nom_machine']);
	}
	elseif($_POST['mode']=='wake_shutdown_or_reboot') {
		wake_shutdown_or_reboot($_POST['ip'],$_POST['nom'],$_POST['wake'],$_POST['shutdown_reboot']);
	}
	elseif($_POST['mode']=='ts_vnc') {
		//include "../parcs/fonc_parc.inc.php";

		$resultat=fping($_POST['ip']);
		if($resultat){
			$ts=ts($_POST['ip']);
			
			$vnc=vnc($_POST['ip']);
			if ($ts) { echo $ts; }
			if ($vnc) { echo $vnc; }
			if ((!$ts) and (!$vnc)) { 
				$ret = "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('".$action_parc['msgPortsClosed']."',WIDTH,250,SHADOW,true,DURATION,5000);\"".
				"><img type=\"image\" border=\"0\" title=\"".$action_parc['msgPortsClosed']."\" src=\"../elements/images/disabled.png\">"
				."</span>\n";
				echo($ret);
			}
			

		}
		else {
			$ret = "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('".$action_parc['msgPingKo']."',WIDTH,250,SHADOW,true,DURATION,5000);\">".
			"<img type=\"image\" border=\"0\" title=\"".$action_parc['msgPingKo']."\" src=\"../elements/images/disabled.png\">"
			."</span>\n";
			echo($ret);
			
		}
	}


?>
