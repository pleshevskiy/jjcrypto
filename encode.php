<?php

if (!isset($argv[1])) {
	die("Script required url to file");
}


// support method for generate template
function getCharKey($i, $maxPow) {
	$resultKey = "";
	for ($j = $maxPow; $j >= 0; $j--) {
		// i love bitmasks
		$resultKey .= $i & pow(2, $j) ? "$" : "_";
	}
	return $resultKey;
}

// check utf-8 character
function isUtf8($char) {
	return mb_detect_encoding($char) == "UTF-8";
}

// str_split bad working for utf-8...
// it's best alternative
function mbStringToArray($string) {
	$strlen = mb_strlen($string);
	while ($strlen) {
		$array[] = mb_substr($string, 0, 1, "UTF-8");
		$string = mb_substr($string, 1, $strlen, "UTF-8");
		$strlen = mb_strlen($string);
	}
	return $array;
}

// encode char
function encodeChar($char) {
	// get template
	global $substr;
	
	// write prefix for char code
	$result = '"\\\\"';

	// if character is utf-8
	if (isUtf8($char)) {
		// get utf-8 code without quotes and slash
		$code = substr(json_encode($char), 2, -1);
	} else {
		// convert decimal to octal
		$code = base_convert(ord($char), 10, 8);
		// fix code if digist < 3
		$code = str_pad($code, 3, 0, STR_PAD_LEFT) . '';
	}

	// convert string to array
	$digits = str_split($code);
	for ($i = 0; $i < count($digits); $i++) {
		// encode code
		$result .= '+$.' . array_search($digits[$i], $substr);
	}
	return $result;
}

// its characters need as it
$ignoreChars = '!"#$%&()*+,-./:;<>=?@[]^_`{|}~\'';

// some characters for decoding
$substr = array(
	'$_$_' => 'a',
	'$_$$' => 'b',
	'$$__' => 'c',
	'$$_$' => 'd',
	'$$$_' => 'e',
	'$$$$' => 'f',
	'_' => 'u',
);

// add to template digits
$i = -1;
while (++$i < 10) {
	$key = getCharKey($i, $i <= 7 ? 2 : 3);
	$substr[$key] = $i;
}


/**
 * START ENCODING!
 */
$scriptText = file_get_contents($argv[1]);
if (!$scriptText) {
	die("File not found\n");
}

// support variable for ignored charaters
$prevIgnored = false;
// for encoded text (result)
$encodedScript = "";
// split text
$chars = mbStringToArray($scriptText, 0, 1, "UTF-8");
// var_dump($chars);

// encoded each character
for ($i = 0; $i < count($chars); $i++) {
	// get character
	$ch = $chars[$i];

	// if character is ignored
	if (strpos($ignoreChars, $ch) != false) {
		// if not first character 
		// and prev element not ignored
		if ($i && !$prevIgnored) {
			// open quotes
			$encodedScript .= '+"';
		}

		// if character is quotes
		if ($ch == '"') {
			// need mirrors
			$encodedScript .= "\\\\\\";
		}

		// else rewrite character
		$encodedScript .= $ch;
		// mark as ignored
		$prevIgnored = true;
		// get next character
		continue;
	} else if ($prevIgnored) {
		// close quotes if character not ignored
		// and if prev character is ignored
		$encodedScript .= '"';
	}

	// add plus
	if ($i) {
		$encodedScript .= '+';
	}

	// search encoded character in template
	$ech = array_search($ch, $substr);

	// bug... if array_search is false then return "___"
	if ($ech == "___" ? $ch == '0' : $ech != false) {
		// add $. for decoder
		$encodedScript .= '$.'.$ech;
	} else {
		// encode charater ASCII \xxx or UTF-8 \uxxxx
		$encodedScript .= encodeChar($ch);
	}

	$prevIgnored = false;
}

if ($prevIgnored) {
	$encodedScript .= '"';
}

// Decode wrapper
$start = '$=~[];$={___:++$,$$$$:(![]+"")[$],__$:++$,$_$_:(![]+"")[$],_$_:++$,$_$$:({}+"")[$],$$_$:($[$]+"")[$],_$$:++$,$$$_:(!""+"")[$],$__:++$,$_$:++$,$$__:({}+"")[$],$$_:++$,$$$:++$,$___:++$,$__$:++$};$.$_=($.$_=$+"")[$.$_$]+($._$=$.$_[$.__$])+($.$$=($.$+"")[$.__$])+((!$)+"")[$._$$]+($.__=$.$_[$.$$_])+($.$=(!""+"")[$.__$])+($._=(!""+"")[$._$_])+$.$_[$.$_$]+$.__+$._$+$.$;$.$$=$.$+(!""+"")[$._$$]+$.__+$._+$.$+$.$$;$.$=($.___)[$.$_][$.$_];$.$($.$($.$$+"\""+';
$end = '+"\"")())();';

// wrap encoded script
$encodedScript = $start.$encodedScript.$end;

// write to file with ext .jj
file_put_contents($argv[1].'.jj', $encodedScript);