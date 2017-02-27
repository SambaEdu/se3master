<?php

/**

 * Gestion des cles pour clients Windows (Fonctions pour registre)
 * @Version $Id$


 * @Projet LCS / SambaEdu

 * @auteurs  Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note

 */
/**

 * @Repertoire: registre
 * file: include.inc.php

 */
require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

/**

 * Fonctions Connexion a se3db par include de config.inc.php

 * @Parametres
 * @Return

 */
Function connexion() {   // connexion a une base de donnees #require("conf.php");
    require ("config.inc.php");
}

/**

 * Fonctions Affiche un lien Retour au menu

 * @Parametres
 * @Return

 */
Function retour() {
    echo"<a href=indexcle.php>" . gettext("Retour au menu") . "</a>";
}

/**

* Fonctions traitement des URL supprime les doubles barres
	
* @Parametres $string url a traiter
* @Return  $final l'url traite
   
*/

Function enlevedoublebarre($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5C%5C/","%5C",$temp);
        $final=rawurldecode($temp1);
	return $final;
}


/**

* Fonctions Supprime le retour chariot
	
* @Parametres $string ce qu'il faut traiter
* @Return  $final 
   
*/
Function enleveretourchariot($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/[^ \n\r\t]/","",$temp);
        $final=rawurldecode($temp1);
	return $final;
}	

/**

* Fonctions Ajout une double barre
	
* @Parametres $string ce qu'il faut traiter
* @Return  $final 
   
*/
Function ajoutedoublebarre($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5C/","%5C%5C",$temp);
        $final=rawurldecode($temp1);
	return $final;
}

/**

* Fonctions supprime les antislash
	
* @Parametres $string
* @Return 
   
*/
Function enleveantislash($string)
{
	$temp=rawurlencode($string);
        $temp1=preg_replace("/%5C%27/","%27",$temp);
        $temp2=preg_replace("/%5C%22/","%22",$temp1);
        $final=rawurldecode($temp2);
	return $final;
}

/**

* Fonctions supprime les doubles slash
	
* @Parametres $string
* @Return 
   
*/
Function enlevedoubleslash($string)
{
	//$temp=rawurlencode($string);
        $temp1=preg_replace("////","/",$temp);
        //$temp2=preg_replace("/%5C%22/","%22",$temp1);
        //$final=rawurldecode($temp1);
        $final = $temp1;
        return $final;
}

/**

* Fonctions supprime les crochets
	
* @Parametres $string
* @Return 
   
*/

Function enlevecrochets($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5B/","",$temp);
        $temp2=preg_replace("/%5D/","",$temp1);
        $final=rawurldecode($temp2);
        return $final;
}

/**

* Fonctions supprime les quotes
	
* @Parametres $string
* @Return 
   
*/
Function enlevequotes($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%22/","",$temp);
        $final=rawurldecode($temp1);
	return $final;
}


/**

* Fonctions supprime les #
	
* @Parametres $string
* @Return 
   
*/
Function enlevediese($string)
{
	$temp=rawurlencode($string);
        $temp1 = preg_replace("/%23/","",$temp);
        $final=rawurldecode($temp1);
        return $final;
}

/**

 * Fonctions fonction permettant d'appliquer un modele a un template ( pas de refreshzrn dans cette fonction (gere a part)

 * @Parametres
 * @Return

 */
function applique_modele($mod, $salle, $affiche) {
    connexion();
    $query = "SELECT `cle`,`etat` FROM `modele` WHERE `mod`= '$mod' ;";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    while ($row = mysqli_fetch_row($resultat)) {
        $cle = $row[0];
        $query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle';";
        $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $row1 = mysqli_fetch_row($insert);
        $query = "SELECT cleID,valeur FROM restrictions WHERE cleID='$cle' AND groupe='$salle';";
        $verif = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $row2 = mysqli_fetch_row($verif);

        if ($row[1] == "1") {
            $row1[2] = ajoutedoublebarre($row1[2]);
            if ($row2[0]) {
                $query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            } else {
                $query = "INSERT INTO restrictions (resID,valeur,cleID,groupe,priorite) VALUES ('','$row1[2]','$row[0]','$salle','priorite($salle)');";
                $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            }
        } else {
            if ($row1[4] == "config") {
                $query = "DELETE FROM restrictions where cleID='$cle' and `groupe` = '$salle';";
                $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            } else {
                if ($row2[0]) {
                    $query = "UPDATE `restrictions` SET `valeur` = '$row1[3]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                } else {
                    $query = "INSERT INTO restrictions (resID,valeur,cleID,groupe,priorite) VALUES ('','$row1[3]','$row[0]','$salle','priorite($salle)');";
                    $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                }
            }
        }
    }
}

/**

 * Fonctions permettant d'afficher le choix du niveau (obsolete)

 * @Parametres
 * @Return

 */
function choixniveau($but, $testniveau, $affiche) {
    if ($testniveau) {
        if ($affiche == "oui") {
            echo "<br>" . gettext("Actuellement , le niveau choisi est ");
            if ($testniveau == 1) {
                echo gettext("D&#233butant");
            }
            if ($testniveau == 2) {
                echo gettext("Interm&#233diaire");
            }
            if ($testniveau == 3) {
                echo gettext("Confirm&#233");
            }
        }
    } else {
        echo gettext("Choisissez un niveau :");
    }

    echo "<br /><div align=\"center\">&nbsp;";
    echo "<form action=\"$but\" name=\"niv\" method=\"post\">" . gettext("Choix du niveau :") . "  &nbsp;";
    echo "<select name=\"niveau\" size=\"1\">";
    echo "<option value=\"1\" ";
    if ($testniveau == 1) {
        echo "selected";
    }
    echo">" . gettext("D&eacute;butant") . "</option><option value=\"2\" ";
    if ($testniveau == 2) {
        echo "selected";
    }
    echo ">" . gettext("Interm&eacute;diaire") . "</option><option value=\"3\" ";
    if ($testniveau == 3) {
        echo "selected";
    }
    echo ">" . gettext("Confirm&eacute;") . "</option>";
    echo "</select><input type=\"submit\" name=\"submit\" value=\"OK\" /></form></div><br />";
}

/**

 * Fonctions permettant de recuperer le niveau (obsolete)

 * @Parametres
 * @Return

 */
function niveau() {
    $niveau = $_POST['niveau'];
    $testniveau = $HTTP_COOKIE_VARS["NiveauChoisiSE3"];
    if ($niveau) {
        $testniveau = $niveau;
        setcookie("NiveauChoisiSE3", "", time() - 36000);
        setcookie("NiveauChoisiSE3", $niveau, time() + 36000);
    }
}

/**

 * Fonctions retourne le niveau de l'interface

 * @Parametres $testniveau permettait de modifier le comportement (en particulier, l'affichage de la categorie tout)
 * @Return

 */
function afficheniveau($testniveau) {
    if ($testniveau == 1) {
        $afficheniveau = "D&#233;butant";
    }
    if ($testniveau == 2) {
        $afficheniveau = "Interm&#233;diaire";
    }
    if ($testniveau == 3) {
        $afficheniveau = "Confirm&#233;";
    }
    return $afficheniveau;
}

/**

 * Fonctions fonction permettant d'afficher les categories et sous-categories

 * @Parametres $cible permet de renvoyer vers la bonne page (affiche_cle, affiche_restrictions,....) -  $testniveau permettait de modifier le comportement (en particulier, l'affichage de la categorie tout) (obsolete)
 * @Return  HTML

 */
function affichelistecat($cible, $testniveau, $cat) {
    //affichage des cles attribuees au groupe
    connexion();
    echo "<table><tr>";
    $query = "Select DISTINCT categorie from corresp group by categorie;";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    $i = 1;

    while ($row = mysqli_fetch_row($resultat)) {
        if ($row[0]) {
            if ($row[0] == $cat)
                echo "<td class=\"enabledheader\" width=\"130\" height=\"30\" align=\"center\">";
            else
                echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
            echo "<a href=\"$cible&cat=$row[0]\" >" . htmlentities($row[0]) . "</a></td>";
            if (($i % 7) == 0) {
                echo "</tr><tr>";
            }
        }
        $i++;
    }
    if ($cat == "tout")
        echo "<td class=\"enabledheader\" width=\"130\" height=\"30\" align=\"center\">";
    else
        echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
    echo "<a href=\"$cible&cat=tout\">" . gettext("Tout") . "</a></td>";
    echo "</tr></table><br>";

    //affichage des sous-categories (si la categorie est choisie)
    if ($cat) {
        $query = "Select distinct sscat from corresp where '$cat'=categorie group by sscat;";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $i = 1;
        echo "<table><tr>";
        while ($row = mysqli_fetch_row($resultat)) {
            if ($row[0]) {
                echo "<td class=\"menucell\" width=\"130\" height=\"30\" align=\"center\">";
                echo "<a href=\"$cible&cat=$cat&sscat=$row[0]\" >" . htmlentities($row[0]) . "</a></td>";
                if (($i % 7) == 0) {
                    echo "</tr><tr>";
                }
            }
            $i++;
        }
        echo "</tr></table>";
    }
}

/**

 * Fonctions fonction permettant d'afficher les cles dans affiche_cle.php

 * @Parametres $cible,$getcible1,$getcible2,$query,$affichetitle,$testniveau
 * @Return  HTML

 */
function affichelisteget($cible, $getcible1, $getcible2, $query, $affichetitle, $testniveau) {
    connexion();
    //$query="Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin,categorie,sscat from corresp ".$ajout.$ajoutsscat;
    //echo "$query";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    if (mysqli_num_rows($resultat)) {
        echo $affichetitle;
        while ($row = mysqli_fetch_array($resultat)) {
            echo "<tr><td><DIV ALIGN=CENTER>";
            echo "<a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">";
            echo "<img src=\"/elements/images/system-help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";
            echo "<td>" . htmlentities($row[0]) . "&nbsp;</DIV></td><td><DIV ALIGN=CENTER>&nbsp;" . htmlentities($row[4]) . "</DIV></td>";
            if ($row[6] == "config") {
                if ($testniveau > 2) {
                    echo "<a href=\"$cible&$getcible1=$row[1]\"><td>";
                    echo "<DIV ALIGN=CENTER>&nbsp;" . htmlentities($row[2]) . "</DIV> </td></a><td>&nbsp;</td>";
                } else {
                    echo"<td><DIV ALIGN=CENTER>&nbsp;" . htmlentities($row[2]) . "</DIV><td>&nbsp;</td>";
                }
            }

            if ($row[6] == "restrict") {
                if ($testniveau > 2) {
                    echo "<a href=\"$cible&modifkey=$row[1] \"><td BGCOLOR=\"#a5d6ff\"><DIV ALIGN=CENTER>";
                    echo "&nbsp;" . htmlentities($row[2]) . "</DIV> </td></a>";
                    echo "<a href=\"$cible&$getcible1=$row[1]\"><td BGCOLOR=\"#e0dfde\">";
                    echo "<DIV ALIGN=CENTER>" . htmlentities($row[5]) . "</DIV></td></a>";
                } else {
                    echo "<td BGCOLOR=\"#FF0000\"><DIV ALIGN=CENTER>";
                    echo "&nbsp;" . htmlentities($row[2]) . "</DIV> </td><td BGCOLOR=\"#e0dfde\"><DIV ALIGN=CENTER>" . htmlentities($row[5]) . "</DIV></td>";
                }
            }

            if ($testniveau > 2) {
                echo "<td><DIV ALIGN=CENTER>";
                echo "<a href=\"$cible&$getcible1=$row[1]\">";
                echo "<img src=\"/elements/images/edit.png \" alt=\"" . gettext("Modifier la valeur") . "\" title=\"" . gettext("Modifier la valeur") . "\" width=\"16\" height=\"16\" border=\"0\" /></a></DIV></td><td >";
                echo "<a href=\"$cible&$getcible2=$row[1]&$getcible1=$row[1]\">";
                echo "<img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer la cl&eacute;") . "\" title=\"" . gettext("Supprimer la cl&eacute;") . "\" width=\"16\" height=\"16\" border=\"0\" /></a></td>";
            } else {
                echo" <td><DIV ALIGN=CENTER><img src=\"/elements/images/editpale.png\" alt=\"" . gettext("Valeur non modifiable") . "\" title=\"" . gettext("Valeur non modifiable") . "\" width=\"16\" height=\"16\" border=\"0\" /></DIV></td><td >";
                echo "<img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Valeur non modifiable") . "\" title=\"" . gettext("Valeur non modifiable") . "\" width=\"16\" height=\"16\" border=\"0\" /></td>";
            }
        }
        echo"</tr></table>    ";
    }
}

function test_bdd_registre() {
// Controle l'installation des cles
    connexion();
    $query = "select * from corresp";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    $ligne = mysqli_num_rows($resultat);
    if ($ligne == "0") {
        echo "<a href=\"cle-maj.php?action=maj\">" . gettext("Effectuer la mise a jour de la base de cl&#233s ?") . "</a><br>";
        include ("pdp.inc.php");
        return false;
    } else {
        return true;
    }
}

/**

 * Fonction permettant de rafraichir les templates

 * @Parametres $string
 * @Return

 */
Function refreshtemplates($string) {
    connexion();
    $query = "Select corresp.CleID,restrictions.valeur,corresp.chemin,corresp.OS,corresp.genre,corresp.type,corresp.valeur,corresp.antidote
        from restrictions,corresp where restrictions.groupe='$string' and restrictions.cleID=corresp.cleID order by corresp.OS;";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    $prio = priorite($string);
    //au moins un template associe on continue
    if (mysqli_num_rows($resultat)) {
        // template vide
        if (!isset($string)) {
            echo gettext("groupe non inscrit");
        } else {
            while ($row = mysqli_fetch_array($resultat)) {
                $deleteSQL = "delete from restrictions where cleID='row[0]' and groupe='$string'";
                $addSQL = "Insert into restrictions values ('', '$row[0]', '$row[1]', '$value', '$prio')";
                mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
                mysqli_query($GLOBALS["___mysqli_ston"], $addSQL);
            }
        }
    }
    echo "<h3>" . gettext("Restrictions mises a jour pour ") . " $string</h3><br>";
}

/**

 * Fonctionrenvoyant la liste triee des templates

 * @Parametres [ array $groups ]
 * @Return $array template=>priorite

 */
Function gettemplates() {
    if (func_num_args() == 1) {
        foreach (func_get_arg(0) as $value) {
            $groupes[] = strtolower($value);
        }
    } else {
        unset($groupes);
    }
    $handle = opendir('/home/templates');
    $filesArray = array();
    $filesArray2 = array();
    while ($file = readdir($handle)) {
        $file = strtolower($file);
        if ($file <> '.' and $file <> '..' and $file <> 'registre.vbs' and $file <> 'skeluser') {
            $template = explode("@@", $file);
            $p = 0;
            if (isset($groupes)) {
                // construction de l'intersection templates et groupes
                $test = 0;
                foreach ($template as $value) {
                    if (in_array($value, $groupes)) {
                        $test++;
                    } else {
                        $test = 0;
                        continue;
                    }
                }
                if ($test) {
//                    echo "ajout de ".$file."<br>";
                    $filesArray[$file] = priorite($file);
                }
            } else {
                // construction de la liste des templates avec et sans cles
                $pr = is_template($file);
                if (isset($pr)) {
                    //	template contenant des cles
                    $filesArray[$file] = $pr;
                } else {
                    // template sans cles
                    $filesArray2[] = $file;
                }
            }
        }
    }
    closedir($handle);
    asort($filesArray);
    asort($filesArray2);
    return (array($filesArray, $filesArray2));
}

Function is_template($template) {
    // test presence de cles dans la table restrictions
    connexion();
    // initialisation de la table si besoin
    $query = "select priorite from restrictions limit 1";
    if (!mysqli_query($GLOBALS["___mysqli_ston"], $query)) {
        $query = "alter table restrictions add priorite INTEGER";
        mysqli_query($GLOBALS["___mysqli_ston"], $query);
    }
    $query = "select groupe from restrictions where groupe = '" . $template . "' group by groupe";
    if (!mysqli_num_rows(mysqli_query($GLOBALS["___mysqli_ston"], $query))) {
        return;
    } else {
        return(priorite($template));
    }
}

Function update_priorite($template, $priorite) {
            connexion();
            $query = "select groupe from restrictions where groupe = '" . $template . "' group by groupe";
            if (mysqli_num_rows(mysqli_query($GLOBALS["___mysqli_ston"], $query))) {
                $query = "update restrictions set priorite='" . $priorite . "' where groupe = '" . $template . "'";
                mysqli_query($GLOBALS["___mysqli_ston"], $query);
            }
            echo "mise a jour de la priorite pour le template : ".$template." priorite : ".$priorite."<br>";
            return $priorite;
}


Function priorite() {
    // retourne la priorite du groupe, et l'enregistre dans la table restriction si besoin.
    // argument 1 : le template
    // argument 2 : le decalage (0 = forcer la valeur )
    // argument 3 : la valeur
    // groupes : p=n*8
    // parcs   : p=n*8092
    $template = func_get_arg(0);
    if (func_num_args() > 1) {
        $dec = func_get_arg(1);
        if (func_num_args() > 2) {
            $prio = func_get_arg(2);
        }
    } else {
        $dec = 0;
    }
    // valeurs predefinies des priorites
    $priorite = array("base" => 0,
        "eleves" => 1, "profs" => 2,
        "administratifs" => 4,
        "groupes" => 8,
        "parcs" => 8092,
        "machine" => 16777216,
        "utilisateur" => 33554432,
        "overfill" => 67108864,
        "admin" => 134217727,
        "imposee" => 134217728
    );
    // valeurs de base des groupes
    $grppriorite = array("groupes" => 8,
        "parcs" => 8092,
    );
    // priorite d'un template predefini
    if (isset($priorite[$template])) {
        return $priorite[$template];
    }
    $type = group_type($template);
    if (!isset($type)) {
        // type de groupe inconnu : groupe compose @@ ou n'importe quoi
        $sousgroupes = explode("@@", $template);
        if (count($sousgroupes) > 1) {
            // groupe avec des @@
            $prio = 0;
            foreach ($sousgroupes as $sousgroupe) {
                $prio = $prio + priorite($sousgroupe);
            }
        }
    }
    if ($dec == 0) {
        connexion();
        if (isset($prio)) {
            // on met a jour et retourne la priorite du template
            return update_priorite($template, $prio);
        } elseif (isset($type)) {
            // type de groupe connu : on verifie que la priorite est dans la bonne plage, et on la retourne si oui
            $query = "SELECT priorite from restrictions where groupe = '" . $template . "' GROUP BY priorite,groupe";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            if (mysqli_num_rows($resultat)) {
                $row = mysqli_fetch_array($resultat);
                if ($row[0] && (($row[0] % $priorite[$type]) == 0)) {
                    return $row[0];
                }
            }
            if (isset($grppriorite[$type])) {
                // si groupes ou parcs on calcule la premiere valeur libre
                $query = "SELECT priorite from restrictions  GROUP BY priorite,groupe ORDER BY priorite ASC ";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                if (mysqli_num_rows($resultat)) {
                    $p = 20;
                    $test = 0;
                    $newprio = $p * $grppriorite[$type];
                    foreach(mysqli_fetch_array($resultat) as $row) {
                        if ($row[0] == $newprio) {
                            $newprio = $p++ * $grppriorite[$type];
                            $test = 1;
                        } elseif ($test) {
                            break;
                        }
                    }
                    return update_priorite($template, $newprio);
                }
            } else {
                // autre template : on retourne la valeur predefinie
                return update_priorite($template, $priorite[$type]);
            }
        }
    } else {
        $oldprio = priorite($template);
        $newprio = $oldprio + $dec * $priorite[$type];
        connexion();
        $query = "update restrictions set priorite='" . $newprio . "' where groupe = '" . $template . "'";
        mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $query = "update restrictions set priorite='" . $oldprio . "' where groupe <> '" . $template . "' and priorite = '" . $newprio . "'";
        mysqli_query($GLOBALS["___mysqli_ston"], $query);
        return $newprio;
    }
}
?>
