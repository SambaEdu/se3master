#!/usr/bin/perl

##### Change le shell de tous les utilisateurs #####

# $Id$ #


use Net::LDAP; 
require '/etc/SeConfig.ph'; 

die("Erreur d'argument.  Usage : changeShellAllUsers.pl shell_courant shell_cible.\n")
	if ($#ARGV != 1);
($shell_courant, $shell_cible) = @ARGV;
die ("Erreur d'argument. shell : /bin/bash|/bin/false|/usr/lib/sftp-server.\n")
	if ((   $shell_courant ne '/bin/bash'
	     && $shell_courant ne '/bin/false'
	     && $shell_courant ne '/usr/lib/sftp-server') ||
	    (   $shell_cible   ne '/bin/bash'
	     && $shell_cible   ne '/bin/false'
	     && $shell_cible   ne '/usr/lib/sftp-server'));


$lcs_ldap = Net::LDAP->new(
	"$slapdIp",
	port    => "$slapdPort",
	debug   => "$slapdDebug",
	timeout => "$slapdTimeout",
	version => "$slapdVersion"
);
$lcs_ldap->bind(
	dn       => $adminDn,
	password => $adminPw,
	version  => '3'
);
$res = $lcs_ldap->search(
	base     => "$baseDn",
	scope    => 'sub',
	filter   => "loginShell=$shell_courant"
);
foreach $entry ($res->entries) {
	$dnToModify = $entry->dn;
	$homeDirectory = $entry->get_value('homeDirectory');
	$res = $lcs_ldap->modify(
		$dnToModify,
		replace => { 'loginShell' => $shell_cible }
	);
	if ($homeDirectory !~ /\/\.\// &&
	    $shell_cible eq '/usr/lib/sftp-server') {
		$res = $lcs_ldap->modify(
			$dnToModify,
			replace => { 'homeDirectory' => "$homeDirectory/./" }
		);
	}
	if (   ($shell_cible eq '/bin/bash' || $shell_cible eq '/bin/true')
	    && $homeDirectory =~ /(.*)\/\.\//) {
		$res = $lcs_ldap->modify(
					 $dnToModify,
					 replace => { 'homeDirectory' => "$1" }
		);
	}
}
