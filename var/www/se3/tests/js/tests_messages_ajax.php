<?php
 /**
   
   * Page qui teste les differents services
   * @Version $Id: tests_messages_ajax.php 9181 2016-02-20 20:51:11Z keyser  - correction accents 
   * 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note 
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
   * Modification du systeme d'infos bulles.(Nouvelle version de wz-tooltip) Ancienne version incompatible avec ajax
   * Externalisation des messages contenus dans les infos-bulles
   * Fonctions Tip('msg') et UnTip();
   * Nouvelle organisation de l'arborescence.
   
   * Ce script recupere en javascript le tableau associatif des messages pour un traitement Ajax.
   */

   /**

   * @Repertoire: /tests/js/
   * file: tests_messages_ajax.js
   */


// Ce script contient les messages en francais des infos bulles. 
    
    $prefix = 'tests';	
    require_once("config.inc.php");
    require_once("/var/www/se3/$prefix/messages/$lang/".$prefix."_messages.php");
    header('text/javascript');
    
	
    foreach($tests_msg as $key=>$value){
	$flux .= "var $key=\"$value\";\n";
    }

	$flux = str_replace('&#233;','é',$flux);
	$flux = str_replace('&#232;','è',$flux);
	$flux = str_replace('&#224;','à',$flux);


	die($flux);
?>
