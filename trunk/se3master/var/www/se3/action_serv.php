<?php

   /**
   
   * Stop ou reboot le serveur  
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Philippe Chadefaux
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: alction_serv.php

  */	



require ("entete.inc.php");

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');



$action=$_GET['action'];

//aide 
$_SESSION["pageaide"]="L\'interface_web_administrateur#Action_serveur";


$texte_alert="Vous allez stopper ou redemarrer le serveur. Voulez vous vraiment continuer ?";
?>

<script type="text/javascript">


/**
* Demande confirmation avant
* @language Javascript
* @Parametres 
* @Return true si on confirme
* @Return false si on refuse
*/

function areyousure()
       {
       var messageb = "<?php echo "$texte_alert"; ?>";
       if (confirm(messageb))
               return true;
        else
                return  false;
      }
</script>

<?php															
if (ldap_get_right("se3_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BO
DY></HTML>");


echo "<h1>".gettext("Action sur le serveur")."</H1>";

if ($action == "stop")  {
	echo "<center>".gettext("Arr&#234;t du serveur en cours ...!");
	echo "<br>";
	echo gettext("Veuillez patienter ...");
	echo "</center>";
	exec("/usr/bin/sudo /usr/share/se3/scripts/start_stop_serv.sh stop");
}	
	
if ($action == "restart")  {
	echo "<center>".gettext("Red&#233;marrage du serveur en cours ...!");
	echo "<br>";
	echo gettext("Veuillez patienter ...");
	echo "</center>";
	exec("/usr/bin/sudo /usr/share/se3/scripts/start_stop_serv.sh restart");
}	



echo "<br><br>";
echo "<center>";
echo "<TABLE border=\"1\" width=\"80%\">";


/********************** Modules ****************************************************/

// Modules disponibles
echo "<TR><TD align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Actions disponibles");
echo "</TD></TR>";

echo "<TR><TD align=\"center\">";
	echo "<a href=action_serv.php?action=stop onClick=\"return areyousure('Vous allez stopper le serveur. Voulez vous vraiment continuer ?')\">";
	echo gettext("Stopper le serveur");
	echo "</a>";
echo "</td></tr>\n";


// Module Inventaire
echo "<TR><TD align=\"center\">";
	echo "<a href=action_serv.php?action=restart onClick=\"return areyousure('Vous allez red&#233;marrer le serveur. Voulez vous vraiment continuer ?')\">";
	echo gettext("Red&#233;marrer le serveur");
	echo "</a>";
echo "</td></tr>\n";

echo "</table></center>";

include("pdp.inc.php");
?>
