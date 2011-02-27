# shop-js encrypted message decoder in Python
# Copyright John Hanna (c) 2002 under the terms of the GPL
# see http://shop-js.sf.net for details and latest version

debug=0

import privateKey
import sys

def rc4(key, string):
    """Return string rc4 (de/en)crypted with RC4."""
    s,i,j,klen=range(256),0,0,len(key)
    for i in range(256):
        j=(ord(key[i%klen])+s[i]+j)%256
        s[i],s[j]=s[j],s[i]
    for i in range(256):
        j=(ord(key[i%klen])+s[i]+j)%256
        s[i],s[j]=s[j],s[i]
    r=''
    for i in range(len(string)):
        i2=i % 256
        j=(s[i2]+j)%256
        s[i2],s[j]=s[j],s[i2]
        r+=chr(ord(string[i])^s[(s[i2]+s[j])%256])
    return r

def inverse(x, n):
     """Return the mod n inverse of x."""
     y, a, b = n, 1, 0
     while y>0:
         x, (q, y) = y, divmod(x, y)
         a, b = b, a - b*q
     if a < 0:
         a = a + n
     assert x==1, "No inverse, GCD is %d" % x
     return a


def crt_RSA(m, d, p, q):
     """ Compute m**d mod p*q for RSA private key operations."""
     xp = pow(m % p, d%(p-1), p)
     xq = pow(m % q, d%(q-1), q)
     t = ((xq - xp) * inverse(p, q)) % q
     if t < 0:
         t = t + q
     return t * p + xp


b64s='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_"'
def base64ToText(text):
    r,m,a,c='',0,0,0
    for i in text[:]:
        c=b64s.find(i)
        if(c >= 0) :
             if(m):
                   r += chr((c << (8-m))& 255 | a)
             a = c >> m
             m+=2
             if(m==8):m=0
    return r

def t2b(s):
    r=0L
    m=1L
    for i in s[:]:
        r+=m*ord(i)
        m*=256L
    return r

def b2t(b):
    r=''
    while(b):
        r+=chr(b % 256)
        b>>=8
    return r

def fix(a):
    r=0L
    s=0
    for i in a[:]:
        r|=long(i) << s
        s+=28
    return r

def rsaDecode(key, text):
    """ decode the text based on the given rsa key. """
    # separate the session key from the text
    text=base64ToText(text)
    sessionKeyLength=ord(text[0])
    sessionKeyEncryptedText=text[1:sessionKeyLength+1]
    text=text[sessionKeyLength+1:]
    sessionKeyEncrypted=t2b(sessionKeyEncryptedText)

    # un-rsa the session key
    sessionkey=crt_RSA(sessionKeyEncrypted,fix(key[0]),fix(key[1]),fix(key[2]))
    #sessionkey=crt_RSA(sessionKeyEncrypted,fix(d),fix(p),fix(q))
    sessionkey=b2t(sessionkey)

    text=rc4(sessionkey,text)
    return text

if  debug:
     message=rsaDecode(privateKey.value, sys.argv[1])
     print "Debug :"
     print message

else:
    print rsaDecode(privateKey.value, sys.argv[1])

