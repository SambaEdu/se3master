<?php

/**

 * Gestion des cles pour clients Windows (mise a jour des cles)
 * @Version $Id$ 


 * @Projet LCS / SambaEdu 

 * @auteurs  Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note 

 */
/**

 * @Repertoire: registre
 * file: cle-maj.php

 */
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "include.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

echo "<h1>Importation des cl&#233;s</h1>";

// connexion();

if (ldap_get_right("computers_is_admin", $login) != "Y")
    die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");

// Aide
$_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

$act = $_GET['action'];
if (!$act) {
    $act = $_POST['action'];
}
$ajout = $_POST['ajout'];

switch ($act) {
    default:
        break;

    case "file":
        if (isset($_POST['upload'])) { // si formulaire soumis
            if (file_exists("/tmp/rules.xml"))
                unlink("/tmp/rules.xml");
            $content_dir = '/tmp/'; // dossier ou sera deplace le fichier
            $tmp_file = $_FILES['fichier']['tmp_name'];

            if (!is_uploaded_file($tmp_file)) {
                exit(gettext("Le fichier est introuvable"));
            }
            $type_file = $_FILES['fichier']['type'];
            if (!strstr($type_file, 'xml')) {
                exit(gettext("Le fichier n'est pas un fichier xml"));
            }
// on copie le fichier dans le dossier de destination
            $name_file = $_FILES['fichier']['name'];

            if (!move_uploaded_file($tmp_file, $content_dir . $name_file)) {
                exit(gettext("Impossible de copier le fichier dans") . " $content_dir");
            }

            $fichier_xml = $content_dir . $name_file;
            echo gettext("Le fichier") . " $name_file " . gettext("a bien &#233t&#233 upload&#233");
        }

        break;

    case "maj":
        $fichier_xml = "/usr/share/se3/data/rules.xml";

        break;
}

if (($fichier_xml) && (!$retval_rules)) {
    echo "<br>" . gettext("D&#233but de l'analyse du fichier") . " $fichier_xml<br>";

    /**

     * Fonctions Analyse le debut d'un fichier XML

     * @Parametres
     * @Return

     */
    function gestionnaire_debut($analyseur, $nom, $attribut) {
        global $nb;
        global $ligne;
        $nb++;

        if (sizeof($attribut)) {
            foreach ($attribut as $cle => $valeur) {
                $ligne = $ligne . $valeur . "-:-";
            }
        }
    }

    /**

     * Fonctions Analyse la fin d'un fichier XML

     * @Parametres
     * @Return

     */
    function gestionnaire_fin($analyseur, $nom) {
        global $nb;
        global $ligne;
        $nb--;

        if ($nb < 5) {
            $ligne = $ligne . ";&;";
        } else {
            $ligne = $ligne . "--";
        }
    }

    /**

     * Fonctions Analyse le texte d'un fichier XML

     * @Parametres 
     * @Return  

     */
    function gestionnaire_texte($analyseur, $texte) {
        global $nb;
        global $ligne;
        if ($nb > 2) {
            $ligne = $ligne . $texte;
        }
    }

// 
    $nb = 0;
    $analyseur_xml = xml_parser_create();
    xml_set_element_handler($analyseur_xml, "gestionnaire_debut", "gestionnaire_fin");
    xml_set_character_data_handler($analyseur_xml, "gestionnaire_texte");

    if (!($id_fichier = fopen($fichier_xml, "r"))) {
        die(gettext("Impossible d'ouvrir le fichier XML !"));
    }

    while ($donnee = fread($id_fichier, filesize($fichier_xml))) {
        if (!xml_parse($analyseur_xml, $donnee, feof($id_fichier))) {
            die(sprintf(gettext("Une erreur XML %s s'est produite &#224; la ligne %d et &#224; la colonne %d."), xml_error_string(xml_get_error_code($analyseur_xml)), xml_get_current_line_number($analyseur_xml), xml_get_current_column_number($analyseur_xml)));
        }
    }
    xml_parser_free($analyseur_xml);
    if ($fichier_xml != "/usr/share/se3/data/rules.xml")
        unlink($fichier_xml);

    $patterns[0] = "|CHECKBOX|";
    $patterns[1] = "|EDIT|";
    $patterns[2] = "|#DEL|";
    $patterns[3] = "|SELECT|";
    $patterns[4] = "|LECTEURS|";
    $patterns[5] = "|CHAINE|";
    $patterns[6] = "|REG_|";

    $replacements[0] = "restrict";
    $replacements[1] = "config";
    $replacements[2] = "SUPPR";
    $replacements[3] = "config";
    $replacements[4] = "config";
    $replacements[5] = "SZ";
    $replacements[6] = "";

    $liste = preg_replace($patterns, $replacements, $ligne);

    $categorie = preg_replace("/\"/", "", $categorie);
    $categorie = explode(";&;", $liste);
    for ($j; $j < count($categorie); $j++) {
//    	print "$categorie[$j]<br>\n";
        if ((preg_match("/INFO/", $categorie[$j]))) {
            $oldnom = $nom;
            $tab = preg_split("#-:-#", $categorie[$j]);
            if (count($tab) == 3) {
                list($nom, $rest) = $tab;
                $sscat = "";
            } elseif (count($tab) == 2) {
                $reste = $tab[1];
                $tab2 = preg_split("#--#", $reste);
                if ("$tab2[1]" == "") {
                    $sscat = $tab2[2];
                } else {
                    $sscat = $tab2[1];
                }
            }
        } else {
            if (preg_match("/REGISTRE/", $categorie[$j])) {
                list($partcomp1, $partcomp2, $partcomp3) = preg_split("#-:-#", $categorie[$j]);
                list($OS, $reg, $Intitule, $type, $genre) = preg_split("#--#", $partcomp2);
                list($finreg, $valeur, $antidote, $comment) = preg_split("#--#", $partcomp3);
                if ($reg) {
                    list($poub, $reg) = preg_split("#reg:///#", $reg);
                }
                if ("$genre" == "") {
                    $genre = "EXPAND_SZ";
                }
                $sscat = preg_replace("/\"/", "", $sscat);
                $sscat = preg_replace("/([\r\n])/", "", $sscat);
                $part1 = preg_replace("/\"/", "", $part1);
                $envoi = trim($envoi) . trim($Intitule) . "--" . trim($valeur) . "--" . trim($antidote) . "--REG_" . trim($genre) . "--" . trim($OS) . "--" . trim($type) . "--" . trim($reg) . "\\" . trim($finreg) . "--" . trim($comment) . "--" . trim($nom) . "--" . trim($sscat) . ";&;";
            }
        }
    }
}

if ($envoi) {
    $envoi = preg_replace("#(\r\n)|(\n)|(\r)#", "", $envoi);
    echo "<br>" . gettext("Premi&#232;re analyse des cl&#233;s &#224; importer  en cours :") . " <br>";
    
    //Passage en UTF-8 donc plus necessaire
    //if (mb_detect_encoding($envoi, "UTF-8")) {
    //    $envoi = mb_convert_encoding($envoi, 'ISO-8859-1', 'UTF-8');
    //}
    $brutout = enleveantislash($envoi);
    $result = preg_split("/;&;/", $brutout);
    $nombre = count($result);
    $nombre1 = $nombre - 1;

    echo "<br><FORM METHOD=POST ACTION=\"cle-maj.php\" name=\"ajoute\">";
    connexion();
    echo "<table border=\"1\">";
	echo "<tr><td>" . gettext("Selectionner") . "</td><td>" . gettext("Etat") . "</td><td>" . gettext("Intitule") . "</td><td>" . gettext("Valeur") . "</td><td>" . gettext("Antidote") . "</td><td>" . gettext("Genre") . "</td><td>OS</td><td>" . gettext("Type") . "</td><td>" . gettext("Chemin") . "</td><td>" . gettext("Commentaires") . "</td><td>" . gettext("Categorie") . "</td></tr>";
 
    for ($j = 0; $j < $nombre; $j++) {
        $export[$j] = enlevedoublebarre($result[$j]);
        $cle = preg_split("/--/", $export[$j]);

        if ($cle[6]) {
            $cletrim = ajoutedoublebarre(($cle[6]));
            $query = "SELECT Intitule,valeur,antidote,genre,OS,type,chemin,comment,categorie,sscat FROM corresp WHERE chemin='$cletrim';";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            if (mysqli_num_rows($resultat)) {
                $row = mysqli_fetch_array($resultat);
                if ($row[6]) {
                    if (($row[0] <> $cle[0]) or ($row[1] <> $cle[1]) or ($row[2] <> $cle[2]) or ($row[3] <> $cle[3]) or ($row[4] <> $cle[4]) or ($row[5] <> $cle[5]) or ($row[6] <> $cle[6]) or ($row[7] <> $cle[7]) or ($row[8] <> $cle[8]) or ($row[9] <> $cle[9])) {
                        echo "<tr><td><INPUT TYPE=\"checkbox\" NAME=\"test$j\" value=\"".str_replace("\"","&#34;",$export[$j])."\" CHECKED ></td><td bgcolor=\"#FF3300\">" . gettext("A Modifier ?") . "</td>";
                        $cle[6] = enlevedoublebarre($cle[6]);
                        for ($i = 0; $i < 10; $i++) {
                            $color = "";
                            $title = "";
                            if ($row[$i] <> $cle[$i]) {
                                $color = "#FF3300";
                                $title = "ancien : " . $row[$i] . "<br> nouveau : ";
                            }
                            echo "<td bgcolor=\"" . $color . "\"><div>" . $title . $cle[$i] . "</div></td>";
                            $title = "";
                        }
                        echo "</tr>";
                        $exist++;
                    }
                }
            } else {
                $nouv++;
                $valeur = str_replace("\"", "&#34;", $export[$j]);
                echo "<tr><td><INPUT TYPE=\"checkbox\" NAME=\"test$j\" value=\"".str_replace("\"","&#34;",$valeur)."\" CHECKED></td><td bgcolor=\"00CC33\" >New</td>";
                $cle[6] = enlevedoublebarre($cle[6]);
                for ($i = 0; $i < 10; $i++) {
                    echo "<td>" . $cle[$i] . "</td>\n";
                }
                echo "</tr>";
            }
        }
    }
    echo"</table><INPUT TYPE=\"hidden\" name=\"ajout\" value=\"7\">";
    echo"<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$nombre1\">";

    if ($nouv) {
        echo gettext("Attention par d&#233;fault, les cl&#233;s d&#233;j&#224; existantes et &#224; modifier seront modifi&#233;es !!") . " ( $exist )<br>";
        echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Pret pour l'importation des cl&#233s nouvelles!") . "\"></FORM>";
    } else {
        echo "<br>" . gettext("Pas de cl&#233;s nouvelles !!") . "<br>";
        if ($exist) {
            echo $exist." Cles a modifier<br><INPUT TYPE=\"submit\" value=\"" . gettext("Continuer") . "\"></FORM>";
        }
    }

    ((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
}

if ($ajout == 7) {

    $test = $_POST['test'];
    $test = preg_replace("/([\r\n])|([\n])|([\r])/", "", $test);
    $test = preg_replace("/([\r\n])/", "", $test);
    echo "<table border=1><tr><td>" . gettext("Etat") . "</td><td>" . gettext("Intitule") . "</td><td>" . gettext("Valeur") . "</td><td>" . gettext("Antidote") . "</td><td>" . gettext("Genre") . "</td><td>OS</td><td>" . gettext("Type") . "</td><td>" . gettext("Chemin") . "</td><td>" . gettext("Commentaires") . "</td><td>" . gettext("Categorie") . "</td></tr>";
    $nb = $_POST['nombre'];

    for ($j = 0; $j < $nb; $j++) {
        $valeur = str_replace("&#34;", "\"", $_POST['test' . $j]);
        $cle[$j] = $valeur;
//        echo $valeur."<br>";
        if ($cle[$j]) {
            $cleok = preg_split("/--/", $cle[$j]);
            connexion();

            if ($cleok[5] == "config") {
                $cleok[2] = "";
            }
            $cleok[8] = strtolower($cleok[8]);
            $cleok[9] = strtolower($cleok[9]);
            $cleok[9] = preg_replace("/([\r\n])/", "", $cleok[9]);
            $cleok[8] = trim($cleok[8]);
            $cleok[9] = trim($cleok[9]);
            $cletrim = ($cleok[6]);
            $query1 = "SELECT chemin,CleID FROM corresp WHERE '".$cletrim."'=chemin";
            $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
            $num = mysqli_num_rows($resultat1);
            if (!$num) {
                $cleok[6] = ajoutedoublebarre(($cleok[6]));
                $cleok[1] = ajoutedoublebarre(($cleok[1]));
                $cleok[2] = ajoutedoublebarre(($cleok[2]));
                $cleok[0] = addslashes(($cleok[0]));
                $cleok[7] = addslashes(($cleok[7]));
                $cleok[9] = addslashes(($cleok[9]));
		echo $query."<br>";
                $query = "INSERT INTO corresp (Intitule,valeur,antidote,genre,OS,type,chemin,comment,categorie,sscat) VALUES ('$cleok[0]','$cleok[1]','$cleok[2]','$cleok[3]','$cleok[4]','$cleok[5]','$cleok[6]','$cleok[7]','$cleok[8]','$cleok[9]')";
                $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
//  echo $query;
//  if ($cleok[5]=="restrict")
//  {
                $query = "SELECT cleID FROM corresp WHERE '$cleok[6]'=chemin;";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                $row = mysqli_fetch_array($resultat);
                $querymod = "SELECT `cle` FROM modele WHERE `mod`='norestrict'";
                $resultmod = mysqli_query($GLOBALS["___mysqli_ston"], $querymod);
                if (!mysqli_num_rows($resultmod)) {
                    $query2 = "INSERT INTO modele( `etat`, `cle`, `mod` ) VALUES ('0','$row[0]','norestrict');";
                    $insert2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
                }

//   }
//insertion dans le modele  norestrict
                echo "<tr><td>" . gettext("Fait") . "</td>";
                for ($i = 0; $i < 9; $i++) {
                    $cleok[$i] = enlevedoublebarre($cleok[$i]);
                    $cleok[$i] = enleveantislash($cleok[$i]);
                    echo "<td>$cleok[$i]&nbsp;</td>";
                }
                echo "</tr>";
            } else {
                $query1 = "SELECT chemin,cleID FROM corresp WHERE chemin='$cletrim';";
                $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                if (mysqli_num_rows($resultat1)) {
                    $row = mysqli_fetch_array($resultat1);

                    $query = "UPDATE corresp SET intitule='$cleok[0]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    $query = "UPDATE corresp SET valeur='$cleok[1]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    $query1 = "UPDATE corresp SET antidote='$cleok[2]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET genre='$cleok[3]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET OS='$cleok[4]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET type='$cleok[5]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET comment='$cleok[7]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET categorie='$cleok[8]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    $query1 = "UPDATE corresp SET sscat='$cleok[9]' where cleID='$row[1]'";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    echo "<tr><td>" . gettext("Modifi&#233") . "</td>";

                    for ($i = 0; $i < 9; $i++) {
                        $cleok[$i] = enlevedoublebarre($cleok[$i]);
                        $cleok[$i] = enleveantislash($cleok[$i]);
                        echo "<td>$cleok[$i]&nbsp;</td>";
                    }
                    echo "</tr>";
                }
            }
            $testclecree++;
        } else {
            $testcleignoree++;
        }
    }
    echo "</table>";
    if ($testclecree) {
        echo "<br> $testclecree " . gettext("cl&#233;s ont &#233;t&#233; cr&#233;&#233;es ou modifi&#233;es") . " <br>";
    }

    retour();
}

include("pdp.inc.php");
?>
