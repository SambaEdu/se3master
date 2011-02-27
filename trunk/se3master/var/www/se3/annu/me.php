<?php


   /**
   
   * Affiche la page utilisateur a partir de l'annuaire
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
   * file: me.php
   */





	require ("config.inc.php");
	require ("functions.inc.php");
	$login=isauth();
	if ($login == "") header("Location:$urlauth");
	else header("Location:people.php?uid=$login");

?>
