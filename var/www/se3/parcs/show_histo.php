<?php
 

   /**
   
   * affiche l'historique des connexions 
   * @Version $Id: show_histo.php 8559 2015-03-15 13:08:23Z plouf $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs jLCF >:> jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: show_histo.php


   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "fonc_parc.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


$selectionne=isset($_GET['selectionne']) ? $_GET['selectionne'] : NULL;
$ipaddr=isset($_GET['ipaddr']) ? $_GET['ipaddr'] : NULL;
$cnx_start2=isset($_GET['cnx_start2']) ? $_GET['cnx_start2'] : NULL;
$cnx_start1=isset($_GET['cnx_start1']) ? $_GET['cnx_start1'] : NULL;
$cnx_start=isset($_GET['cnx_start']) ? $_GET['cnx_start'] : NULL;
$user=isset($_GET['user']) ? $_GET['user'] : NULL;
$mpenc=isset($_GET['mpenc']) ? $_GET['mpenc'] : NULL;

//aide
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Historique";

//debug_var();

// Affichage du formulaire de saisie d'adresse IP
echo "<H1>".gettext("Historique des connexions")."</H1>";
		
if ((is_admin("computers_is_admin",$login)=="Y") or (is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y")  or (is_admin("Annu_is_admin",$login)=="Y") or $login == $user) {
	
	if ((is_admin("computers_is_admin",$login)=="N") and ((is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y"))) {
                echo "<h3>".gettext("Votre d&#233;l&#233;gation a &#233;t&#233; prise en compte pour l'affichage de cette page.")."</h3>";
                $acces_restreint=1;
                $list_delegate=list_parc_delegate($login);
                if (count($list_delegate)>0) {
                        $delegate="yes";
		}
	} else {
		echo "<FORM action=\"show_histo.php\" method=\"GET\">\n";
		echo "<SELECT NAME=\"selectionne\" SIZE=\"1\" onchange=submit()>\n";

		echo "<option value=\"0\"";
		if ((!isset($selectionne)) || ($selectionne=="0")) { echo " selected";}
		echo ">".gettext("S&#233;lectionner\n");
		echo "<option value=\"1\" ";
		if ($selectionne=="1") { echo " selected"; }
		echo ">".gettext("Par adresse IP\n");
		echo "<option value=\"2\"";
		if ($selectionne=="2") { echo " selected"; }
		echo ">".gettext("Par nom de machine\n");
		echo "<option value=\"3\"";
		if ($selectionne=="3") { echo " selected"; }
		echo ">".gettext("Par utilisateur\n");
		echo "</SELECT>\n";
}	

	if ($selectionne=="1") {
		if (($ipaddr==$REMOTE_ADDR) || ($ipaddr=="")) { $IP=$REMOTE_ADDR; } else { $IP=$ipaddr; }
		echo " <INPUT TYPE=\"text\" NAME=\"ipaddr\"\n VALUE=\"$IP\" SIZE=\"12\">";
	} elseif ($selectionne=="2") {
		echo " <INPUT TYPE=\"text\" NAME=\"mpenc\" VALUE=\"$mpenc\" SIZE=\"12\">";
	} elseif ($selectionne=="3") {
		echo " <INPUT TYPE=\"text\" NAME=\"user\" VALUE=\"$user\" SIZE=\"12\">";
	}
	echo " <input type=\"submit\" value=\"".gettext("Valider")."\">\n";
	echo "</FORM>\n";
	echo "<BR>";
	echo "<HR>";

	
	// Si recherche sur adresse IP
    	if (($selectionne=="1") && (isset($ipaddr))) {
		// Affichage des renseignements sur la machine depuis la table connexions
		echo "<BR>";
		echo gettext("Table des connexions sur l'adresse IP")." <STRONG><FONT color='red'>$ipaddr</FONT></STRONG>\n";
		$smb_sess=smbstatus();
                foreach($smb_sess as $key=>$value){
                        $ips[$key]=$value['ip'];
                }
		$machine=array_search($ip_adrr, $ips);
		if (!$machine) echo "<P>".gettext("Aucune connexion en cours sur cette machine")."</P>\n";
		else {
			$login=$smb_sess[$machine]['login'];
			echo "<P><STRONG><FONT color='red'>$login</FONT></STRONG> ".gettext("est actuellement connect&#233; sur cette machine")."</P>\n";
		}

		if (! isset($cnx_start)) $cnx_start=0;
		echo "<P><STRONG>".gettext("Etat des 10 derni&#232;res connexions sur la machine")."</STRONG></P>\n";
		$query=" select * from connexions where ip_address='$ipaddr' order by id desc limit ";
		$query .= $cnx_start;
		$query .= ",10";

		$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
		if (($result)) {
			// Affichage des liens de navigation
			if ($cnx_start < 10) { $cnx_start1=0; $cnx_start2=$cnx_start+10; }
			else if (mysqli_num_rows($result)==0) { $cnx_start2=$cnx_start; $cnx_start1=$cnx_start-10; }
			else { $cnx_start1=$cnx_start-10; $cnx_start2=$cnx_start+10; }
			echo "<TABLE width='100%'><TR><TD WIDTH='50%'>";
			if ($cnx_start >= 10) echo "<A HREF='show_histo.php?ipaddr=$ipaddr&cnx_start=$cnx_start1&selectionne=1'><-- ".gettext("Voir les 10 connexions pr&#233;c&#233;dentes")."</A>&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "</TD><TD>";
			if ($cnx_start2 != $cnx_start) echo "<A HREF='show_histo.php?ipaddr=$ipaddr&cnx_start=$cnx_start2&selectionne=1'>".gettext("Voir les 10 connexions suivantes")." --></A>\n";
			echo "</TD></TR></TABLE>\n";
			// affichage de la table connexions
			echo "<TABLE  align='center' border='1'>\n";
			echo "<TR><TD  class='menuheader'>".gettext("Utilisateur connect&#233;")."</TD><TD  class='menuheader'>".gettext("Nom machine")."</TD><TD  class='menuheader'>".gettext("Date/Heure de connexion")."</TD><TD  class='menuheader'>".gettext("Date/Heure de d&#233;connexion")."</TD></TR>";
			while ($r=mysqli_fetch_array($result)) {
				//echo "<TR align='center'><TD>".$r["username"]."</TD>\n";
				echo "<TR align='center'><TD><A HREF='show_histo.php?selectionne=3&user=".$r["username"]."'>".$r["username"]."</A></TD>\n";
				//echo "<TD>".$r["netbios_name"]."</TD>\n";
				echo "<TD><A HREF='show_histo.php?selectionne=2&mpenc=".$r["netbios_name"]."'>".$r["netbios_name"]."</A></TD>\n";
				echo "<TD>".$r["logintime"]."</TD>\n";
				echo "<TD>".$r["logouttime"]."</TD></TR>\n";
			}
			echo "</TABLE>\n";
		} else echo gettext("erreur lors de la lecture de la base se3");

  	}




	// Affichage des renseignements sur l'utilisateur
	
    	if (($selectionne=="3") && (isset($user))) {
                $smb_sess=smbstatus();
		foreach($smb_sess as $key=>$value){
			$logins[$key]=$value['login'];
		}
                $machines=array_keys($logins, $user);
		$nbmach=count($machines);
		if ($nbmach>0) {
			echo "<P><STRONG><FONT color='red'>";
			echo $nbmach;
			// echo "</FONT></STRONG> connexion(s) en cours sous le login <STRONG><FONT color='red'>$user</FONT></STRONG> sur ";
			echo "</FONT></STRONG>".gettext(" connexion(s) en cours sous le login")." <A HREF='../annu/people.php?uid=$user'><STRONG><FONT color='red'><U>$user</U></FONT></STRONG></A>".gettext(" sur ");
 			foreach($machines as $machine) {
				if (is_admin("computers_is_admin",$login)=="Y")  echo "<A HREF='show_histo.php?selectionne=2&mpenc=".urlencode($machine)."'><U>$machine</U></A> ";
				else echo "<STRONG>$machine</STRONG> ";
			}
		} else {
			echo "<BR>";
			//echo "Aucune connexion en cours sous le login <STRONG><FONT color='red'>$user</FONT></STRONG>";
			echo gettext("Aucune connexion en cours sous le login");
			echo " <A HREF='../annu/people.php?uid=$user'><STRONG><FONT color='red'><U>$user</U></FONT></STRONG></A>";
		}
		echo "\n";

		if (! isset($cnx_start)) $cnx_start=0;
		echo "<P><STRONG>".gettext("Etat des 10 derni&#232;res connexions de l'utilisateur")." $user</STRONG></P>\n";
		$query=" select * from connexions where username='$user' order by id desc limit ";
		$query .= $cnx_start;
		$query .= ",10";

		$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
		if (($result)) {
			// Affichage des liens de navigation
			if ($cnx_start < 10) { $cnx_start1=0; $cnx_start2=$cnx_start+10; }
			else if (mysqli_num_rows($result)==0) { $cnx_start2=$cnx_start; $cnx_start1=$cnx_start-10; }
			else { $cnx_start1=$cnx_start-10; $cnx_start2=$cnx_start+10; }
			echo "<TABLE width='100%'><TR><TD WIDTH='50%'>";
			if ($cnx_start >= 10) echo "<A HREF='show_histo.php?user=$user&cnx_start=$cnx_start1&selectionne=3'><-- ".gettext("Voir les 10 connexions pr&#233;c&#233;dentes")."</A>&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "</TD><TD>";
			if ($cnx_start2 != $cnx_start) echo "<A HREF='show_histo.php?user=$user&cnx_start=$cnx_start2&selectionne=3'>".gettext("Voir les 10 connexions suivantes")." --></A>\n";
			echo "</TD></TR></TABLE>\n";
			// affichage de la table connexions
			echo "<TABLE  align='center' border='1'>\n";
			echo "<TR><TD class='menuheader'>".gettext("Nom machine")."</TH><TD class='menuheader'>".gettext("Adresse IP")."</TH>";
			echo "<TD class='menuheader'>".gettext("Date/Heure de connexion")."</TH><TD class='menuheader'>".gettext("Date/Heure de d&#233;connexion")."</TH></TR>";
			while ($r=mysqli_fetch_array($result)) {
				// echo "<TR align='center'><TD>".$r["netbios_name"]."</TD>\n";
				echo "<TR align='center'><TD>";
				if (is_admin("computers_is_admin",$login)=="Y") { echo "<A HREF='show_histo.php?selectionne=2&mpenc=".$r["netbios_name"]."'>"; }
				echo $r["netbios_name"];
				if (is_admin("computers_is_admin",$login)=="Y") echo "</A>";
				echo "</TD>\n";
				//echo "<TD>".$r["ip_address"]."</TD>\n";
				echo "<TD>";
				if (is_admin("computers_is_admin",$login)=="Y") { echo "<A HREF='show_histo.php?selectionne=1&ipaddr=".$r["ip_address"]."'>"; }
				echo $r["ip_address"];
				if (is_admin("computers_is_admin",$login)=="Y") {"</A>"; }
				echo "</TD>\n";
				echo "<TD>".$r["logintime"]."</TD>\n";
				echo "<TD>".$r["logouttime"]."</TD></TR>\n";
			}
			echo "</TABLE>\n";
		} else echo gettext("erreur lors de la lecture de la base se3");
	}


	// Affichage par nom de la machine
	
	// Affichage des renseignements sur la machine depuis la table connexions
	
    	if (($selectionne=="2") && (isset($mpenc))) {
        echo "<p><strong>$mpenc</strong> <a href='cherche_machine.php?mpenc=$mpenc' title='Voir les parcs de la machine.'><img src='../elements/images/computer.png' /></a></p>";
		$mp=urldecode($mpenc);
		$mp_curr=search_machines("(&(cn=$mp)(objectClass=ipHost))","computers");
		echo "<P><STRONG>".gettext("Adresse IP inscrite dans l'annuaire:")." </STRONG><FONT color='red'>".$mp_curr[0]["ipHostNumber"]."</FONT></P>\n";
                $smb_sess=smbstatus();
                $login=$smb_sess[$mp]['login'];

		if ($login=="") echo "<P>".gettext("Aucune connexion en cours sur cette machine")."</P>\n";
		else {
			echo "<P><STRONG><FONT color='red'><A HREF='show_histo.php?selectionne=3&user=$login'>$login</A></FONT></STRONG>".gettext(" est actuellement connect&#233; sur cette machine")."</P>\n";
		}

		if (! isset($cnx_start)) $cnx_start=0;
		echo "<P><STRONG>".gettext("Etat des 10 derni&#232;res connexions sur la machine")."</STRONG></P>\n";
		$query=" select * from connexions where netbios_name='$mp' order by id desc limit ";
		$query .= $cnx_start;
		$query .= ",10";
		
		$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
		if (($result)) {
			// Affichage des liens de navigation
			if ($cnx_start < 10) { $cnx_start1=0; $cnx_start2=$cnx_start+10; }
			else if (mysqli_num_rows($result)==0) { $cnx_start2=$cnx_start; $cnx_start1=$cnx_start-10; }
			else { $cnx_start1=$cnx_start-10; $cnx_start2=$cnx_start+10; }
			echo "<TABLE width='100%'><TR><TD WIDTH='50%'>";
			if ($cnx_start >= 10) echo "<A HREF='show_histo.php?mpenc=$mpenc&cnx_start=$cnx_start1&selectionne=2'><-- ".gettext("Voir les 10 connexions pr&#233;c&#233;dentes")."</A>&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "</TD><TD>";
			if ($cnx_start2 != $cnx_start) echo "<A HREF='show_histo.php?mpenc=$mpenc&cnx_start=$cnx_start2&selectionne=2'>".gettext("Voir les 10 connexions suivantes")." --></A>\n";
			echo "</TD></TR></TABLE>\n";
			// affichage de la table connexions
			echo "<TABLE  align='center' border='1'>\n";
			echo "<TR><TD class='menuheader'>".gettext("Utilisateur connect&#233")."</TD><TD class='menuheader'>".gettext("Adresse IP")."</TD><TD class='menuheader'>".gettext("Date/Heure de connexion")."</TD><TD class='menuheader'>".gettext("Date/Heure de d&#233;connexion")."</TD></TR>";
			while ($r=mysqli_fetch_array($result)) {
				// echo "<TR align='center'><TD>".$r["username"]."</TD>\n";
				echo "<TR align='center'><TD><A HREF='show_histo.php?selectionne=3&user=".$r["username"]."'>".$r["username"]."</A></TD>\n";
				//echo "<TD>".$r["ip_address"]."</TD>\n";
				echo "<TD><A HREF='show_histo.php?selectionne=1&ipaddr=".$r["ip_address"]."'>".$r["ip_address"]."</A></TD>\n";
				echo "<TD>".$r["logintime"]."</TD>\n";
				echo "<TD>".$r["logouttime"]."</TD></TR>\n";
			}
			echo "</TABLE>\n";
		} else echo gettext("erreur lors de la lecture de la base se3");
		
  	} 



} else {
	echo "Vous n'avez pas les droits pour acc&#233;der &#224; cette page";
}


include ("pdp.inc.php");
?>
