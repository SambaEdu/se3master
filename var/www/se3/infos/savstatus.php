<?php

   /**
   
   * Gestion de la sauvegarde sur bande
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: savstatus.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');



$action=$_GET['action'];
$newstatus=$_GET['newstatus'];
$newhome=$_GET['newhome'];
$newse3=$_GET['newse3'];
$newbande=$_GET['newbande'];
$newdevice=$_GET['newdevice'];
$newmail=$_GET['newmail'];
$newniveau=$_GET['newniveau'];
$form_action=$_GET['form_action'];

if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");



echo "<h1>".gettext("Sauvegarde sur bande")."</h1>\n";

echo "<center>";
if (isset($action)) {
	echo gettext("L'action demand&#233;e a &#233;t&#233; effectu&#233;e, ")."\n";
	echo "<br>\n";
	if ($action=="changestatus") {
		if($newstatus=="1") { echo "D&#233;sactive ou suspend la sauvegarde"; }
		if($newstatus=="0") { echo "Active la sauvegarde"; }
		setparam(savsuspend,$newstatus);
		$savsuspend=$newstatus;
	}	
	
	if ($action=="changehome") {
		setparam(savhome,$newhome);
		$savhome=$newhome;
	}	
	
	if ($action=="changese3") {
		setparam(savse3,$newse3);
		$savse3=$newse3;
	}	
	if ($action=="changebande") {
		echo "Bande $savbandnbr -> ".$newbande;
		setparam(savbandnbr,$newbande);
		$savbandnbr=$newbande;
	}	
	if ($action=="changedevice") { 
		setparam(savdevice,$newdevice);
		echo "$savdevice -> ".$newdevice;
		$savdevice=$newdevice;
	}

	if ($action=="changemail") { 
		setparam(melsavadmin,$newmail);
		echo "$melsavadmin -> ".$newmail;
		$melsavadmin=$newmail;
	}	

	if ($action=="changeniveau") { 
		setparam(savlevel,$newniveau);
		if ($newniveau=="0") { echo "incr&#233;mentielle -> compl&#233;te"; }
		if ($newniveau=="1") { echo "compl&#233;te -> incr&#233;mentielle"; }
		$savlevel=$newniveau;
	}	
}

echo "<br>";
echo "<TABLE border=\"1\" width=\"80%\">\n";
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Informations de param&#233;trage");
echo "</TD></TR>\n";
// parametrage du device
echo "<TR><TD>\n";
echo gettext("P&#233;riph&#233;rique de stockage : ");
echo "</TD><TD align=\"center\">\n";
if ($form_action == "modif_perif") {
	echo "<form method=\"get\" action=\"savstatus.php\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"changedevice\">\n";
	echo "<input type=\"text\" name=\"newdevice\"  value=\"$savdevice\">\n";
	echo "<input type=\"submit\" value=\"Ok\">\n";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le p&#233;riph&#233;rique de stockage sur bande.<br>Le plus souvent /dev/st0')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
	echo "</form>\n";
} else {
	if ($savdevice=="") {$savdevice="/dev/st0"; }
	echo "<u onmouseover=\"return escape".gettext("('Cliquer sur le device pour le modifier')")."\">\n";
	echo "<a href=savstatus.php?form_action=modif_perif>$savdevice</a>\n";
	echo "</u>";
}

echo "</TD></TR>\n";
echo "<TR><TD>\n";

// parametrage du mail
echo gettext("Mail responsable de la sauvegarde:");
echo "</TD><TD align=\"center\">\n";
if (($form_action == "modif_mail") || ($melsavadmin=="")) {
	echo "<form method=\"get\" action=\"savstatus.php\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"changemail\">\n";
	echo "<input type=\"text\" name=\"newmail\"  value=\"$melsavadmin\">\n";
	echo "<input type=\"submit\" value=\"Ok\">\n";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le mail de la personne en charge de la sauvegarde.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
	echo "</form>\n";
} else {
	 echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le mail de la personne en charge de la sauvegarde.')")."\">\n";
	echo "<a href=savstatus.php?form_action=modif_mail>$melsavadmin</a>\n";
	echo "</u>";
}	
echo "</TD></TR>\n";

// Niveau
echo "<TR><TD>\n";
echo gettext("Niveau actuel de la sauvegarde:");
echo "</TD><TD align=\"center\">";

if ($form_action == "modif_niveau") {
	echo "<form method=\"get\" action=\"savstatus.php\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"changeniveau\">\n";
	echo "<select name =\"newniveau\" ONCHANGE=\"this.form.submit();\">";
	echo "<option"; if ($savlevel=="0") { echo " selected"; } echo " value=\"0\">Compl&#233;te</option>";
	echo "<option"; if ($savlevel=="1") { echo " selected"; } echo " value=\"1\">Incr&#233;mentielle</option>";
	// echo "<option"; if ($savlevel=="2") { echo " selected"; } echo " value=\"2\">2</option>";
	echo "</select>\n";
	// echo "<input type=\"submit\" value=\"Ok\">\n";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le niveau.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
	echo "</form>\n";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le niveau.')")."\">\n";
	echo "<A HREF=\"savstatus.php?form_action=modif_niveau\">";
	if($savlevel=="0") { echo "Compl&#233;te"; }
	if($savlevel=="1") { echo "Incr&#233;mentielle"; }
	echo "</A>\n";
	echo "</u>\n";
}
echo "</TD></TR>\n";

//Bande
echo "<TR><TD>\n";
echo gettext("Bande");
echo "</TD><TD align=\"center\">";

if ($form_action == "modif_bande") {
	echo "<form method=\"get\" action=\"savstatus.php\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"changebande\">\n";
	echo "<select name =\"newbande\" ONCHANGE=\"this.form.submit();\">";
	echo "<option"; if ($savbandnbr=="0") { echo " selected"; } echo " value=\"0\">0</option>";
	echo "<option"; if ($savbandnbr=="1") { echo " selected"; } echo " value=\"1\">1</option>";
	echo "<option"; if ($savbandnbr=="2") { echo " selected"; } echo " value=\"2\">2</option>";
	echo "<option"; if ($savbandnbr=="3") { echo " selected"; } echo " value=\"3\">3</option>";
	echo "<option"; if ($savbandnbr=="4") { echo " selected"; } echo " value=\"4\">4</option>";
	echo "<option"; if ($savbandnbr=="5") { echo " selected"; } echo " value=\"5\">5</option>";
	echo "<option"; if ($savbandnbr=="6") { echo " selected"; } echo " value=\"6\">6</option>";
	echo "<option"; if ($savbandnbr=="7") { echo " selected"; } echo " value=\"7\">7</option>";
	echo "<option"; if ($savbandnbr=="8") { echo " selected"; } echo " value=\"8\">8</option>";
	echo "<option"; if ($savbandnbr=="9") { echo " selected"; } echo " value=\"9\">9</option>";
	echo "</select>\n";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le nun&#233;ro de la bande.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
	echo "</form>\n";
} else {
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici le num&#233;ro de la bande.')")."\">\n";
	echo "<A HREF=\"savstatus.php?form_action=modif_bande\">";
	echo "$savbandnbr";
	echo "</A>\n";
	echo "</u>\n";
}
echo "</TD></TR>\n";

// Etat de la sauvegarde
echo "<TR><TD colspan=\"2\" align=\"center\" class=\"menuheader\">\n";
echo gettext("Informations d'&#233;tat");
echo "</TD></TR>\n";
echo "<TR><TD>";
echo "Etat de la sauvegarde";
echo "</TD><TD align=\"center\">";
if ($savsuspend == 1) {
	echo " <A HREF=\"savstatus.php?action=changestatus&newstatus=0\">\n";
	echo "<u onmouseover=\"return escape".gettext("('La sauvegarde est inactive ou suspendue. Vous pouvez la basculer en <b>mode actif</b>  en cliquant sur ce bouton.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></u>\n";
	echo "</A>";	
} else {
	echo " <A HREF=\"savstatus.php?action=changestatus&newstatus=1\">\n";
	echo "<u onmouseover=\"return escape".gettext("('La sauvegarde est active. Vous pouvez la mettre en attente en cliquant sur ce bouton.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>\n";
	echo "</A>";	
}

// if (($savsuspend==0) && ($savse3<=1) && ($savhome==1)) echo gettext("La derni&#232;re sauvegarde s'est d&#233;roul&#233;e sans probl&#232;mes. Pensez &#224; changer la bande si vous voulez faire une rotation.");
echo "</TD></TR>\n";

// sauvegarde de home
echo "<TR><TD>";
echo "Sauvegarde de /home";
echo "</TD><TD align=\"center\">";
if ($savhome == 0) {
        echo "<A HREF=\"savstatus.php?action=changehome&newhome=1\">";
	echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /home est d&#233;sactiv&#233;e.<br><br>Cliquer sur le bouton pour la r&#233;activer.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/disabled.png\"></u>\n";
	echo "</A>\n";
}
if ($savhome == 1) {
        echo "<A HREF=\"savstatus.php?action=changehome&newhome=0\">";
        echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /home est planif&#233;e.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>\n";
	echo "</A>\n";
}
if ($savhome == 2) {
       echo "<A HREF=\"savstatus.php?action=changestatus&newstatus=0\">";
       echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /home est en cours, mais un changement de bande est n&#233;cessaire.<br><br>Ins&#233;rer la bande $savbandnbr, puis cliquer sur le bouton pour la r&#233;activer.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></u>\n";
	echo "</A>\n";
}
echo "</TD></TR>\n";

// Sauvegarde de /var/se3
echo "<TR><TD>";
echo "Sauvegarde de /var/se3";
echo "</TD><TD align=\"center\">";

if ($savse3 == 0) {
	echo "<A HREF=\"savstatus.php?action=changese3&newse3=1\">";
	echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /var/se3 est d&#233;sactiv&#233;e.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/disabled.png\"></u>\n";
	echo "</A>\n";
}
if ($savse3 == 1) {
	echo "<A HREF=\"savstatus.php?action=changese3&newse3=0\">";
        echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /var/se3 est planif&#233;e.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>\n";
	echo "</A>\n";
}
if ($savse3 == 2) {
       echo "<A HREF=\"savstatus.php?action=changestatus&newstatus=0\">";
       echo "<u onmouseover=\"return escape".gettext("('La sauvegarde de /var/se3 est en cours, mais un changement de bande est n&#233;cessaire.<br><br>Ins&#233;rer la bande $savbandnbr puis, cliquer sur le bouton pour la r&#233;activer.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></u>\n";
	echo "</A>\n";
}

echo "</TD></TR>\n";
echo "</TABLE>\n";
echo "</CENTER>\n";
echo "<BR><BR>\n";


// Affichage des logs si le fichier existe
$fichier_log="/var/log/se3/backup.log";
if (file_exists($fichier_log)) {
	echo "<H3>".gettext("Extrait du fichier de log de la sauvegarde...")."</H3>\n";
	echo "<PRE>\n";
	system ("tail /var/log/se3/backup.log");
	echo "</PRE>\n";
}

require ("pdp.inc.php");
?>
