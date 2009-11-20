#!/usr/bin/perl

# $Id$
#use Crypt::SmbHash;
use Encode::compat;
use Encode qw(encode decode);

$password = $ARGV[0];
if ( !$password ) {
                print "Not enough arguments\n";
                print "Usage: $0 password\n";
                exit 1;
}

# Génération du mot de passe crypté
$salt  = chr (rand(75) + 48);
$salt .= chr (rand(75) + 48);
$crypt = crypt $password, $salt;

#return "$crypt";
print "$crypt";
