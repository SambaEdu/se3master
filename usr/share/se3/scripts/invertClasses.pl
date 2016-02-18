#!/usr/bin/perl -w


## $Id$  ##


# Met à jour l'arborescence des partages Classes
# en en inversant le nom et le prenom dans le cas d'un login prenom.nom
#
#     - eleve : login d'un eleve, la Classe est lue dans l'annuaire
#
#       Denis Bonnenfant (denis.bonnenfant@diderot.org) 7 septembre 2007 : ajout d'un cas possible de migration 

# supprime les Warnings du module Se.pm
BEGIN { $SIG{'__WARN__'} = sub { warn $_[0] if $DOWARN } }
use Se;
$DOWARN = 1; # Warnings activés à nouveau

$PathClasses = '/var/se3/Classes';
die("Syntaxe : invertClasses.pl login") if ($#ARGV != 0);
($LOGIN) = @ARGV;

$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(
        dn       => $adminDn,
        password => $adminPw,
        version  => '3');

# Recherche du dossier Eleve
# test de l'inversion prenom.nom
@NOM = split(/\./, $LOGIN);
if ( @NOM == 2 ) {
 # on inverse  
 $ELEVE = @NOM[1] . "." . @NOM[0];
 @REP = <$PathClasses/Classe_*/$LOGIN>;
 @REPINV = <$PathClasses/Classe_*/$ELEVE>;
 if ( @REP > 0 ) {
  foreach $rep (@REP) {
   # print  "inversion : " . ( 1 + $#REP ) ." répertoires trouvés pour $LOGIN !<br>\n";
   # print "$rep\n"; 
   $tmpClasse = $rep;
   $tmpClasse =~ s!^$PathClasses/Classe_(.+)/$LOGIN$!$1!;
   $res = $lcs_ldap->search(base => "$groupsDn",
         scope    => 'one',
         filter   => "(&(cn=Classe_*)(memberUid=$LOGIN))");
   warn $res->error if $res->code;
   if ( ($res->entries) == 1 ) {
    print "Inversion de $prenom.$nom -> $ELEVE<br>\n";     
    system("/bin/mv '$PathClasses/Classe_$tmpClasse/$LOGIN' '$PathClasses/Classe_$tmpClasse/$ELEVE'") == 0 or warn "  Erreur: /bin/mv '$PathClasses/Classe_$tmpClasse/$LOGIN' '$PathClasses/Classe_$tmpClasse/$ELEVE'\n";
    print "classe : $tmpClasse\n"; 
    print "inversion de " . $PathClasses . "/Classe_" . $tmpClasse . "/" . $LOGIN . " avec " . $ELEVE . " faite<br>\n";
   }
  }     
 }
}
exit 0 ;
