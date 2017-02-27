<?php

/**

 * Test les restictions sur les clients windows en fonction du nom et de la machine
 * @Version $Id$ 

 * @Projet LCS / SambaEdu 

 * @auteurs Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note 
 */
/**
 * @Repertoire: registre
 * file: testreg.php
 */
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "include.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

foreach ($_GET as $key => $valeur)
    $$key = $valeur;


connexion();
if (test_bdd_registre() == false) {
    exit;
}
if (is_admin("computers_is_admin", $login) == "Y") {

    //aide
    $_SESSION["pageaide"] = "Gestion_des_clients_windowsNG#Test_des_restrictions";

    echo "<H1>" . gettext("Simulation de cl&#233s de registre appliqu&#233es") . "</H1>\n";

    if (!isset($tstlogin)) {
        echo "<FORM>\n";
        echo "<TABLE BORDER=0>\n";
        echo "<TR><TD>" . gettext("Nom d'utilisateur") . "</TD><TD><INPUT TYPE=text NAME=tstlogin></TD></TR>\n";
        echo "<TR><TD>" . gettext("Nom de l'ordinateur") . "</TD><TD><INPUT TYPE=text NAME=tstnetbios></TD></TR>\n";
        echo "</TABLE><INPUT TYPE=submit VALUE=\"" . gettext("Lancer le test") . "\"></FORM>\n";
    } else {
        // Affichage des groupes d'appartenance d'un utilisateur
        $templates = array();
        array_push($templates, trim($tstlogin));
        array_push($templates, trim($tstnetbios));
        list($user, $groups) = people_get_variables($tstlogin, true);
        echo "<H3>" . $user["fullname"] . "</H3>\n";
        if ($user["description"])
            echo "<p>" . $user["description"] . "</p>";
        if (count($groups)) {
            echo "<U>" . gettext("Membre des groupes") . "</U> :<BR><UL>\n";
            for ($loop = 0; $loop < count($groups); $loop++) {
                if ($groups[$loop]["cn"]) {
                    $test = $groups[$loop]["cn"];
                    $query = "select groupe from restrictions where groupe='$test' group by groupe ;";
                    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    if (mysqli_num_rows($resultat)) {
                        echo $groups[$loop]["cn"] . "<BR>\n";
                        array_push($templates, $test);
                    } else {
                        echo $groups[$loop]["cn"] . " ( " . gettext("pas de template d&#233fini pour ce groupe") . ") <BR>\n";
                    }
                }
            }
            echo "</UL>\n";
        }
        // Affichage des parcs d'appartenance de la machine
        $parcs = search_parcs($tstnetbios);
        if (isset($parcs)) {
            echo "<U>" . gettext("La machine est dans les Parcs") . "</U> :<BR><UL>\n";
            foreach ($parcs as $test) {
                    $query = "select groupe from restrictions where groupe=\"".$test['cn']."\" group by groupe ;";
                    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    if (mysqli_num_rows($resultat)) {
                        echo $test['cn'] . "<BR>\n";
                        array_push($templates, $test['cn']);
                    } else {
                        echo $test['cn'] . " (" . gettext("pas de template d&#233fini pour ce parc") . ") <BR>\n";
                    }
                
            }
            echo "</UL>\n";
        }
        array_push($templates, "base");
        array_push($templates, "imposees");
        $templ = gettemplates($templates);
        foreach ($templ[0] as $key => $value) {
            $templates2[] = $key;
        }
    }
}

if ($test) {
    echo"<h3>" . gettext("R&#233sultat du test") . "</h3>";
    affichelistecat("testreg.php?tstlogin=$tstlogin&tstnetbios=$tstnetbios", $testniveau, $cat);

    if (($cat) and !($cat == "tout")) {
        $ajout = " and corresp.categorie = '$cat'";
        if ($sscat) {
            $ajoutsscat = " AND corresp.sscat='$sscat'";
            echo "<h3>" . gettext("Sous-cat&#233gorie :") . " $sscat</h3>";
        } else {
            $ajoutsscat = "";
        }

        if (($testniveau == 2) and !($sscat)) {
            $ajoutpasaffiche = " and corresp.sscat= '' ";
        }
    } else {
        echo gettext("Choisissez une cat&#233gorie ci-dessus");
    }
    if ($cat == "tout") {
        $ajout = "";
        if ($sscat) {
            $ajoutsscat = "";
        }
        $ajoutpasaffiche = "";
    }
    $query = "Select Intitule,corresp.CleID,corresp.valeur,corresp.genre,corresp.OS,corresp.antidote,corresp.type,corresp.chemin,restrictions.valeur,restrictions.groupe,restrictions.priorite
            from corresp,restrictions 
            where corresp.CleID = restrictions.cleID " . $ajout . $ajoutsscatvide . $ajoutsscat . " and ( ";
    for ($i = 0; $i < count($templates2); $i++) {
        $query.="restrictions.groupe  = '" . $templates2[$i] . "' ";
        if ($i < (count($templates2) - 1)) {
            $query.="or ";
        }
    }
    $query.=") GROUP BY CleID,restrictions.valeur ORDER BY priorite,groupe,genre,restrictions.valeur DESC,Intitule ASC";
    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    if (mysqli_num_rows($resultat)) {
        echo "<table border=\"1\"><tr>
           	<td><img src=\"/elements/images/system-help.png\" alt=\"" . gettext("Aide") . "\" title=\"" . gettext("Aide") . "\" width=\"16\" height=\"18\" border=\"0\" /></td>
           	<td>" . gettext("Template") . "</td>
           	<td>" . gettext("Intitul&#233") . "</td>
           	<td>" . gettext("Valeur") . "</td>
           	<td>" . gettext("Etat") . "</td>
           	<td><img src=\"/elements/images/edittrash.png\" alt=\"Supprimer\" title=\"" . gettext("Supprimer") . "\" width=\"15\" height=\"15\" border=\"0\"></td>
           	</tr>";
        while ($row = mysqli_fetch_array($resultat)) {
            //bouton aide
            echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">\n";
            echo "<img src=\"/elements/images/system-help.png\" alt=\"aide\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></td>\n";
            echo "<td><DIV ALIGN=CENTER>" . htmlentities($row[9]) . "</DIV></td>\n";
            echo "<td><DIV ALIGN=CENTER>" . htmlentities($row[0]) . "</DIV></td>\n";
            echo "<td><DIV ALIGN=CENTER>" . htmlentities($row[2]) . "</DIV></td>\n";
            $act = False;
            if ($row['type'] == "config") {
                $state = 1;
                echo "<td BGCOLOR=#a5d6ff><DIV ALIGN=CENTER>
					<a href=\"#\" onClick=\"window.open('edit_cle.php?cle=$row[1]&template=$row[9]&state=$state','Editer','scrollbars=no,width=400,height=200')\">" . htmlentities($row[8]) . "</a></DIV></td>";
            } elseif ($row['type'] == "restrict") {
                if ($row[8] == $row[5]) {
                    echo "<td BGCOLOR=#e0dfde><DIV ALIGN=CENTER>
    				    	<a href=\"#\" onClick=\"window.open('edit_cle.php?cle=$row[1]&template=$row[9]&state=0&choix=Active&value=$row[2]','Editer','scrollbars=no,width=400,height=200')\">
    				    	Inactive</a></DIV></td>";
                } elseif ($row[8] == $row[2]) {
                    echo "<td BGCOLOR=#a5d6ff><DIV ALIGN=CENTER>
    					<a href=\"#\" onClick=\"window.open('edit_cle.php?cle=$row[1]&template=$row[9]&state=1&choix=Inactive&antidote=$row[5]','Editer','scrollbars=no,width=400,height=200')\">			
				        Active</a></DIV></td>";
                } else {
                    echo "<td><DIV ALIGN=CENTER>Non configur&eacute;e</DIV></td>";
                    $state = -1;
                }
            }
            echo "<td><DIV ALIGN=CENTER><a href=\"#\" onClick=\"window.open('edit_cle.php?cle=$row[1]&template=$row[9]&choix=del','Supprimer','scrollbars=yes,width=600,height=620')\">\n";
            echo "<img src=\"/elements/images/edittrash.png\" alt=\"Supprimer\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></DIV></td>\n";
        }
        echo "</table>";
    } else {
        echo gettext("Aucune entr&#233e trouv&#233e, pour utiliser cette fonctionnalit&#233 vous devez inscrire un template dans le menu 'Attribution des cl&#233s'");
    }
}

include ("pdp.inc.php");
?>
