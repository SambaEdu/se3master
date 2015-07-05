#!/usr/bin/perl

#
## $Id$ ##
#
##### Expédie un mail en fonction des alertes définies dans l'inventaire #####
##
#

if (($ARGV[0] eq  "--help") || ($ARGV[0] eq "-h")) {
        print "Expédie un mail en fonction des alertes définies dans l'inventaire\n";
	print "Usage : aucune option\n";
        exit;
}
		

use POSIX;
use Net::LDAP;

my $DEBUG="1"; # 0 désactivé - 1 activé

require '/etc/SeConfig.ph';
my $se3_db = DBI->connect("DBI:mysql:$connexionDb@$mysqlServerIp", $mysqlServerUsername, $mysqlServerPw)
or die "Unable to connect to contacts Database: $se3_db->errstr\n";
$se3_db->{RaiseError} = 1;


$inventaire="Inventaire";
$table = "alertes";
# $requete = "SELECT * FROM $table WHERE menu='inventaire'";

$requete = "SELECT * FROM $table WHERE menu='inventaire' AND ACTIVE='1'";

$bdd_invent="ocsweb";
$server_sql="localhost";
$user_invent=`cat /var/www/se3/includes/dbconfig.inc.php | grep COMPTE_BASE | cut -d= -f2 | cut -d\\" -f2`;
$pass_invent=`cat /var/www/se3/includes/dbconfig.inc.php | grep PSWD_BASE | cut -d= -f2 | cut -d\\" -f2`;
$server_sql=`cat /var/www/se3/includes/dbconfig.inc.php | grep SERVEUR_SQL | cut -d= -f2 | cut -d\\" -f2`;

chomp($user_invent);
chomp($bdd_invent);
chomp($pass_invent);
chomp($server_sql);

my $sth = $se3_db->prepare($requete);
$sth->execute or
die "Unable to execute query: $se3_db->errstr\n";


while (my $ref = $sth->fetchrow_hashref())
{
	#   print $ref->{'Q_ALERT'},"\n";
    	$test_action=$ref->{'ACTIVE'};
    	$predef=$ref->{'PREDEF'};
    	$rights=$ref->{'MAIL'};
    	if (($test_action) and (!$predef)) {
    		$requete_en_cours=$ref->{'Q_ALERT'};
    		$choix=$ref->{'CHOIX'};
    		$value=$ref->{'VALUE'};
    		$parc=$ref->{'PARC'};
    		$name=$ref->{'NAME'};
	
		if ($DEBUG=="1") {
			print "\n\nTraitement de l'alerte $name: \n";
		}
	
		my $inventaire_db = DBI->connect("DBI:mysql:$bdd_invent@$server_sql", $user_invent, $pass_invent)
or die "Unable to connect to contacts Database: $inventaire_db->errstr\n";
		$inventaire_db->{RaiseError} = 1;

		#phase de preparation : on compte les machhines du parc ou presente dans l'inventaire
		#recherche des machines presentes dans l'inventaire
		#on cree un tableau avec toutes les machines de l'inventaire
    		my $sthcomp = $inventaire_db->prepare("SELECT DISTINCT NAME FROM hardware");
    		$sthcomp->execute or 
    		die "Unable to execute query: $inventaire_db->errstr\n";
    		#parcours des valeurs renvoyées par la requete
		@result_comp=();
    		while (@enr = $sthcomp -> fetchrow_array) {
        		$machine_invent=@enr[0];
        		$machine_invent=~ tr /A-Z/a-z/ ;
        		push(@result_comp,"$machine_invent");
     			#   print "dans l'inventaire ".$machine_invent."\n";
        	}
        	# on a le tableau @result_comp avec tous les elements de l'inventaire
		#recherche des machines du parc (presentes dans l'inventaire)
    		if ($parc)  {
    			$parc =~ tr /A-Z/a-z/ ;
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
			
			$res_ldap = $ldap->search( base => "$parcDn",
                        scope => "sub",
                         filter => "cn=$parc",
                         );

			@members_parc_final=();
			@members_parc=();
			foreach $entry ($res_ldap->entries) {
              			@members_parc  = $entry->get_value('member');

            		}
			foreach $machine (@members_parc) {
             			@cn=split(/,/,$machine);
             			@cn_computer = split (/=/,$cn[0]);

      				#      print "dans le parc $parc ".@cn_computer[1]."\n";
            			push(@members_parc_final,"@cn_computer[1]");
                	}
	
			#on a le tableau @members_parc_final avec tous les ordi du parc $parc ,trions les elements qui sont aussi dans l'inventaire
			@intersection = @difference = ();
    			%count = ();
    			foreach $element (@members_parc_final, @result_comp) { $count{$element}++ }
    			foreach $element (keys %count) {
       	 		push @{ $count{$element} > 1 ? \@intersection : \@difference }, $element;
    		}
 		my $nombre_elements_parc = @intersection;
		$nombre_elements_parc_final=$nombre_elements_parc;
		
		if ($DEBUG=="1") {
			print "éléments pris en compte dans le parc".$nombre_elements_parc_final."\n";
		}
	} else {
    		# il faut compter toutes les machines de l'inventaire
    		#il suffit de compter le nombre d'element du tableau @result_comp
    		my $nombre_elements_invent = @result_comp;
		$nombre_elements_invent_final=$nombre_elements_invent;
		
		if ($DEBUG=="1") {
			print "nombre d'éléments répertories dans l inventaire : ".$nombre_elements_invent_final." \n";
		}
    	}


    	my $sthi = $inventaire_db->prepare($requete_en_cours);
    	$sthi->execute or
    	die "Unable to execute query: $inventaire_db->errstr\n";


    	# parcours des valeurs renvoyées par la requete
	@result_query = ();
    	while (@enr = $sthi -> fetchrow_array)
        {
                $machine_invent=@enr[0];
                $machine_invent=~ tr /A-Z/a-z/ ;
                push(@result_query,$machine_invent);
        }

	$sthi -> finish;
	my $nombre_elements_reel = @result_query;

	#print "$nombre_elements_reel renvoyé par la requete dans l'inventaire en tout sans parc \n";
	# il ne faut prendre que les elements du parc

	if ($parc) {
		if($DEBUG=="1") {
    			print "Alerte définie uniquement dans le parc $parc \n";
		}	
		@intersection_query = @difference_query = ();
    		%count = ();
    		foreach $element (@members_parc_final, @result_query) { $count{$element}++ }
    		foreach $element (keys %count) {
        		push @{ $count{$element} > 1 ? \@intersection_query : \@difference_query }, $element;
    		}

		my $nombre_elements_reel = @intersection_query;
	}


	if ($DEBUG=="1") {
		print "$nombre_elements_reel réels trouvés grâce a la requète  \n";
	}



	$inventaire_db -> disconnect;
	#comptons le nombre d'elements renvoyés par le requete enregistrée

	#comparons le contenu du tableau parc et le contenu des machines de la requete
	# en fonction de la valeur demandé par l'alertes
	#si la valeur n'est pas max mais bien fixée au départ dans l'alerte
	if ($parc) {$nombre_elements=$nombre_elements_parc_final;} else { $nombre_elements=$nombre_elements_invent_final;}
	
	if ("$value" eq "max") { 
		$new_value=$nombre_elements; print "la valeur cherchée est (max) $new_value \n";
	} else { 
		$new_value=$value; print "la valeur cherchée est $new_value\n";
	}

	#declenchement de l'alerte ?
    	SWITCH: {
        	("$choix" eq "egal a")      && do {
               		if ($nombre_elements_reel ne $new_value) {
               			@mails=`/usr/share/se3/sbin/mail-ldap.sh "$rights"`;
                    		foreach $mel (@mails) {
					if($DEBUG=="1") {
                        			print "mail envoye a $mel \n";
					}	
					open(MAIL,"|/usr/sbin/sendmail -t");
					print MAIL "To: $mel";
					print MAIL "Subject:[SE3] Alerte $name\n";
					print MAIL "Alerte $name non valide";
					close MAIL;
                        	}
			} else {
					print $retour="alerte validée (egal)";
			}	
                last SWITCH;
       		};

       		("$choix" eq "<")      && do {
       			if ($nombre_elements_reel < $new_value) {
				print $retour="alerte validée pour <";
			} else  {
				@mails=`/usr/share/se3/scripts/mail-ldap.sh "$rights"`;
                        	foreach $mel (@mails) {
					print "mail envoye a $mel \n";
					open(MAIL,"|/usr/sbin/sendmail -t");
					print MAIL "To: $mel";
					print MAIL "Subject: [SE3 Inventaire] Alerte $name\n";
					print MAIL "Alerte a préciser";
					close MAIL;						    
                         	}
			}
                last SWITCH;
        	};

        	("$choix" eq ">")      && do {
        		if ($count > $new_value) {
				print $retour="alerte validee pour >";
			} else  {
				#print $retour="mail a envoyer >";
				@mails=`/bin/bash /usr/share/se3/scripts/mail-ldap.sh "$rights"`;
                        	foreach $mel (@mails) {
					if($DEBUG=="1") {
						print "mail envoye a $mel \n";
					}	
					open(MAIL,"|/usr/sbin/sendmail -t");
					print MAIL "To: $mel";
					print MAIL "Subject: [SE3 Inventaire] Alerte $name\n";
					print MAIL "Alerte a préciser";
					close MAIL;
				}
			}	
               last SWITCH;
       		};
       		
		$nothing = 1;
    		}


	}
}


$sth -> finish;


