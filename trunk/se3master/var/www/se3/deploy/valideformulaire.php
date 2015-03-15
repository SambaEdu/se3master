<?php

   /**
   
   * Interface de deploiement 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: deploy
   * file: validformulaire.php

  */	


include "entete.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-deploy',"/var/www/se3/locale");
textdomain ('se3-deploy');


//aide
$_SESSION["pageaide"]="Le_module_D%C3%A9ploiement_dans_les_r%C3%A9pertoires_des_utilisateurs";


$ecraser = $_POST['ecraser'];
$classe_gr = $_POST['classe_gr'];
$cours_gr = $_POST['cours_gr'];
$matiere_gr = $_POST['matiere_gr'];
$equipe_gr = $_POST['equipe_gr'];
$autres_gr = $_POST['autres_gr'];
$repertoire = $_POST['repertoire'];
$fich = $_POST['fich'];

// Titre
echo "<h1>".gettext("D&#233ploiement de fichiers")."</h1>\n";

for ($loop=0; $loop < count ($classe_gr) ; $loop++) {
   $filter[$loop]=$classe_gr[$loop];
}
$index=$loop;
for ($loop=0; $loop < count ($matiere_gr) ; $loop++) {
       $filter[$index+$loop]=$matiere_gr[$loop];
}
$index=$index+$loop;
for ($loop=0; $loop < count ($cours_gr) ; $loop++) {
           $filter[$index+$loop]=$cours_gr[$loop];
}
$index=$index+$loop;
for ($loop=0; $loop < count ($equipe_gr) ; $loop++) {
          $filter[$index+$loop]=$equipe_gr[$loop];
}
$index=$index+$loop;
for ($loop=0; $loop < count ($autres_gr) ; $loop++) {
          $filter[$index+$loop]=$autres_gr[$loop];
}


if ($ecraser==""||$filter==""){
	echo "<center>";
        echo "<br>";
    	echo "<H2>".gettext("Votre formulaire est incomplet. Veuillez le ressaisir")."</H2>";
 	echo "<br><br>";
         echo "<a href=accueil.php>Retour</A>";
         echo "</center>";
    	exit();
}

if ($fich == "oui"){
	echo "<center>";
        echo "<br>";
        echo "<H2>".gettext("Il est impossible de d&#233ployer dans un fichier")."</H2>";
        echo "<br><br>";
        echo "<a href=accueil.php>Retour</A>";
        echo "</center>";
	exit();
}


$dir    = "/var/se3/Docs/deploy";
$dh  = opendir($dir);
while (false !== ($filename = readdir($dh))) {
    $files[] = $filename;
}
rsort ($files);
$nombre=count ($files);

$maxi=3;
if ($nombre > $maxi) {
	echo "<center>";
        echo "<br>";
    	echo "<B>".gettext("Le r&#233pertoire deploy ne doit contenir qu'un seul fichier ou un seul r&#233pertoire")."</B>";
	echo "<br><br>";
        echo "<a href=accueil.php>Retour</A>";
        echo "</center>";
    	exit();
}
$presence=2;
if ($nombre == $presence) {
	echo "<center>";
	echo "<br>";
        echo "<B>".gettext("Il n'y a aucun r&#233pertoire ou fichier pr&#233sent dans le r&#233pertoire deploy")."</B>";
        echo "<br><br>";
	echo "<a href=accueil.php>Retour</A>";
	echo "</center>";
	exit();
}
$files0=$files[0];

echo gettext("Vous voulez copier le fichier (ou r&#233pertoire)")." <B>$files[0]</B> ".gettext("dans le r&#233pertoire")." <B>/home$repertoire</B> ".gettext("de chaque utilisateur du (des) goupe(s) suivant(s) :")."<BR>";

for ($loop=0; $loop < count($filter); $loop++) {
echo "<B>$filter[$loop]<BR></B>";
}
echo "<BR>".gettext(" A la question \"Voulez-vous &#233;craser le r&#233;pertoire ou fichier si celui-ci existe d&#233;j&#224;?\" vous avez r&#233;pondu")." <B>$ecraser</B>.<BR>";

echo "<form action=\"transfert.php\" method=\"post\">
       <input type=\"hidden\" name=\"choix\" value=\"$choix\">
       <input type=\"hidden\" name=\"ecraser\" value=\"$ecraser\">
       <input type=\"hidden\" name=\"repertoire\" value=\"$repertoire\">
       <input type=\"hidden\" name=\"files0\" value=\"$files0\">
       <input type=\"submit\" value=\"".gettext("Valider")."\">";

for ($loop=0; $loop < count($filter); $loop++) {
           echo "<input type=\"hidden\" name=\"filter[$loop]\" value=\"$filter[$loop]\">";
}
echo "</form>";

include ("pdp.inc.php");

?>





