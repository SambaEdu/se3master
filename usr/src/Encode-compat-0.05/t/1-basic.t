#!/usr/bin/perl -w
# $File: //member/autrijus/Encode-compat/t/1-basic.t $ $Author: autrijus $
# $Revision: #3 $ $Change: 1122 $ $DateTime: 2002/10/01 01:40:18 $

use strict;
use Test;

BEGIN { plan tests => 8 }

ok(eval { use Encode::compat; 1 });

use Encode qw(decode from_to is_utf8 FB_HTMLCREF);

my $text = '°®©[';

ok(!is_utf8($text));
ok(length(decode(big5 => $text)), 2);
ok(is_utf8(decode(big5 => $text)));

from_to($text, 'big5eten' => 'utf8');
ok(!is_utf8($text));
ok(length($text), 6);

from_to($text, 'utf-8' => 'latin1', FB_HTMLCREF);
ok(!is_utf8($text));
ok($text, '&#20094;&#22372;');

__END__
