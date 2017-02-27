<?php

/**

 * Gestion des cles pour clients Windows (exportation des modeles)
 * @Version $Id$ 


 * @Projet LCS / SambaEdu 

 * @auteurs  Sandrine Dangreville

 * @Licence Distribue selon les termes de la licence GPL

 * @note 

 */
/**

 * @Repertoire: registre
 * file: mod_export.php

 */
require "include.inc.php";
connexion();
$act = $_GET['action'];
if (!$act) {
    $act = $_POST['action'];
}

switch ($act) {
    //par defaut : menu effectuer la mise a jour
    //cas export : permet de selectionner les modeles a exporter
    //cas exportfin : effectue l'exportation et permet d'enregistrer le fichier modeles.xml
    default:
        include "entete.inc.php";
        include "ldap.inc.php";
        include "ihm.inc.php";

        require_once ("lang.inc.php");
        bindtextdomain('se3-registre', "/var/www/se3/locale");
        textdomain('se3-registre');

        echo "<a href=\"mod_export.php?action=export\">" . gettext("Effectuer la mise &#224 jour des restrictions ?") . "</a>";
        break;

    case "export":
        include "entete.inc.php";
        include "ldap.inc.php";
        include "ihm.inc.php";

        require_once ("lang.inc.php");
        bindtextdomain('se3-registre', "/var/www/se3/locale");
        textdomain('se3-registre');

        if (ldap_get_right("computers_is_admin", $login) != "Y")
            die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");
        $_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

        connexion();
        $query = "SELECT `mod` FROM modele GROUP BY `mod`;";
        $resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        $j = 0;
        echo gettext("Exporter les groupes suivants :") . " <br><FORM METHOD=POST ACTION=\"mod_export.php\">";

        while ($row = mysqli_fetch_array($resultat)) {
            echo"<input type=\"checkbox\" name=\"export$j\" value=\"$row[0]\" checked />$row[0]<br>";
            $j++;
        }
        echo "<br><br>";
        echo "<input type=\"hidden\" name=\"nombre\" value=\"$j\" /> <input type=\"hidden\" name=\"action\" value=\"exportfin\" />";
        echo "<input type=\"submit\" name=\"export\" value=\"" . gettext("Exporter ces groupes") . "\" /></form>";
        ((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        break;

    case "exportfin":
        connexion();

        $nombre = $_POST['nombre'];
        if ($nombre > 0) {
            $content_dir = '/tmp/';
            $fichier_mod_xml = $content_dir . "modeles.xml";

            if (file_exists($fichier_mod_xml))
                unlink($fichier_mod_xml);
            $get = fopen($fichier_mod_xml, "w+");
            $ligne = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<se3mod>\n<nom>" . gettext("Groupe de cles") . "</nom>\n<version>V 0.1</version>\n<categories>\n";
            fputs($get, $ligne);
            for ($i = 0; $i < $nombre + 1; $i++) {
                $mod = $_POST['export' . $i];
                if ($mod) {
                    $ligne = "<categorie nom=\"$mod\">\n";
                    $query1 = "SELECT `cle`,`etat` FROM `modele` WHERE `mod` = '$mod' ";
                    $resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
                    while ($row1 = mysqli_fetch_array($resultat1)) {
                        $ligne = $ligne . "<regle>\n";
                        $query2 = "SELECT `chemin` FROM `corresp` WHERE `CleID` = '$row1[0]' ";
                        $resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
                        while ($row2 = mysqli_fetch_array($resultat2)) {
                            $ligne = $ligne . "<clef>$row2[0]</clef>\n";
                        }
                        $ligne = $ligne . "<value>$row1[1]</value>\n</regle>\n";
                    }
                    $ligne = $ligne . "</categorie>\n";
                    fputs($get, $ligne);
                }
            }
            $ligne = "</categories>\n</se3mod>\n";
            fputs($get, $ligne);
            fclose($get);
            if (file_exists($fichier_mod_xml)) {
	            header("Content-type: application/force-download");
        	    header("Content-Length: " . filesize($fichier_mod_xml));
            	    header("Content-Disposition: attachment; filename=modeles.xml");
            	    readfile($fichier_mod_xml);
		    exit;
                    unlink($fichier_mod_xml);
            }
	    ((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

            include "entete.inc.php";
            include "ldap.inc.php";
            include "ihm.inc.php";

            require_once ("lang.inc.php");
            bindtextdomain('se3-registre', "/var/www/se3/locale");
            textdomain('se3-registre');
            if (ldap_get_right("computers_is_admin", $login) != "Y")
                die(gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction") . "</BODY></HTML>");
            $_SESSION["pageaide"] = "Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";
        }
        break;
}

retour();
?>
