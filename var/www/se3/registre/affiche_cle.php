<?php

/**

 * Gestion des cles pour clients Windows (affiche l'ensemble des cles enregistrees dans la base)
 * @Version $Id$ 


 * @Projet LCS / SambaEdu 

 * @auteurs  Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note 

 */
/**

 * @Repertoire: registre
 * file: affiche_cle.php

 */
require "include.inc.php";

$cat = $_GET['cat'];
$sscat = $_GET['sscat'];

//pour le retour vers les groupes de cle :(
$lien_retour = $_GET['lien_retour'];
$mod = $_GET['mod'];
if (!$lien_retour) {
    $lien_retour = $_POST['lien_retour'];
}
if (!$mod) {
    $mod = $_POST['mod'];
}

if (!$cat) {
    $cat = $HTTP_COOKIE_VARS["Categorie"];
}
if ($cat) {
    setcookie("Categorie", "", time() - 3600);
    setcookie("Categorie", $cat, time() + 3600);
}

if ($cat == "tout") {
    setcookie("Categorie", "", time() - 3600);
    $sscat = "";
}


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

if (ldap_get_right("computers_is_admin", $login) != "Y")
    die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");
$_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

echo "<h1>" . gettext("Gestion des cl&#233;s") . "</h1>";

$testniveau = getintlevel();

$modif = $_POST['modif'];
if (!$modif) {
    $modif = $_GET['modif'];
}
// if (!lien_retour) {
//	echo "<h1>".gettext("Gestion des cl&#233;s")."  (".afficheniveau($testniveau).")</h1>";
// }
//ce menu n'est pas disponible pour les debutants
if ($testniveau < 2) {
    echo "<br><br>" . gettext("Les fonctionnalit&#233s de ce menu ne sont pas disponibles au niveau d&#233butant");
    exit;
}
connexion();
if (test_bdd_registre() == false) {
    exit;
}
switch ($modif) {
    //cas 3 : modification d'une cle  ou suppression
    //cas 4 : modification d'une cle suite
    //par default affichage des cles
    case "3":
        $clemodif = $_POST['modifkey'];
        if (!$clemodif) {
            $clemodif = $_GET['modifkey'];
        }
        $suppr = $_POST['del'];
        if (!$suppr) {
            $suppr = $_GET['del'];
        }
        trim($clemodif);
        trim($suppr);
        //modification d'une cle mais pas suppression
        if (($clemodif) and (!$suppr)) {
            $query = "Select Intitule,cleID,valeur,genre,OS,type,antidote,chemin,categorie,sscat from corresp where cleID='$clemodif'";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            $row = mysqli_fetch_array($resultat);
            echo"<FORM METHOD=POST ACTION=\"affiche_cle.php\" name=\"modifcle\" >";
            echo"<H3>";
            echo "<a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\"><img src=\"/elements/images/system-help.png\" alt=\"aide\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\">$row[0]</a></H3>";
            echo gettext("Pour info :") . " $row[7] <br><br><table border=\"1\"><tr><td>&nbsp;</td><td>" . gettext("Ancienne valeur") . "</td>";
            echo "<td>" . gettext("Nouvelle valeur") . "</td></tr>";
            echo "<tr><td>" . gettext("Intitule") . "</td><td>$row[0]&nbsp;</td><td><INPUT TYPE=\"text\" NAME=\"newintit\" value=\"$row[0]\"size=\"70\"></td><tr>";
            echo "<tr><td>" . gettext("Valeur") . "</td> <td>" . htmlentities($row[2]) . "</td><td><INPUT TYPE=\"text\" NAME=\"newval\" value=\"" . $row[2] . "\"></td><tr>";
            echo "<tr><td>" . gettext("Categorie") . "</td><td>$row[8]&nbsp;</td><td><INPUT TYPE=\"text\"  NAME=\"newcatm\"><select name=\"newcat\" size=\"1\" >";

            //la cle est dans une categorie : on positionne le menu deroulant dessus

            $query1 = "Select DISTINCT categorie from corresp group by categorie;";
            $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
            while ($row1 = mysqli_fetch_row($resultat1)) {
                if ($row1[0]) {
                    echo"<option value=\"$row1[0]\" ";
                    if ($row1[0] == $row[8]) {
                        echo "selected";
                    }
                    echo">$row1[0]</option>";
                }
            }

            //la cle est dans une sous-categorie : on positionne le menu deroulant dessus
            $query2 = "Select DISTINCT sscat from corresp where '$row[8]'=categorie group by sscat;";
            $resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
            echo "</select></td></tr><tr><td>" . gettext("Sous-Categorie") . "</td><td>$row[9]&nbsp;</td><td>";
            echo "<INPUT TYPE=\"text\" NAME=\"newsscatm\"><select name=\"newsscat\" size=\"1\"><option ></option> ";
            while ($row2 = mysqli_fetch_row($resultat2)) {
                if ($row2[0]) {
                    echo"<option value=\"$row2[0]\" ";
                    if ($row2[0] == $row[9]) {
                        echo "selected";
                    }
                    echo">$row2[0]</option>";
                }
            }
            echo"</select></td></tr>";
            echo"<tr><td>" . gettext("Antidote") . "</td><td>$row[6]</td><td><INPUT TYPE=\"text\" NAME=\"newvalanti\" value=\"$row[6]\"></td><tr>";

            $OS = $row[4];
            echo "<tr><td>OS</td><td>$row[4]</td><td><select name=\"newos[]\" multiple size=\"6\">";

            echo "<option value=\"TOUS\" ";
            if ($OS == "TOUS") {
                echo "SELECTED";
            }
            echo ">TOUS</option>";

            echo "<option value=\"Win9x\" ";
            if (substr_count($OS, "Win9x") > 0) {
                echo "SELECTED";
            }
            echo ">Type Windows 9X</option>";

            echo "<option value=\"2000\" ";
            if (substr_count($OS, "2000") > 0) {
                echo "SELECTED";
            }
            echo ">2000</option>";

            echo "<option value=\"XP\" ";
            if (substr_count($OS, "XP") > 0) {
                echo "SELECTED";
            }
            echo ">XP</option>";

            echo "<option value=\"Vista\" ";
            if (substr_count($OS, "Vista") > 0) {
                echo "SELECTED";
            }
            echo ">Vista</option>";

            echo "<option value=\"Seven\" ";
            if (substr_count($OS, "Seven") > 0) {
                echo "SELECTED";
            }
            echo ">Seven</option>";

            echo "</select></td><tr><td>" . gettext("Genre") . "</td>";

            echo "<td>$row[3]</td><td><select name=\"newgenre\" size=\"1\">
<OPTION value=\"REG_DWORD\" ";
            if ($row[3] == "REG_DWORD") {
                echo "SELECTED";
            }
            echo ">REG_DWORD</option>    <OPTION value=\"REG_BINARY\" ";
            if ($row[3] == "REG_BINARY") {
                echo "SELECTED";
            }
            echo ">REG_BINARY</option>    <OPTION value=\"REG_SZ\" ";
            if ($row[3] == "REG_SZ") {
                echo "SELECTED";
            }
            echo ">REG_SZ</option>     <OPTION value=\"REG_EXPAND_SZ\" ";
            if ($row[3] == "REG_EXPAND_SZ") {
                echo "SELECTED";
            }
            echo ">REG_EXPAND_SZ</option> </select></td>";
            echo "<tr><td>" . gettext("Type de la cl&#233 : restriction ou configuration ?") . "</td><td><select name=\"newtype\" size=\"1\">";
            echo "<option value=\"config\" ";
            if ($row[5] == "config") {
                echo "selected";
            }
            echo">" . gettext("config") . "</option>";
            echo "<option value=\"restrict\" ";
            if ($row[5] == "restrict") {
                echo "selected";
            }
            echo">" . gettext("restrict") . "</option></select> </tr>";

            echo "</table><INPUT TYPE=\"hidden\" value=\"$mod\" name=\"mod\"><INPUT TYPE=\"hidden\" value=\"$lien_retour\" name=\"lien_retour\"><INPUT TYPE=\"hidden\" value=\"$clemodif\" name=\"newkey\">";

            //dois-t-on mettre a jour cette cle dans les templates ?
            $query1 = "SELECT restrictions.groupe,restrictions.valeur FROM restrictions WHERE restrictions.cleID = '$clemodif';";
            $chercher = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
            $i = 0;
            if (mysqli_num_rows($chercher)) {
                echo "<br>" . gettext("De plus cette cl&#233 est utilis&#233e dans les groupes suivants, vous pouvez r&#233actualiser les valeurs modifi&#233es dans les groupes que vous choississez ci-dessous .<br> Si la valeur affich&#233e correspond &#224 l'antidote, il sera remis &#224 la place la nouvelle valeur de l'antidote.<br> Dans le cas contraire, c'est la valeur par d&#233faut qui sera appliqu&#233e.") . "<br><br><br>";
                //affichage des templates
                echo "<table border=\"1\"><tr><td>" . gettext("Choix") . "</td><td>" . gettext("Templates concern&#233s") . "</td><td>" . gettext("Valeur actuelle dans le template") . "</td></tr>";
                $i++;

                while ($liste = mysqli_fetch_row($chercher)) {
                    if ($liste[0]) {
                        echo "<tr><td><input type=\"checkbox\" name=\"refreshtemplate$i\" value=\"$liste[0]\" /></td>";
                        echo "<a href=affiche_restrictions.php?salles=$liste[0]&poser=yes\" target=_blank >";
                        echo "<td><div align=\"center\">$liste[0]</div></td></a><td><div align=\"center\">$liste[1]</div></td></tr>";
                        $i++;
                    }
                }

                echo "</table><input type=\"hidden\" name=\"nombre\" value=\"$i\" /> ";
            }
            echo "<INPUT TYPE=\"hidden\" value=\"4\" name=\"modif\">";
            echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Modifier la valeur") . "\">";
            echo "</FORM>";
        }
//suppression d'une cle
        $n = 0;
        if ($suppr) {
            $confirm = $_POST['confirm'];
            //la cle est presente dans les groupes : d'abord la supprimer des groupes

            $query = "SELECT restrictions.groupe, restrictions.cleID, restrictions.valeur FROM restrictions, corresp  WHERE restrictions.cleID = '$suppr' AND restrictions.cleID = corresp.CleID AND restrictions.valeur <> corresp.antidote";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            while ($row = mysqli_fetch_array($resultat)) {
                $liste[$n] = $row[0];
                $n++;
            }
            //confirmation de la suppression
            $testpresencecle = $_POST[supprgroupe];
            // la cle  peut etre supprimee pour l'instant si elle n'est presente dans aucun groupe
            if ((!$testpresencecle) AND (!$n)) {
                $query = "Select Intitule,cleID,valeur,genre,OS from corresp where cleID='$suppr'";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                $row = mysqli_fetch_array($resultat);
                $query = "DELETE FROM corresp WHERE cleID='$suppr';";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                echo gettext("Cle supprim&#233e");
                $query = "DELETE FROM restrictions WHERE cleID='$suppr';";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                $query = "DELETE FROM `modele` WHERE `cle` = '$suppr';";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=affiche_cle.php\"></HEAD>";
                echo gettext("Commandes prises en compte !");
            } else {
                //presence du test non null on ne supprime pas!!

                echo gettext("Vous devez d'abord supprimer la cl&#233 des groupes suivants!!") . "<br>";
                for ($i = 0; $i < $n + 1; $i++) {
                    echo "<a href=affiche_restrictions.php?salles=$liste[$i]&poser=yes\" target=_blank >$liste[$i]</a><br>";
                }
                echo "<FORM METHOD=POST ACTION=\"affiche_cle.php\">";
                echo "<INPUT TYPE=\"hidden\" name=\"del\" value=\"$suppr\">";
                echo "<INPUT TYPE=\"hidden\" name=\"modif\" value=\"15\">";
                echo "<INPUT TYPE=\"hidden\" name=\"confirm\" value=\"yes\">";
                echo "<input type=\"hidden\" name=\"supprgroupe\" value=\"$test\" >";
                echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Retour") . "\">";
                echo "</FORM>";
                echo "<br>" . gettext("Il est imp&#233ratif de d&#233sactiver la cl&#233 dans les groupes avant de la supprimer de la base. Vous pourriez avoir une restriction impossible &#224 enlever");
            }
        }
        break;

//modification d'une cle
    case "4":
        $nummodif = $_POST['newkey'];
        $valmodif = $_POST['newval'];
        $valmodifanti = $_POST['newvalanti'];
        $nombre = $_POST['nombre'];
        $oss = $_POST['newos'];
        $intitule = $_POST['newintit'];
        $genre = $_POST['newgenre'];
        $newtype = $_POST['newtype'];
        //deux moyen de recuperer les categories : soit par entree manuelle d'un nom
        //soit par la selection dans une categorie preexistente
        $newcat1 = $_POST['newcatm'];
        if (!$newcat1) {
            $newcat1 = $_POST['newcat'];
        }
        $newsscat1 = $_POST['newsscatm'];
        if (!$newsscat1) {
            $newsscat1 = $_POST['newsscat'];
        }
        $newcat = strtolower(trim($newcat1));
        $newsscat = strtolower(trim($newsscat1));

        $os = "";
        for ($i = 0; $i < count($oss); $i++) {
            $os = $os . $oss[$i];
            if ($i + 1 != count($oss))
                $os = $os . ",";
        }

        if ($newtype == "restrict") {
            $query5 = "UPDATE corresp SET antidote='$valmodifanti' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query5);
        }
        if ($valmodif <> "") {
            $query = "UPDATE corresp SET valeur='$valmodif' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        } else {
            $query = "UPDATE corresp SET valeur='' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        if ($intitule) {
            $query = "UPDATE corresp SET Intitule='$intitule' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        if ($genre) {
            $query = "UPDATE corresp SET genre='$genre' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        if ($newtype) {
            $query = "UPDATE corresp SET type='$newtype' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        if ($newcat) {
            $query = "UPDATE corresp SET categorie='$newcat' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        } else {
            $query = "UPDATE corresp SET categorie='' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }

        if ($newsscat) {
            $query = "UPDATE corresp SET sscat='$newsscat' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        if (!$newsscat) {
            $query = "UPDATE corresp SET sscat='' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }

        if ($os) {
            $query = "UPDATE corresp SET OS='$os' WHERE cleID='$nummodif';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        }
        for ($i = 0; $i < $nombre + 1; $i++) {
            $template = $_POST['refreshtemplate' . $i];
            if ($template) {
                $query1 = "SELECT restrictions.groupe,restrictions.valeur,corresp.valeur,corresp.antidote,corresp.categorie,corresp.sscat,corresp.type FROM restrictions,corresp WHERE restrictions.cleID = '$nummodif' and restrictions.cleID=corresp.CleID and restrictions.groupe='$template' ;";
                $chercher = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                $row = mysqli_fetch_row($chercher);
                if ($row[6] == "config") {
                    $query3 = "UPDATE restrictions SET valeur='$valmodif' WHERE cleID='$nummodif' and groupe='$template';";
                    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query3);
                }

                //dans le template base, on met a jour la cle de restriction (active)
                if ($template == "base") {
                    if ($row[6] == "restrict") {
                        echo gettext("Template") . " $template " . gettext("mis &#224 jour");
                        $query3 = "UPDATE restrictions SET valeur='$valmodif' WHERE cleID='$nummodif' and groupe='$template';";
                        //echo $query3;
                        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query3);
                    }
                } else {
                    //dans un autre template , on mets a jour l'antidote
                    if ($row[6] == "restrict") {
                        $query3 = "UPDATE restrictions SET valeur='$valmodifanti' WHERE cleID='$nummodif' and groupe='$template';";
                        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query3);
                    }
                }
                echo gettext("Template") . " $template " . gettext("mis &#224 jour") . " <br>";
            }
        }
        //pour affichage immediat des cles
        //si pas de lien on affiche
        if ($lien_retour) {
            echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=$lien_retour?modact=yes&mod=$mod\">";
            echo "</HEAD>" . gettext("Commandes prises en compte !") . "<br>";

            break;
        }
    case "5":
        affichelistecat("affiche_cle.php?", $testniveau, $cat);
        if ($testniveau >= "3") {
            echo"<FORM METHOD=POST ACTION=\"ajout_cle.php\">";
            echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Ajouter une cl&#233") . "\" name=\"ajoutcle\"></FORM><br/>";
        }
        if ($cat) {
            $ajout = " WHERE `categorie` = '$cat' ";
            if ($sscat) {
                $ajoutsscat = " AND sscat='$sscat'";
                echo "<h3> $sscat</h3>";
            }
            if (!($sscat)) {
                $ajoutsscatvide = " AND sscat='' ";
            }
            $query = "Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin from corresp " . $ajout . $ajoutsscatvide . $ajoutsscat . " order by type desc,OS,valeur,antidote,genre";
            $affichetitle = "<table border=\"1\" ><tr><td><h4><img src=\"/elements/images/system-help.png\" alt=\"Aide\" title=\"" . gettext("Aide") . "\" width=\"16\" height=\"18\" border=\"0\" /></h4></td><td><DIV ALIGN=CENTER>" . gettext("Intitul&#233") . "</DIV></td><td>OS</td><td><DIV ALIGN=CENTER>" . gettext("Valeur (defaut)") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Antidote") . "</DIV></td><td><img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier la valeur") . "\" title=\"" . gettext("Modifier") . "\" width=\"16\" height=\"16\" border=\"0\" /></td><td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"" . gettext("Supprimer") . "\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
            affichelisteget("affiche_cle.php?modif=3", "modifkey", "del", $query, $affichetitle, $testniveau);
            if ($cat == "tout") {
                $query = "Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin,categorie,sscat from corresp order by type desc,OS,valeur,antidote,genre";
                $affichetitle = "<table border=\"1\" ><tr><td><h4><img src=\"/elements/images/system-help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></h4></td><td><DIV ALIGN=CENTER>" . gettext("Intitul&#233") . "</DIV></td><td>OS</td><td><DIV ALIGN=CENTER>" . gettext("Valeur (defaut)") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Antidote") . "</DIV></td><td><img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier la valeur") . "\" title=\"" . gettext("Modifier") . "\" width=\"16\" height=\"16\" border=\"0\" /></td><td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"" . gettext("Supprimer") . "\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
                affichelisteget("affiche_cle.php?modif=3", "modifkey", "del", $query, $affichetitle, $testniveau);
            }
        } else {
            echo "<br><h3>" . gettext("Choisir une cat&#233gorie ci-dessus") . " </h3><br><br>";
        }
        break;

//par default affichage des cles
    default:
        affichelistecat("affiche_cle.php?", $testniveau, $cat);
        if ($testniveau >= "3") {
            echo"<FORM METHOD=POST ACTION=\"ajout_cle.php\">";
            echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Ajouter une cl&#233") . "\" name=\"ajoutcle\"></FORM><br/>";
        }
        if ($cat) {
            $ajout = " WHERE `categorie` = '$cat' ";
            if ($sscat) {
                $ajoutsscat = " AND sscat='$sscat'";
                echo "<h3> $sscat</h3>";
            }
            if (!($sscat)) {
                $ajoutsscatvide = " AND sscat='' ";
            }
            $query = "Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin from corresp " . $ajout . $ajoutsscatvide . $ajoutsscat . " order by type desc,OS,valeur,antidote,genre";
            $affichetitle = "<table border=\"1\" ><tr><td><h4><img src=\"/elements/images/system-help.png\" alt=\"Aide\" title=\"" . gettext("Aide") . "\" width=\"16\" height=\"18\" border=\"0\" /></h4></td><td><DIV ALIGN=CENTER>" . gettext("Intitul&#233") . "</DIV></td><td>OS</td><td><DIV ALIGN=CENTER>" . gettext("Valeur (defaut)") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Antidote") . "</DIV></td><td><img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier la valeur") . "\" title=\"" . gettext("Modifier") . "\" width=\"16\" height=\"16\" border=\"0\" /></td><td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"" . gettext("Supprimer") . "\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
            affichelisteget("affiche_cle.php?modif=3", "modifkey", "del", $query, $affichetitle, $testniveau);
            if ($cat == "tout") {
                $query = "Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin,categorie,sscat from corresp order by type desc,OS,valeur,antidote,genre";
                $affichetitle = "<table border=\"1\" ><tr><td><h4><img src=\"/elements/images/system-help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></h4></td><td><DIV ALIGN=CENTER>" . gettext("Intitul&#233") . "</DIV></td><td>OS</td><td><DIV ALIGN=CENTER>" . gettext("Valeur (defaut)") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Antidote") . "</DIV></td><td><img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier la valeur") . "\" title=\"" . gettext("Modifier") . "\" width=\"16\" height=\"16\" border=\"0\" /></td><td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"" . gettext("Supprimer") . "\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
                affichelisteget("affiche_cle.php?modif=3", "modifkey", "del", $query, $affichetitle, $testniveau);
            }
        } else {
            echo "<br><h3>" . gettext("Choisir une cat&#233gorie ci-dessus") . " </h3><br><br>";
        }
        break;
}
((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

include("pdp.inc.php");
?>
