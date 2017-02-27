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
 * file: affiche_modele.php

 */
$modact = $_GET['modact'];
$mod = $_GET['mod'];
$liste = $_GET['liste'];
$suppr = $_GET['suppr'];

$cat = $_GET['cat'];
if (!$cat) {
    $cat = $_POST['cat'];
}
$sscat = $_GET['sscat'];
if (!$cat) {
    $cat = $HTTP_COOKIE_VARS["Categorie"];
}
if ($cat) {
    setcookie("Categorie", "", time() - 3600);
    setcookie("Categorie", $cat, time() + 3600);
}

if (!$sscat) {
    $sscat = $HTTP_COOKIE_VARS["Sous-Categorie"];
}
if ($sscat) {
    setcookie("Sous-Categorie", "", time() - 3600);
    setcookie("Sous-Categorie", $sscat, time() + 3600);
}

require ("config.inc.php");
require ("functions.inc.php");

$login = isauth();
if ($login == "")
    header("Location:$urlauth");

include "ldap.inc.php";
include "ihm.inc.php";
include "entete.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

if (ldap_get_right("computers_is_admin", $login) != "Y")
    die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");

$_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";



$testniveau = getintlevel();
?>

<SCRIPT LANGUAGE="JavaScript">

    /**
    * Fonctions passe a checked tous les champs de type box
    * @language Javascript
    * @Parametres
    * @Return
    */

    function checkAll(nombre)
    {
    for (var j = 1; j < nombre+1; j++)
    {
    box = eval("document.mod.change" + j);
    if (box.checked == false) box.checked = true;
    }
    }


    /**
    * Fonctions passe a non checked tous les champs de type box
    * @language Javascript
    * @Parametres
    * @Return
    */

    function uncheckAll(nombre)
    {
    for (var j = 1; j < nombre+1; j++)
    {
    box = eval("document.mod.change" + j);
    if (box.checked == true) box.checked = false;
    }
    }

    /**
    * Fonctions passe a  checked tous les champs de type box pour les cles
    * @language Javascript
    * @Parametres
    * @Return
    */

    function checkAllcle(nombre)
    {
    for (var j = 1; j < nombre+1; j++)
    {
    box = eval("document.mod.cle" + j);
    if (box.checked == false) box.checked = true;
    }
    }


    /**
    * Fonctions passe a  unchecked tous les champs de type box pour les cles
    * @language Javascript
    * @Parametres
    * @Return
    */

    function uncheckAllcle(nombre)
    {
    for (var j = 1; j < nombre+1; j++)
    {
    box = eval("document.mod.cle" + j);
    if (box.checked == true) box.checked = false;
    }
    }

</SCRIPT>

<?php
//Gestion des modeles de restriction
$action1 = $_GET['modact'];
$action = $_POST['modact'];
$mod1 = $_GET['mod'];
$mod = $_POST['modele'];
//le passage des form n'est pas toujours par un post
if (!$action) {
    $action = $action1;
};
if (!$mod) {
    $mod = $mod1;
}
require "include.inc.php";
connexion();
if (test_bdd_registre() == false) {
    exit;
}


echo "<h1>" . gettext("Gestion des groupes de cl&#233;s") . "</h1>";
if ($testniveau == 1) {
    echo "<br><br>" . gettext("Les fonctionnalit&#233;s de ce menu ne sont pas disponibles au niveau d&#233;butant");
    exit;
}

switch ($action) {
    //default : affichage des noms de modeles
    // cas new : ajout d'un modele
    //cas choinew :ajout d'un nouveau modele copie d'un autre modele eventuel
    //cas yes : affichage d'un modele
    //cas ajoutcle : ajout d'une cle a un modele
    //cas choixajoutcle : cle ajoutee choisie et insertion base
    //cas modifclelien : pour avoir les liens directs en modification et suppression
    //cas modifcle : modification des cles du modele : passage de la valeur par defaut ou de l'antidote de $mod
    //cas template : application d'un modele a un template

    default:
        echo "<h2>" . gettext("Choisir un groupe de cl&#233;s") . "</h2>";
        $query = "SELECT `mod` FROM modele GROUP BY `mod`;";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        echo"<FORM METHOD=POST ACTION=\"affiche_modele.php\" >";

        while ($row = mysqli_fetch_array($resultat)) {
            echo"<a href=\"affiche_modele.php?modact=yes&mod=$row[0] \">$row[0]</a><br>";
        }

        echo"<br><br>";
        if ($testniveau > 2) {
            echo "<form action=\"affiche_modele.php\" name=\"nouveau\" method=\"post\">";
            echo "<input type=\"hidden\" name=\"modact\" value=\"new\" />";
            echo "<input type=\"submit\" name=\"New\" value=\"" . gettext("Cr&#233;er un groupe de cl&#233;s") . "\" /></form>";
        }
        break;


    //ajout d'un modele
    case "new":
        $query = "SELECT `mod` FROM modele GROUP BY `mod`;";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        echo gettext("Choisir le groupe de cl&#233; support de votre nouveau groupe ");

        echo "<br><br>";
        echo " <FORM METHOD=POST ACTION=\"affiche_modele.php\" >";
        echo "<select name=\"modele\" size=\"1\"><option></option> ";
        while ($row = mysqli_fetch_array($resultat)) {
            echo"<option>$row[0]</option>";
            $choix[$i] = $row[0];
            $i++;
        }
        echo "</select> ";
        echo "<u onmouseover=\"return escape" . gettext("('Choisir un groupe de cl&#233; qui va servir de mod&#233;le pour cr&#233;er ce nouveau groupe.<br>Vous pouvez ne pas s&#233;lectionner de mod&#233;le, pour partir d\'un mod&#233;le vide.')") . "\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>";
        echo "<br><br><input type=\"text\" name=\"nom\" value=\"\" size=\"20\" />";
        echo "<INPUT TYPE=\"hidden\" name=\"modact\" value=\"choixnew\">";
        echo "<INPUT TYPE=\"submit\" name=\"inscrire\" value=\"" . gettext("Ajouter") . "\"></FORM>";

        break;

    //ajout d'un nouveau modele copie d'un autre modele eventuel
    case "choixnew":
        $choix = $_POST['modele'];
        $nommod = $_POST['nom'];

        if ($nommod == "") {
            echo "Vous devez donner un nom correct";
            echo "<br><br>";
            echo "<a href=\"affiche_modele.php?modact=new\">Retour</a>";
            exit;
        }
        //un modele support est defini
        if ($choix) {
            $query = "SELECT `cle`,`etat` FROM `modele` WHERE `mod` = '$mod' ";
            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            echo "<br>" . gettext("Inscription de") . " $nommod<br>";
            while ($row = mysqli_fetch_array($resultat)) {
                $query4 = "INSERT  INTO modele( `modID`, `cle`, `mod`, `etat` )  VALUES ('','$row[0]','$nommod','$row[1]');";
                $resultat4 = mysqli_query($GLOBALS["___mysqli_ston"], $query4);
            }
            echo "<br>" . gettext("Le nouveau groupe de cl&#233; s'appelle") . " $nommod. " . gettext("Il est bas&#233; sur le groupe de cl&#233;") . " $choix<br>";
        } else {  //aucun modele support de defini
            echo gettext("Vous devez d&#233finir au moins une cl&#233; tout de suite") . "<br>";
        }
        echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=affiche_modele.php?mod=$nommod&modact=yes\">";
        echo "</HEAD>" . gettext("Commandes prises en compte !") . "<br>";


        break;

    //affichage d'un modele
    case "yes":
        echo "<h2>" . gettext("Groupe de cl&#233; :") . " $mod </h2>";
        connexion();
        affichelistecat("affiche_modele.php?modact=yes&mod=$mod", $testniveau, $cat);
        if (($cat) and !($cat == "tout")) {
            $ajout = " and corresp.categorie = '$cat'";
            if ($_GET['sscat']) {
                $ajoutsscat = " AND corresp.sscat='$sscat' ";
            } else {
                $ajoutsscat = "";
            }
        } else {
            echo "<h3>" . gettext("Choisissez une cat&#233;gorie ci-dessus") . "</h3><br><br>";
            $ajout = " and corresp.categorie = ''";
        }
        if ($cat == "tout") {
            $ajout = "";
            if ($_GET['sscat']) {
                $ajoutsscat = "";
            }
        }
        if ($_GET['sscat']) {
            echo "<h3>" . gettext("Sous-cat&#233;gorie") . " $sscat</h3>";
        }
        connexion();
        $query = "SELECT `cle`,`etat` FROM `modele` WHERE `mod` = '$mod' ";
        $resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        if (!mysqli_num_rows($resultat2)) {
            $row4 = mysqli_fetch_array($resultat2);
            if (!$row4[0]) {
                echo gettext("Ce groupe n'a pas encore de cl&#233; <br><br> Si vous n'en ajouter pas tout de suite, le groupe de cl&#233; sera supprim&#233;");
                echo "<br><FORM METHOD=POST ACTION=\"affiche_modele.php\" name=\"mod\">";
                echo "<INPUT TYPE=\"hidden\" name=\"modact\" value=\"ajoutcle\">";
                echo "<input type=\"hidden\" name=\"modele\" value=\"$mod\" />";
                echo "<INPUT TYPE=\"submit\" name=\"inscrire\" value=\"" . gettext("Ajouter une cl&#233 &#224") . " $mod\"></FORM>";
                break;
            }
        } else {
            $query = "Select Intitule,CleID,valeur,genre,OS,antidote,type,chemin,modele.etat,modele.mod
        from corresp
        left outer join modele
        on corresp.CleID = modele.cle
        where modele.mod  = '" . $mod . "' " . $ajout . $ajoutpasaffiche . $ajoutsscat . "
        order by type,modele.etat desc,OS,genre,valeur desc";

            $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            if (mysqli_num_rows($resultat)) {
                //affichage de l'en-tete du tableau en fonction des cas
                echo "<table border=\"1\" ><tr BGCOLOR=#fff9d3><td><img src=\"/elements/images/system-help.png\" alt=\"" . gettext("Aide") . "\" title=\"Aide\" width=\"16\" height=\"18\" border=\"0\" />\n";
                echo "</td>$affichetout <td><DIV ALIGN=CENTER>" . gettext("Intitul&#233") . "</DIV></td>\n";
                echo "<td><DIV ALIGN=CENTER>" . gettext("OS") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Etat") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Editer") . "</DIV></td>\n";
            }
            unset($liste);
            while ($row = mysqli_fetch_array($resultat)) {
                //bouton aide
                $liste.= "-" . $row[0];

                echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[CleID]','aide','scrollbars=yes,width=800,height=400')\">\n";
                echo "<img src=\"/elements/images/system-help.png\" alt=\"aide\" title=\"" . $row['chemin'] . "\" width=\"15\" height=\"15\" border=\"0\"></a></td>\n";
                echo "<td><DIV ALIGN=CENTER>" . $row['Intitule'] . "</DIV></td>\n";
                echo "<td><DIV ALIGN=CENTER>" . $row['OS'] . "</DIV></td>\n";
                if ($row['etat'] == '1') {
                    echo "<td BGCOLOR=#a5d6ff><DIV ALIGN=CENTER>Active</DIV></td>";
                    $state = 1;
                } elseif ($row['etat'] == '0') {
                    echo "<td BGCOLOR=#e0dfde><DIV ALIGN=CENTER>Inactive</DIV></td>";
                    $state = 0;
                } else {
                    echo "<td><DIV ALIGN=CENTER>Non configur&eacute;e</DIV></td>";
                    $state = -1;
                }

                echo "<td><DIV ALIGN=CENTER><a href=\"#\" onClick=\"window.open('edit_cle_grp.php?cle=$row[CleID]&modele=$mod&state=$state&etat=$row[etat]','Editer','scrollbars=yes,width=800,height=400')\">\n";
                echo "<img src=\"/elements/images/edit.png\" alt=\"Editer\" title=\"" . $row['type'] . "\" width=\"15\" height=\"15\" border=\"0\"></a></DIV></td>\n";
            }
            echo "</table>";

            if ($testniveau > 2) {
                echo "<a href=\" affiche_modele.php?modact=modifcle&mod=$mod&liste=$liste \" >" . gettext("Modifier toutes les valeurs affich&#233es") . "</a><br>";
                echo "<a href=\" affiche_modele.php?modact=modifcle&mod=$mod&suppr=1&liste=$liste \" >" . gettext("Supprimer toutes les valeurs affich&#233es") . "</a><br><br>";
            }
        }
        /*            $query = "SELECT `cle`,`etat` FROM `modele`  WHERE `mod` = '$mod' ";
          $resultat3 = mysql_query($query);
          $query6 = "SELECT modele.cle,modele.etat FROM modele,corresp  WHERE modele.mod = '$mod' and corresp.CleID=modele.cle " . $ajout . $ajoutpasaffiche . $ajoutsscat;
          $resultat6 = mysql_query($query6);
          if (mysql_num_rows($resultat6)) {
          echo "<br>" . gettext("Vert indique que la restriction est inactive <br>Rouge indique que la restriction est active");
          echo "<br><FORM METHOD=POST ACTION=\"affiche_modele.php\" name=\"mod\">";
          echo "<table border=1>";
          echo "<tr><td><img src=\"/elements/images/help.png\" alt=\"" . gettext("Aide") . "\" title=\"Aide\" width=\"16\" height=\"18\" border=\"0\" /></td>";
          if ($cat == "tout") {
          echo"<td><DIV ALIGN=CENTER>" . gettext("Cat&#233;gorie") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Sous-Cat&#233;gorie") . "</DIV></td>";
          }
          echo"<td>" . gettext("Intitule") . "</td><td>" . gettext("OS") . "</td><td>" . gettext("Etat") . "</td><td>" . gettext("Valeur") . "</td><td><img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier") . "\" title=\"Modifier\" width=\"16\" height=\"16\" border=\"0\" /></td>";
          echo "<td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"Supprimer\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
          while ($row = mysql_fetch_array($resultat3)) {
          $n++;
          $query1 = "SELECT `valeur`,`antidote`,`Intitule`,`type`,`OS`,`chemin`,`categorie`,`sscat` FROM `corresp` WHERE `CleID` = '$row[0]' " . $ajout . $ajoutpasaffiche . $ajoutsscat;
          $resultat1 = mysql_query($query1);
          $num = mysql_num_rows($resultat1);
          if ($num) {
          $row1 = mysql_fetch_array($resultat1);
          $liste = $liste . "-" . $row[0];
          $valeur = $row1[0];
          $couleur = "";
          $etat = "&nbsp;";
          if ($row1[3] == "restrict") {
          if ($row[1] == 1) {
          $valeur = $row1[0];
          $etat = gettext("Cl&#233; activ&#233;e");
          $couleur = "#FF0000";
          }
          if ($row[1] == 0) {
          $valeur = $row1[1];
          $etat = gettext("Cl&#233; d&#233;sactiv&#233;e");
          $couleur = "#00FF00";
          }
          }
          echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[0]','aide','scrollbars=yes,width=600,height=620')\">";
          echo "<img src=\"/elements/images/help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row1[5]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";

          if ($cat == "tout") {
          echo"<td><DIV ALIGN=CENTER>$row1[6]</DIV></td><td><DIV ALIGN=CENTER>$row1[7]</DIV></td>";
          }
          echo"<td>$row1[2]</td><td>$row1[4]</td>";
          if ($row1[3] == "restrict") {
          if ($testniveau > 2) {
          echo "<a href=\" affiche_modele.php?change=$row[0]&modact=modifclelien&mod=$mod \">";
          echo "<td BGCOLOR=$couleur>$etat</td></a><td>$valeur</td>";
          echo "<td><a href=\" affiche_modele.php?change=$row[0]&modact=modifclelien&mod=$mod \">";
          echo "<img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier") . "\" title=\"Modifier\" width=\"16\" height=\"16\" border=\"0\" /></a></td>";
          } else {
          echo "<td BGCOLOR=$couleur>$etat</td><td>$valeur</td>";
          echo "<td><img src=\"/elements/images/editpale.png\" alt=\"" . gettext("Valeur non modifiable") . "\" title=\"Valeur non modifiable\" width=\"16\" height=\"16\" border=\"0\" /></td>";
          }
          } else {
          if ($testniveau > 2) {
          echo "<a href=\"affiche_modele.php?change=$row[0]&modact=modifclelien&mod=$mod \">";
          echo "<td BGCOLOR=$couleur>$etat</td></a><td>$valeur</td>";
          echo "<td><a href=\" affiche_cle.php?modifkey=$row[0]&modif=3&lien_retour=affiche_modele.php&mod=$mod\">";
          echo "<img src=\"/elements/images/edit.png\" alt=\"" . gettext("Modifier") . "\" title=\"Modifier\" width=\"16\" height=\"16\" border=\"0\" /></a>&nbsp;</td>";
          } else {
          echo"<td BGCOLOR=$couleur>$etat</td><td>$valeur</td>";
          echo "<td>&nbsp;</td>";
          }
          }

          if ($testniveau > 2) {
          echo "<td><a href=\" affiche_modele.php?change=$row[0]&modact=modifclelien&mod=$mod&suppr=$row[0] \">";
          echo "<img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Supprimer") . "\" title=\"" . gettext("Supprimer") . "\" width=\"16\" height=\"16\" border=\"0\" /></a></td></tr>";
          } else {
          echo "<td><img src=\"/elements/images/edittrash.png\" alt=\"" . gettext("Valeur non modifiable") . "\" title=\"" . gettext("Valeur non modifiable") . "\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>";
          }
          }
          }

          echo "</table>";

          if ($testniveau > 2) {
          echo "<a href=\" affiche_modele.php?modact=modifcle&mod=$mod&liste=$liste \" >" . gettext("Modifier toutes les valeurs affich&#233es") . "</a><br>";
          echo "<a href=\" affiche_modele.php?modact=modifcle&mod=$mod&suppr=1&liste=$liste \" >" . gettext("Supprimer toutes les valeurs affich&#233es") . "</a><br><br>";
          }
          } else {
          echo gettext("Pas de cl&#233;s pour votre s&#233;lection");
          }
         */
        if ($testniveau > 2) {
            echo "<FORM METHOD=POST ACTION=\"affiche_modele.php\" >";
            echo "<INPUT TYPE=\"hidden\" name=\"modact\" value=\"ajoutcle\">";
            echo "<input type=\"hidden\" name=\"modele\" value=\"$mod\" />";
            echo "<INPUT TYPE=\"submit\" name=\"inscrire\" value=\"" . gettext("Ajouter une cl&#233; &#224;") . " $mod\"></FORM> ";
        }

        echo "<br><br>" . gettext("Pour supprimer un groupe de cl&#233; il suffit de supprimer toutes ses cl&#233;s !<br>Il faut appliquer le groupe de cl&#233; &#224; un template pour que la restriction soit effectivement appliqu&#233;e ou pas") . "<br>";
        echo "<br><br>" . gettext("Appliquer les restrictions choisies aux templates suivants (Les nouvelles restrictions s'appliqueront aussit&#244;t)") . "<br><form action=\"affiche_modele.php\" method=post>";
        $handle = opendir('/home/templates');
        while ($file = readdir($handle)) {
            if ($file <> '.' and $file <> '..' and $file <> 'registre.vbs' and $file <> 'skeluser') {
                echo "<div  alt=\"$file\" title=\" $file\"><input type=\"checkbox\" name=\"template$i\" value=\"$file\" />$file</div>";
            }
            $i++;
        }
        echo "<input type=\"hidden\" name=\"nombre\" value=\"$i\" />";
        echo "<input type=\"hidden\" name=\"modele\" value=\"$mod\" />";
        echo "<input type=\"hidden\" name=\"modact\" value=\"template\" />";
        echo "<input type=\"submit\" value=\"" . gettext("Inscrire") . "\" /></form>";
        break;


    //ajout d'une cle a un modele
    case "ajoutcle":
        echo "<h2>" . gettext("Groupe de cl&#233; :") . " $mod </h2><h3>" . gettext("Ajout de cl&#233;") . "</h3>";
        affichelistecat("affiche_modele.php?modact=ajoutcle&mod=$mod", $testniveau, $cat);
        connexion();
        if (($cat) and !($cat == "tout")) {
            $ajout = " `categorie` = '$cat' and ";
            $ajoutvidewhere = " where`categorie` = '$cat' ";
            if ($_GET['sscat']) {
                $ajoutsscat = " sscat='$sscat' AND ";
                $ajoutsscatvide = " and sscat='$sscat' ";
            } else {
                $ajoutsscatvide = "";
                $ajoutsscat = "";
            }

            if (($testniveau == 2) and !($_GET['sscat'])) {
                $ajoutpasaffiche = " sscat= '' and";
                $ajoutpasaffichevide = " and sscat= '' ;";
            }
        } else {
            echo gettext("Choisissez une cat&#233;gorie ci-dessus") . "<br>";
            $ajout = " `categorie` = '' ";
            $ajoutsscat = '';
            $ajoutpasaffiche = '';
        }

        if ($cat == "tout") {
            $ajout = "";
            $ajoutvide = "";
            if ($sscat) {
                $ajoutsscatvide = "";
                $ajoutsscat = "";
            }
        }
        $query = "SELECT `cle` FROM `modele` WHERE `mod` = '$mod' ";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $rowserv = mysqli_fetch_array($resultat);
        if ($rowserv[0]) {
            $values = "($rowserv[0]";
            while ($rowserv = mysqli_fetch_array($resultat)) {
                $values = $values . ",$rowserv[0]";
            }

            $values = $values . ")";
            $query = "SELECT cleID,Intitule,type,chemin,OS,categorie,sscat FROM corresp WHERE  $ajout $ajoutsscat $ajoutpasaffiche cleID NOT IN $values;";
        } else {
            $query = "SELECT cleID,Intitule,type,chemin,OS,categorie,sscat FROM corresp" . $ajoutvidewhere . $ajoutsscatvide . $ajoutpasaffichevide;
        }

        if ($sscat) {
            echo "<blockquote>" . gettext("Sous-Categorie") . " $sscat</blockquote>";
        }

        echo "<FORM METHOD=POST ACTION=\"affiche_modele.php\" name=\"mod\">";
        echo "<table border=\"1\"><tr><td><a href=\"aide_cle.php?cle=$row[1]\" target=\"_blank\" >";
        echo "<img src=\"/elements/images/help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";

        if ($cat == "tout") {
            echo"<td><DIV ALIGN=CENTER>" . gettext("Cat&#233;gorie") . "</DIV></td><td><DIV ALIGN=CENTER>" . gettext("Sous-Cat&#233;gorie") . "</DIV></td>";
        }

        echo "<td>" . gettext("Intitul&#233;") . "</td><td>" . gettext("OS") . "</td><td>" . gettext("Choisir") . "</td><td>" . gettext("Rendre la restriction active") . "</td></tr>";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        while (($resultat) && ( $row = mysqli_fetch_array($resultat))) {
            $j++;
            echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[0]','aide','scrollbars=yes,width=600,height=620')\"><img src=\"/elements/images/help.png\" alt=\"" . gettext("Aide") . "\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";
            if ($cat == "tout") {
                echo"<td><DIV ALIGN=CENTER>$row[5]</DIV></td><td><DIV ALIGN=CENTER>$row[6]</DIV></td>";
            }

            echo"<td>$row[1]</td><td>$row[4]</td><td><INPUT TYPE=\"checkbox\" NAME=\"cle$j\" value=\"$row[0]\"></td>";

            if ($row[2] == "restrict") {
                echo"<td ><input type=\"checkbox\" name=\"etat$j\" />" . gettext("Activ&#233;e ?") . "</td>";
            } else {
                echo "<td><input type=\"hidden\" name=\"etat$j\" value=\"0\"/>&nbsp;</td>";
            }
            echo"</tr>";
        }
        echo "</table>";

        if ($j) {
            echo "<input type=button value=\"Tout\" onClick=\"checkAllcle($j)\">";
            echo "<input type=button value=\"" . gettext("Tout activ&#233;") . "\" onClick=\"checkAlletat($j)\"><br>";
            echo "<input type=button value=\"" . gettext("Rien") . "\" onClick=\"uncheckAllcle($j)\">";
            echo "<input type=button value=\"" . gettext("Tout desactiv&#233;") . "\" onClick=\"uncheckAlletat($j)\"><br>";
        }

        echo "<INPUT TYPE=\"hidden\" name=\"modele\" value=\"$mod\">";
        echo "<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$j\">";

        if (!$j) {
            echo gettext("Pas de cl&#233;s &#224; ajouter !!");
        }

        echo "<br><br><INPUT TYPE=\"hidden\" name=\"modact\" value=\"choixajoutcle\" >";
        echo "<INPUT TYPE=\"submit\" value=\"" . gettext("Ajouter les cl&#233;s choisies") . "\"></form>";

        echo "<form action=\"affiche_modele.php\" name=\"fin ajout cle\" method=\"post\">";
        echo "<input type=\"hidden\" name=\"modact\" value=\"yes\" />";
        echo "<input type=\"hidden\" name=\"modele\" value=\"$mod\" />";
        echo "<input type=\"hidden\" name=\"ssact\" value=\"$ssact\" />";
        echo "<input type=\"submit\" name=\"fin ajout cle\" value=\"" . gettext("J'ai fini d'ajouter des cl&#233;s &#224; ce groupe") . "\" /></form>";

        break;


    //cle ajoutee choisie et insertion base
    case "choixajoutcle":
        $nombre = $_POST['nombre'];
        $n = 0;
        for ($i = 0; $i < $nombre + 1; $i++) {
            $cle = $_POST['cle' . $i];
            if ($cle) {
                $etat = $_POST['etat' . $i];
                if (!$etat) {
                    $etat = 0;
                } else {
                    $etat = 1;
                }
                $n++;
                $query = "INSERT INTO `modele` ( `etat`, `cle`, `mod` ) VALUES ('$etat','$cle','$mod');";
                $insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
            }
        }
        $ssact = $_POST['sscat'];
        echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_modele.php?mod=$mod&modact=ajoutcle&sscat=$sscat\"></HEAD>";
        break;



    //modification des cles du modele : passage de la valeur par defaut ou de l'antidote de $mod";
    case "modifcle":
        $suppr = $_GET['suppr'];
        $cle = preg_split("/-/", $liste);
        for ($i; $i < count($cle) + 1; $i++) {
            if ($cle[$i]) {
                if ($suppr) {
                    $query = "DELETE FROM `modele` WHERE `mod`='$mod' and cle='$cle[$i]'";
                    $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                    $test++;
                }

                if (($cle[$i]) and (!$suppr)) {
                    $query2 = "SELECT `etat` FROM `modele` WHERE `cle` = '$cle[$i]' AND `mod` = '$mod' ";
                    $resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
                    $row2 = mysqli_fetch_row($resultat2);
                    if ($row2[0] == 1) {
                        $etat = 0;
                    }

                    if ($row2[0] == 0) {
                        $etat = 1;
                    }

                    $query1 = "UPDATE `modele` SET `etat` = '$etat' WHERE `cle` = '$cle[$i]' AND `mod` = '$mod' ";
                    $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                }
            }
        }

        //affichage apres l prise en compte des modifications
        echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_modele.php?mod=$mod&modact=yes\"></HEAD>";
        echo gettext("Commandes prises en compte !") . "<br>";

        break;

    //pour avoir les liens directs en modification et suppression
    case "modifclelien":

        $cle = $_GET['change'];
        $suppr = $_GET['suppr'];
        if ($cle) { //suppression du modele
            if ($suppr) {
                $query = "DELETE FROM `modele` WHERE `cle`=$cle AND `mod`='$mod';";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                $test++;
            } else {
                $query = "SELECT `etat` FROM `modele` WHERE `cle` = '$cle' AND `mod` = '$mod'";
                $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
                $row = mysqli_fetch_row($resultat);

                if ($row[0] == 1) {
                    $etat = 0;
                }
                if ($row[0] == 0) {
                    $etat = 1;
                }

                $query1 = "UPDATE `modele` SET `etat` = '$etat' WHERE `cle` = '$cle' AND `mod` = '$mod';";
                $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                $test++;
            }
        } else {
            $test++;
        }

        //aucune des cles n'a ete selectionnee
        if (!$test) {
            echo gettext("Pas de changement du mod&#232;le");
        }
        //affichage apres l prise en compte des modifications
        echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_modele.php?mod=$mod&modact=yes\">";
        echo "</HEAD>Commandes prises en compte !<br>";
        break;


    // application d'un modele a un template
    case "template":
        $nombre = $_POST['nombre'];
        //$n=0;
        for ($i = 0; $i < $nombre + 1; $i++) {
            $groupe = $_POST['template' . $i];
            if ($groupe) {
                applique_modele($mod, $groupe, "oui");
            }
        }
//        applique_modele($mod, "base", "oui");
        echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"100; URL=affiche_modele.php?mod=$mod&poser=yes\">";
        echo "</HEAD>" . gettext("Modification effectu&#233;e pour les groupes ci-dessus") . " <br>";
        echo gettext("Commandes prises en compte !");
        break;
}
((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

include("pdp.inc.php");
?>
