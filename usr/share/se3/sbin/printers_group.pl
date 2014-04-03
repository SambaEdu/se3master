#!/usr/bin/perl

########################################################################
#   Projet SE3 : Configure SAMBA de telle sorte qu'un utilisateur d'un #
#      parc ne puisse pas utiliser les imprimantes d'un autre parc     #
#   /usr/share/se3/sbin/printers_group.pl                              #
#   Patrice André <h.barca@free.fr>                                    #
#   Carip-Académie de Lyon -avril-juin-2004                            #
#   Modifié Philippe Chadefaux @ sambaedu.org			       # 	
#   Distribué selon les termes de la licence GPL                       #
########################################################################

#####Recrée les conf des imprimantes pour samba dans /etc/samba/printers_conf##### 
## $Id$ ##

use Net::LDAP;

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
           
#Liste de tous les parcs existants
$recherche_p = $ldap->search( base => $parcDn,
                         scope => "sub",
                         filter => "cn=*",
                         );

#Liste de toutes les imprimantes						 
$recherche_i = $ldap->search( base => $printersDn,
                         scope => "sub",
                         filter => "printer-name=*",
                         );
                         
#Liste tous les noms Netbios des machines d'un parc.
my $i;
my $j;
my $k;
my $h;
foreach $entree_p ($recherche_p->all_entries()) {
    $member=$entree_p->get_value('member',asref=>1);  #renvoie une référence sur un tableau (plusieurs occurences de members)
    $nb_member=scalar(@$member);
    $i=$j=$h=0;
    for ($i=0; $i<$nb_member; $i++) {                # Pour chaque parc
        @a=split(/,/,$member->[$i]);
        if ($a[1]eq $computersRdn) {                 # SI membre du parc est une machine, on récupère son nom netbios.
            @cn_computer = split (/=/,$a[0]);
            $recherche_h =$ldap->search( base => $computersDn,
                                         scope => "sub",
                                         filter => "cn=$cn_computer[1]",
                                         attrs => [ 'cn' ]
                                         );
			if ($recherche_h->code()) {  # la machnine n'existe plus !
			    $parc=$entree_p->get_value('dn',asref=>1);
				print "cn=$cn_computer[1],$computersDn a supprimer de cn=$parc,$parcDn";
#			    system("/usr/share/se3/sbin/groupDelEntry.pl \"cn=$cn_computer[1],$computersDn\",\"cn=$parc,$parcDn\"";
            } else {
				foreach $entree_h ($recherche_h->all_entries()) {
					$add_host=$entree_h->get_value('cn');
					$tab_computers[$k][$h]=$add_host;           # Le nom netbios est rangé dans un tableau 
					$h++;
				}
			}
         }
        elsif ($a[1] eq $printersRdn) {                  # Si membre est une imprimante, on récupère son nom
            @printer_name = split (/=/,$a[0]);
            $tab_printers[$k][$j]=$printer_name[1];      # Le nom d'imprimante est rangé dans un tableau
            $j++;
        }  
         
    }
    $tab_parks[$k]=(@tab_printers[$k],@tab_computers[$k]); #Regroupement des deux tableaux précédents dans un seul correspondant
    $k++;                                                  #  à un parc.
}

#Tri du tableau précédent de façon à ce que pour une seule machine, on ait
# les noms netbios de toutes les imprimantes, tous parcs confondus.
$m=0;
for ($i=0;$i<=$#tab_parks;$i++) {
    for ($k=0;$k<=$#{$tab_computers[$i]};$k++) {
        $n=0;
        if ($tab_computers[$i][$k] ne "") {
            $computer_one[$m][$n]=$i;
            $computern[$m]=$tab_computers[$i][$k];        #nom de l'ordinateur
            for ($j=($i+1);$j<=$#tab_parks;$j++) {
                for ($l=0;$l<=$#{$tab_computers[$j]};$l++) {
                    if ($tab_computers[$i][$k] eq $tab_computers[$j][$l]) {
                        $n++;
                        $computer_one[$m][$n]=$j;        #Dans ce tableau,on range les indices des parcs communs à une machine.
                        $tab_computers[$j][$l]="";       #nom redondant remplaçé par une chaîne vide
                    }
                }
            }
            $m++;
        }
    }
}

$i=0;
foreach $entree_i ($recherche_i->all_entries()) {
        $printer_n[$i]=$entree_i->get_value('printer-name');
	$printer_l[$i]=$entree_i->get_value('printer-location');
	$printer_d[$i]=$entree_i->get_value('nprintHardwareQueueName');
	$i++;
}


#Destruction de tous les fichiers du répertoire /etc/samba/printers_se3
system ("/bin/rm -f /etc/samba/printers_se3/*");

#Ecriture des fichiers de partage d'imprimantes pour chaque machine
$m=0;
for ($i=0;$i<=$#computern;$i++) {
	foreach $j (@{$computer_one[$i]}) {
	  foreach $k (@{$tab_printers[$j]}) {


		#Liste de toutes les imprimantes						 
 print "Parc : $computern[$i] computer: $j printer: $k  Driver: $nprintHQ location: $location client_driver $client_driver\n";
		my $recherche = $ldap->search( base => $printersDn,
                         scope => "sub",
                         filter => "printer-name=$k",
			 attrs => [ 'printer-location',
			 	    'nprintHardwareQueueName', 
				    'printer-more-info' ]	
                         );
	        # tester le resultat ! 
	        if ( $recherche->count == 1 ) {
		    my $entry = $recherche->pop_entry();
		    my $nprintHQ = $entry->get_value ('nprintHardwareQueueName');
		    my $location = $entry->get_value('printer-location');
		    my $devmode = $entry->get_value('printer-more-info');
	
		    if($nprintHQ eq "dep") { $client_driver="no"; } else { $client_driver="yes"; } 
		    if($devmode eq "on")   { $default_devmode="yes"; } else { $default_devmode="no"; }
		    print "Parc : $computern[$i] printer : $k  Driver : $nprintHQ location : $location client_driver $client_driver\n";
		    open (PRINTER,">>/etc/samba/printers_se3/$computern[$i].inc");
		    printf (PRINTER    "\n\n[%s]\ncomment = %s\npath = /var/spool/samba\nprinter name = %s\nvalid users = %%U\nbrowseable = yes\nguest ok = no\nwritable = yes\nprintable = yes\ndefault devmode = %s\nuse client driver = %s",$k,$location,$k,$default_devmode,$client_driver);
		    close (PRINTER);
		}
	        $m++;			
	  }
    	
	}
}

#Par défaut toutes les imprimantes sont accessibles au serveur Samba.
$nb_printers=$#printer_n;

$name_server = $netbios_name;

system ("/bin/rm -f /etc/samba/printers_se3/",$name_server,".inc");
$i=0;
foreach $k (@printer_n) {
	open (PRINTER,">>/etc/samba/printers_se3/$name_server.inc");
	if($printer_d[$i] eq "dep") {
		printf (PRINTER    "\n\n[%s]\ncomment = %s\npath = /var/spool/samba\nprinter name = %s\nvalid users = %%U\nbrowseable = yes\nguest ok = no\nwritable = yes\nprintable = yes\nuse client driver = no",$k,$printer_l[$i],$k);
	} else {
		printf (PRINTER    "\n\n[%s]\ncomment = %s\npath = /var/spool/samba\nprinter name = %s\nvalid users = %%U\nbrowseable = yes\nguest ok = no\nwritable = yes\nprintable = yes\nuse client driver = yes",$k,$printer_l[$i],$k);
	}

	close (PRINTER);
	$i++;
}

exit 0;
