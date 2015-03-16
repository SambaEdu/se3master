<?php


   /**
   
   * Export un ldif de l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: export.php
   */



	require ("config.inc.php");
	include "functions.inc.php";
	include "ihm.inc.php";
	include "ldap.inc.php";
        
        // HTMLPurifier
        require_once ("traitement_data.inc.php");
        
	$login=isauth();
	if ($login == "") header("Location:$urlauth");

	if (is_admin("se3_is_admin",$login)=="Y") {
		if (isset($_POST['filtre'])) {
			$filtre=$_POST['filtre'];
			if ($filtre == "") $filtre = "objectclass=*";
			system("ldapsearch -xLLL -h $ldap_server -D \"$adminRdn,$ldap_base_dn\" -w $adminPw $filtre > /tmp/export.ldif");
			header("Content-Type: octet-stream");
			header("Content-Length: ".filesize ("/tmp/export.ldif") );
			header("Content-Disposition: attachment; filename=\"/tmp/export.ldif\"");
			include ("/tmp/export.ldif");
		}
	}
?>
