<?php


   /**
   
   * Page qui permet d'enregistrer le serveur la premiere fois que l'on se connecte
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs Olivier LECLUSE

   * @Licence Distribu&#233; selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: blank.php

  */	


require_once ("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

// Ajout traitement HTMLPurifier
require_once ("traitement_data.inc.php");

  $register=isset($_POST['register']) ? $_POST['register'] : "";
  $usage=isset($_POST['usage']) ? $_POST['usage'] : "";
  $srvcomm=isset($_POST['srvcomm']) ? $_POST['srvcomm'] : "";
  $typetab=isset($_POST['typetab']) ? $_POST['typetab'] : "";
  $dept=isset($_POST['dept']) ? $_POST['dept'] : "";
  $vernbr=isset($_POST['vernbr']) ? $_POST['vernbr'] : "";
  $rne=isset($_POST['rne']) ? $_POST['rne'] : "";
  
// Demande de s'enregistrer
if (isset ($register)) {
	if ($register == "yes") {

		// Verifie si proxy defini
		$proxy=exec("cat /etc/profile | grep http_proxy= | cut -d= -f2");
		if ($proxy != "") {
	        	preg_match("/http:\/\/(.*)\"/i",$proxy,$rest);
		        putenv("http_proxy=$rest[1]");
		}
   		$http=exec("wget -q -T 10 -O - http://wawadeb.crdp.ac-caen.fr && echo \$? ",$out,$retour);
      		if ($retour=="0") {
			require ("config.inc.php");
			require ("functions.inc.php");
			setparam("registred",3);
			header("location:http://wawadeb.crdp.ac-caen.fr/majse3/register.php?usage=".$usage."&srvcomm=".$srvcomm."&typetab=".$typetab."&dept=".$dept."&vernbr=".$vernbr."&rne=".$rne."");
            	} else {
			require ("entete.inc.php");
			echo "<h1>".gettext("Recensement du serveur")."</h1>";
			echo "<center<br><br><b>".gettext("Impossible de recenser le serveur</b><br>Il est possible que votre connexion internet ne soit pas fonctionnelle, ou bien que le serveur central soit momentan&#233;ment indisponible.<br><br>Veuillez r&#233;essayer une autre fois.<br>");
			echo "<br><br><a href=\"test.php\">".gettext("Voir l'&#233;tat de votre serveur")."</a></center>";
           	exit;
		}
	} 
//        else {
//	
//		require ("entete.inc.php");
//		echo "<H1>".gettext("Recensement du serveur")."</H1>\n";
//		setparam("registred",2);
//		echo gettext("Votre demande de ne pas participer au recensement des serveurs SambaEdu3 a &#233;t&#233; prise en compte. Aucune information n'a &#233;t&#233; envoy&#233;e.")."\n";
//		require ("pdp.inc.php");
//    		echo "<SCRIPT LANGUAGE=JavaScript>";
//		//echo "setTimeout('top.location.href=\"index.html\"',\"2000\")";
//		echo "</SCRIPT>";
//    
//		//exit;
//	}
}


require ("entete.inc.php");

if (($login == "admin")&&($registred <= 1)) {

	//require ("config.inc.php");
	if ($registred=="1") {
		echo "<H1><FONT color=red>".gettext("Mise a jour du recensement")."</FONT></H1>";
		echo gettext("Vous avez recens&#233; votre serveur SambaEdu et nous vous en remercions. Afin d'affiner nos statistiques, nous avons &#233;t&#233; amen&#233;s &#224; ajouter le champ num�ro RNE aux champs remont&#233;s dans notre base de donn&#233;e. Nous vous serions reconnaissants de bien vouloir compl&#233;ter &#224; nouveau le formulaire de recensement afin que nous puissions mettre a jour nos statistiques d'usage. D'avance, merci.");
	}

	echo "<H1>".gettext("Recensement du serveur")."</H1>\n";

	echo "<P>".gettext("F&#233;licitations, votre serveur SambaEdu est maintenant op&#233;rationnel. Afin d'avoir une id&#233;e du nombre d'&#233;tablissements qui utilise SambaEdu3, il est important que nous proc&#233;dions &#224; un recensement de ceux-ci. En remplissant le formulaire ci-dessous, vous nous aiderez &#224; mieux connaitre les conditions d'implantation de SambaEdu dans les &#233;tablissements.</P>");
	echo "<P>".gettext("Le renseignement de ce formulaire est facultatif. En cochant la case <STRONG>Je ne souhaite pas participer &#224; ce recensement</STRONG>, aucune donn&#233;e ne sera envoy&#233;e et vous ne serez plus sollicit&#233;.</P>");
	echo "<FORM action=\"blank.php\" method=\"post\">";
	echo "<INPUT TYPE=\"radio\" NAME=\"register\" VALUE=\"yes\" CHECKED>&nbsp;".gettext("Oui je souhaite recenser mon serveur.");

	echo "<TABLE ALIGN=\"center\" WIDTH=\"80%\">\n";
	echo "<TR>\n";
		echo "<TD WIDTH=\"50%\">".gettext("Ce serveur a vocation &#224; &#234;tre utilis&#233; ...")."</TD>\n";
		echo "<TD><SELECT SIZE=\"1\" NAME=\"usage\">";
			echo "<OPTION VALUE=\"1\">".gettext("En production")."</OPTION>";
			echo "<OPTION VALUE=\"2\">".gettext("En test")."</OPTION>";
			echo "<OPTION VALUE=\"3\">".gettext("Pour formation")."</OPTION>";
			echo "</SELECT>\n";
		echo "</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
		echo "<TD WIDTH=\"50%\">".gettext("Ce serveur est install&#233; ...")."</TD>";
		echo "<TD><SELECT SIZE=\"1\" NAME='typetab'>";
			echo "<OPTION VALUE=\"1\">".gettext("En lyc&#233;e")."</OPTION>";
			echo "<OPTION VALUE=\"2\">".gettext("En coll&#232;ge")."</OPTION>";
			echo "<OPTION VALUE=\"3\">".gettext("En &#233;cole")."</OPTION>";
			echo "<OPTION VALUE=\"4\">".gettext("Autre &#233;tablissement")."</OPTION>";
			echo "</SELECT>\n";
		echo "</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
		echo "<TD>".gettext("Ce serveur est utilis&#233; conjointement &#224; ...")."</TD>";
		echo "<TD><SELECT SIZE=\"1\" NAME=\"srvcomm\">";
			echo "<OPTION VALUE=\"Lcs\">".gettext("Un serveur de communication Lcs")."</OPTION>";
			echo "<OPTION VALUE=\"SLIS\">".gettext("Un serveur de communication SLIS")."</OPTION>";
			echo "<OPTION VALUE=\"other\">".gettext("Un serveur de communication autre")."</OPTION>";
			echo "<OPTION VALUE=\"None\">".gettext("Aucun serveur de communication")."</OPTION>";
			echo "</SELECT>\n";
		echo "</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
		echo "<TD>".gettext("N&#176; du d&#233;partement o&#249; ce serveur est implant&#233; (ou Code Pays &#224; l'ext&#233;rieur de la France)")."</TD>\n";
		echo "<TD><INPUT TYPE=\"text\" SIZE=\"3\"  MAXLENGTH=\"3\" NAME=\"dept\"></TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
		echo "<TD>".gettext("RNE de l'&#233;tablissement")."</TD>\n";
		echo "<TD><INPUT TYPE=\"text\" SIZE=\"8\"  MAXLENGTH=\"8\" NAME=\"rne\"></TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
	
	echo "<INPUT TYPE=\"radio\" NAME=\"register\" VALUE=\"no\" SELECTED>&nbsp;";
	echo gettext("Non je ne souhaite pas participer &#224; ce recensement.");
	echo "<INPUT TYPE=\"hidden\" NAME=\"vernbr\" VALUE=\"$majnbr\">";
	echo "<DIV ALIGN=\"center\"><INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\"></DIV>\n";
	echo "</FORM>\n";

	echo "<br><br>";
	require ("pdp.inc.php");

	exit;
}


if (ldap_get_right("se3_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BO
DY></HTML>");

if ($registred > 1) {


	// Ajout popup d'alerte
	include("fonc_outils.inc.php");
	
	entree_table_param_exist(url_popup_alert,"http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Alerte_popup.html",4,"Url du popup alerte");
	// $url="http://bcdi.crdp.ac-creteil.fr/alerte_popup.html";
	
	entree_table_param_exist(tag_popup_alert,0,4,"Tag du popup alerte");
	// On relit la table
	require ("config.inc.php");
	system("cd /tmp; wget -q --tries=1 --timeout=2 $url_popup_alert");
   	if (file_exists("/tmp/Alerte_popup.html")) {
        	$lines = file("/tmp/Alerte_popup.html");
	        foreach ($lines as $line_num => $line) {
			$line=trim($line);
			if(preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/","$line",$matche)) {
				// test la persence du tag precedent
				$tag_alerte=$matche[1].$matche[2].$matche[3];
				if ($tag_alerte==$tag_popup_alert) {
					$ok_alert="0";
				} else {	
	                        	$ok_alert="1";
				}	
	                }
	        }
	}												
	@unlink("/tmp/Alerte_popup.html");	
	if ($ok_alert=="1") {
		echo "<SCRIPT LANGUAGE=JavaScript>";
		echo "window.open(\"$url_popup_alert\",\"PopUp\",\"width=500,height=350,location=no,status=no,toolbars=no,scrollbars=no,left=100,top=80\")";
		echo "</SCRIPT>";
		
		// require ("functions.inc.php");
		setparam("tag_popup_alert",$tag_alerte);
	}
	// Fin popup

	echo "<h1>".gettext("Interface SambaEdu")."</h1>";

	echo "<BR><BR>";
	$nom=exec("/bin/hostname");

	$la=date("G:i:s d/m/Y");
	echo "<CENTER><TABLE border=1 width=\"60%\">";
	
	echo "<TR>\n";
	  echo "<TD class=\"menuheader\" height=\"30\" align=center colspan=\"5\">".gettext("Informations")."\n";

  	  echo "&nbsp;&nbsp;";
	
	  echo "<u onmouseover=\"return escape".gettext("('Cliquer ici pour voir plus d\'information et lancer un diagnostique.<br><b>Attention : </b> Cela peut &#234;tre gourmand en ressources et relativement long.<br><br>Vous pouvez remplacer cette page par d&#233;faut par la page de diagnostique, en activant l\'option Affichage de la page d\'&#233;tat  dans configuration g&#233;n&#233;rale.')")."\">";
	  echo "<a href=\"test.php\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/plus.png\" ALT=\"Voir plus\"></a>\n";
	echo "</u>\n";
	echo "</TD></TR>\n";
	echo "<TR><TD align=\"center\">";
	echo "$nom.$domain";
	echo "</TD></TR>\n";
	
	echo "<TR><TD align=\"center\">";
	echo "$la";
	echo "</TD></TR>\n";
	
	echo "<TR><TD align=\"center\">";
	$vers=exec("dpkg -s se3|grep Version|cut -d ' ' -f2");
	echo "Version $vers";
	echo "</TD></TR>\n";
	
	echo "</TABLE></CENTER>\n";
	require ("pdp.inc.php");
	
}

    
?>
