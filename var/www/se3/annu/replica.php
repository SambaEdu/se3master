<?php


   /**
   
   * Met en place la replication d'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo /usr/share/se3/scripts/mkSlapdConf.sh
   */

   /**

   * @Repertoire: annu
   * file: replica.php
   */




require "entete.inc.php";
require "ihm.inc.php";
require "config.inc.php";
 
require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');
      
if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$texte_alert=gettext("Attention vous risquez de perdre le contrôle de votre serveur. Voir la documentation avant de mettre cela en oeuvre. Etes vous sure de vouloir continuer ?");

?>
<script language="JavaScript">
	
	
	/**
	* Affiche une boite de dialogue pour demander confirmation
	* @language Javascript
	* @Parametres
	* @return 
	*/

	function areyousure()
	{
		var messageb = "<?php echo "$texte_alert"; ?>";
		if (confirm(messageb))
			return true;
		else
			return	false;
	}
</script>

<?php
// Aide
$_SESSION["pageaide"]="R%C3%A9plication_d%27annuaires";

echo "<h1>".gettext("R&#233;plication de l'annuaire LDAP")."</h1>";


// *******************************//
$action = $_POST['action'];
$replica = $_POST['replica'];
$ip = $_POST['ip'];
$syncrepl = $_POST['syncrepl'];


// ######################################################################
// ################### Creation du champ manquant #########################

$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT name from params where name='replica_status'");
$num = mysqli_num_rows( $result);
if ($num == "0" ) {
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params set id='NULL', name='replica_status', value='0', srv_id='0',descr='Etat du serveur de r&#233;plication',cat='4'");
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set descr='Adresse du serveur Lcs ou Slis (optionnel)' where name='lcsIp'");
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "INSERT into params set id='NULL', name='replica_ip', value='', srv_id='0',descr='Adresse IP du serveur de r&#233;plication',cat='4'");

}

// ###################### FIN #############################################

// Si replica est vide
if ($action != "") {
	if ($replica == "") {
		$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_status'");
		if ($result)
	    		while ($r=mysqli_fetch_array($result)) {
	       			$replica=$r[2];
			}
	}
}

// Validation de la page
if ($action == "Ok" || $replica=="0") { 
	// Test la connexion au serveur LDAP maitre ou esclave
	if ($replica == "1" || $replica == "2" || $replica == "3" || $replica == "4") {
		// test la validite de l'ip
		if (!is_string($ip)) { $ok = 0;}
		$ip_long = ip2long($ip);
		$ip_revers = long2ip($ip_long);
		if ($ip != $ip_revers) { $ok = 0; }
	
		$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='adminRdn'");
		if ($result)
    			while ($r=mysqli_fetch_array($result)) {
        			$adminRdn=$r[2];
			}
	
		$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='adminPw'");
		if ($result)
 	   		while ($r=mysqli_fetch_array($result)) {
        			$adminPw=$r[2];
			}

		$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='ldap_base_dn'");
		if ($result)
    			while ($r=mysqli_fetch_array($result)) {
        			$basedn=$r[2];
			}
		
		$admin_dn="$adminRdn,$basedn";

		$ldapconn = ldap_connect("$ip");
		if ($ldapconn) {    //Connexion au serveur LDAP   
			$ldapbind = @ldap_bind($ldapconn, $admin_dn, $adminPw);    // Identification    
			if ($ldapbind) {        
				$ldap_ok="1";  // Connexion LDAP reussie
			} else {
				$ldap_ok="0"; // Connexion LDAP echouee
			}
		} else {
			$ldap_ok="0";
		}
	}

	//Si pas d'erreurs on peut modifier dans la table et lancer le script 
	if ($ok != "0" && $ldap_ok != "0") {
		
		//Lance le script mkslapd
		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='ldap_server'");
		if ($resultat)
  	  		while ($r=mysqli_fetch_array($resultat)) {
        			$IP_ldap=$r[2];
			}
		//On verifie l'etat anterieur avant de modifier		
		$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_status'");
		if ($result)
	    		while ($r=mysqli_fetch_array($result)) {
	       			$maitre=$r[2];
			}
		if ($maitre=="2" || $maitre=="4") { // etait en esclave avant on recupere l'adresse ip du maitre	
			$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_ip'");
		   	if ($result)
	    	   		while ($r=mysqli_fetch_array($result)) {
	       				$ip_maitre=$r[2];
				}
		} else { // sinon son adresse IP 
			$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='ldap_server'");
		   	if ($result)
				while ($r=mysqli_fetch_array($result)) {
	        			$ip_maitre=$r[2];
				}
		}
			
		if ($replica == "0") {
			$ip = "";
			$options = "-e";
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set cat='2' where name='ldap_server'");
			//Si on etait esclave avant on doit changer l'IP

			if ($maitre == $replica) {
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='' where name='replica_ip'");
//			}
//			elseif ($maitre=="1" || $maitre=="3") {
//		       		$resultat=mysql_query("UPDATE params set value='$ip_maitre' where name='ldap_server'");
//		       		$resultat=mysql_query("UPDATE params set value='$ip' where name='replica_ip'");
				
			} else {	
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip_maitre' where name='ldap_server'");
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='replica_ip'");
			}
		}

		if ($replica == "1" || $replica == "3") {
			//si pas de compte indique on utilise le compte AdmRdn et le MdP AdmPw
			$options = "-c -m";  
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set cat='4' where name='ldap_server'");
	 		if ($maitre == $replica) {
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='replica_ip'");
			}
			elseif ($maitre=="2") {
		       		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip_maitre' where name='ldap_server'");
   				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='replica_ip'");
			} else {
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='replica_ip'");
		   		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip_maitre' where name='ldap_server'");
			}
		}
			
		if ($replica == "2" || $replica == "4") {	
			//si pas de compte indique on utilise le compte AdmRdn et le MdP AdmPw
			$options = "-c -s";  
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set cat='4' where name='ldap_server'");
			if ($maitre == $replica) {
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='ldap_server'");
			} else {
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip_maitre' where name='replica_ip'");
				$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$ip' where name='ldap_server'");
			}
		}


		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='$replica' where name='replica_status'");
			
		// Lancement des scripts
		exec ("/usr/bin/sudo /usr/share/se3/scripts/mkSlapdConf.sh");	
	} else {
		if ($ok == "0") {
	   		echo "<font color=\"rouge\">".gettext("L'adresse IP n'est pas conforme ou absente")."</font><br>";
		}
		if ($ldap_ok == "0") {
			echo "<font color=\"rouge\">".gettext("Impossible de se connecter au serveur LDAP distant")."</font>";
		}
	}
}

// Interface
$replica_status="$replica";
if ($replica == "") {	
	$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_status'");
	if ($result)
    		while ($r=mysqli_fetch_array($result)) {
        		$replica_status=$r[2];
		}
}


// Verification si l'annuaire est deporte
$nom_se3=exec('/bin/hostname');
$ip_se3=gethostbyname($nom_se3);
if (($ip_se3 == $ldap_server) || ($ldap_server == "localhost") || ($ldap_server == "127.0.0.1")) {

	$ldap_deport = "no";
} else {
	//cas ou il est en esclave
	if($replica_ip!="") {
		$ldap_deport = "no";
	} else {	
		$ldap_deport = "yes";
	}	
}	

?>

<H3><?php echo gettext("Mise en place de la r&#233;plication de l'annuaire"); ?></H3>

  <form name = "auth" action="replica.php" method="post">
      <table border="0" width="90%">
	  <tbody>
	    <tr>
		<td colspan=3>
		<font color="orange"><center><?php echo gettext("Attention, la mise en place de la r&#233;plication peut provoquer la perte de votre annuaire, <br>Il est vivement conseill&#233; de faire une sauvegarde de celui-ci avant toute modification."); ?></center></font><br><br>
		<?php
		if ($ldap_deport=="yes") {
		?>
		<font color="orange"><center><?php echo gettext("Votre annuaire est actuellement d&#233;port&#233; sur une autre machine. Il risque donc de ne contenir aucune entr&#233;e. Vous ne pouvez pas en cons&#233;quence le placer comme serveur d'annuaire ma&#238;tre"); ?></center></font><br><br>
		<?php } ?>
		</td>
	    </tr>
	    <tr>
	      <td><?php echo gettext("Etat du serveur"); ?>&nbsp; :&nbsp;</td>
	      <td>
		<input type="hidden" name="action" value="SUB">
                <select name="replica" onchange=submit()>
		  <?php 
		  // Cas ou l'annuaire est deporte
		  if ($ldap_deport=="no") {
		  ?>
	          <option <?php if ($replica_status == "0") {echo "selected"; } ?> value="0"><?php echo gettext("Serveur non r&#233;pliqu&#233;"); ?></option>
		  // Cas ou l'annuaire est deporte
		 if ($ldap_deport=="no") {
		?>
		  <option <?php if ($replica_status == "3") {echo "selected"; } ?> value="3"><?php echo gettext("Serveur LDAP principal (m&#233;thode syncrepl)"); ?></option>
		  <?php } ?>
		  <option <?php if ($replica_status == "4") {echo "selected"; } ?> value="4"><?php echo gettext("Serveur LDAP secondaire (m&#233;thode syncrepl)"); ?></option>
		</select>
	      </td>
              <td>
<?php
if ($ldap_deport=="no") {
echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Vous pouvez choisir de mettre en place une r&#233;plication, ou de la supprimer pour votre annuaire LDAP.<br>Le but est de disposer d\'un second annuaire sur une seconde machine.<br> Pour cela vous avez quatre possibilit&#233;s :<br><br><b>Serveur LDAP principal (ou ma&#238;tre)</b><br>Cette machine dispose de l\'annuaire de r&#233;ferrence. Les autres machines, ne disposeront que d\'une r&#233;plique. Seule les modifications faites sur l\'annuaire de cette machine seront prises en compte.<br><br><b>Serveur LDAP secondaire (ou esclave)</b><br>Cette machine ne disposera que d\'une r&#233;plique de l\'annuaire du serveur principal.<br><br><b>Serveur principal (m&#233;thode syncrepl)</b><br>M&#234;me chose que pour le serveur LDAP principal, mais la m&#233;thode change. Celle-ci est plus performante que la pr&#233;c&#233;dente, mais ne peut fonctionner avec tous les serveurs. Voir la documentation.<br><br><b>Serveur secondaire (m&#233;thode syncrepl)</b><br>Idem, mais attention cette option d&#233;truit compl&#233;tement l\'annuaire local (il est sauvegard&#233; automatiquement dans /var/se3/save).<br><br><b>Attention</b><br>Toutes les entr&#233;es existant dans cet annuaire et n\'existant pas sur le ma&#238;tre seront perdues.<br><br>Si vous souhaitez d&#233;porter l\'annuaire sur une autre machine, il vous faut aller dans le mode sans &#233;chec, et indiquer l\'adresse IP du serveur disposant de votre annuaire.<br><br><b>Il est vivement conseill&#233; de faire une sauvegarde de votre annuaire avant de faire des modifications.</b> ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
} else {

echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Votre annuaire LDAP est actuellement d&#233;port&#233; sur un autre serveur. Il est possible qu\'il ne contienne aucune entr&#233;e. Vous ne pouvez donc pas le d&#233;finir comme ma&#238;tre, au risque d\'en perdre le contr&#244;le.<br>Pour le reconstruire compl&#233;tement &#224; partir de votre serveur LDAP actuel, placez vous en esclave m&#233;thode syncrepl et l\'autre serveur en ma&#238;tre. Il vous sera alors possible de le rebasculer en ma&#238;tre par la suite.<br><b>Attention : toute modification sur l\'annuaire peut avoir des cons&#233;quences importantes. Il est donc conseill&#233; de le sauvegarder avant.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
}
?>

              </td>
	    </tr>
   </form>	
   <form name="truc" action="replica.php" method="post" onSubmit="return areyousure()">

<?php

// Affichage si on a un esclave ou un maitre
if ($replica == "1" || $replica == "2" || $replica_status=="1" || $replica_status=="2" || $replica == "3" || $replica == "4" || $replica_status == "3" || $replica_status == "4") {
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_ip'");
	
	if ($resultat)
  		while ($r=mysqli_fetch_array($resultat)) {
        		$replica_ip=$r[2];
		}   
	
	echo "<tr>\n";

	// recup l'adresse IP de l'esclave dans la base sql
	if($replica== "1" || $replica_status=="1" || $replica == "3" || $replica_status=="3") {
		echo"<td>".gettext("Adresse IP du serveur esclave")." :&nbsp;</td>";
		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_ip'");
		if ($resultat)
  			while ($r=mysqli_fetch_array($resultat)) {
       		 		$replica_ip=$r[2];
			}
	}
	
	// recup l'adresse IP du maitre dans la base sql	      
	if($replica== "2" || $replica_status=="2" || $replica == "4" || $replica_status == "4") {
		echo"<td>".gettext("Adresse IP du serveur ma&#238;tre")."  :&nbsp;</td>";
		$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='ldap_server'");
		if ($resultat)
  			while ($r=mysqli_fetch_array($resultat)) {
       		 		$replica_ip=$r[2];
			}

	}
			
	// Pour vider l'adresse IP en cas de re-select
	if ($action=="SUB") {
		$replica_ip="";
	}	
?>
			
	<td><input type="text" name="ip" value="<?php echo "$replica_ip"; ?>" size="20"></td>
        <td>

<?php
	echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Adresse IP du serveur ma&#238;tre</b><br>Vous devez indiquer l\'adresse IP du serveur LDAP ma&#238;tre.<br><br><b>Adresse IP du serveur esclave</b><br>Vous devez indiquer l\'adresse IP du serveur esclave.<br><br><b>Attention</b> Afin de pouvoir mettre en place la r&#233;plication, il faut que le serveur distant (ma&#238;tre ou esclve) soit joignable.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
	echo "</td>\n";
	echo "</tr>\n";


?>		

		<tr>
			<td></td>
	      		<td align="left">
			<?php
	      		echo"<input type=\"hidden\" name=\"replica\" value=\"$replica\">";
			?>
			<input type="hidden" name="action" value="Ok">
			<input type="submit" value="<?php echo gettext("Modifier"); ?>">
                      </td>
		      <td></td>
		    </tr>
		<?php } ?>    
	  </tbody>
        </table>
      </form>

	<?php

//log l'etat de la replication

$result=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * from params where name='replica_status'");
if ($result)
    	while ($r=mysqli_fetch_array($result))
    		{
        	$status=$r[2];
		}

if ($status == "1" || $status == "2" || $status == "3" || $status == "4") {
	if ($status == "1" || $status == "3") { $options = "m"; }
	if ($status == "2" || $status == "4") { $options = "s"; }
?>
	<br>
	<H3><?php echo gettext("Contr&#244;ler et synchroniser les annuaires"); ?></H3>
  	<form action="replica_log.php" method="post">
	  <input type="hidden" name="ip" value=<?php echo "$replica_ip"; ?>>
	  <input type="hidden" name="status" value=<?php echo"$options"; ?>>
	  <input type="hidden" name="action" value="ok">

	  <table border="0" width="70%">
	   <tbody>
	    <tr>
	       <td align='left'><input type="radio" name="type" value="anonymous" checked="on"></td>
	       <td><?php echo gettext("Comparer les deux annuaires"); ?></td>
		<td>
		<?php
		echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Compare les annuaires entre le serveur principal (ma&#238;tre) et le serveur secondaire (esclave).<br><br>Cette fonction ne modifie  aucun des deux annuaires. ')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
		?>
		</td>	
	   </tr>
	   <tr>
	       <td align="center" colspan="3" height="51"><font color="orange"><?php echo gettext("Il est vivement conseill&#233; pour les deux autres choix de faire un export LDAP avant"); ?></font></td>
	   <tr>
	   
	       <td align='left'><input type="radio" name="type" value="only_pass"></td>
	      <td><?php echo gettext("Ajouter les entrees manquantes et synchroniser les mots de passe (par rapport au ma&#238;tre)"); ?></td>
		<td>
		<?php
		echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Cette option va modifier les entr&#233;es de l\'annuaire secondaire (esclave) en modifiant les entr&#233;es qui ne sont pas identiques, entre le ma&#238;tre et l\'esclave.<br> Les entr&#233;es du serveur secondaire (esclave) seront modifi&#233;es.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
		?>
		</td>
	   </tr>
	   <tr>

	       <td align='left' height="51"><input type="radio" name="type" value="full"></td>
	       <td><?php echo gettext("Synchroniser la totalit&#233; des deux annuaires (par rapport au ma&#238;tre)"); ?></td>
		<td>
		<?php
		echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Cette option va modifier les entr&#233;es de l\'annuaire secondaire (esclave) en modifiant les entr&#233;es qui ne sont pas identiques entre le ma&#238;tre et l\'esclave, et en ajoutant les entr&#233;es manquantes  et en supprimant les entr&#233;es en trop dans l\'esclave.<br><br><b>Les entr&#233;es du serveur secondaire (esclave) seront modifi&#233;es, voire supprim&#233;es</b>.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>\n";
		?>
	       </td>
	   </tr>		
	   <tr>
	       
	       <td align="center" colspan='2'><input type="submit" value="<?php echo gettext("Contr&#244;ler la r&#233;plication"); ?>"><td>
	   </tr>
	  </tbody>
	</table>  
   </form>	
<?php
} 
  
include "pdp.inc.php";
?>
