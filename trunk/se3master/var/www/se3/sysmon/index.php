<?php


   /**
   
   * Affiche la page MRTG
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: sysmon/
   * file: index.php
   */	

include ("entete.inc.php");
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

//aide
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Tableau_de_bord";

$login=isauth();
if (is_admin("se3_is_admin",$login)=="Y") {
    ?>
	

    <h1>MRTG Index Page</h1>
    <!-- Command line is easier to read using "View Page Properties" of your browser -->
    <!-- But not all browsers show that information. :-(                             -->
    <META NAME="Command-Line" CONTENT="/usr/bin/indexmaker --output=/var/www/se3/21d51d/sysmon/index.html /etc/mrtg.cfg">
    <META HTTP-EQUIV="Refresh" CONTENT="300">
    <META HTTP-EQUIV="Cache-Control" content="no-cache">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <META HTTP-EQUIV="Expires" CONTENT="Fri, 28 Jul 2006 10:37:26 GMT">
<style type="text/css">
<!--
/* commandline was: /usr/bin/indexmaker --output=/var/www/se3/21d51d/sysmon/index.html /etc/mrtg.cfg */
/* sorry, no style, just abusing this to place the commandline and pass validation */
-->
</style>
</HEAD>



<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=10>
<tr>
<td><DIV><B>eth0 - Trafic Ethernet SE3 </B></DIV>
<DIV><A HREF="se3-eth0.php"><IMG BORDER=1 ALT="se3-eth0 Traffic Graph" SRC="se3-eth0-day.png"></A><BR>
<SMALL><!--#flastmod virtual="se3-eth0.php" --></SMALL></DIV>
</td><td><DIV><B>Charge CPU SE3 </B></DIV>
<DIV><A HREF="se3-cpu.php"><IMG BORDER=1 ALT="se3-cpu Traffic Graph" SRC="se3-cpu-day.png"></A><BR>
<SMALL><!--#flastmod virtual="se3-cpu.php" --></SMALL></DIV>
</td></tr>
<tr>
<td><DIV><B>Usage memoire serveur se3 </B></DIV>
<DIV><A HREF="se3_mem.php"><IMG BORDER=1 ALT="se3_mem Traffic Graph" SRC="se3_mem-day.png"></A><BR>
<SMALL><!--#flastmod virtual="se3_mem.php" --></SMALL></DIV>
</td><td><DIV><B>Charge Samba </B></DIV>
<DIV><A HREF="se3_smb.php"><IMG BORDER=1 ALT="se3_smb Traffic Graph" SRC="se3_smb-day.png"></A><BR>
<SMALL><!--#flastmod virtual="se3_smb.php" --></SMALL></DIV>
</td></tr>
<tr>
<td></td>
</tr>
</TABLE>

<BR>

<?php
}

include ("pdp.inc.php");
?>
