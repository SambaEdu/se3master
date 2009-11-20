<?php


   /**
   
   * Page servant a detecter si on est en presence d'un Se3 ou d'un LCS pour les pages d'import Sconet
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Stephane Boireau (Animateur de Secteur pour les TICE sur Bernay/Pont-Audemer (27))
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr Portage LCSorSE3
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Include depuis import_sconet.php
   */

   /**

   * @Repertoire: includes
   * file: se3orlcs_import_sconet.php
   */




/* se3orlcs_import_sconet.php Derniere version : 18/06/07 */
// Detection LCS ou SE3
if ( file_exists("/var/www/se3") ) {
    // Section SE3
    $user_web = "www-se3";
    // Chemin:
    $pathlcsorse3 = "";
    $path_crob_ldap_functions = "";
    $racine_www= "/var/www/se3";
    $chemin_www_includes= "/var/www/se3/includes";
    $dossier_tmp_import_comptes= "/var/lib/se3/import_comptes";
    $chemin_csv="/setup/csv";
    $php="/usr/bin/php";
    $chemin="/usr/share/se3/scripts";
    $chemin_fich="$dossier_tmp_import_comptes";
    // Style du rapport
    $background ="/elements/images/fond_SE3.png";
    $stylecss="/elements/style_sheets/sambaedu.css";
    $helpinfo="../elements/images/system-help.png";

    include "entete.inc.php";
    include "ldap.inc.php";
    include "ihm.inc.php";

    require_once ("lang.inc.php");
    bindtextdomain('se3-annu',"/var/www/se3/locale");
    textdomain ('se3-annu');

    echo "<h1>".gettext("Importation des comptes et des groupes.")."</h1>\n";
    $_SESSION["pageaide"]="gestion_des_utilisateurs";

} else {
    // Section LCS
    $user_web = "www-data";
    // Chemin:
    $pathlcsorse3 = "../lcs/includes/";
    $path_crob_ldap_functions = "includes/";
    $racine_www= "/var/www";
    $chemin_www_includes= "/var/www/lcs/includes";
    $dossier_tmp_import_comptes= "/var/lib/lcs/import_comptes";
    $chemin_csv="/setup/csv";
    $php="/usr/bin/php";
    $chemin="/usr/share/lcs/scripts";
    $chemin_fich="$dossier_tmp_import_comptes";
    // Style du rapport
    $background ="Images/espperso.jpg";
    $stylecss="style/style.css";
    $helpinfo="../images/system-help.png";

    include "../lcs/includes/headerauth.inc.php";
    include "includes/ldap.inc.php";
    include "includes/ihm.inc.php";

    list ($idpers,$login)= isauth();
    if ($idpers == "0") header("Location:$urlauth");

    header_html();
}
?>
