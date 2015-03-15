<?php

   /**
   
   * Page de deconnexion 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lecluse "wawa"
   * @auteurs jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs  oluve  olivier.le_monnier@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: logout.php
   */

session_start();

require ("config.inc.php");
require ("functions.inc.php");
unset($_SESSION['comptes_crees']) ;
close_session();
header("Location:auth.php?al=0");
?>
