<?php


   /**
   * Contient une fonction qui compare la date entre client et serveur et ouvre un popup en cas de diff
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Stephane Boireau (crob)
   
   * @Note Ce fichier doit etre appele par un include dans toutes les pages.

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: test_date.inc.php
   * @Repertoire: includes/ 
   */  
  



/**

* Fonctions qui compare la date et heure entre serveur et client et ouvre un popup.
	
* @Parametres
* @Return 
*/

function test_et_alerte_dates(){
	// Date du SE3:
	$aujourdhui = getdate();
	$mois_se3 = $aujourdhui['mon'];
	$jour_se3 = $aujourdhui['mday'];
	$annee_se3 = $aujourdhui['year'];
	$heure_se3 = $aujourdhui['hours'];
	$minute_se3 = $aujourdhui['minutes'];
	$seconde_se3 = $aujourdhui['seconds'];

	$timestamp_se3=time();

	echo "<script type='text/javascript'>
	// Date du SE3:
	var annee_se3=$annee_se3;
	var mois_se3=$mois_se3;
	var jour_se3=$jour_se3;
	var heure_se3=$heure_se3;
	var minute_se3=$minute_se3;
	var seconde_se3=$seconde_se3;

	// Date du client:
	var d = new Date();
	jour_client =d.getDate();
	mois_client =eval(d.getMonth() + 1);
	annee_client =d.getFullYear();
	heure_client =d.getHours();
	minute_client =d.getMinutes();
	seconde_client =d.getSeconds();

	// Timestamp du client et du serveur:
	timestamp_client=Math.floor((new Date()).getTime() / 1000);
	timestamp_se3=$timestamp_se3;

	// Test sur l'ecart entre les timestamp:
	test=Math.abs(timestamp_client-timestamp_se3);

	// Quelle est l'ecart minimum qui provoque les problemes de connexion?
	// Lors de mes tests, c'etait entre 3 et 4 minutes...
	if(test>200){
			alert('L\'heure du client et celle du serveur ne coïncident pas.\\nCela peut empêcher la connexion:\\nServeur: '+jour_se3+'/'+mois_se3+'/'+annee_se3+' '+heure_se3+':'+minute_se3+':'+seconde_se3+'\\n'+'Client:    '+jour_client+'/'+mois_client+'/'+annee_client+' '+heure_client+':'+minute_client+':'+seconde_client)
	}

	</script>\n";

	/*
	// Pour tester, effectuer:
	// # date --set='2 minutes ago'
	// Et pour retablir:
	// # ntpdate ntp.ac-creteil.fr
	*/
}
?>
