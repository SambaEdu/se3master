#!/usr/bin/perl

#########################################################################
#   Projet SE3 : Ajout d'une imprimante à la branche printers de LDAP   #
#                et configuration dans CUPS                             #
#   /usr/share/se3/sbin/printerAdd.pl                                   #
#   Patrice André <h.barca@free.fr>                                     #
#   Carip-Académie de Lyon -avril-juin-2004                             #
#   Distribué selon les termes de la licence GPL                        #
#########################################################################

#####Ajout de l'imprimante dans CUPS et LDAP#####

## $Id$ ##

use Net::LDAP;
use Unicode::String qw(latin1 utf8);

require '/etc/SeConfig.ph';

$ldap = Net::LDAP->new(
		       "$slapdIp",
		       port    => "$slapdPort",
		       debug   => "$slapdDebug",
		       timeout => "$slapdTimeout",
		       version => "$slapdVersion"
		      );

$ldap->bind(
	    $adminDn,
	    password => $adminPw
	   );

die("Erreur d'argument.\n") if ($#ARGV < 5);


($nom_imprimante,$uri_imprimante,$lieu_imprimante,$info_imprimante,$protocole,$pilote,$mode)=@ARGV;

$nom_imprimante	        = latin1($nom_imprimante)->utf8;
$info_imprimante	= latin1($info_imprimante)->utf8;
$lieu_imprimante	= latin1($lieu_imprimante)->utf8;


if ( $protocole eq "socket" ){
  $uri_imprimante = "socket://".$uri_imprimante.":9100";
}
elsif ( $protocole eq "http" ){
  if ($uri_imprimante=~"^http://") {
  	$uri_imprimante = $uri_imprimante.":631";
  } else {
  	$uri_imprimante = "http://".$uri_imprimante.":631";
  }
}
elsif ( $protocole eq "ipp" ){
  if ($uri_imprimante=~"^http://") {
  	$uri_imprimante=$$uri_imprimante.":631/printers/".$nom_imprimante;
  } else {
  	$uri_imprimante="http://".$uri_imprimante.":631/printers/".$nom_imprimante;
  }
}
elsif ( $protocole eq "parallel" ){
  $uri_imprimante="parallel:/dev/".$uri_imprimante;
}
elsif ( $protocole eq "usb" ){
  $uri_imprimante="usb:/dev/usb/".$uri_imprimante;
}
elsif ( $protocole eq "lpd" ){
  $uri_imprimante="lpd://".$uri_imprimante."/".$nom_imprimante;
}
elsif ( $protocole ne "custom" ){
  $uri_imprimante="smb://adminse3:".$xppass."@".$uri_imprimante."/".$uri_imprimante."/".$nom_imprimante;
}

$result = $ldap->add( "printer-name=$nom_imprimante,$printersDn",
		      attrs =>[
			       'printer-name'		=> $nom_imprimante,
			       'printer-uri'		=> $uri_imprimante,
			       'printer-location'	=> $lieu_imprimante,
			       'printer-info'		=> $info_imprimante,
			       'printer-more-info' 	=> $mode,
			       'nprintHardwareQueueName'=> $pilote,
			       objectClass =>['printerService','nprintNetworkPrinterInfo',
					      'extensibleObject'],
			      ]
		    );		

die("Echec à l'entrée dans ldap.\n") if ($result->code != 0);                    
#$result->code && warn "failed to add entry: ", $result->error ;
$mesg = $ldap->unbind;  # take down session

# Puis on recrée pour pas de driver il faut envoyer raw a cups
if($pilote eq "dep") {
         $pilote="raw";
}
         

die ("Configuration CUPS échouée.\n") if (system("/usr/bin/sudo /usr/share/se3/scripts/lpadmin.sh -p $nom_imprimante -v $uri_imprimante -D \"$info_imprimante\" -L \"$lieu_imprimante\" -m $pilote -E") != 0);

die ("Redémarrage de Samba échoué.\n") if (system("/usr/bin/sudo /usr/share/se3/scripts/sambareload.sh") !=0);

exit 0;
