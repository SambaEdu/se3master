<?php

   /**
   
   * Affiche l'espace utilise sur le disque par repertoire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: du.php

  */	


require("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');


//aide 
$_SESSION["pageaide"]="Informations_syst&#232;me#Occupation_disque";

if ( is_admin("system_is_admin",$login)!="Y")
  if ( ($uid != $login) || (($uid == $login)&&((!preg_match("//home/$login/", $wrep))&&($consul!=1))))
     die ("<h1>Occupation disque</h1><br>".gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


$wrep = $_POST['wrep'];
if($wrep=="") { $wrep=$_GET['wrep']; }
if (isset($wrep)) {
	// test si l'entree est correcte
	if((is_dir($wrep)) || (is_file($wrep))) {
		echo "<H1>".gettext("Occupation de $wrep")."</H1>";
		$reponse=system("/usr/bin/sudo /usr/share/se3/scripts/du.sh $wrep"); 
		echo "$reponse";
		require("pdp.inc.php");
		exit;
	} else { $erreur="1"; }
}

?>
<SCRIPT Language="javascript" type="text/javascript">
  function setpath(rep)
  {
        document.forms[0].wrep.value=rep;
        document.forms[0].submit();
  }
</SCRIPT>
<H1><?php echo gettext("Occupation disque pour les partages"); ?></H1>

<H2><?php echo gettext("Choix du r&#233;pertoire &#224; analyser"); ?></H2>
<?php if ($erreur=="1") { 
	echo "<CENTER><font color=\"#FFA500\">".gettext("Erreur le r&#233;pertoire ou fichier n'est pas correct")."</font></CENTER><br><br>";
}	
echo gettext("Cliquez sur un r&#233;pertoire pr&#233;d&#233;fini, ou bien choisissez-en un autre en indiquant son chemin dans le champ pr&#233;vu &#224; cet effet."); ?>
<FORM ACTION="du.php" METHOD="post">
  <TABLE ALIGN="center" WIDTH="50%" BORDER="1">
  	<TR>
  		<TD><?php echo gettext("Dossier Programmes"); ?></TD>
  		<TD><IMG SRC="/elements/images/folder.png" BORDER="0" ALT="R&#233;pertoire">&nbsp;&nbsp;<A HREF="Javascript:setpath('/var/se3/Progs');">/var/se3/Progs</A></TD>
  	</TR>
  	<TR>
  		<TD><?php echo gettext("Dossier Documents"); ?></TD>
  		<TD><IMG SRC="/elements/images/folder.png" BORDER="0" ALT="R&#233;pertoire">&nbsp;&nbsp;<A HREF="Javascript:setpath('/var/se3/Docs');">/var/se3/Docs</A></TD>
  	</TR>
	<TR>
  		<TD><?php echo gettext("Dossier public"); ?></TD>
  		<TD><IMG SRC="/elements/images/folder.png" BORDER="0" ALT="R&#233;pertoire">&nbsp;&nbsp;<A HREF="Javascript:setpath('/var/se3/Docs/public');">/var/se3/Docs/public</A></TD>
  	</TR>
  	<TR>
  		<TD><?php echo gettext("Dossier Classes"); ?></TD>
  		<TD><IMG SRC="/elements/images/folder.png" BORDER="0" ALT="R&#233;pertoire">&nbsp;&nbsp;<A HREF="Javascript:setpath('/var/se3/Classes');">/var/se3/Classes</A></TD>
  	</TR>
  	
    <TR>
      <TD><?php echo gettext("Autre ..."); ?></TD>
      <TD><IMG SRC="/elements/images/folder.png" BORDER="0" ALT="R&#233;pertoire">&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="wrep" ></TD>
    </TR>
  </TABLE><BR>
  <TABLE ALIGN="CENTER" WIDTH="50%">
    <TR>
      <TD ALIGN="center"><INPUT TYPE="submit" VALUE="<?php echo gettext("Valider"); ?>"></TD>
      <TD ALIGN="center"><INPUT TYPE="reset" VALUE="<?php echo gettext("Effacer"); ?>"></TD>
    </TR>
  </TABLE>
</FORM>
<?php
	require ("pdp.inc.php");
?>
