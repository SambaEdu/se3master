#!/usr/bin/perl

# $Id$

use Unicode::String qw(latin1 utf8);

require '/etc/SeConfig.ph';

($groupType, $cn, @description) = @ARGV;

die("Erreur d'argument" . ".\n") if ($#ARGV < 2 or ($groupType != 1 and $groupType != 2));

$groupType = 'posixGroup' if $groupType == 1;
$groupType = 'groupOfNames' if $groupType == 2;

$description = join ' ', @description;
$description = latin1($description)->utf8;

$gid = getFirstFreeGid(1000);

@args = (
	 "/usr/share/se3/sbin/entryAdd.pl",
	 "cn=$cn,$groupsDn",
	 "cn=$cn",
	 "objectClass=top",
	 "objectClass=$groupType",
	 "description=$description",
	);

$optionalArg = "gidNumber=$gid";
push @args, $optionalArg if $groupType eq 'posixGroup';

$res = 0xffff & system @args;
die("Erreur lors de l'ajout du groupe.\n") if $res != 0;

system("sudo /usr/share/se3/scripts/group_mapping.sh $cn $cn \"$description\"") if $groupType eq 'posixGroup';

exit 0;

sub getFirstFreeGid {
my $gidNumber = shift; # n° à partir duquel la recherche est lancée
my $increment = 1024; # doit etre une puissance de 2
if (defined(getgrgid($gidNumber))) {	
	do {
		$gidNumber += $increment;
	} while (defined(getgrgid($gidNumber)));
	
	$increment = int($increment / 2); 
	$gidNumber -= $increment;
	do {
		$increment = int($increment / 2); 
		if (defined(getgrgid($gidNumber))) {
			$gidNumber += $increment;
		} else {
			$gidNumber -= $increment;
		}
	} while $increment > 1;
	# la boucle suivante est normalement exécutée au plus une fois
	while (defined(getgrgid($gidNumber))) {
		$gidNumber++;
	}
}
return $gidNumber;
}
