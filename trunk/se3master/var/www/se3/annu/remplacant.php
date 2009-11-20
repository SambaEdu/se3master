<?php


/**

* Gestion du professeur remplacant
* @Version $Id$

* @Projet LCS / SambaEdu

* @auteurs Philippe Schwarz
* @auteurs Philippe Chadefaux

* @Licence Distribue selon les termes de la licence GPL

* @note
*/

/**

* @Repertoire: annu
* file: remplacant.php
*/





include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// Aide
$_SESSION["pageaide"]="Annuaire";

echo "<h1>".gettext("Annuaire")."</h1>\n";


aff_trailer ("1");
if (is_admin("Annu_is_admin",$login)=="Y") {

    $abs=$_POST['uid'];
    $rpl=$_POST['remplacant'];

    if ($abs == $rpl ) {
        echo "<BR>".gettext("Le professeur")." <B>$rpl</B>".gettext(" se remplace lui-m&#234;me; Vous appliquez les d&#233;cisions du ministre. C'est bien, poursuivez......")."<BR><BR><HR>";
    } else {
        echo gettext("Attribution des cours,classes, equipes et droits de gestion serveur de")." <B>$abs</B> ".gettext(" &#224;")." <B>$rpl</B>.<BR><BR>";
        echo "<B>$rpl </B>".gettext("sera ajout&#233; dans les groupes suivants :");
        echo "<FORM action=\"add_user_group.php\" method=\"post\">\n";
        echo "<TABLE BORDER=0><TR><BR>";

        //list($abs, $groups)=people_get_variables($uid, true);
        list($absent, $groups)=people_get_variables($abs, true);

        //echo "<H3>".$abs["fullname"]."</H3>\n";
        //if ($abs["description"]) echo "<p>".$abs["description"]."</p>";
        if ($absent["description"]) {echo "<p>".$absent["description"]."</p>";}

        if ( count($groups) ) {
                //echo "<U>Membre des groupes</U> :<BR><UL>\n";
                for ($loop=0; $loop < count ($groups) ; $loop++) {
                    //echo "<LI><A href=\"group.php?filter=".$groups[$loop]["cn"]."\">";
                    echo "<BR>";
                    $usergrpe=$groups[$loop]["cn"];
                    //if ("$usergrpe" == "Profs") {
                    //	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                //}

                if ( preg_match ("/Cours_/", $groups[$loop]["cn"]) ) {
                    echo "<INPUT TYPE=CHECKBOX  NAME=cours_gr[] VALUE=".$usergrpe."  CHECKED> &nbsp;";
                } elseif ( preg_match ("/Equipe_/", $groups[$loop]["cn"]) ) {
                    echo "<INPUT TYPE=CHECKBOX  NAME=equipe_gr[] VALUE=".$usergrpe."  CHECKED> &nbsp;";
                } elseif ( preg_match ("/Matiere_/", $groups[$loop]["cn"]) ) {
                    echo "<INPUT TYPE=CHECKBOX  NAME=matiere_gr[] VALUE=".$usergrpe."  CHECKED> &nbsp;";
                }
                elseif ("$usergrpe" == "Profs") {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                }
                else {
                    echo "<INPUT TYPE=CHECKBOX  NAME=autres_gr[] VALUE=".$usergrpe."  CHECKED> &nbsp;";
                }


                if ($groups[$loop]["type"]=="posixGroup") {
                    echo "<STRONG>".$usergrpe."</STRONG>";
                }
                else {
                    echo $groups[$loop]["cn"];
                    echo "</A>,<font size=\"-2\"> ".$groups[$loop]["description"];
                    $login1=preg_split ("/[\,\]/",ldap_dn2ufn($groups[$loop]["owner"]),2);

                    if ( $uid == $login1[0] ) {echo "<strong><font color=\"#ff8f00\">&nbsp;(".gettext("professeur principal").")</font></strong>";}
                }
                echo "</font></LI>\n";
            }

            echo "</UL>";
        }


        echo "<INPUT type=hidden name=uid value=$rpl>";
//		echo "<input type=\"hidden\" name=\"categorie\" value=\"Profs\">";
        echo "<input type=\"hidden\" name=\"add_user_group\" value=\"true\">";
        echo "<input type=\"hidden\" name=\"remplacant\" value=\"true\">";
        echo "</TABLE><HR><div align=center><input type=\"submit\" Value=\"".gettext("Attribuer les droits au remplacant")."\"></div></FORM></TR></HTML>";
    }

} else {
    echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");

?>
