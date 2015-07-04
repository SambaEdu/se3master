<?php

   /**
   
   * Page du menu  
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier Lecluse "wawa"
   * @auteurs jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: menu.php
   */




	require_once("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');

	require ("config.inc.php");
	//require ("menu.inc.php");
	require ("functions.inc.php");
	$login=isauth();
	require ("menu.inc.php");
	if ($login == ""){
		header("Location:$urlauth");
	}
	else{
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<title><?php echo gettext("Interface d'administration de SambaEdu"); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="elements/style_sheets/sambaedu.css" />
<script type="text/javascript" language="JavaScript">
<!--
function MM_reloadPage(init)
{
//reloads the window if Nav4 resized
    if (init==true) with (navigator) {
        if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
            document.MM_pgW=innerWidth;
            document.MM_pgH=innerHeight;
            onresize=MM_reloadPage;
        }
    }
    else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH)
        location.reload();
}

MM_reloadPage(true);

function MM_findObj(n, d)
{
//v4.01
    var p,i,x;
    if(!d) d=document;
    if((p=n.indexOf("?"))>0&&parent.frames.length) {
        d=parent.frames[n.substring(p+1)].document;
        n=n.substring(0,p);
    }
    if(!(x=d[n])&&d.all) x=d.all[n];
    for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
    for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
    if(!x && d.getElementById) x=d.getElementById(n);
    return x;
}

function P7_autoLayers()
{
//v1.1 PVII
    var g,b,k,f,args=P7_autoLayers.arguments;
    if(!document.p7setc) {
        p7c=new Array();
        document.p7setc=true;
    }
    for(k=0; k<p7c.length; k++) {
        if((g=MM_findObj(p7c[k]))!=null) {
            b=(document.layers)?g:g.style;
            b.visibility="hidden";
        }
    }
    for(k=0; k<args.length; k++) {
        if((g=MM_findObj(args[k])) != null) {
            b=(document.layers)?g:g.style;
            b.visibility="visible";
            f=false;
            for(j=0;j<p7c.length;j++) {
                if(args[k]==p7c[j]) {f=true;}
            }
            if(!f) {p7c[p7c.length++]=args[k];}
        }
    }
}
//-->
</script>
</head>
<?php
		if (! isset($menu)) $menu=0;
		//echo "<body bgcolor=\"ghostwhite\" onLoad=\"P7_autoLayers('menu" . $menu ."')\">";
		echo "<body bgcolor=\"#f8f8ff\" onLoad=\"P7_autoLayers('menu" . $menu ."')\">";
		menuprint($login);

		include("pdp.inc.php");
	}
?>
