#!/usr/bin/perl -w
#
### $Id$ ###
#
##### Compare 2 fichiers, utilis� par l'inventaire #####
#
$fichin=$ARGV[0];
$fichout=$ARGV[1];
# $fichin="s.txt";
# $fichout="d.txt";

# ouverture en lecture 
open(IN, $fichin) or die "Impossible d'ouvrir $fichin\n";
open(OUT, ">$fichout");

# debut de la boucle generale
SUIVANT:
while (<IN>) { 
# print "Ligne numero $.\n";
chomp;
s/\r// if /\r$/; 

# tester le d�but de paragraphe
if (/^(\d)/) {
 $op="a" if /a/;
 $op="d" if /d/;
 $op="c" if /c/;
 # on �limine cette ligne
 next SUIVANT;
}
if  (/^---/) {
 # on �limine cette ligne
 next SUIVANT;
}
 # traitement d'une ligne � afficher
 $fin="1;" if /^>/;
 $fin="2;" if /^</;
 $fin="31;" if (/^>/ and $op eq "c") ;
 $fin="32;" if (/^</ and $op eq "c") ;

 s/^.{2}//;
 print OUT "$_$fin\r\n";
}
close OUT;
close IN;
