<?php

   /**
   
   * Gestion de la cle public pour l'authentification  
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs JLC Jean Luc Chretien (Caen) 

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: save_keys.php
   */




include "config.inc.php";
include "functions.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
    $$cle = $val;
}

$login=isauth();
if ($login == "") header("Location:$urlauth");

if (is_admin("Annu_is_admin",$login)=="Y") {
	// Decodage de la chaine d'authentification cote serveur avec une cle privee
	exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$keys'",$AllOutPut,$ReturnValue);
	$tmp = preg_split ("/[\|\]/",$AllOutPut[0],5);
	$p = $tmp[0];
	$q = $tmp[1];
	$pq = $tmp[2];
	$d = $tmp[3];
	$e = $tmp[4];

	include("entete.inc.php");

	//aide 
	$_SESSION["pageaide"]="L\'interface_web_administrateur#Partie_:_Param.C3.A9trage_de_l.27interface_SambaEdu.";
       	
	echo "<h1>".gettext("Sauvegarde du nouveau jeu de cles d'authentification")."</h1>";
	if ( $p && $q && $pq && $d && $e ) {
        	// sauvegarde de la cle publique
        	$public_key="var public_key_e=[".$e."];\n";
        	$public_key.="var public_key_pq=[".$pq."];\n";
        	$fp=@fopen("public_key.js","w");
        	if($fp) {
                	fputs($fp,$public_key."\n");
                	fclose($fp);
                	// sauvegarde de la cle privee
                	$private_key="#[ [d], [p], [q] ]\n";
                	$private_key.="value=[[$d],[$p],[$q]]\n";
                	$fp=@fopen("includes/privateKey.py","w");
                
			if($fp) {
                        	fputs($fp,$private_key."\n");
                        	fclose($fp);

                        	echo "<div align='center'>".gettext("Votre nouvelle paire de cl&#233;s a &#233;t&#233; sauvegard&#233;e avec succ&#232;s.")."</div>\n";
				echo "<DIV class='alert_msg'><STRONG>".gettext("ATTENTION").":</STRONG> ".gettext("Vous devez vider le cache de votre navigateur afin que la nouvelle paire de clefs soit bien prise en compte")."</DIV>";
                	} else {
                        	echo "<div align='center'><b>".gettext("ERREUR")."</b> : ".gettext("Impossible de sauvegarder la nouvelle cl&#233; priv&#233;e.")."</div>\n";
                	}
        	} else {
                	echo "<div align='center'><b>".gettext("ERREUR")."</b> : ".gettext("Impossible de sauvegarder la nouvelle cl&#233; publique.")."</div>\n";
        	}
	} else {
        	echo "<div align='center'><b>".gettext("ERREUR")."</b> : ".gettext("Impossible de sauvegarder cette paire de cl&#233;s.")."</div>\n";
	}

} else {
        echo "<div class=alert_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur Se3 !")."</div>";
}

include ("includes/pdp.inc.php");
?>
