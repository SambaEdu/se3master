#!/usr/bin/perl

## $Id$ ##


#use Crypt::SmbHash;
use Encode::compat;
use Encode qw(encode decode);

$password = $ARGV[0];
if ( !$password ) {
                print "Not enough arguments\n";
                print "Usage: $0 password\n";
                exit 1;
}

# Generation du mot de passe crypte
$crypt = `/usr/sbin/slappasswd -h {MD5} -s '$password'`;



#return "$crypt";
print "$crypt";
