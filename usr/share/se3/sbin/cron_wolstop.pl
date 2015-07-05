#!/usr/bin/perl

#
## $Id$ ##
#
##### Effectue les actions programmées dans actionse3 #####
##
#

if (($ARGV[0] eq  "--help") || ($ARGV[0] eq "-h")) {
        print "Effectue les actions programmées dans actionse3\n";
	print "Usage : aucune option\n";
        exit;
}
require '/etc/SeConfig.ph';
my $se3_db = DBI->connect("DBI:mysql:$connexionDb@$mysqlServerIp", $mysqlServerUsername, $mysqlServerPw)
or die "Unable to connect to contacts Database: $se3_db->errstr\n";
$se3_db->{RaiseError} = 1;

($sec,$min,$heure,$mjour,$mois,$annee,$sjour,$ajour,$isdst) =localtime(time);
($secbis,$minbis,$heurebis,$mjourbis,$moisbis,$anneebis,$sjourbis,$ajourbis,$isdstbis) =localtime(time+900);
@jour=('d','l','ma','me','j','v','s');
$table = "actionse3";

$requete = "SELECT * FROM $table WHERE jour='$jour[$sjour]' and heure > '$heure:$min:$sec' and heure < '$heurebis:$minbis:$secbis';";
#print $requete;
my $sth = $se3_db->prepare($requete);
$sth->execute or
die "Unable to execute query: $se3_db->errstr\n";

while (my $ref = $sth->fetchrow_hashref())
{
       print $ref->{'heure'},"\n";
       system "/usr/share/se3/scripts/start_client.sh $ref->{'parc'} $ref->{'action'} ";
}
