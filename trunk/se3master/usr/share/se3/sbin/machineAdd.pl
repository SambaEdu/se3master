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
# print "$machine\n";
# print "$machine_uid\n";
$mac = `nmblookup -A $ipAddress |  awk '/MAC Address/ {print \$4}' | sed -e "s/-/:/g"`;
if ($mac eq "") {
$mac = "--";
}

# print "$mac\n";

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
$res = $ldap->search(
   base => "cn=$machine,$computersDn",
   scope => 'base',
   attrs => 'cn',
   filter => "cn=$machine"
);
print "$machine n'existe pas dans $computersDn\n"  if $res->code;
#print "res->code = ". $res->code .", res->entries0 = ". ($res->entries)[0] ."\n";
if (($res->entries)[0]) {
   $cn = ($res->entries)[0]->get_value('cn');
   print "entree cn=$cn existante\n"
}
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
# $cn=1;
if ($cn) {
# on vire l'entree machine existante
print "on supprime l'entree machine existante \"cn=$machine,$computersDn\"\n";
system("/usr/share/se3/sbin/entryDel.pl \"cn=$machine,$computersDn\"");
}
#print "res->code = ". $res->code .", res->entries0 = ". ($res->entries)[0] ."\n";

# Uid
#my $uidNumber = 1000;
#while (defined(getpwuid($uidNumber))) {
#  $uidNumber++;
#}

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

# $ip = system("nmblookup $machine | grep -v \"query\" | awk \'{print $1}\'");

  # l'entrée $machine n'existe pas dans l'annuaire : il faut l'ajouter
#    system("/bin/ping -c1 $ipAddress > /dev/null 2>&1");
#    $arp = (split /\s+/,`/usr/sbin/arp -n $ipAddress | grep $ipAddress`)[2];
   #



# print "ip=$ip\n";
# print "mac=$mac\n";
#print "arp=$arp\n";
$ldap = Net::LDAP->new(
"$slapdIp",
port => "$slapdPort",
debug => "$slapdDebug",
timeout => "$slapdTimeout",
version => "$slapdVersion"
);
$ldap->bind(
$adminDn,
password => $adminPw
);
# Ajoût
# -----
$res = $ldap->add(
"cn=$machine,$computersDn",
attrs => [
cn => $machine,
objectClass => 'top',
objectClass => 'ipHost',
objectClass => 'ieee802Device',
objectClass => 'organizationalRole',	
ipHostNumber => $ipAddress,
macAddress => $mac
]);
die("Erreur lors de l'ajoût de l'entrée dans l'annuaire.\n") if ($res->code() != 0);

# Déconnexion
# -----------
$ldap->unbind;

exit 0;


