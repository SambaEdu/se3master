<?php


   /**
   
   * Expedie une popup a un group
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: respop_group.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if ((is_admin("annu_can_read",$login)=="Y") || (is_admin("Annu_is_admin",$login)=="Y") || (is_admin("savajon_is_admin",$login)=="Y"))  {
	
	$messsage=$_POST['message'];

	// Aide
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Popup")."</h1>";


	// test existence message

	if(isset($message)) {
		$file = fopen("/tmp/popup.txt","w+");
		fwrite($file,($message));
		fclose($file);
	}

	// recuperation du champ hidden de pop_group.php
	$filter= $_POST['nomgroupe'];
	$group=search_groups ("(cn=".$filter.")");
	$uids = search_uids ("(cn=".$filter.")");
	$people = search_people_groups ($uids,"(sn=*)","cat");
	
	#$TimeStamp_1=microtime();
  	#############
  	# DEBUG     #
  	#############
  	#echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR><BR>";
  	#############
  	# Fin DEBUG #
  	#############

	if (count($people)) {
    		// affichage des r?sultats
    		// Nettoyage des _ dans l'intitul? du groupe
    		$intitule =  strtr($filter,"_"," ");
    		echo "<H1>".gettext("Pop Up vers")." $intitule <font size=\"-2\">".$group[0]["description"]."</font></H1>\n";
    		echo "<H3>".gettext("Il y a ").count($people).gettext(" membre");
    		if ( count($people) >1 ) echo "s";
    		echo gettext(" dans ce groupe.")."</H3>\n";
    
    		echo "<H3>".gettext("Les r&#233;sultats du Pop Up sont :")."</H3>";
     		$nmbconnect=0;

    		for ($loop=0; $loop < count($people); $loop++) {

      			$uid=$people[$loop]["uid"];
      			$connect=`smbstatus -u $uid|grep $uid`;
      
      			if (empty($connect)) {
         			// echo "<H1>Pop Down :-)</H1><P>";
         			// echo "<br>";
         			// echo "<br>";
         			// echo $people[$loop]["fullname"]."</TD><TD>pas de session ouverte</TD><TD></TD>\n";
                        } else {
       				$nmbconnect=$nmbconnect +1;
       
				// recherche de la machine sur laquelle est connecte 
				// l'utilisateur et envoi du pop up
          			if (($tri=="") OR (($tri != 0) AND ($tri != 2)) ) $tri=2; // tri par ip par defaut
				// modif du tri
				// /usr/bin/smbstatus -S| awk 'NF>6 {print $2,$5,$6}'|sort -u +2
				// le +POS de la fin donne le rang de la variable de tri (0,1,2...)
				if ("$smbversion" == "samba3") {
       					exec ("/usr/bin/smbstatus -b | grep -v root | grep -v nobody | awk 'NF>4 {print $2,$4,$5}' | sort -u",$out); }
				elseif ($tri == 0) {
 			      		exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u",$out); 
				} else  {
					exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u +2",$out); 
				}

				for ($i = 0; $i < count($out) ; $i++) {
    					$test=explode(" ",$out[$i]);
    					$test[2]=strtr($test[2],"()","  ");
    					$test[2]=trim($test[2]);

    					$uidtest=$test[0];
    					$machine=$test[1];
    					$ip=$test[2];

    					if ("$uid" == "$uidtest") {
  						exec ("cat /tmp/popup.txt|smbclient -U 'Administrateur Samba Edu 3' -M $test[1]");
    						echo "<li><small><b>".$machine."</b>".gettext(" est destinataire du Pop Up (session ouverte par")." <b>".$people[$loop]["fullname"]." </b>)</small></li>\n";
    						echo "<br>";
    					}
				} 
   			} //fin else { $nmbconnect=$nmbconnect +1;
   		} //fin for ($loop=0; $loop < count($people); $loop++)
   
	}  else {
		echo " <STRONG>".gettext("Pas de membres")."</STRONG>".gettext(" dans le groupe")." $filter.<BR>";
	}
         
	if  ($nmbconnect==0) {
		echo"<b><small>".gettext("pas d'&#233;mission de Pop Up car il n'y aucun membre du groupe connect&#233; !")."</small></b>";
	} else {
	       echo "<H3>".gettext("Nombre total de Pop Up &#233;mis:")." $nmbconnect </H3>\n";
	}

}
include ("pdp.inc.php");
?>
