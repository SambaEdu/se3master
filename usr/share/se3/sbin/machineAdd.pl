#!/usr/bin/perl

# $Id$ #

##### Script utilisé par samba pour l'ajout des machines dans l'annuaire#####

use Net::Domain;
use Unicode::String qw(latin1 utf8);
use Net::LDAP;
use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($machine_uid, $ipAddress) = @ARGV;
$machine = $machine_uid;
chop($machine);
$machine =~ tr/A-Z/a-z/;
# print "$machine\n";
# print "$machine_uid\n";

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------
$ldap = Net::LDAP->new(
   "$slapdIp",
   port => "$slapdPort",
   debug => "$slapdDebug",
   timeout => "$slapdTimeout",
   version => "$slapdVersion"
);
$ldap->bind(); # Anonymous BIND

# base => 'uid='.$machine_uid.','.$ComputersDn,
$res = $ldap->search(
   base => 'uid='.$machine_uid.','.$computersDn,
   scope => 'base',
   filter => 'uid=*'
);
print "$machine_uid n'existe pas dans $computersDn\n"  if $res->code;
if (($res->entries)[0]) {
   $uid = ($res->entries)[0]->get_value('uid');
   print "entree uid=$uid existante \n"
}
$ldap->unbind();

if ($uid) {
print "on supprime l'entree machine existante en \"uid=$machine_uid,$computersDn\"\n";

system("/usr/share/se3/sbin/entryDel.pl \"uid=$machine_uid,$computersDn\"");
}


my $uidNumber = 30000; # n° à partir duquel la recherche est lancée
my $increment = 1024; # doit etre une puissance de 2
if (defined(getpwuid($uidNumber))) {
	do {
		$uidNumber += $increment;
	} while (defined(getpwuid($uidNumber)));
	
	$increment = int($increment / 2); 
	$uidNumber -= $increment;
	do {
		$increment = int($increment / 2); 
		if (defined(getpwuid($uidNumber))) {
			$uidNumber += $increment;
		} else {
			$uidNumber -= $increment;
		}
	} while $increment > 1;
	# la boucle suivante est normalement exécutée au plus une fois
	while (defined(getpwuid($uidNumber))) {
		$uidNumber++;
	}
}

# Gid Computers
$gid = getgrnam('machines');

$rid = 2 * $uidNumber + 1000;
$pgrid = 2 * $gid + 1001;
$sambaPasses = `/usr/share/se3/sbin/mkntpwd '$password'`;
$sambaPasses =~ /(.*):(.*)/;
$lmPassword = $1;
$ntPassword = $2;
$sambasid = `net getlocalsid | cut -d: -f2 | sed -e \"s/ //g\"`;

# Génération du mot de passe crypté
$salt  = chr (rand(75) + 48);
$salt .= chr (rand(75) + 48);
$crypt = crypt $password, $salt;

@args = (
	 "/usr/share/se3/sbin/entryAdd.pl",
	 "uid=$machine_uid,$computersRdn,$baseDn",
	 "uid=$machine_uid",
	 "cn=$machine_uid",
	 "objectClass=top",
	 "objectClass=account",
	 "objectClass=posixAccount",
	 "objectClass=shadowAccount",
	 "loginShell=/bin/false",
	 "uidNumber=$uidNumber",
	 "gidNumber=$gid",
	 "homeDirectory=/dev/null",
	 "userPassword=\{crypt\}$crypt",
	 "gecos=machine"
	 );
 
$res = 0xffff & system @args;
die("Erreur lors de l'ajoût de l'utilisateur.") if $res != 0;

system("/usr/share/se3/shares/shares.avail/connexion.sh adminse3 $machine $ipAddress");
# reconstruction pour wpkg
if (-e "/usr/share/se3/scripts/update_hosts_profiles_xml.sh") {
	system("/usr/share/se3/scripts/update_hosts_profiles_xml.sh $computersRdn $parcsRdn $baseDn");
	}
exit 0;


