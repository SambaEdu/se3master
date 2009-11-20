#!/usr/bin/perl

use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($machine,$lcs) = @ARGV;

die "L'acces au partage est interdit depuis $machine.\n" if ($lcs eq $machine);

exit 0;
