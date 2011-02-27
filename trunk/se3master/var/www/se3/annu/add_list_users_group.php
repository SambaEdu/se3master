<?php

   /**
   
   * Ajoute des utilisateurs aux groupes dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: add_list_users_group.php
   */

  
  
  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-annu',"/var/www/se3/locale");
  textdomain ('se3-annu');
      
  // Aide
  $_SESSION["pageaide"]="Annuaire";
  
  echo "<h1>".gettext("Annuaire")."</h1>";

  $cn=$_POST['cn'];
  if ($cn=="") { $cn=$_GET['cn']; }
  $new_uids=$_POST['new_uids'];
  $add_list_users_group=$_POST['add_list_users_group'];

  if (is_admin("Annu_is_admin",$login)=="Y") {

  	$filter="8_".$cn;	
	aff_trailer ("$filter");
    	if ( !$add_list_users_group ) {
      		echo "<H4>".gettext("Ajouter des membres au groupe :")." $cn</H4>\n";
      		// cas d'un groupe de type Equipe
      		if ( preg_match ("/Equipe_/", $cn) ) {
        		// Recherche de la liste des uid  des membres de ce groupe
        		$uids_act = search_uids ("(cn=$cn)");
        		// Reherche de la liste des professeurs
        		$uids_profs = search_uids ("(cn=Profs)");
        		// Constitution d'un tableau excluant les membres actuels
        		$k=0;
        		for ($i=0; $i < count($uids_profs); $i++ ) {
            			for ($j=0; $j < count($uids_act); $j++ ) {
              				if ( $uids_profs[$i]["uid"] == $uids_act[$j]["uid"] )  {
                				$exist = true;
                				break;
              				} else { $exist = false; }
            			}
            			if (!$exist) {
              				$uids_new_members[$k]["uid"] = $uids_profs[$i]["uid"];
              				$k++;
            			}
        		}
         		$people_new_members=search_people_groups ($uids_new_members,"(sn=*)","cat");
      		} elseif   ( preg_match ("/Classe_/", $cn) ) {
        		// Recherche de la liste des Eleves appartenant a une classe
        		$uids_eleves_classes =   search_uids ("(cn=Classe_*)");
        		##DEBUG
        		#echo "Eleves Classes>".  count($uids_eleves_classes)."<BR>";
        		#for ($i=0; $i < count($uids_eleves_classes ); $i++ ) {
        		#echo $uids_eleves_classes[$i]["uid"]."<BR>";
        		#}
        		##DEBUG
        		// Recherche de la liste des Eleves
        		$uids_eleves = search_uids ("(cn=Eleves)");
        		##DEBUG
        		#echo "Eleves >".  count($uids_eleves)."<BR>";
        		#for ($i=0; $i < count($uids_eleves); $i++ ) {
        		#echo $uids_eleves[$i]["uid"]."<BR>";
        		#}
        		##DEBUG
        		// Recherche des Eleves qui ne sont pas affectes a une classe
        		$k=0;
        		for ($i=0; $i < count($uids_eleves); $i++ ) {
        	  		$affect = false;
          			for ($j=0; $j < count($uids_eleves_classes); $j++ ) {
            				if ( $uids_eleves[$i]["uid"] == $uids_eleves_classes[$j]["uid"] ) {
              					$affect = true;
              					break;
            				}
          			}
            			if ($affect==false )  {
                			$uids_eleves_no_affect[$k]["uid"]=$uids_eleves[$i]["uid"];
                			$k++;
            			}
        		}
        		$people_new_members = search_people_groups ($uids_eleves_no_affect,"(sn=*)","cat");
        		##DEBUG
        		#echo "---->".  count($uids_eleves_no_affect)."<BR>";
        		#for ($i=0; $i < count($uids_eleves_no_affect); $i++ ) {
        		# echo $uids_eleves_no_affect[$i]["uid"]."<BR>";
        		# echo $people_new_members[$i]["fullname"]."<BR>";
        		#}
        		##DEBUG
      		}
      		
		// Affichage de la liste dans une boite de selection
      		if   ( count($people_new_members)>15) $size=15; else $size=count($people_new_members);
      		if ( count($people_new_members)>0) {
        		$form = "<form action=\"add_list_users_group.php\" method=\"post\">\n";
        		$form.="<p>".gettext("S&#233;lectionnez les membres &#224; ajouter au groupe :")."</p>\n";
        		$form.="<p><select size=\"".$size."\" name=\"new_uids[]\" multiple=\"multiple\">\n";
        		echo $form;
        		for ($loop=0; $loop < count($people_new_members); $loop++) {
          			echo "<option value=".$people_new_members[$loop]["uid"].">".$people_new_members[$loop]["fullname"];
         		}
        		$form="</select></p>\n";
        		$form.="<input type=\"hidden\" name=\"cn\" value=\"$cn\">\n";
        		$form.="<input type=\"hidden\" name=\"add_list_users_group\" value=\"true\">\n";
        		$form.="<input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">\n";
        		$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
        		$form.="</form>\n";
        		echo $form;
      		} else {
        		echo "<font color=\"orange\">".gettext("Vous ne pouvez pas ajouter d'&#233;l&#232;ves car il n'existe plus d'&#233;l&#232;ves non affect&#233;s &#224; des classes !!")."</font><BR>";
      		}
    	}   else {
      		// Ajout des membres au groupe
       		echo "<H4>".gettext("Ajout des membres au groupe :")." <A href=\"group.php?filter=$cn\">$cn</A></H4>\n";
       		for ($loop=0; $loop < count ($new_uids) ; $loop++) {
          		exec("/usr/share/se3/sbin/groupAddUser.pl  $new_uids[$loop] $cn" ,$AllOutPut,$ReturnValue);
          		echo  gettext("Ajout de l'utilisateur")."&nbsp;".$new_uids[$loop]."&nbsp;";
          		if ($ReturnValue == 0 ) {
            			echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";
          		} else { echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
       		}
    	}
  } else {
  	echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur LCS !")."</div>";
  }
  
  include ("pdp.inc.php");
?>
