<?php

/**

 * Action sur un parc (arret - start)
 * @Version $Id$

 * @Projet LCS / SambaEdu

 * @auteurs  Stephane Boireau - MrT Novembre 2008

 * @Licence Distribue selon les termes de la licence GPL

 * @note
 * Ajaxification des pings - script parc_ajax_lib.php sur une proposition de St�phane Boireau
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
require_once ("fonc_outils.inc.php");
require_once ("ldap.inc.php");
require_once ("ihm.inc.php");
require_once ('fonc_parc.inc.php');
$prefix = "action_parc";
//$lang = "en";
require_once("messages/$lang/" . $prefix . "_messages.php");

//echo "<script type='text/javascript' src='position.js'></script>\n";



if ($_POST['mode'] == 'ping_ip') {
    $resultat = fping($_POST['ip']);
    if ($resultat) {
        //echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border='0' title='".$_POST['ip']."' title='".$_POST['ip']."' />";
        //echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";

        $nom_machine = isset($_POST['nom_machine']) ? $_POST['nom_machine'] : NULL;
        $parc = isset($_POST['parc']) ? $_POST['parc'] : NULL;
        if ((isset($nom_machine)) && (isset($parc))) {
            //echo gettext($action_parc['msgStationIsOn']),
            echo "<a target=\"main\" href=\"action_machine.php?machine=$nom_machine&action=shutdown&parc=$parc&retour=action_parc\""
            . "onmouseout=\"UnTip();\" onmouseover=\"Tip('" . $action_parc['msgStationIsOn'] . "',WIDTH,250,SHADOW,true,DURATION,5000);\""
            . "onclick=\"if (window.confirm('" . $action_parc['msgConfirmEteindreMachine'] . " $mp_en_cours ?')) {return true;} else {return false;}\"/>"
            . "<img type=\"image\" border=\"0\" title=\"" . $action_parc['msgStationIsOn'] . "\" src=\"../elements/images/enabled.png\"></a>\n";
        } else {
            echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"" . $_POST['ip'] . "\" title=\"" . $_POST['ip'] . "\" />";
        }
    } else {
        //echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border='0' title='".$_POST['ip']."' title='".$_POST['ip']."' />";
        //echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border=\"0\" title=\"".$_POST['ip']."\" title=\"".$_POST['ip']."\" />";

        $nom_machine = isset($_POST['nom_machine']) ? $_POST['nom_machine'] : NULL;
        $parc = isset($_POST['parc']) ? $_POST['parc'] : NULL;
        if ((isset($nom_machine)) && (isset($parc))) {

            echo "<a target=\"main\" href=\"action_machine.php?machine=$nom_machine&action=wol&parc=$parc&retour=action_parc\" target='_blank' "
            . "onmouseout=\"UnTip();\" onmouseover=\"Tip('" . $action_parc['msgStationIsOff'] . "',WIDTH,250,SHADOW,true,DURATION,5000);\" >"
            . "<img type=\"image\" border=\"0\" title=\"" . $action_parc['msgStationIsOff'] . "\" src=\"../elements/images/disabled.png\">"
            . "</a>\n";
        } else {
            echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border=\"0\" title=\"" . $_POST['ip'] . "\" title=\"" . $_POST['ip'] . "\" />";
        }
    }
} elseif ($_POST['mode'] == 'session') {
    $session = get_smbsess($_POST['nom_machine']);
    echo $session['html'];
} elseif ($_POST['mode'] == 'wake_shutdown_or_reboot') {
    wake_shutdown_or_reboot($_POST['ip'], $_POST['nom'], $_POST['wake'], $_POST['shutdown_reboot']);
} elseif ($_POST['mode'] == 'test_logon') {
    $machine = $_POST['nom_machine'];
    if (is_dir('/home/netlogon/machine/' . $machine)) {
        if (is_file('/home/netlogon/machine/' . $machine . '/gpt.ini')) {
            echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"" . $machine . " : int&#233;gration OK \"/>";
        } else {
            echo "<img type=\"image\" src=\"../elements/images/warning.png\" border=\"0\" title=\"" . $machine . " : probl&#232;me avec les domscripts\"/>";
        }
    } else {
        $session = get_smbsess($machine);
        if ($session['login']) {
            echo "<img type=\"image\" src=\"../elements/images/warning.png\" border=\"0\" title=\"" . $machine . " : probl&#232;me avec les domscripts, le script de logon ne se lance pas \"/>";
        } elseif (fping($_POST['ip'])) {
            unset($texte);
            exec("sudo /usr/share/se3/scripts/force_gpo.sh " . $machine . " " . $_POST['ip'], $texte, $ret);
            if ($ret) {
                // afficher les codes d'erreur en fonction des r�sultats du script
                echo "<img type=\"image\" src=\"../elements/images/warning.png\" border=\"0\" title=\"" . $machine . " : probl&#232;me avec les domscripts, le script de logon a renvoy&#233; une erreur " . $ret;
                foreach ($texte as $ligne) {
                    echo $ligne . "<br>";
                }
                echo "\"/>";
            } else {
                echo "<img type=\"image\" src=\"../elements/images/enabled.png\" border=\"0\" title=\"" . $machine . " : int&#233;gration OK \"/>";
            }
        } else {
            echo "<img type=\"image\" src=\"../elements/images/disabled.png\" border=\"0\" title=\"" . $machine . " : il faut allumer la machine \"/>";
        }
    }
} elseif ($_POST['mode'] == 'ts_vnc') {

    $resultat = fping($_POST['ip']);
    if ($resultat) {
        $ts = ts($_POST['ip']);

        $vnc = vnc($_POST['ip']);
        if ($ts) {
            echo $ts;
        }
        if ($vnc) {
            echo $vnc;
        }
        if ((!$ts) and (!$vnc)) {
            $ret = "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('" . $action_parc['msgPortsClosed'] . "',WIDTH,250,SHADOW,true,DURATION,5000);\"" .
                    "><img type=\"image\" border=\"0\" title=\"" . $action_parc['msgPortsClosed'] . "\" src=\"../elements/images/disabled.png\">"
                    . "</span>\n";
            echo($ret);
        }
    } else {
        $ret = "<span onmouseout=\"UnTip();\" onmouseover=\"Tip('" . $action_parc['msgPingKo'] . "',WIDTH,250,SHADOW,true,DURATION,5000);\">" .
                "<img type=\"image\" border=\"0\" title=\"" . $action_parc['msgPingKo'] . "\" src=\"../elements/images/disabled.png\">"
                . "</span>\n";
        echo($ret);
    }
}
?>
