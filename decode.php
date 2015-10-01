<?php

// Decoder wrapper
$start = '$=~[];$={___:++$,$$$$:(![]+"")[$],__$:++$,$_$_:(![]+"")[$],_$_:++$,$_$$:({}+"")[$],$$_$:($[$]+"")[$],_$$:++$,$$$_:(!""+"")[$],$__:++$,$_$:++$,$$__:({}+"")[$],$$_:++$,$$$:++$,$___:++$,$__$:++$};$.$_=($.$_=$+"")[$.$_$]+($._$=$.$_[$.__$])+($.$$=($.$+"")[$.__$])+((!$)+"")[$._$$]+($.__=$.$_[$.$$_])+($.$=(!""+"")[$.__$])+($._=(!""+"")[$._$_])+$.$_[$.$_$]+$.__+$._$+$.$;$.$$=$.$+(!""+"")[$._$$]+$.__+$._+$.$+$.$$;$.$=($.___)[$.$_][$.$_];$.$($.$($.$$+"\""+';
$end = '+"\"")())();';


if (!isset($argv[1])) {
	die("Decoder required encoded file");
}

// Read file
$r = file_get_contents($argv[1]);

// Replace decoder wrapper
$r = str_replace($start, "", $r);
$r = str_replace($end, "", $r);

// Template decoder
// from largest char length to smallest
$subst = array(
	array('$.$___','8'),
	array('$.$__$','9'),
	array('$.$_$_','a'),
	array('$.$_$$','b'),
	array('$.$$__','c'),
	array('$.$$_$','d'),
	array('$.$$$$','f'),
	array('$.$$$_','e'),
	array('$.___','0'),
	array('$.__$','1'),
	array('$._$_','2'),
	array('$._$$','3'),
	array('$.$__','4'),
	array('$.$_$','5'),
	array('$.$$_','6'),
	array('$.$$$','7'),
	array('$.$_', 'c'),
	array('$._$', 'o'),
	array('$.$$', 'n'),
	array('$.__', 't'),
	array('$.$', 'r'),
	array('$._', 'u'),
	array('"\\\\"' , '\\'),
	array('"+"', '%%%%'), // tmp
	array('+', ""),
	array('%%%%', "+")
);

// replace by template
foreach ($subst as $s) {
	$r = str_replace($s[0], $s[1], $r);
}

echo "$r\n";

// replace my quotes
$r = preg_replace('/([^\\\\])"([^"]+)"/', '${1}${2}', $r);

// ASCII decode
preg_match_all('/\\\\(\d{3})/', $r, $matches);
for ($i = 0; $i < count($matches[1]); $i++) {
	// convert octal to decimal
	$octal = base_convert($matches[1][$i], 8, 10);
	// get character and replace
	$r = str_replace($matches[0][$i], chr($octal), $r);
}

// UTF-8 decode
preg_match_all('/(\\\\u\w{4})/', $r, $matches);
for ($i = 0; $i < count($matches[1]); $i++) {
	// get character and replace
	$r = str_replace($matches[0][$i], json_decode('"'.$matches[1][$i].'"'), $r);
}

// show decoded code
echo $r;
// if need save file
// file_put_contents(substr($argv[1], 0, -3), $r);