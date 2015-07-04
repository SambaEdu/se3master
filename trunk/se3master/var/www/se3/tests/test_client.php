<?php

   /**
   
   * Page qui teste test le mot de passe root pour LDAP.
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_client.php
   */




require_once('entete_ajax.inc.php');
require_once('config.inc.php');

$cmd_smb="smbclient -L localhost -U adminse3%$xppass && echo \$?";
$samba_root=exec("$cmd_smb",$out,$retour2);
// echo "$cmd_smb";
	if ($retour2 == "0") {
		$ok="1";
	} else {
		$ok="0";
        }

die($ok);
?>
