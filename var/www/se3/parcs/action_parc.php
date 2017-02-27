<?php

/**

 * Action sur un parc (arret - start)
 * @Version $Id$
 * @Projet LCS / SambaEdu

 * @auteurs  sandrine dangreville matice creteil 2005 - MrT Novembre 2008

 * @Licence Distribue selon les termes de la licence GPL

 * @note
 * Ajaxification des pings - script parc_ajax_lib.php sur une proposition de Stephane Boireau
 * Gestion des infobulles nouvelle mouture Tip et UnTip
 * Modification des fonctions ts et vnc
 * Externalisation des messages dans messages/fr/action_parc_messages.php dans un hash global
 * 
 */
/**

 * @Repertoire: parcs/
 * file: action_parc.php

 */
include "entete.inc.php";
require_once ("ldap.inc.php");
require_once ("ihm.inc.php");
require_once ("fonc_parc.inc.php");

###########
// Internationnalisation
$prefix = "action_parc";
//$lang="en";
require_once("messages/$lang/" . $prefix . "_messages.php");
###########
//aide
$_SESSION["pageaide"] = "Gestion_des_parcs#Action_sur_parcs";
//** Ajout de javascript local
##########
?>
<script type="text/javascript" src="/elements/js/wz_tooltip_new.js"></script>
<?

#########
//***************Definition des droits de lecture  et aide en ligne
// Verifie les droits
if ((is_admin("computers_is_admin", $login) == "Y") or (is_admin("parc_can_view", $login) == "Y") or (is_admin("parc_can_manage", $login) == "Y") or
        (is_admin("inventaire_can_read", $login) == "Y")) {

    echo "<h1>" . gettext("$action_parc[pageTitre]") . "</h1>";

    //*****************cas des parcs delegues***********************************/
    if ((is_admin("computers_is_admin", $login) == "N") and ((is_admin("parc_can_view", $login) == "Y") or (is_admin("parc_can_manage", $login) == "Y"))) {
        echo "<h3>" . gettext($action_parc['msgDelegationAccept']) . "</h3>";
        $acces_restreint = 1;

        $list_delegate = list_parc_delegate($login);

        if (count($list_delegate) > 0) {
            $delegate = "yes";
        } else {
            echo "<center>";
            echo $action_parc['msgNoDelegation'];
            echo "</center>\n";
            exit;
        }
    }

    /*     * *********************** Declaration des variables *********************************** */
    $action = $_POST['action'];
    if (!$action) {
        $action = $_GET['action'];
    }
    $parc = isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);

    if ($action == "") {
        $action = "detail";
    }
    if ($action == "choix_time") {
        $action = "detail";
    }

    switch ($action) {

        case "detail":

            $list_parcs = search_machines("objectclass=groupOfNames", "parcs");
            if (count($list_parcs) > 0) {
                sort($list_parcs);
                echo "<CENTER>";
                echo "<FORM method=\"post\" action=\"action_parc.php\">\n";
                echo "<SELECT NAME=\"parc\" SIZE=\"1\" onchange=submit()>";
                echo "<option value=\"SELECTIONNER\">" . $action_parc['msgSelect'] . "</option>";
                if ($delegate == "yes") {

                    foreach ($list_delegate as $info_parc_delegate) {
                        echo "<option value=\"" . $info_parc_delegate . "\"";
                        if ($parc == $info_parc_delegate) {
                            echo " selected";
                        }
                        echo ">$info_parc_delegate</option>\n";
                    }
                } else {
                    for ($loop = 0; $loop < count($list_parcs); $loop++) {
                        echo "<option value=\"" . $list_parcs[$loop]["cn"] . "\"";
                        if ($parc == $list_parcs[$loop]["cn"]) {
                            echo " selected";
                        }
                        echo ">" . $list_parcs[$loop]["cn"] . "\n";
                        echo "</option>";
                    }
                }
                echo "</SELECT>&nbsp;&nbsp;\n";
                echo "<img onmouseout=\"UnTip();\" onmouseover=\"Tip('" . gettext($action_parc['msgSelectParc']) . "',WIDTH,250,SHADOW,true,DURATION,5000);\" name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"> ";
                //      echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
                echo "</FORM>\n";
                echo "</CENTER>\n";
                echo "<br>";
            } else {
                echo "<br><center>";
                echo gettext($action_parc['msgNoParc']);
                echo "</center>\n";
            }


            if (($parc != "") && ($parc != "SELECTIONNER")) {
//                smbstatus();
                echo "<CENTER><table align=center><tr>\n";
                echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
                echo "<input type=\"hidden\" name=\"wolstop_station.php\" value=\"shutdown\" />";
                echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"stop\" />";
                echo "<input  type=\"submit\" value=\"" . gettext($action_parc['btnEteindreTitre']) . "\" onclick=\"if (window.confirm('" . $action_parc['msgConfirmEteindre'] . "')) {return true;} else {return false;}\" />";
                echo "</form></td>";

                echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
                echo "<input type=\"hidden\" name=\"action_poste\" value=\"wol\" />";
                echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"start\" />";
                echo "<input  type=\"submit\" value=\"" . gettext($action_parc['btnAllumerTitre']) . "\" />";
                echo "</form></td>";

                //===================================
                echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
                echo "<input type=\"hidden\" name=\"action_poste\" value=\"reboot\" />";
                echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"reboot\" />";
                echo "<input  type=\"submit\" value=\"" . gettext($action_parc['btnRebooterTitre']) . "\"  onclick=\"if (window.confirm('" . $action_parc['msgConfirmRebooter'] . "')) {return true;} else {return false;}\" />";
                echo "</form></td>";
                //===================================
                // Test le niveau de delegation
                // manage ou view
                // Si manage on peut aller sur programmer
                $niveau_delegation = niveau_parc_delegate($login, $parc);
                if ($niveau_delegation != "view") {
                    echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
                    echo "<input type=\"hidden\" name=\"programmation\" value=\"check\" />";
                    echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
                    echo "<input type=\"hidden\" name=\"action\" value=\"timing\" />";
                    echo "<input type=\"submit\" value=\"" . gettext($action_parc['btnProgrammerTitre']) . "\" />";
                    echo "</form>\n";
                    echo "</td>\n";
                }
				echo "<td><form action=\"show_parc.php\" method=\"post\">\n";
				echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
				echo "<input type=\"submit\" value=\"".gettext($action_parc['btnListerTitre'])."\" />";
				echo "</form>\n";
                echo "</td>\n";
                echo "</tr>\n";

                echo "<tr>\n";
                //	echo "<td colspan=\"3\" align=\"center\"><form action=\"action_parc.php\" method=\"post\">\n";
                echo "<td colspan=\"4\" align=\"center\"><form action=\"action_parc.php\" method=\"post\">\n";
                echo "<input type=\"hidden\" name=\"action_poste\" value=\"check\" />";
                echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"detail\" />";
                echo "<input type=\"submit\" value=\"" . gettext($action_parc['btnRafraichirTitre']) . "\" />";
                echo "</form>\n";

                echo "</td></tr></table>\n";

                echo "</center>\n";


                require_once ("printers.inc.php");


                global $smbversion;
                echo "\n<br>\n<CENTER>\n";
                echo "<TABLE border=1 width=\"60%\">\n<tr class=menuheader style=\"height: 30\">\n";
                echo "<td align=\"center\"></td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayStationTitre'] . "</td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayIp'] . "</td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayEtatTitre'] . "</td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayConnexionTitre'] . "</td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayControleTitre'] . "</td>\n";
                echo "<td align=\"center\">" . $action_parc['arrayLogonTitre'] . "</td></tr>\n";

                $mp_all = gof_members($parc, "parcs", 1);

                // Filtrage selon critere
                if ("$filtrecomp" == "") {
                    $mp = $mp_all;
                } else {
                    $lmloop = 0;
                    $mpcount = count($mp_all);
                    for ($loop = 0; $loop < count($mp_all); $loop++) {
                        $mach = $mp_all[$loop];
                        if (preg_match("/$filtrecomp/", $mach)) {
                            $mp[$lmloop++] = $mach;
                        }
                    }
                }

                if (count($mp) > 0) {
                    sort($mp);
                    for ($loop = 0; $loop < count($mp); $loop++) {
                        $mpenc = urlencode($mp[$loop]);
                        $mp_en_cours = urldecode($mpenc);
                        $mp_curr = search_machines("(&(cn=$mp_en_cours)(objectClass=ipHost))", "computers");

                        // Test si on a une imprimante ou une machine
                        $resultat = search_imprimantes("printer-name=$mp_en_cours", "printers");
                        $suisje_printer = "0";
                        for ($loopp = 0; $loopp < count($resultat); $loopp++) {
                            if ($mp_en_cours == $resultat[$loopp]['printer-name']) {
                                $suisje_printer = "1";
                                continue;
                            }
                        }

                        // On teste si la machine a des connexions actives
                        // en fonction de la version de samba
                        // On ne rentre dedans que si on est pas une imprimante

                        if ($suisje_printer != "1") {
                            // Inventaire
				//$sessid=session_id();
				if (file_exists("/var/www/se3/includes/dbconfig.inc.php")) {
					include_once "fonc_outils.inc.php";
                                        $sessid=session_id();
                                        $systemid=avoir_systemid($mpenc);
				}


                            // Affichage du tableau
                            echo "<tr>\n";
                            // Affichage de l'icone informatique
                            echo "<td align=\"center\">\n";
                            if (isset($systemid)) {
                                // Type d'icone en fonction de l'OS - modif keyser
                                    $retourOs = type_os($mpenc);
                                    if($retourOs == "0") { $icone="computer_disable.png"; }
                                    elseif($retourOs == "Linux") { $icone="linux.png"; }
                                    elseif($retourOs == "XP") { $icone="winxp.png"; }
                                    elseif($retourOs == "7") { $icone="win7.png"; }
                                    elseif($retourOs == "10") { $icone="win10.png"; }
                                    elseif($retourOs == "vista") { $icone="winvista.png"; }
                                    elseif($retourOs == "98") { $icone="win.png"; }
                                    else { $icone="computer_disable.png"; }
                                    $ip=avoir_ip($mpenc);
                                    echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/$icone\" title=\"".$retourOs." - ".$ip."\" alt=\"$retourOs\" WIDTH=20 HEIGHT=20 onclick=\"popuprecherche('../ocsreports/machine.php?systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=550');\">";

                                //echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" onclick=\"popuprecherche('../ocsreports/machine.php?systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=550');\"  title=\"Station\" alt=\"Station\"></td>\n";
                            } else {
                                echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/computer_disable.png\" alt=\"Ordinateur\" WIDTH=20 HEIGHT=20 >";

                                //echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" title=\"Station\" alt=\"Station\"></td>\n";
                            }
                            echo "<td align=center ><a href=show_histo.php?selectionne=2&amp;mpenc=$mp_en_cours>$mp_en_cours</a></td>\n";
                            $iphost = $mp_curr[0]["ipHostNumber"];
                            echo "<td align=center>$iphost</td>\n";
                            echo "<td align=center>\n";
                            //$etat
                            

                            echo "<div id='divip$loop'><img src=\"../elements/images/spinner.gif\"></img></div>\n";

                            echo "<script type='text/javascript'>
					// <![CDATA[
					new Ajax.Updater($('divip$loop'),'parcs_ajax_lib.php',{method: 'post', parameters: '?ip=$iphost&parc=$parc&nom_machine=" . $mp[$loop] . "&mode=ping_ip' });
					//]]>
				</script>\n";

                            echo "</td>\n";
                            echo "<td align=center>\n";
                            //$etat_session

                            echo "<div id='divsession$loop'><img src=\"../elements/images/spinner.gif\"></img></div>\n";

                            echo "<script type='text/javascript'>
					// <![CDATA[
					new Ajax.Updater($('divsession$loop'),'parcs_ajax_lib.php',{method: 'post', parameters: '?nom_machine=" . $mp[$loop] . "&mode=session'});
					//]]>
				</script>\n";

                            echo "</td>\n";
                            echo "<td align=\"center\">";

                            echo "<div id='divtsvnc$loop'><img src=\"../elements/images/spinner.gif\"></img></div>\n";

                            echo "<script type='text/javascript'>
					// <![CDATA[
					new Ajax.Updater($('divtsvnc$loop'),'parcs_ajax_lib.php',{method: 'post', parameters: '?ip=$iphost&mode=ts_vnc'});
					//]]>
				</script>\n";

                            echo "</td>";
                            echo "</td>\n";
                            echo "<td align=\"center\">";

                            echo "<div id='divlogon$loop'><img src=\"../elements/images/spinner.gif\"></img></div>\n";

                            echo "<script type='text/javascript'>
					// <![CDATA[
					new Ajax.Updater($('divlogon$loop'),'parcs_ajax_lib.php',{method: 'post', parameters: '?nom_machine=" . $mp[$loop] . "&ip=" . $iphost . "&mode=test_logon'});
					//]]>
				</script>\n";

                            echo "</td></tr>\n";
                        }
                    }
                }
                echo "</table>\n";
                echo "</center>\n";
                echo "<br>";
                detail_parc_printer($parc);
                //$heure_act=date("H");
                $nomjour = date("l");
                //  echo $nomjour;
            }

            switch ($nomjour) {

                case "Monday":
                    $nomjour = "l";
                    break;

                case "Tuesday":
                    $nomjour = "ma";
                    break;

                case "Wednesday":
                    $nomjour = "me";
                    break;

                case "Thursday":
                    $nomjour = "j";
                    break;

                case "Friday":
                    $nomjour = "v";
                    break;

                case "Saturday":
                    $nomjour = "s";
                    break;

                case "Sunday":
                    $nomjour = "d";
                    break;
            }

            $resultf = mysqli_query( $authlink, "select heure,action from actionse3 where parc='$parc' and jour='$nomjour' ;") or die("Impossible d'effectuer la requete");
            if ($resultf) {
                if (mysqli_num_rows($resultf) > 0) {
                    while ($row = mysqli_fetch_row($resultf)) {
                        if ($row[1] == "wol") {
                            echo "<h3>" . gettext($action_parc['msgPoweronAction']) . " $row[0] " . gettext("ce jour") . "</h3>";
                        }
                        if ($row[1] == "stop") {
                            echo "<h3>" . gettext($action_parc['msgShutdownAction']) . " $row[0] " . gettext("ce jour") . "</h3>";
                        }
                    }
                } else {
                    if (($parc != "") && ($parc != "SELECTIONNER")) {
                        echo "<h3>" . gettext($action_parc['msgNoActions']) . " $parc</h3>";
                    }
                }
            }
            break;
    }

// echo "</div>";
}

require ("pdp2.inc.php");
?>
