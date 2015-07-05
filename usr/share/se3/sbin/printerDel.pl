#!/usr/bin/perl

######################################################################
#   Projet SE3 : Suppression intégrale d'une imprimante              #
#              supprimée de CUPS et n'est plus membre d'aucun parc   #
#   /usr/share/se3/sbin/printerDel.pl                                          #
#   Patrice André <h.barca@free.fr>                                  #
#   Carip-Académie de Lyon -avril-juin-2004                          #
#   Dernière mise-à-jour:25/05/2004                                  #
#   Distribué selon les termes de la licence GPL                     #
######################################################################

## $Id$ ##

#Suppression définitive des imprimantes

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
           
die("Erreur d'argument.\n") if ($#ARGV < 0);

$nom_imprimante = shift @ARGV;
$nom_imprimante = latin1($nom_imprimante)->utf8;

# Suppression de l'imprimante de la branche $printersDn (Printer)
$result = $ldap->delete( "printer-name=$nom_imprimante,$printersDn",
			 attrs =>[
				  'printer-name' => $nom_imprimante,
				  ]
			 );
$result->code && warn "failed to delete entry ", $result->error ;

# Recherche de tous les parcs existants. Dans le but de supprimer les occurences de l'imprimante
# qui peuvent apparaitre dans l'attribut "member" de la branche $parcDn (Parc)
$recherche = $ldap->search( base => $parcDn,
                         scope => "sub",
                         filter => "cn=*",
                         attrs => ['member']
                         );

die("Echec à l'entrée dans ldap.\n") if ($result->code != 0);

# Dans chaque parc, si une occurence de l'imprimante a été trouvée, on l'efface.                             
foreach $entree ($recherche->all_entries()) {
    $member=$entree->get_value('member',asref=>1);  #renvoie une référence sur un tableau (plusieurs occurences de members)
    $nb_member=scalar(@$member);
    for ($i=0; $i<$nb_member; $i++) {
        if  ($member->[$i] eq "cn=$nom_imprimante,$printersDn") {
            $cn_parc=$entree->get_value('cn');
            $result = $ldap->modify( "cn=$cn_parc,$parcDn",
		        	         delete => {'member' => "cn=$nom_imprimante,$printersDn"}
			 );
            die("Echec à l'entrée dans ldap.\n") if ($result->code != 0);
        }
    }
}
$mesg = $ldap->unbind;  # take down session

die ("Configuration CUPS échouée.\n") if (system("/usr/sbin/lpadmin -h 127.0.0.1 -x $nom_imprimante") != 0);

die ("Redémarrage de Samba échoué.\n") if (system("/usr/bin/sudo /usr/share/se3/scripts/sambareload.sh") !=0);
	
die ("Script de partage d'imprimantes Samba échoué.\n") if (system("/usr/share/se3/sbin/printers_group.pl") !=0);

exit 0;
