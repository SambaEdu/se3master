<?php

/**

 * Gestion des cles pour clients Windows (affichage des templates vu dans /home/templates ,lien vers choisirprotect ou vers affiche_restrictions en fonction du niveau)
 * @Version $Id$ 


 * @Projet LCS / SambaEdu 

 * @auteurs  Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note 

 */
/**

 * @Repertoire: registre
 * file: indexcle.php

 */
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre', "/var/www/se3/locale");
textdomain('se3-registre');

if ((is_admin("computers_is_admin", $login) != "Y") or (is_admin("parc_can_manage", $login) != "Y"))
    die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");
$_SESSION["pageaide"] = "Gestion_des_clients_windowsNG#Le_menu_Attribution_des_cl.C3.A9s";

$testniveau = getintlevel();
require "include.inc.php";

$template = $_GET['template'];
$action = $_GET['action'];

connexion();
if (test_bdd_registre() == false) {
    exit;
}

echo "<h1>" . gettext("Gestion des templates") . "</h1>";
echo gettext("Choisir un template ");
echo "<u onmouseover=\"return escape" . gettext("('Choisir un template correspond &#224; un groupe de machine, un groupe de personnes. Dans ce menu, vous pouvez visualiser les protections des clients windows de votre parc en leur attribuant des groupes de cl&#233;s. Selon le niveau de s&#233;curit&#233; que vous souhaitez, choisissez un des groupes des cl&#233;s qui va vous &#234;tre propos&#233;. Attention, vous pouvez<font color=#FF0000> uniquement enlever des restrictions </font> ou faire des r&#233;glages sur les cl&#233;s de configuration ( changer votre page de d&#233;marrage pour Internet Explorer, par exemple), seuls les administrateurs r&#233;seau peuvent ajouter des restrictions. <font color=#FF0000>Soyez tr&#232;s prudent avec ce menu !!</font>. Faites-vous aidez par votre administrateur r&#233;seau au d&#233;but.')") . "\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>";

echo "<br><br>";

if (($action) && ($template)) {
    $priorite = priorite($template, $action);
    echo $template." : priorite ".$priorite."<br>";
}
$listes = gettemplates();

echo "<h2>".gettext("templates sans cles")." :</h2><br>";

foreach ($listes[1] as $value) {
    if ($testniveau < "3") {
        if ((this_parc_delegate($login, $value, 'manage')) or (is_admin("computers_is_admin", $login) == "Y")) {
            echo "<a href=\"choisir_protect.php?salles=$value\">$value</a>";
            echo "<br>";
            $test_affiche++;
        }
    } else if ($testniveau >= "3") {
        if ((this_parc_delegate($login, $value, 'manage')) or (is_admin("computers_is_admin", $login) == "Y")) {
            echo "<a href=\"affiche_restrictions.php?salles=$value\">$value </a>";
 //                echo " priorite : ".$value;
            echo "<br>";
            $test_affiche++;
        }
    }

}
echo "<h2>".gettext("templates contenant des cles")." :</h2><br>";

foreach ($listes[0] as $key => $value) {
    $type = group_type($key);
    if ($testniveau < "3") {
        if ((this_parc_delegate($login, $key, 'manage')) or (is_admin("computers_is_admin", $login) == "Y")) {
            echo "<a href=\"choisir_protect.php?salles=$key\">$key</a>";
            echo "<br>";
            $test_affiche++;
        }
    } else if ($testniveau >= "3") {
        if ((this_parc_delegate($login, $key, 'manage')) or (is_admin("computers_is_admin", $login) == "Y")) {
            echo "<a href=\"affiche_restrictions.php?salles=$key&cat=tout\">$key </a>";
            echo $type;
            if (($type == "groupes" and  $key <> 'eleves' and $key <> 'profs' and $key <> 'base' and $key <> 'administratifs' and $key <> 'overfill') or $type == "parcs") {
                echo "<a href=\"indexcle.php?template=$key&action=-1\"> monter </a>";
                echo "<a href=\"indexcle.php?template=$key&action=1\"> descendre </a>";
            }
//                echo " priorite : ".$value;
            echo "<br>";
            $test_affiche++;
        }
    }
}
if ($test_affiche == 0) {
    echo gettext("Vous n'avez pas de droit sur ce template. ");
}

include("pdp.inc.php");
?>
