<?php

   /**
   
   * Permet de changer le niveau (debutant - ...)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs  oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: chlevel.php

  */	

require ("config.inc.php");
require ("functions.inc.php");

setintlevel($new_level);
header("Location:index.html");
?>
