<?php
 namespace Symfony\Component\Inflector; final class Inflector { private static $pluralMap = array( array('a', 1, true, true, array('on', 'um')), array('ea', 2, true, true, 'a'), array('secivres', 8, true, true, 'service'), array('eci', 3, false, true, 'ouse'), array('esee', 4, false, true, 'oose'), array('i', 1, true, true, 'us'), array('nem', 3, true, true, 'man'), array('nerdlihc', 8, true, true, 'child'), array('nexo', 4, false, false, 'ox'), array('seci', 4, false, true, array('ex', 'ix', 'ice')), array('seifles', 7, true, true, 'selfie'), array('seivom', 6, true, true, 'movie'), array('teef', 4, true, true, 'foot'), array('eseeg', 5, true, true, 'goose'), array('hteet', 5, true, true, 'tooth'), array('swen', 4, true, true, 'news'), array('seires', 6, true, true, 'series'), array('sei', 3, false, true, 'y'), array('sess', 4, true, false, 'ss'), array('ses', 3, true, true, array('s', 'se', 'sis')), array('sevit', 5, true, true, 'tive'), array('sevird', 6, false, true, 'drive'), array('sevi', 4, false, true, 'ife'), array('sevom', 5, true, true, 'move'), array('sev', 3, true, true, array('f', 've', 'ff')), array('sexa', 4, false, false, array('ax', 'axe', 'axis')), array('sex', 3, true, false, 'x'), array('sezz', 4, true, false, 'z'), array('suae', 4, false, true, 'eau'), array('se', 2, true, true, array('', 'e')), array('s', 1, true, true, ''), array('xuae', 4, false, true, 'eau'), array('elpoep', 6, true, true, 'person'), ); private function __construct() { } public static function singularize($plural) { $pluralRev = strrev($plural); $lowerPluralRev = strtolower($pluralRev); $pluralLength = strlen($lowerPluralRev); foreach (self::$pluralMap as $map) { $suffix = $map[0]; $suffixLength = $map[1]; $j = 0; while ($suffix[$j] === $lowerPluralRev[$j]) { ++$j; if ($j === $suffixLength) { if ($j < $pluralLength) { $nextIsVocal = false !== strpos('aeiou', $lowerPluralRev[$j]); if (!$map[2] && $nextIsVocal) { break; } if (!$map[3] && !$nextIsVocal) { break; } } $newBase = substr($plural, 0, $pluralLength - $suffixLength); $newSuffix = $map[4]; $firstUpper = ctype_upper($pluralRev[$j - 1]); if (is_array($newSuffix)) { $singulars = array(); foreach ($newSuffix as $newSuffixEntry) { $singulars[] = $newBase.($firstUpper ? ucfirst($newSuffixEntry) : $newSuffixEntry); } return $singulars; } return $newBase.($firstUpper ? ucfirst($newSuffix) : $newSuffix); } if ($j === $pluralLength) { break; } } } return $plural; } } 