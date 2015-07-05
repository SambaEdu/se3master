#!/usr/bin/perl

#
## $Id$ ##
#
##### Effectue les actions programmées dans alertes #####
# Philippe Chadefaux
##
#
# Mode DEBUG
my $DEBUG=0; # Mettre à 0 pour descativer le mode debug
$REP_SCRIPT="/usr/share/se3/scripts-alertes/";

#
# Modifier dans syslog.conf afin que local5 soit utilisé
# en ajoutant la ligne suivante
# local5.*                        /var/log/se3/alertes
#
# Pour éviter de mettre les logs dans messages modifier 
#
# local5;\
# mail,news.none          -/var/log/messages
#         

use Sys::Syslog qw(:DEFAULT setlogsock);
use Time::Local;
# use strict;
# 




if (($ARGV[0] eq  "--help") || ($ARGV[0] eq "-h")) {
        print "Effectue les actions programmées dans actionse3\n";
	print "Usage : aucune option\n";
        exit;
}

setlogsock('unix');
openlog("Alertes",'pid','local5');

if ($DEBUG=="1") {
	syslog('info','Script cron_alertes');
}

require '/etc/SeConfig.ph';
my $se3_db = DBI->connect("DBI:mysql:$connexionDb@$mysqlServerIp", $mysqlServerUsername, $mysqlServerPw)
or die "Unable to connect to contacts Database: $se3_db->errstr\n";
$se3_db->{RaiseError} = 1;

# ($sec,$min,$heure,$mjour,$mois,$annee,$sjour,$ajour,$isdst) =localtime(time);
# ($secbis,$minbis,$heurebis,$mjourbis,$moisbis,$anneebis,$sjourbis,$ajourbis,$isdstbis) =localtime(time+900);
# @jour=('d','l','ma','me','j','v','s');
my $table = "alertes";


$requete = "SELECT ID,NAME,MAIL,VARIABLE,TEXT,SCRIPT,FREQUENCE,unix_timestamp(PERIODE_SCRIPT),MAIL_FREQUENCE FROM $table WHERE ACTIVE='1' AND AFFICHAGE='1';";
my $sth = $se3_db->prepare($requete);
$sth->execute or
die "Unable to execute query: $se3_db->errstr\n";

while (my $ref = $sth->fetchrow_hashref())
{
	$id=$ref->{'ID'};
	$name=$ref->{'NAME'};
	$script=$ref->{'SCRIPT'};
	$variable=$ref->{'VARIABLE'};
	$rights=$ref->{'MAIL'};
	$text=$ref->{'TEXT'};
	$frequence=$ref->{'FREQUENCE'};
	$periode_script=$ref->{'unix_timestamp(PERIODE_SCRIPT)'};
	$frequence_mail=$ref->{'MAIL_FREQUENCE'};
	
	# Test l'existance du script
	@script_only = split(/ /,$script);
	$script_test = $REP_SCRIPT.$script_only[0];
	if (-f $script_test) { 
		if($DEBUG=="1") {
			print "Le script $script_test exist\n";
		}	
	} else {
		if($DEBUG=="1") {
			print "le script $script_test n'existe pas\n";
		}
		# Open syslog
		syslog('info','Le script '.$script_test.' ne semble pas exister');
	}	
	
	$epoch_now=time();
	# Calcul de la frequence
	#
	# duree  entre maintenant et la derniere remontée
	$duree = $epoch_now - $periode_script;
	
	if ($DEBUG=="1") {
		if($duree >= $frequence) { 
			print "frequence $frequence plus petit que la dernière cron : $duree donc script lanc�\n"; 
		}
		if ($duree <= $frequence) { 
			print "duree $duree plus petit que freq $frequence, donc script non lancé\n"; 
		}
	}	
	
	# On lance le script en fonction de la frequence demande par l'admin
	# donc chaque fois que duree devient plus grand que frequence
	if ($duree >= $frequence) { 
		if ($DEBUG=="1") {
			print "\nTraitement de l'alerte $name: \n";
		}	
		$retour=`/usr/share/se3/scripts-alertes/$script  2>&1`;
		$retour_err=`echo $?`;
	

		# Mode deubug
		if ($DEBUG=="1") {
			print "retour_err  $retour_err << $retour >>\n";	
		}
	
		if ($retour_err=="0") { # Retour positif pas d'alerte
			# Si anciennement variable = 0  alors on avait pas de probleme
			if ($variable=="0") {
				# On expédie le mail informant retour normal
				@mails=`/usr/share/se3/sbin/mail-ldap.sh "$rights"`;
				foreach $mel (@mails) {
					if ($DEBUG=="1") {
						print "mail pour $rights envoye a $mel \n";
					}	
					open(MAIL,"|/usr/sbin/sendmail -t");
				        print MAIL "To: $mel";
				        print MAIL "Subject: [SE3] Fin alerte $name\n\n";
				        print MAIL "\n\nFin de alerte  $text";
			        	close (MAIL);
				}
			}	
			# On repasse variable a 1
			$requete = "UPDATE $table SET VARIABLE='1',PERIODE_SCRIPT=NOW() WHERE `ID`='$id';";
			my $sth = $se3_db->prepare($requete);
			$sth->execute or
			die "Unable to execute query: $se3_db->errstr\n";
			# Requete pour mettre VARIABLE à 1 dans la table (en cas de retour à la normal)

			if ($DEBUG=="1") {
				print "\nOK pour script $script\n";
			}
			
			# Open syslog
			if ($DEBUG=="1") {
				syslog('info',$name.':'.$retour_err.':'.$retour);
 			}
		
		} else {
			# Probleme on balance le mail sauf si VARIABLE est déja à 0
			# cela voulant dire que le mail est déjà parti.
			# + pour pas le renvoyer à chaque cron on passe VARIABLE à 0
			# 
			# # VARIABLE etant a 1 aucun message n'a encore été envoyé
			if ($DEBUG=="1") {
				print "Etat probleme\n\n"; 
			}

			# On passe variable a 0
			$requete = "UPDATE $table SET VARIABLE='0',PERIODE_SCRIPT=NOW() WHERE `ID`='$id';";
			my $sth = $se3_db->prepare($requete);
			$sth->execute or
			die "Unable to execute query: $se3_db->errstr\n";
		
			if (($variable == '1') || ($frequence_mail == "1")) {
				# On expédie le mail
				@mails=`/usr/share/se3/sbin/mail-ldap.sh "$rights"`;
				foreach $mel (@mails) {
					if ($DEBUG=="1") {
						print "mail pour $rights envoye a $mel \n";
					}	
					open(MAIL,"|/usr/sbin/sendmail -t");
				        print MAIL "To: $mel";
				        print MAIL "Subject: [SE3] Alerte $name\n\n";
				        print MAIL "\n\nAlerte  $text";
			        	close (MAIL);
				}
				

				# Open syslog
				syslog('info',$name.':'.$retour_err.':'.$retour);
			}	
		}	
	
	}
}	
