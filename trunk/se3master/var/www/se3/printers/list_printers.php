<?php


   /**
   
   * Liste les imprimantes de SE3
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: list_printers.php

  */	

// Liste des imprimantes

include "entete.inc.php";
include "ldap.inc.php";
include "printers.inc.php";
include "ihm.inc.php";     // pour is_admin()

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

$view = $_POST['view'];

if ((is_admin("printers_is_admin",$login)=="Y") AND ($login != "admin")) {
        echo "<H1>".gettext(" Liste des imprimantes")."</H1>";
        $parc_name=search_parc ($_SERVER['REMOTE_ADDR']);
        //if ($parc_name!="") {
        if (isset($parc_name)) {
                //echo "<H5>".gettext("Votre machine")." (IP = ".($_SERVER['REMOTE_ADDR']).") ".gettext("est dans le parc :")." $parc_name </H5> ";
                echo "<H5>".gettext("Votre machine")." (IP = ".($_SERVER['REMOTE_ADDR']).") ";
                if(count($parc_name)==1){
                        echo gettext("est dans le parc :")." $parc_name[0] </H5> ";
                }
                else{
                        echo gettext("est dans les parcs :")." $parc_name[0]";
                        for($i=1;$i<count($parc_name);$i++){
                                echo ", $parc_name[$i]";
                        }
                        echo " </H5> ";
                }

                echo "<TABLE BORDER=0>\n";
                echo "<HR>";
                for($i=0;$i<count($parc_name);$i++){
                        //      echo " La machine est dans le parc ".$parc_name;
                        //echo "<TR><TD WIDTH=200 BGCOLOR=\"cornflowerblue\"><B>$parc_name</B></TD></TR>";
                        echo "<TR><TD WIDTH=200 BGCOLOR=\"cornflowerblue\"><B>$parc_name[$i]</B></TD></TR>\n";
                        //$printers_parc=printers_members($parc_name,"parcs",1);
                        $printers_parc=printers_members($parc_name[$i],"parcs",1);
                        $nb_printers_parc=count($printers_parc);
                        for ($j=0; $j<$nb_printers_parc; $j++) {
                                $sys= exec("/usr/bin/lpstat -o $printers_parc[$j]");
                                if ($sys != "") $status=gettext("OUI");
                                else $status=gettext("NON");
                                echo "<TR><TD WIDTH=200 BGCOLOR=\"lightsteelblue\"><LI><A href='view_printers.php?one_printer=$printers_parc[$j]'>$printers_parc[$j]</A></LI></TD>";
                                echo "<TD><FONT COLOR=\"cornflowerblue\">".gettext("Travaux en cours=")."$status\n</FONT></TD></TR>\n";
                        }
                        echo "<TR><TD HEIGHT=30></TD></TR>\n";
                }
                echo "</TABLE>\n";
        } else {
                echo "<H5>".gettext("Votre machine")." (IP = ".($_SERVER['REMOTE_ADDR']).") ".gettext("n'appartient &#224 aucun parc !")."</H5>\n";
        }
} elseif ((is_admin("printers_is_admin",$login)=="Y") AND ($login == "admin")) {
        echo "<H1>".gettext(" Liste des imprimantes")."</H1>";
        echo "<FORM ACTION=\"list_printers.php\" METHOD=\"post\">";
        if (!isset($view) || ($view=="v_parc")) {
                echo "<INPUT TYPE=\"radio\" NAME=\"view\" VALUE=\"v_parc\" CHECKED>".gettext("par parc")." &nbsp&nbsp";
                echo "<INPUT TYPE=\"radio\" NAME=\"view\" VALUE=\"v_printers\">".gettext("par imprimante")." &nbsp&nbsp";
        } else {
                echo "<INPUT TYPE=\"radio\" NAME=\"view\" VALUE=\"v_parc\">".gettext("par parc")." &nbsp&nbsp";
                echo "<INPUT TYPE=\"radio\" NAME=\"view\" VALUE=\"v_printers\" CHECKED>".gettext("par imprimante")." &nbsp&nbsp";
        }
        echo "<INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\">";
        echo "<HR>";

        //Par parc
        $all_parcs=$list_parcs=search_machines("objectclass=groupOfNames","parcs");
        $nb_parcs=count($all_parcs);

        if ($view != "v_printers") {
                echo "<H3>".gettext("Classement par parc")."</H3>";
                echo "<TABLE BORDER=0>";
                for ($i=0; $i<$nb_parcs; $i++) {
                        $parc_name=$all_parcs[$i]['cn'];
                       
                        // Recherche de l'impra=imante par defaut
                        $imprim_defaut = get_default_printer($parc_name);

                        $printers_parc=printers_members($parc_name,"parcs",1);
                        $nb_printers_parc=count($printers_parc);
                       
                        echo "<TR><TH WIDTH=200 BGCOLOR=\"cornflowerblue\">&nbsp;$parc_name</TH><TH BGCOLOR=\"cornflowerblue\">&nbsp;".gettext("Travaux en cours")."&nbsp;</TH><TH BGCOLOR=\"cornflowerblue\"> &nbsp;".gettext("par d&#233;faut")."&nbsp;</TH></TR>";
                        if ($nb_printers_parc == 0) {echo "<TR><td colspan=3><i> ".gettext("Aucune imprimante n'est rattach&#233;e &#224; ce parc")."</i></td></TR>";};
                        for ($j=0; $j<$nb_printers_parc; $j++) {
                                $sys= exec("/usr/bin/lpstat -o $printers_parc[$j]");
                                if ($sys != "") $status=gettext("OUI");
                                else $status=gettext("NON");
                                echo "<TR><TD WIDTH=200 BGCOLOR=\"lightsteelblue\"><LI><A href='view_printers.php?one_printer=$printers_parc[$j]'>$printers_parc[$j]</A></LI></TD>";
                                echo "<TD><FONT COLOR=\"cornflowerblue\">$status\n</FONT></TD>";
                               
                                if ($imprim_defaut == $printers_parc[$j]) {
                                        echo "<TD><img style=\"border: 0px solid ;\" src=\"../elements/images/enabled.png\" title=\"par defaut\" alt=\"par defaut\" ></TD>";
                                } else {
                                        echo "<TD></TD>";
                                }
                               
                                echo "</TR>";
                        }
                        echo "<TR><TD HEIGHT=30></TD></TR>";
                }
                echo "</TABLE>";

        // par imprimante
        } elseif ($view == "v_printers") {
                $all_printers=search_printers("printer-name=*");
                $nb_printers=count($all_printers);
                echo "<H3>".gettext("Classement par imprimante")."</H3>";
                echo "<TABLE BORDER=0>";
                for ($i=0; $i<$nb_printers; $i++) {
                        $parc_trouve[$i]=false;          // On considere au prealable qu'une imprimante n'appartient a aucun parc
                        $printer_name=$all_printers[$i]['printer-name'];
                        $sys= exec("/usr/bin/lpstat -o $printers_parc[$i]");
                        if ($sys != "") $status=gettext("OUI");
                        else $status=gettext("NON");
                        echo "<TR><TD WIDTH=200 BGCOLOR=\"cornflowerblue\"><A href='view_printers.php?one_printer=$printer_name'><font color=\"black\"><B>$printer_name</B></font></A></TD>";
                        echo "<TD>".gettext("Travaux en cours")."=$status\n</TD></TR>";
                        for ($j=0; $j<$nb_parcs; $j++) {
                                $parc_name=$all_parcs[$j]['cn'];
                                $printers_parc=printers_members($parc_name,"parcs",1);
                                for ($k=0; $k<count($printers_parc);$k++) {
                                        if ($printers_parc[$k]==$printer_name) {
                                                echo "<TR><TD WIDTH=200 BGCOLOR=\"lightsteelblue\">$parc_name\n</TD></TR>";
                                                $parc_trouve[$i]=true;      //l'imprimante appartient au moins a un parc
                                        }
                                }
                        }
                        echo "<TR><TD HEIGHT=30></TD></TR>";
                }
                echo "</TABLE>";
                // Affichage des imprimantes qui ne font pas partie d'un parc.
                $all_printers=search_printers("printer-name=*");
                $nb_printers=count($all_printers);
                $n=0;  // on fait l'affichage s'ils existent des imprimantes sans parc
                for ($i=0; $i<$nb_printers; $i++) {
                        if ($parc_trouve[$i]==false) { $n = $n+1; }
                }
                if ($n != 0) {
                        echo "<BR><BR><HR>";
                        echo "<H4><FONT COLOR=\"red\"><BLINK>".gettext("Les imprimantes suivantes n'appartiennent &#224 aucun parc:")."</BLINK></FONT></H4>";
                        for ($i=0; $i<$nb_printers; $i++) {
                                if ($parc_trouve[$i]==false) {
                                        echo "<FONT COLOR=\"red\">";
                                        echo "{$all_printers[$i]['printer-name']}";
                                        echo "</FONT>";
                                        echo "<BR>";
                                }
                        }
                }
        }
}


include "pdp.inc.php";
?>
