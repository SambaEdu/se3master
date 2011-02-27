<?php

   /**
   
   * Gestion de la cle public pour l'authentification  
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs JLC Jean Luc Chertien (Caen) 

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: setup_keys.php
   */


include "config.inc.php";
include "functions.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


$login=isauth();
if ($login == "") header("Location:$urlauth");

// Calcul du random seed
$allow = "abcdef0123456789";
srand((double)microtime()*1000000);
for($j=0; $j<2; $j++) {
        $RandomSeed .=  $allow[rand()%strlen($allow)];
}
for  ($i=0; $i<1023; $i++) {
        $tmp="";
        for($j=0; $j<2; $j++) {
                $tmp .= $allow[rand()%strlen($allow)];
        }
        $RandomSeed.=" ".$tmp." ";
}


?>
                <script language = 'javascript' type = 'text/javascript' src="crypto.js"></script>
                <script language = 'javascript' type = 'text/javascript' src="public_key.js"></script>
                <script language = 'javascript' type = 'text/javascript'>
                <!--
// seed the random number generator with entropy in s
function seed(s) {
 rSeed=[];
 var n=0,nn=0;
 while(n < s.length) {
  while(n<s.length && s.charCodeAt(n)<=32) n++;
  if(n < s.length) rSeed[nn]=parseInt("0x"+s.substr(n,2));
  n+=3; nn++;
 }

 var x, y, t;
 Rs=[];
 Rsl=rSeed.length;
 Sr= r(256)
 Rbits=0

 if(Rs.lengh==0) {for (x=0; x<256; x++) Rs[x]=x;}
 y=0
 for (x=0; x<256; x++) {
  y=(rSeed[x] + s[x] + y) % 256
  t=s[x]; s[x]=s[y]; s[y]=t
 }
 Rx=Ry=0;
//alert("Random seed updated. Seed size is: "+Rsl);
}
// generate a random number 0 .. 255
// uses entropy from seed
function rc() {
  // this first bit is basically RC4
  Rx=++Rx & 255;
  Ry=( Rs[Rx] + Ry) & 255;
  var t=Rs[Rx]; Rs[Rx]=Rs[Ry]; Rs[Ry]=t;
  Sr^= Rs[(Rs[Rx] + Rs[Ry]) & 255];

  // xor with javascripts rand, just in case there's good entropy there
  Sr^= r(256);

  Sr^= ror(rSeed[r(Rsl)],r(8));
  Sr^= ror(rSeed[r(Rsl)],r(8));
  return Sr;
}
// javascript's random 0 .. n
function r(n) {return  Math.floor(Math.random()*n);}
// rotate right
//function ror(a,b) {return b?((a<<b)|(a>>(8-b))&255):a;}
// random number between 0 .. n -- based on repeated calls to rc
function rand(n) {
 if(n==2) {
  if(! Rbits) {
   Rbits=8;
   Rbits2=rc(256);
  }
  Rbits--;
  var r=Rbits2 & 1;
  Rbits2>>=1;
  return r;
 }
 var m=1, r=0;
 while (n>m && m > 0) {
  m<<=8; r=(r<<8) |rc();
 }
 if(r<0) r ^= 0x80000000;
 return r % n;
}

tstval=[1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]

// functions for generating keys-----------------------------
function bgcd(uu,vv) { // return greatest common divisor
 // algorythm from http://algo.inria.fr/banderier/Seminar/Vallee/index.html
 var d, t, v=vv.concat(), u=uu.concat()
 for(;;) {
  d=bsub(v,u)
  if(beq(d,[0])) return u
  if(d.length) {
   while((d[0] & 1) ==0)
    d=brshift(d).a // v=(v-u)/2^val2(v-u)
   v=d
  } else {
   t=v; v=u; u=t // swap u and v
  }
 }
}

function rnum(bits) {
 var n,b=1,c=0
 var a=[]
 if(bits==0) bits=1
 for(n=bits; n>0; n--) {
  if(rand(2)) {
   a[c]|=b
  }
  b<<=1
  if(b==bx2) {
   b=1; c++
  }
 }
 return a
}

// function to generate keys
function genkey(bits,f) {
 bits=parseInt(bits)*8
 var q,p,p1q1,n,factorMe,d,e,r
 var c,cc,ccc,pq
 q=mpp(bits); p=mpp(bits)
 f.p.value=p; f.q.value=q
 p1q1=bmul(bsub(p,[1]),bsub(q,[1]))
 for(c=5; c<Primes.length; c++) {
  e=[Primes[c]]
  d=modinverse(e,p1q1)
  if(d.length != 1 || d[0]!=0) break
 }
 f.d.value=d; f.e.value=e; f.pq.value=(pq=bmul(p,q))
 // test
 c=bmod(tstval,pq)
 cc=bmodexp(c,e,pq)
 ccc=crt_RSA(cc,d,p,q)
 return
}

function parseArray(a) {
 a=a.split(",")
 for(var n=0; n<a.length; n++) {
  a[n]=parseInt(a[n])
 }
 return a
}

function enc(f) {
 f.text.value=rsaEncode(parseArray(f.e.value),parseArray(f.pq.value),f.text.value)
}
function dec(f) {
 f.text.value=rsaDecode([parseArray(f.d.value),
  parseArray(f.p.value),
  parseArray(f.q.value)],
  f.text.value)
}

function encrypt(f) {
        encode = f.p.value+"|"+f.q.value+"|"+f.pq.value+"|"+f.d.value+"|"+f.e.value;
        f.keys.value=rsaEncode(public_key_e,public_key_pq,encode);
        // Reset des valeurs des cles pour ne pas les transmettre en clair
        f.p.value="";
        f.q.value="";
        f.pq.value="";
        f.d.value="";
        f.e.value="";
}

Primes=[3, 5, 7, 11, 13, 17, 19,
	23, 29, 31, 37, 41, 43, 47, 53,
	59, 61, 67, 71, 73, 79, 83, 89,
	97, 101, 103, 107, 109, 113, 127, 131,
	137, 139, 149, 151, 157, 163, 167, 173,
	179, 181, 191, 193, 197, 199, 211, 223,
	227, 229, 233, 239, 241, 251, 257, 263,
	269, 271, 277, 281, 283, 293, 307, 311,
	313, 317, 331, 337, 347, 349, 353, 359,
	367, 373, 379, 383, 389, 397, 401, 409,
	419, 421, 431, 433, 439, 443, 449, 457,
	461, 463, 467, 479, 487, 491, 499, 503,
	509, 521, 523, 541, 547, 557, 563, 569,
	571, 577, 587, 593, 599, 601, 607, 613,
	617, 619, 631, 641, 643, 647, 653, 659,
	661, 673, 677, 683, 691, 701, 709, 719,
	727, 733, 739, 743, 751, 757, 761, 769,
	773, 787, 797, 809, 811, 821, 823, 827,
	829, 839, 853, 857, 859, 863, 877, 881,
	883, 887, 907, 911, 919, 929, 937, 941,
	947, 953, 967, 971, 977, 983, 991, 997,
	1009, 1013, 1019, 1021]


sieveSize=4000
sieve0=-1* sieveSize
sieve=[]

lastPrime=0
function nextPrime(p) { // returns the next prime > p
 var n
 if(p == Primes[lastPrime] && lastPrime <Primes.length-1) {
  return Primes[++lastPrime]
 }
 if(p<Primes[Primes.length-1]) {
  for(n=Primes.length-2; n>0; n--) {
   if(Primes[n] <= p) {
    lastPrime=n+1
    return Primes[n+1]
   }
  }
 }
 // use sieve and find the next one
 var pp,m
 // start with p
 p++; if((p & 1)==0) p++
 for(;;) {
  // new sieve if p is outside of this sieve's range
  if(p-sieve0 > sieveSize || p < sieve0) {
   // start new sieve
   for(n=sieveSize-1; n>=0; n--) sieve[n]=0
   sieve0=p
   primes=Primes.concat()
  }

  // next p if sieve hit
  if(sieve[p-sieve0]==0) { // sieve miss

   // update sieve if p divisable

   // find a prime divisor
   for(n=0; n<primes.length; n++) {
    if((pp=primes[n]) && p % pp ==0) {
     for(m=p-sieve0; m<sieveSize; m+=pp) sieve[m]=pp
     p+=2;
     primes[n]=0
     break
    }
   }
   if(n >= primes.length) {
    // possible prime
    return p
   }
  } else {
    p+=2;
  }
 }

}

function divisable(n,max) { //return a factor if n is divisable by a prime less than max, else return 0
 if((n[0] & 1) == 0) return 2
 for(c=0; c<Primes.length; c++) {
  if(Primes[c] >= max) return 0
  if(simplemod(n,Primes[c])==0)
   return Primes[c]
 }
 c=Primes[Primes.length-1]
 for(;;) {
  c=nextPrime(c)
  if(c >= max) return 0
  if(simplemod(n,c)==0)
   return c
 }
}

function bPowOf2(pow) { // return a bigint
 var r=[], n, m=Math.floor(pow/bs)
 for(n=m-1; n>=0; n--) r[n]=0;
 r[m]= 1<<(pow % bs)
 return r
}

function mpp(bits) { //returns a Maurer Provable Prime, see HAC chap 4 (c) CRC press
 if(bits < 10) return [Primes[rand(Primes.length)]]
 if(bits <=20) return [nextPrime(rand(1<<bits))]
 var c=10, m=20, B=bits*bits/c, r, q, I, R, n, a, b, d, R2, nMinus1
 if(bits > m*2) {
  for(;;) {
   r=Math.pow(2,Math.random()-1)
   if(bits - r * bits > m) break
  }
 } else {
  r=0.5
 }
 q=mpp(Math.floor(r*bits)+1)
 I=bPowOf2(bits-2)
 I=bdiv(I,q).q
 Il=I.length
 for(;;) {
  // generate R => I < R < 2I
  R=[]; for(n=0; n<Il; n++) R[n]=rand(bx2);
  R[Il-1] %= I[Il-1]; R=bmod(R,I);
  if(! R[0]) R[0]|=1 // must be greater or equal to 1
  R=badd(R,I)
  n=blshift(bmul(R,q),1) // 2Rq+1
  n[0]|=1
  if(!divisable(n,B)) {
   a=rnum(bits-1)
   a[0]|=2 // must be greater than 2
   nMinus1=bsub(n,[1])
   var x=bmodexp(a,nMinus1,n)
   if(beq(x,[1])) {
    R2=blshift(R,1)
    b=bsub(bmodexp(a,R2,n),[1])
    d=bgcd(b,n)
    if(beq(d,[1])) return n
   }
  }
 }
}

seed('<?php echo $RandomSeed
          ?>');
// -->
</script>
<?php

include ("includes/entete.inc.php");

//aide 
$_SESSION["pageaide"]="L\'interface_web_administrateur#Partie_:_Param.C3.A9trage_de_l.27interface_SambaEdu.";

if (is_admin("Annu_is_admin",$login)=="Y") {
?>
<h1><?php echo gettext("G&#233;n&#233;ration d'un nouveau jeu de cl&#233;s d'authentification"); ?></h1>
<p>
<form name="t" action="save_keys.php" method="post">
<p>Longueur de la cl&#233; en octets : <input type=text name=keylen size=3 value=8>&nbsp;&nbsp;
<input type="hidden" name="keygen" value="true">
<input type="button" value="G&#233;n&#233;ration des cl&#233;s" onClick="genkey(document.t.keylen.value,document.t)"><br>
<hr width="80%">
        prime factor p : <input type=text name=p value="<?php echo $p ?>" size=30><br>
        prime factor q : <input type=text name=q value="<?php echo $q ?>" size=30><br>
        Public Modulo (p*q): <input type=text name=pq value="<?php echo $pq ?>" size=50><br>
        Private exponent (d): <input type=text name=d  value="<?php echo $d ?>" size=50 ><br>
        Public exponent (e): <input type=text name=e value="<?php echo $e ?>" size=5><br>
<hr width="80%">
<div align='center'>
        <h2><?php echo gettext("V&#233;rification des cl&#233;s"); ?></h2>
        <input name='text' rows='8', cols='50' value='Texte &#224; crypter'>
        <input type='button' value='Cryptage' onClick='enc(document.t)'>
        <input type='button' value='D&#233;cryptage' onClick='dec(document.t)'>
        <input type='hidden' name='keys' value=''>
        <p><input type='submit' value='<?php echo gettext("V&#233;rification des cl&#233;s OK ? Sauvegarde des cl&#233;s !"); ?>' onClick='encrypt(document.t)'>
</div>
<?php
} else {
        echo "<div class=alert_msg>".gettext("Cette fonctionnalit&#233;, n&#233;cessite les droits d'administrateur du serveur LCS !")."</div>";
}

include ("includes/pdp.inc.php");
?>




