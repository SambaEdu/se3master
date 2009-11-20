<?php


   /**
   * Librairie de fonctions utilisees dans l'interface d'administration
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Philippe Chadefaux
   * @Auteurs Sandrine Dangreville
   
   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: fonc_inventaire.php
   * @Repertoire: includes/ 
   */  
  
  

//================================================= 

/**
* expedie un mail aux membres du groupe right, avec sujet et texte 

* @Parametres $right le groupe qui va recevoir le mail, 
* @Return 
*/


function alerte_mail($right,$subject,$texte_mail) { 
      $mp=gof_members($right,"rights",0);

        if ( count($mp)>0) {
            for ($loop=0; $loop < count($mp); $loop++) {
                 $value=extract_login($mp[$loop]);
             list($user, $groups)=people_get_variables($value, true);
                 $mail_value=$user["email"];
                 mail($mail_value,$subject,$texte_mail);
                 $envoi="<li>".$mail_value."</li>".$envoi;
            }
    }
        return "Liste des personnes alertees : (droit $right) <ul> $envoi </ul>";
    
}

?>

                       
