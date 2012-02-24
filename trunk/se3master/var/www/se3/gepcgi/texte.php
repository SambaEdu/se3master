<?php

   /**
   
   * Affiche la page avant import sconet
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe TICE CRDP de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: gepcgi/
   * file: texte.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if (is_admin("Annu_is_admin",$login)=="Y") {
        $_SESSION["pageaide"]="Annuaire";

?>
    <h1>Format des fichiers texte pour importation</h1>

    <h2><tt>F_ELE</tt> : El&#232;ves</h2>
    <h3>Entr&#233;e type : <tt style="color: black">01561|BIGEON|C&#233;line|19820702|F|STS2</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>Num&#233;ro unique de l'&#233;l&#232;ve dans l'&#233;tablissement (optionnel<a href="#footnote"><sup>*</sup></a>)</li>
      <li>Nom</li>
      <li>Pr&#233;nom</li>
      <li>Date de naissance (format <tt>aaaammjj</tt>)</li>
      <li>Sexe (<tt>F</tt> : fille <tt>|</tt> <tt>M</tt> : gar&#231;on)</li>
      <li>Classe</li>
    </ul>

    <!-- h2><tt>F_EAG</tt> : Affectation des &#233;l&#232;ves dans les groupes</h2>
    <h3>Entr&#233;e type : <tt style="color: black">02548|2MPIG1</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li><tt>Eleonet</tt> (num&#233;ro unique &#233;tablissement)</li>
      <li>Groupe (code &#233;galement utilis&#233; dans <tt>F_GRO</tt>)</li>
    </ul>

    <h2><tt>F_GRO</tt> : Groupes</h2>
    <h3>Entr&#233;e type : <tt style="color: black">1TLAT|LATIN 1RE-TERM</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>Groupe (code &#233;galement utilis&#233; dans <tt>F_EAG</tt>)</li>
      <li>Intitul&#233; du groupe</li>
    </ul -->

    <h2><tt>F_DIV</tt> : Classes</h2>
    <h3>Entr&#233;e type : <tt style="color: black">1STT1|1ERE STT1|10918</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>Classe</li>
      <li>Intitul&#233; de la classe</li>
      <li>Num&#233;ro unique du professeur principal (optionnel<a href="#footnote"><sup>*</sup></a>)</li> 
    </ul>

    <h2><tt>F_MEN</tt> : Cours</h2>
    <h3>Entr&#233;e type : <tt style="color: black">AGL1|1ES1|21914</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>code mati&#232;re</li>
      <li>code groupe ou classe</li>
      <li>Num&#233;ro unique du professeur donnant le cours (optionnel<a href="#footnote"><sup>*</sup></a>)</li>
    </ul>

    <!-- h2><tt>F_TMT</tt> : Mati&#232;res</h2>
    <h3>Entr&#233;e type : <tt style="color: black">SES|SCIENCES ECONOMIQUES ET SOCIALES</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>code mati&#232;re</li>
      <li>Intitul&#233; de la mati&#232;re</li>
    </ul -->

    <h2><tt>F_WIND</tt> : Enseignants</h2>
    <h3>Entr&#233;e type : <tt style="color: black">14333|MANNHAI|DAVID|19710919|1</tt></h3>
    <h3>Descriptif des diff&#233;rents champs :</h3>
    <ul>
      <li>num&#233;ro unique du professeur dans l'&#233;tablissement (optionnel<a href="#footnote"><sup>*</sup></a>)</li>
      <li>Nom</li>
      <li>Pr&#233;nom</li>
      <li>Date de naissance (format <tt>aaaammjj</tt>)</li>
      <li>Sexe (<tt>1</tt> : homme <tt>|</tt> <tt>2</tt> : femme)</li>
    </ul>

    <a name="footnote"><p><span style="font-size: large; font-weight: bold">Note * :</span> Si vous ne pouvez pas -- ou choisissez de ne pas -- fournir de num&#233;ro unique aux &#233;l&#232;ves et/ou enseignants, le suivi des comptes d'une ann&#233;e &#224; l'autre sera plus d&#233;licat, la v&#233;rification des doublons s'effectuant alors sur le pr&#233;nom et le nom, informations modifiables par les utilisateurs.</p>
    <p>D'autre part, en ce qui concerne le num&#233;ro unique affect&#233; aux professeurs, celui-ci sert &#233;galement &#224; renseigner le r&#244;le de professeur principal dans les groupes « &#233;quipes p&#233;dagogiques » et de professeur enseignant dans les groupes « Cours ».  En l'absence de num&#233;ro unique, ces informations devront &#234;tre ins&#233;r&#233;es manuellement dans l'annuaire.</p>
    </a>

    <h2>Importation</h2>
    <h3>Pr&#233;fixe</h3>
    <p>Si votre &#233;tablissement est compos&#233; de plusieurs entit&#233;s, comme un lyc&#233;e professionnel et un lyc&#233;e technique par exemple, vous diposez de plusieurs bases de donn&#233;es.</p>
    <p>il faut donc sp&#233;cifier un suffixe pour chacune de ces bases afin de diff&#233;rencier les importations n&#233;cessairement s&#233;par&#233;es..</p>
    <p>Si vous ne disposez que d'une seule base de donn&#233;es, laissez ce champ vide.</p>
    <h3>Importation de d&#233;but d'ann&#233;e</h3>
    <p>L'importation de d&#233;but d'ann&#233;e n&#233;cessite une pr&#233;paration en profondeur de l'annuaire pour l'int&#233;gration des nouveaux &#233;l&#232;ves et la r&#233;g&#233;n&#233;ration des nouveaux groupes de 'Classe', 'Equipe', 'Cours' et 'Matiere'. Ces groupes datant de l'ann&#233;e pr&#233;c&#233;dente seront donc effac&#233;s avant d'&#234;tre recr&#233;&#233;s avec leur nouveau contenu</p>
    <p>L'activation de la case ci-dessous permettra la mise en place automatique de cette pr&#233;paration. Vous pouvez importer les fichiers  autant de fois que vous voulez durant l'ann&#233;e scolaire mais cette case devra &#234;tre coch&#233;e uniquemnt lors de la premi&#232;re importation, en g&#233;n&#233;ral, d&#233;but septembre.</p>
    <form method="post" action="/cgi-bin/gep2.cgi" enctype="multipart/form-data">
      <table width="80%">
	  <tr><td><h4>Suffixe &#233;ventuel : <input type="text" name="prefix" size="5" maxlength="5"> (<em>ex</em> : <tt>LEP</tt>)</h4></td>
	    <td><h4>Importation de d&#233;but d'ann&#233;e ? <input type="checkbox" name="annuelle"></h4></td>
      </table>
      <h3>Fichiers :</h3>
      <table><tbody>
	  <tr><th align="left">Fichier « El&#232;ves » (<em>ele</em>)</th>
	    <td><input type="file" name="f_ele" ></td></tr>
	  <!-- tr><th align="left">Fichier « Divisions par module d'enseignement » (<em>eag</em>)</th>
	    <td><input type="file" name="f_eag"></td></tr>
	  <tr><th align="left">Fichier « Groupes » (<em>gro</em>)</th>
	    <td><input type="file" name="f_gro"></td></tr -->
	  <tr><th align="left">Fichier « Classes » (<em>div</em>)</th>
	    <td><input type="file" name="f_div"></td></tr>
	  <tr><th align="left">Fichier « Modules d'enseignement » (<em>men</em>)</th>
	    <td><input type="file" name="f_men"></td></tr>
	  <!-- tr><th align="left">Fichier « Mati&#232;res » (<em>tmt</em>)</th>
	    <td><input type="file" name="f_tmt"></td></tr -->
	  <tr><th align="left">Fichier « Professeurs » (<em>wind</em>)</th>
	    <td><input type="file" name="f_wind"></td></tr>
	</tbody></table>
      <!-- h3>Politique des mots de passe :</h3>
      <p><input type="radio" name="pass" value="date" checked="checked">&nbsp;Date de naissance au format <tt>AAAAMMJJ</tt> (<em>ex</em> : <tt>19711008</tt> pour le 8 octobre 1971) &nbsp;?</p>
      <p><input type="radio" name="pass" value="defaut">&nbsp;Valeur unique, par d&#233;faut, avec obligation de changer &#224; la premi&#232;re connexion&nbsp;?&nbsp;:&nbsp;<input type="text" value="LCServer" size="8" maxlength="8"></p>
      <p><input type="radio" name="pass" value="auto">&nbsp;G&#233;n&#233;ration automatique (liste des mots de passe communiqu&#233;e par mel)&nbsp;?</p -->
      <table align="center" width="100%"><tbody>
	  <tr><td colspan="2">&nbsp;</td></tr>
	  <tr><td colspan="2" align="right"><input type="submit" value="Transf&#233;rer les fichiers et importer !"></td></tr>
	</tbody></table>
    </form>


<?php
}
include("pdp.inc.php");
?>
