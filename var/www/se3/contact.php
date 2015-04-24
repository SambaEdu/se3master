<?php


   /**
   
   * Page qui affiche les contacts et le team se3
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lecluse "wawa"

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: contact.php

  */	



require ("entete.inc.php");

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


$texte=gettext("<P>SambaEdu3 est distribu&#233; selon <A HREF='http://www.gnu.org/licenses/quick-guide-gplv3.fr.html'>les termes de la licence GPL</A>.");
mktable (gettext("A propos"), "<DIV ALIGN='center'>SambaEdu-3 Version $version</DIV>".$texte);
print "<BR>";

$texte = gettext("<P>Ce projet a &#233;t&#233; initi&#233; par le <A HREF='http://www.crdp.ac-caen.fr' TARGET='_new'>CRDP de Basse-Normandie</A> et est &#224; pr&#233;sent port&#233; par des contributeurs de plusieurs acad&#233;mies : Rouen, Creteil, Versailles, Grenoble, Lyon... 
<P>Pour tout renseignement, vous pouvez contacter <A HREF='mailto:olivier.lecluse1@ac-caen.fr'>Olivier LECLUSE</A>, chef du projet SambaEdu3.</P>
<P>Un grand merci &#224; tous ceux qui contribuent au d&#233;veloppement, aux tests, ou &#224; la documentation du projet, en particulier, pour l'Acad&#233;mie de Caen</P>
<UL>
<LI><A HREF='mailto:oluve@crdp.ac-caen.fr'>Olivier Le monnier</A> qui a d&#233;velopp&#233; la structure de l'annuaire, l'API perl et le l&#233;gendaire gep.cgi</LI>
<LI><A HREF='mailto:jean-luc.chretien@tice.ac-caen.fr'>Jean-luc Chr&#233;tien</A> auteur d'une grande partie de l'interface web et du paquet se3-esclave</LI>
<LI><A HREF='mailto:fabrice.legros@tice.ac-caen.fr'>Fabrice Legros</A> Pour sa contribution importante &#224; la documentation</LI>
<LI><A HREF='mailto:david.gloux@tice.ac-caen.fr'>David Gloux</A> Pour le script de d&#233;ploiement, la gestion des ACLs et la documentation</LI>
<LI><A HREF='mailto:guillaume.marquis@crdp.ac-caen.fr'>Guillaume Marquis</A> pour le design et les logos de l'interface SambaEdu</LI>
<LI><A HREF='mailto:sebastien.tack@tice.ac-caen.fr'>S&#233;bastien Tack</A> pour l'interface NAS et les optimisations Ajax</LI>
<LI><A HREF='mailto:jean.lebail@etab.ac-caen.fr'>Jean LeBail</A> pour son travail autour du d&#233;ploiement d'applications WPKG </LI>
</UL>
<P>pour les acad&#233;mies de Creteil et Versailles
<UL>
<LI>Philippe Chadefaux pour son tr&#232;s gros travail autour de la r&#233;novation de l'interface web 
<LI>Eric Mercier pour le module DHCP

<LI>Olivier LeCam a l'origine de l'installateur automatique <EM>DiGloo</EM>
<LI>Sandrine Dangreville pour l'interface de gestion des cles de registe windows
<LI>Jean Gourdin pour l'interface de remise de devoirs
<LI>Olivier Lacroix pour l'interface quotas et tout le travail autour de wpkg
<LI>Louis Maurice de Sousa et Francois Lafont pour le support des clients Linux
</UL>

<P>pour l'acad&#233;mie de Paris
<UL>
<LI> Denis Bonnenfant pour son expertise, toutes ses contributions (se3-internet, se3-synchro, se3-domain) et la veille au quotidien du projet
</UL>
<P>pour le Carip de l'Acad&#233;mie de Lyon
<UL>
<LI>Patrice Andr&#233; et Jean Navarro pour l'interface imprimantes
</UL>
<P>pour l'acad&#233;mie de Grenoble
<UL>
<LI>Laurent Cooper pour le module antivirus
</UL>
<P>pour l'acad&eacute;mie de Rennes
<UL>
<LI>Jean Diraison pour logonc, ses interventions sur les listes de diffusion toujours tr&#232;s pertinentes, son expertise et ses scripts de haut vol ...
</UL>
<P>pour l'acad&eacute;mie de Rouen
<UL>
<LI><A HREF='mailto:Stephane.boireau@ac-rouen.fr'>St&eacute;phane Boireau </A>pour le module clonage / TFTP et l'import des comptes depuis SIECLE / STS
<LI><A HREF='mailto:franck.molle@ac-rouen.fr'>Franck Molle </A>pour le suivi au quotidien du projet, la mise &#224; jour de l'installeur, des scripts d'installation et de mise &#224; jour.
</UL>
<P>Et tous les autres contributeurs au projet, qu'ils soient d&#233;veloppeurs, testeurs ou r&#233;dacteurs sur la documentation.
<P>SambaEdu est un produit libre et ind&#233;pendant, d&#233;velopp&#233; par des professeurs b&#233;n&#233;voles pour l'ensemble de la communaut&#233; &#233;ducative. C'est un projet en constante &#233;volution depuis 2000 et qui est officiellement support&#233; et d&#233;ploy&#233; dans plusieurs acad&#233;mies comme Caen, Rouen, Versailles, Strasbourg, Nantes, Clermont ferrand ...  
");

mktable (gettext("A Propos"),$texte);
require ("pdp.inc.php");
?>
