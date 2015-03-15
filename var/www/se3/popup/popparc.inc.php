<?php
  
   /**	
   * Permet d'envoyer des popup a un parc 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter Caen 
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: popup
   * file: popparc.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-popup',"/var/www/se3/locale");
textdomain ('se3-popup');


  
  $parc=$_POST['parc'];
  


if (is_admin("computers_is_admin",$login)=="Y") {

	//aide
        $_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";
    	
	// Affichage du formulaire de selection de parc
    	if (!isset($parc)) {
		echo "<H1>".gettext("Pop Down :-) ")."</H1>\n";
		echo "<BR>";
        	echo "<H3>".gettext("S&#233;lection du parc destinataire du Pop Up")."</H3>";
        	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
        	if ( count($list_parcs)>0) {
            		echo "<FORM method=\"post\" action=\"popparc.inc.php\">\n"; 
			echo gettext("Choisir le parc:")." \n";
            		echo "<SELECT NAME=\"parc\" SIZE=\"1\">";
            		for ($loop=0; $loop < count($list_parcs); $loop++) {
               	 		echo "<option value=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."\n";
                        }
            		echo "</SELECT>&nbsp;&nbsp;\n";
            		echo "<input type=hidden name=\"destination\" value=\"popparc\">\n";
            		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
            		echo "</FORM>\n";
               }
       } else {
    		// Lecture des membres du parc
		$mp_all=gof_members($parc,"parcs",1);  
    		// Filtrage selon critere
    		if ("$filtrecomp"=="") $mp=$mp_all;
    		else {
        		$lmloop=0;
        		$mpcount=count($mp_all);
        		for ($loop=0; $loop < count($mp_all); $loop++) {
            			$mach=$mp_all[$loop];
            			if (preg_match("/$filtrecomp/",$mach)) $mp[$lmloop++]=$mach;
        		}
    		}
    		if ( count($mp)>15) $size=15; else $size=count($mp);
    		if ( count($mp)==0) {
         		echo ("<H1>".gettext("Pop Down :-) ")."</H1><BR><small><B>".gettext(" Ce parc est vide !")."</small></B>");
     		}
    		if ( count($mp)>0) {
	  		sort($mp);
      			echo "<H1>".gettext("Envoi du Pop Up au parc")." $parc </H1>\n";


      			echo "<H3>".gettext("R&#233;sultat du Pop Up aux machines du parc")." $parc: </H3>\n";
			echo gettext("Le parc")." $parc ".gettext("contient "). count($mp).gettext(" machines");

      			$nbrconnect=0;
      
      			for ($loop=0; $loop < count($mp); $loop++) {
        			$connect=`smbstatus |grep -w $mp[$loop]`;
        			if (empty($connect)) {
            				//echo "<LI><small><b>$mp[$loop]</b> n'est pas connect&#233;e !</small></LI>";
                                } else {
            				$nbrconnect= $nbrconnect + 1;
            				exec ("cat /tmp/popup.txt|smbclient -U 'Administrateur Samba Edu 3' -M $mp[$loop]");
            				echo "<LI><small><b>$mp[$loop]</b>".gettext("est destinataire du Pop Up")."</small></LI>";
              			}

                       }
			
			echo "<br><br>";
     			if ($nbrconnect==0) {
         			echo "<b><small>".gettext("Pas d'&#233;mission de Pop Up car aucune machine du parc n'est actuellement connect&#233;e !")."</small></b>";
                        } else {
         			echo gettext("Nombre total de popup &#233;mis: ")." $nbrconnect";
             		}

		}
	}
}

include ("pdp.inc.php");

?>

