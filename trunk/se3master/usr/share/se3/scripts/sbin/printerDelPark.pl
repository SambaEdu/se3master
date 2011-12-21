#!/usr/bin/perl

######################################################################
#   Projet SE3 :Suppression d'une imprimante comme membre d'un parc  #
#                dans LDAP                                           #
#   /usr/share/se3/sbin/printerDelPark.pl                                      #
#   Patrice Andr� <h.barca@free.fr>                                  #
#   Carip-Acad�mie de Lyon -avril-juin-2004                          #
#   Derni�re mise-�-jour:25/05/2004                                  #
#   Distribu� selon les termes de la licence GPL                     #
######################################################################

#Supprime une imprimante d'un parc.

use Net::LDAP;

require '/etc/SeConfig.ph';

use Unicode::String qw(latin1 utf8);

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
           
die("Erreur d'argument.\n") if ($#ARGV < 1);

($nom_imprimante,$nom_parc)=@ARGV;
#NJ- 4/10/2004 modif pour les noms de parc avec espace
if ($#ARGV > 1)
        {for (my $i = 2; $i <= $#ARGV; $i++)
                  { $nom_parc = $nom_parc." ".$ARGV[$i];
        }
}

#----------------

$nom_imprimante	        = latin1($nom_imprimante)->utf8;
$nom_parc		= latin1($nom_parc)->utf8;

$result = $ldap->modify( "cn=$nom_parc,$parcDn",
			 delete => {'member' => "cn=$nom_imprimante,$printersDn"}
			 );

die("Echec � l'entr�e dans ldap.\n") if ($result->code != 0);
#$result->code && warn "failed to delete attribute ", $result->error ;
$mesg = $ldap->unbind;  # take down session

die ("Script de partage d'imprimantes Samba �chou�.\n") if (system("/usr/share/se3/sbin/printers_group.pl") !=0);

exit 0;
