<?php


   /**
   
   * Supprime les profiles Win NT
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   * @auteurs oluve olivier.le_monnier.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: del_nt_profile.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$_SESSION["pageaide"]="L\'interface_&#233;l&#232;ve#Voir_ma_fiche";

$uid=$_GET['uid'];
$action=$_GET['action'];

echo "<h1>".gettext("Annuaire")."</h1>\n";
 
aff_trailer ("3");
if ((is_admin("Annu_is_admin",$login)=="Y")||($uid==$login)) {
    // suppression d'un  profil nt d'utilisateur en cas de pb
    if ($uid == "admin" )  {
      	echo "<div class=error_msg>".gettext("Vous ne pouvez pas effacer le profil administrateur !")."</div>";
    } elseif (!$uid)  {
      	echo "<div class=error_msg>".gettext("Vous devez pr&#233;ciser le login du compte pour effacer son profil ! !")."</div>";
    } elseif ($action == "del") {
        exec ("/usr/share/se3/sbin/userProfileDel.pl $uid $action",$AllOutPut,$ReturnValue);
        if ($ReturnValue == "0") {
          	echo gettext("L'effacement du  profil de")." <strong>$uid</strong> ".gettext("a &#233;t&#233; programm&#233; avec succ&#232;s !"); 
	  	echo "<br>";
		echo gettext("il sera r&#233;g&#233;n&#233;r&#233; &#224; la prochaine ouverture de session")."<BR>\n";
        } else {
          	echo "<div class=error_msg>".gettext("Echec, le profil de ")." $uid ".gettext(" n'a pas &#233;t&#233; effac&#233; !"); 
                echo gettext("(type d'erreur : ")." $ReturnValue), ".gettext(" veuillez contacter");
                echo "<A HREF='mailto:$MelAdminLCS?subject=Effacement profil utilisateur $uid'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
        }
    } elseif ($action == "lock") {
        exec ("/usr/share/se3/sbin/userProfileDel.pl $uid $action",$AllOutPut,$ReturnValue);
	if ($ReturnValue == "0") {
	    	echo gettext("le verrouillage du  profil de")." <strong> $uid </strong><br>";
	echo gettext("a &#233;t&#233; programm&#233; avec succ&#232;s !<br><br>Il sera actif &#224; la prochaine ouverture de session")." <BR>\n";
	 } else {
	     echo "<div class=error_msg>".gettext("Echec, le profil de")." $uid ".gettext(" n'a pas &#233;t&#233; verrouill&#233; !");
	     echo gettext("(type d'erreur :")." $ReturnValue), ".gettext("veuillez contacter");
	     echo "<A HREF='mailto:$MelAdminLCS?subject=Verrouillage du profil utilisateur $uid'>";
	     echo gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
	}
    } elseif ($action == "unlock") {
        exec ("/usr/share/se3/sbin/userProfileDel.pl $uid $action",$AllOutPut,$ReturnValue);
        if ($ReturnValue == "0") {
             echo gettext("le d&#233;verrouillage du  profil de ")."<strong>$uid</strong> ".gettext(" a &#233;t&#233; programm&#233; avec succ&#232;s !<br> 
	          il sera actif &#224; la prochaine ouverture de session<br><br> 
		  Pour que les modifications soient prises en compte, la session de")." <strong>$uid</strong> ".gettext(",<br>doit
		 &#234;tre ferm&#233;e avant de modifier le profil")."<BR>\n";
        } else {
             echo "<div class=error_msg>".gettext("Echec, le profil de ")." $uid ".gettext("n'a pas &#233;t&#233; d&#233;verrouill&#233; !");
             echo gettext("(type d'erreur :")." $ReturnValue),".gettext(" veuillez contacter");
             echo "<A HREF='mailto:$MelAdminLCS?subject=Verrouillage du profil utilisateur $uid'>";
             echo gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
	  }
    }
  } else {
    	echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur de l'annuaire SE3 !")."</div>";
  }

include ("pdp.inc.php");
?>
