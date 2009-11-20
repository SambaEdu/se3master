#!/usr/bin/perl

use Net::LDAP;
use DBI;
use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 2);
($user, $machine, $ipAddress) = @ARGV;

# Renseignement de la base MySql
# ------------------------------

$connexion_db = DBI->connect("DBI:mysql:$connexionDb@$mysqlServerIp", $mysqlServerUsername, $mysqlServerPw);
$requete = $connexion_db->prepare(
				  "SELECT id FROM connexions WHERE 
				  	username='$user' AND ip_address='$ipAddress' AND logouttime=0
				   ORDER BY logintime DESC;"
				 );
$requete->execute();
@row = $requete->fetchrow_array();
$id = $row[0];
$requete->finish;

$requete = $connexion_db->prepare(
                                  "UPDATE connexions SET logouttime=now() WHERE id=$id;"
                                                                                          );
$requete->execute();
$requete->finish;
$requete = $connexion_db->prepare(
                                  "DELETE FROM connexions WHERE id>$id AND username='$user' AND ip_address='$ipAddress';"
                                                                                          );
$requete->execute();
$requete->finish;
$connexion_db->disconnect;
# `rm /home/netlogon/$user.bat`;
# `rm /home/netlogon/$user.txt`;
exit 0;

=head1 NOM

deconnexion.pl

=head1 SYNTAXE

connexion.pl nom_netbios_machine adresse_ip

=head1 DESCRIPTIF

Crée l entrée correspondant à la machine dans la branche Computers si elle n existe pas.
Renseigne la base MySql avec les informations %u %m %I et date.

Renvoie 0 (succès) ou un message (gettext :) d erreur.

=cut
