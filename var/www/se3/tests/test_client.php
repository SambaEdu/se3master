<?php

   /**
   
   * Page qui teste test le mot de passe root pour LDAP.
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposées par Sébastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_client.php
   */




require_once('entete_ajax.inc.php');
copy("/var/se3/Progs/install/installdll/confse3.ini", "/tmp/confse3.ini");
exec ("dos2unix /tmp/confse3.ini");
$compte=exec("cat /tmp/confse3.ini | grep password_ldap_domain | cut -d= -f2",$out,$retour);
unlink("/tmp/confse3.ini");
$cmd_smb="smbclient -L localhost -U adminse3%$compte && echo \$?";
$samba_root=exec("$cmd_smb",$out,$retour2);
// echo "$cmd_smb";
	if ($retour2 == "0") {
		$ok="1";
	} else {
		$ok="0";
        }

die($ok);
?>
