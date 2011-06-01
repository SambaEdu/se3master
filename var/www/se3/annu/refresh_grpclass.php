<?php


   /**
   
   * Rafraichir les groupes classe
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
   * file: refresh_grpclass.php
   */


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-annu',"/var/www/se3/locale");
  textdomain ('se3-annu');

  // Aide
  $_SESSION["pageaide"]="Annuaire";
  
  $nom_grp=$_GET['nom_grp'];

  echo "<h1>".gettext("Annuaire")."</h1>";

  aff_trailer ("6");
  if (is_admin("Annu_is_admin",$login)=="Y") {
        	exec ("/usr/bin/sudo /usr/share/se3/scripts/creer_grpclass.sh $nom_grp");
		echo "<P><B>".gettext("Cr&#233;ation ou rafraichissement d'une ressources Groupe Classe(s) ordonnanc&#233;e :")."</B> <BR><P>";
		echo gettext("Le r&#233;pertoire")." <B>Classe_grp_$nom_grp</B>".gettext(" sera cr&#233;&#233; ou modifi&#233; d'ici quelques instants...")."</B> ";
 	} else 	{
     		echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur Se3 !")."</div>";
 		}

  include ("pdp.inc.php");
?>
