<?php

   /**
   
   * Page d'avertissement  
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lecluse "wawa"
   * @auteurs jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: noryet.php
   */


require ("entete.inc.php");

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


print "<HR>";
mktable (gettext("Erreur..."), gettext("La fonction que vous demandez n'existe pas."));

require ("pdp.inc.php");
?>
