<?php
//TODO - rewrite $finalInflectionArray so it's properly handled and I do not need to do explodes by ", " everywhere like a retard.
// 14:20 runtime on unused CPU


//	$data = file_get_contents('fly.json');
//$word="abandonere";

scrapShit("NB");
scrapShit("NN");


function scrapShit($languageToScrap) {
	$files = scandir("../2_wiktionaryDump/$languageToScrap/");
	$finalInflectionArray = array();
	$finalWordDefinitionArray = array();
	
	foreach($files as $file) {
		if ($file != "." && $file != "..") {
			$word = substr($file, 0, -5 );
			getDefinitions($word, $finalInflectionArray, $finalWordDefinitionArray, $languageToScrap);
		}
	}

	// Tests
	$arrayTempNumber = count($finalWordDefinitionArray); // count() can't be the for rule if we're gonna unset from within
	for ($i = 0; $i < $arrayTempNumber; $i++) {
		$testExplode = explode("	", $finalWordDefinitionArray[$i]);
		if (count($testExplode) == 1){
			echo "ERROR - WORD HAS NO DEFINITION BUT IS IN .txt FILE: ".$finalWordDefinitionArray[$i]."\n";
			unset($finalWordDefinitionArray[$i]);
		}
	}

	// Don't think this can happen after rewrite of addInflection()
/*	foreach($finalInflectionArray as $key => $arr) {
		$arrayTempNumber = count($finalInflectionArray); // count() can't be the for rule if we're gonna unset from within
		for ($i = 0; $i < $arrayTempNumber; $i++) {
			$testExplode = explode(", ", $finalInflectionArray[$i]);
			if (count($testExplode) == 1){
				echo "ERROR - WORD HAS NO INFLECTIONS BUT IS IN .inf FILE: ".$finalInflectionArray[$i]."\n";
				unset($finalInflectionArray[$i]);
			}
		}
	}
*/
	// End of tests

	// This is a hack for a Kindle-specific firmware-level issue, when there's a direct definition for a word, it takes that and ignores inflections. It also ignores everything but the first inflected form if there are multiple.
	// This is why the script runs so long, this part is unoptimized as hell.
	// https://www.mobileread.com/forums/showthread.php?t=309147
//	$finalInflectionArray     = array_values($finalInflectionArray);     // Sort arrays since unset() was used on them
	$finalWordDefinitionArray = array_values($finalWordDefinitionArray); // Sort arrays since unset() was used on them
	$tempCatWordDefArray = array();

	// For every word definition line
	for($i = 0; $i < count($finalWordDefinitionArray); $i++) {
		$wordDefExplode = explode("	", $finalWordDefinitionArray[$i], 2);
		// For every inflection line
		foreach($finalInflectionArray as $infParent => $infArray){
			foreach($infArray as $inflection) {
				// If current inflection member matches the current word in a word definition line
				if (removeAccents($inflection) == removeAccents($wordDefExplode[0])){
					// For every word definition line
					for($l = 0; $l < count($finalWordDefinitionArray); $l++) {
						$wordDefExplode2 = explode("	", $finalWordDefinitionArray[$l], 2);
//						if(removeAccents($wordDefExplode2[0]) == removeAccents($inflectionExplode[0])) {
						if($wordDefExplode2[0] == $infParent) {
								echo "Definition for $infParent will be copied to word $inflection; Which means it'll look like |$inflection	$wordDefExplode2[1]|\n";
							array_push($tempCatWordDefArray, $inflection."	".$wordDefExplode2[1]);
						}
					}
				}
			}
		}
	}


 // This part of the hack is for when there's two inflected forms of the word, since that's bugged too apparently.
	$tempTestInflectionArray = array();
	$finalTestInflectionArray = array();
	// For every inflection line
	foreach($finalInflectionArray as $infArray){
		foreach($infArray as $inflection) {
			if (in_array($inflection, $tempTestInflectionArray)) {
				if ( ! in_array($inflection, $finalTestInflectionArray)) {
					array_push($finalTestInflectionArray, $inflection);
					//echo count($finalTestInflectionArray)." - added $inflectionExplode[$k] to final\n";
				}
			}
			else {
				array_push($tempTestInflectionArray, $inflection);
				//echo count($tempTestInflectionArray)." - test\n";
			}
		}
	}

	for ($i = 0; $i < count($finalTestInflectionArray); $i++){
		foreach($finalInflectionArray as $infParent => $infArray){
			foreach($infArray as $inflection) {
				// If current inflection member matches the conflicting inflection list
				if ($inflection == $finalTestInflectionArray[$i]){
					// For every word definition line
					for($l = 0; $l < count($finalWordDefinitionArray); $l++) {
						$wordDefExplode2 = explode("	", $finalWordDefinitionArray[$l], 2);
//						if(removeAccents($wordDefExplode2[0]) == removeAccents($inflectionExplode[0])) {
						if($wordDefExplode2[0] == $infParent) {
							echo "[DUPE] Definition for $infParent will be copied to word $finalTestInflectionArray[$i]; Which means it'll look like |$finalTestInflectionArray[$i]	$wordDefExplode2[1]| COWABUNGA\n";
							array_push($tempCatWordDefArray, $finalTestInflectionArray[$i]."	".$wordDefExplode2[1]);
						}
					}
				}
			}
		}
	}
	// END of firmware-level bug workaround

	$finalWordDefinitionArray = array_merge($finalWordDefinitionArray, $tempCatWordDefArray);
	$finalWordDefinitionArray = array_unique($finalWordDefinitionArray, SORT_REGULAR);

	// Tests
	$finalWordDefinitionArray = array_values($finalWordDefinitionArray); // Sort array since array_unique() was used on it

	foreach($finalInflectionArray as $infParent => $infArray){
		for($j = 0; $j < count($finalWordDefinitionArray); $j++) {
			$wordDefExplode = explode("	", $finalWordDefinitionArray[$j], 2);
			if($wordDefExplode[0] == $infParent) {
				continue 2;
			}
		}
		fwrite(STDERR, "ERR:$languageToScrap: Inflected word ".$infParent." has no definition!\n");
	}
	// End of tests

	// Prep the inflection array so we can easily write it out to a file in the required format
	$infArr = array();
	foreach($finalInflectionArray as $infParent => $arr) {
		// Sort array so when diffing inflections before/after changes words just don't jump around in order.
		sort($arr);
		$line = $infParent;
		for ($i = 0; $i < count($arr); $i++){
			$line = $line.", $arr[$i]";
		}
		array_push($infArr, $line);
	}
	$finalInflectionArray = $infArr;

	// Write out arrays to appropriate files
	if ($languageToScrap == "NB"){
		$my_file = './nb-NOtoENdictionary_Wiktionary.inf';
		$handle = fopen($my_file, 'w') or die('Cannot open file: '.$my_file);
		fwrite($handle, implode("\n", $finalInflectionArray));

		$my_file = './nb-NOtoENdictionary_Wiktionary.txt';
		$handle = fopen($my_file, 'w') or die('Cannot open file: '.$my_file);
		fwrite($handle, implode("\n", $finalWordDefinitionArray));
	}
	if ($languageToScrap == "NN"){
		$my_file = './nn-NOtoENdictionary_Wiktionary.inf';
		$handle = fopen($my_file, 'w') or die('Cannot open file: '.$my_file);
		fwrite($handle, implode("\n", $finalInflectionArray));

		$my_file = './nn-NOtoENdictionary_Wiktionary.txt';
		$handle = fopen($my_file, 'w') or die('Cannot open file: '.$my_file);
		fwrite($handle, implode("\n", $finalWordDefinitionArray));
	}
}

function getDefinitions($word, &$finalInflectionArray, &$finalWordDefinitionArray, &$languageToScrap) {
	$verboseMode = "0";
	$data = file_get_contents("../2_wiktionaryDump/$languageToScrap/$word.json");
	$inflectionString = $word;
	$wordDefinitionString = $word;
	$json = json_decode($data);
	for ($i = 0; $i < count($json); $i++) { // There can be multiple $json's inside each other each with a number of definitions[]
		for ($z = 0; $z < count($json[$i]->definitions); $z++) {
			$tempInflections = array();
			$parentWord = "";
			$defString = "";
			for ($zz = 0; $zz < count($json[$i]->definitions[$z]->text); $zz++){
				$defString = $defString.$json[$i]->definitions[$z]->text[$zz]."\n";
			}
			if ($verboseMode == "1"){
				echo "Raw entry:\n";
				echo $defString."";
				echo "-----\n";
			}
			// Explode by new lines, we only need the first line, rest is definitions, not inflections.
			// > "fly n (definite singular flyet, indefinite plural fly, definite plural flya or flyene)"
			$fuck = explode("\n", $defString);

			if ($verboseMode == "1"){
				echo "\nWord definition:\n";
			}
			for ($x = 1; $x < count($fuck); $x++) {
				if (isInflectionLine($fuck[$x])){
					processInflectionLine($fuck[$x], $word, $tempInflections, $inflectionString, $verboseMode);
					continue;
				}
				$fuck[$x] = stripDefinitionGarbage($fuck[$x], $word, $tempInflections, $parentWord);
				if($fuck[$x] != ""){
					if ($verboseMode == "1"){
						echo $fuck[$x]."\n";
					}
					$wordDefinitionString = $wordDefinitionString."	".$fuck[$x];
				}
			}
			processInflectionLine($fuck[0], $word, $tempInflections, $inflectionString, $verboseMode);
			// IF $word NOW HAS INFLECTIONS FOR IT BUT NO DEFINITIONS, MOVE THE WHOLE INFLECTED $word INTO $parentWord
//			if ( (! wordDefExists($word, $finalWordDefinitionArray) && ! wordDefExists($word, $wordDefinitionString)) || $word == "grein" ) {
			$wordToAddInflectionsFor = $word;
			if ($parentWord != "" && ! wordDefExists($word, $finalWordDefinitionArray)){
//				fwrite(STDERR, "DBG: Inflections for the word $word will be moved into $parentWord!\n");
//				fwrite(STDERR, "DBG: $wordDefinitionString!\n");
				$wordToAddInflectionsFor = $parentWord;
			}
			// We can't add inflections on the fly directly to the final array be cause there are Alternative definitions like auke - https://en.wiktionary.org/wiki/auke#Norwegian_Bokm%C3%A5l
			// which we need to move the inflections of to the parent word.
//			var_dump($tempInflections);
			if (isset($tempInflections[$word])) {
				for ($q = 0; $q < count($tempInflections[$word]); $q++) {
					addInflection($wordToAddInflectionsFor, $tempInflections[$word][$q], $finalInflectionArray);
				}
			}
			if (isset($tempInflections[$parentWord])) {
				for ($q = 0; $q < count($tempInflections[$parentWord]); $q++) {
					addInflection($wordToAddInflectionsFor, $tempInflections[$parentWord][$q], $finalInflectionArray);
				}
			}
		}

			//var_dump($inflectionArray);
			if ($verboseMode == "1"){
				echo "\n-----------------------------------------------------------------------------------------\n";
			}
	}
	if ($verboseMode == "1"){
		echo "Inflections: $inflectionString\n\n";
	}
	//array_push($finalInflectionArray, $inflectionString);
	array_push($finalWordDefinitionArray, $wordDefinitionString);

	if ($inflectionString == "" || $wordDefinitionString == ""){
		echo "ERROR: inflectionString or wordDefinitionString is empty for word $word";
		exit(3);
	}
}

function wordDefExists($word, $finalWordDefinitionArray) {
	if (is_array($finalWordDefinitionArray)) {
		// Checks if $word has a definition in the word definition array
		for($i = 0; $i < count($finalWordDefinitionArray); $i++) {
			$wordDefExplode = explode("	", $finalWordDefinitionArray[$i], 2);
			if ($wordDefExplode[0] == $word){
				return true;
			}
		}
		return false;
	} 
	else {
		$wordDefExplode = explode("	", $finalWordDefinitionArray, 2);
		if ($wordDefExplode[0] == $word){
			return true;
		}
		return false;
	}
}


function isInflectionLine($line) {
	// Filter mostly same as inflection filter with a few deletions since definitions can easily contain things like "plural".
	$filterArray = ["inflections same as above", "inflections as above", "preceded by ei", "used as a modifier", "past tense and past participle ", "simple past and past participle ", "definite and plural form ",
	"past simple and past participle", "past tense and participle",
	"definite singular and plural ", "no plural form", "definite and plural ", "singular and plural ", "imperative and present tense ", "indefinite neuter singular ", "indefinite superlative ",
	"singular definite ", "definite superlative", "singular indefinite", "indefinite singular ", "indefinite plural", "dative form ", "definite plural", "definite form", "neuter singular ",
	"past participle ", "stressed form ",
	"present participle ", "not declined", "no gender", "gender indeterminate", "singular masculine ", "genitive form ", "imperative ", "passive form of",
	"past perfect ", "past tense ", "present tense ", "past tense ", "uppercase", "upper case", "lowercase", "lower case", "singulare tantum",
	"feminine singular ", "objective case "];
	for ($x = 0; $x < count($filterArray); $x++) {
		if(strpos($line, $filterArray[$x]) !== false) {
//			echo("$line removed because it matches '$filterArray[$x]'");
			return true;
		}
	}
	return false;
}

function processInflectionLine($line, $word, &$finalInflectionArray, $inflectionString, $verboseMode) {
	$inflectionCheckArray = array($word);

			// This array gets filtered out before any other processing
			$filterArrayFirst = ["(uncountable)", "(indeclinable)", "(mostly used in definite form)", "(not comparable)", "(superlative)", "(Until 2005)",
			"(usually in plural form)", "(especially in plural form)", "(mainly in plural form)", "(often reflexive, with seg / oneself)", "(mainly used in plural form)",
			"(mostly used in the plural form)", "(transitive)", "(intransitive)", "(also separable)", "(both countable and uncountable)", "(usually in definite form)",
			"(virtually never inflected)", "(often in the form gårsdagens (yesterday's))", "(often used in definite singular form)", "(usually in definite singular form)",
			"(often in plural form)", "(used only in definite form)", "(used mostly in the plural form)", "(after a number -  euro)", "(plural form of denne and dette)",
			"(also functioning as a conjunction)", "(modal verb)", "(countable or uncountable)", "(idiomatic use only)", "(used mostly in plural form)", "(usually in the plural form)",
			"(informal)", "(for other meanings see til and slutt)", "(normally used in definite singular form)", "(non-standard since 1938)", "(commonly used in the plural form)",
			"Before 1959:", "Before 1938:", "(used especially in the plural form)", "(inflections as for Etymology 1)", "(e-verb)", "(a-verb)", '(“the man with the scythe”)',
			"(inflection identical to the previous definition)", "(not inflected or declined in any way)", "(uncertain of plural forms, possibly same as Bokmål)",
			"(hardly used in plural form)", "(With a comparative or more and a verb phrase, establishes a parallel with one or more other such comparatives.)",
			"(mostly used as a past participle)", "(inflections as for vin, musserende is indeclinable)", "(the drink itself)", "(a glass, bottle or can of beer)", "(seg)",
			"(mainly plural)", "(reflexive)", "(literary, usually in definite form)",
			$word." f, m", $word." f", $word." m",$word." n"];
			// ^ Since the inflection multiline definition always starts with "WORD m" for example, we remove those.

			for ($xy = 0; $xy < count($filterArrayFirst); $xy++) {
				$line = str_replace($filterArrayFirst[$xy], '', $line);
			}
			// Some rare cases like `skula`
			if ($word != "bie"){ // bie is retarded, maybe the 'or' in there should be removed?
				$line = str_replace($word." (", '(', $line);
			}
			// explode by ( so first element is "fly n " and second is "definite singular flyet, indefinite plural fly, definite plural flya or flyene)"
			$me = explode("(", $line);
			// Trim the ending ) so we end up with "imperative fly, present tense flyr, simple past fløy, past participle flydd or fløyet"
			if (count($me) < 2) {
				if ($verboseMode == "1"){
					fwrite(STDOUT, "NOTICE: NO INFLECTIONS FOUND FOR THE WORD: ".$word.", JUMPING OUT\n");
				}
				return;
				#				continue;
			}
			// Since words like `antiklimaks` have more than one "line" with inflections (the parser does not actually make it a new line making it easy to work with)
			$kurva = "";
			for ($ac = 1; $ac < count($me); $ac++){
				$kurva = $kurva.rtrim($me[$ac],") ");
				$kurva = $kurva.", ";
			}
			$kurva = rtrim($kurva,") ");
			// Explode by , so we get entries like "past participle flydd or fløyet" or "simple past fløy"
			$inflectionArray = explode(",", $kurva);
			for ($j = 0; $j < count($inflectionArray); $j++) {
				// Garbage explode check, if the member is a single word only (detected by having no spaces), then trash it. It's some bullshit like "uncountable, indeclinable" etc.
				if (count(explode(" ", $inflectionArray[$j])) < 2) {
					if ($verboseMode == "1"){
						fwrite(STDOUT, "NOTICE: GARBAGE FOUND IN THE INFLECTIONS FIELD FOR WORD: $word, IT WAS: ".$inflectionArray[$j].", JUMPING OUT\n");
					}
					// NOTE: TODO; This "garbage" should be added to the definitions in some nice format.
					continue;
				}
				// Some entries are like "past participle flydd or fløyet", so separate by " or "
				$checkForOR = explode(" or ",$inflectionArray[$j]);
				$tempArray = array();
				for ($a = 0; $a < count($checkForOR); $a++) {
					$tempShit = explode("/",$checkForOR[$a]);
					for ($aa = 0; $aa < count($tempShit); $aa++) {
						array_push($tempArray, $tempShit[$aa]);
					}
				}
				$checkForOR = $tempArray;
				for ($k = 0; $k < count($checkForOR); $k++) {
					$filterArray = ["inflections same as above", "inflections as above", "preceded by ei", "used as a modifier", "past tense and past participle ", "simple past and past participle ", "definite and plural form ",
					"past simple and past participle", "past tense and participle",
					"definite singular and plural ", "no plural form", "definite and plural ", "singular and plural ", "masculine and feminine ", "imperative and present tense ", "indefinite neuter singular ", "indefinite superlative ",
					"singular definite ", "definite superlative", "singular indefinite", "indefinite singular ", "definite singular", "indefinite plural", "dative form ", "definite plural", "definite form", "neuter form ", "neuter singular ",
					"simple past ", "past participle ", "stressed form ",
					"present participle ", "not declined", "not inflected", "no gender", "gender indeterminate", "singular masculine ", "genitive form ", "masculine ", "imperative ", "passive form of", "passive ", "comparative ", "superlative ", "accusative ", "genitive ",
					"infinitive ", "plural", "neuter ", "past perfect ", "past tense ", "past ", "definite ", "present tense ", "present ", "past tense ", "uppercase", "upper case", "lowercase", "lower case", "singulare tantum",
					"feminine singular ", "feminine ", "objective case ", "objective", "possessive", "[1]"];

					for ($xx = 0; $xx < count($filterArray); $xx++) {
						$checkForOR[$k] = str_replace($filterArray[$xx], '', $checkForOR[$k]);
					}
					// Strip leading whitespace
					$checkForOR[$k] = ltrim($checkForOR[$k], " ");
					// Strip ending bracket, possible because entries are not standardized - død_og_maktesløs for example
					$checkForOR[$k] = rtrim($checkForOR[$k], ") ");

					// Now we only have a word/sentence that is the inflection
					$currentInflection = $checkForOR[$k];
					// Following is a check if Inflection is unique, as the inflections can be the same as the original word or repeat
					$shouldInflectionBeAdded = "yes";
					for ($y = 0; $y < count($inflectionCheckArray);$y++) {
						if ($inflectionCheckArray[$y] == $currentInflection || $currentInflection == "" || $currentInflection == "indeclinable" || $currentInflection == "declined" || $currentInflection == "uncountable" || $currentInflection == "gender" || $currentInflection == "-" || $currentInflection == "indeterminate" || strpos($currentInflection, 'abbreviat') !== false) {
							$shouldInflectionBeAdded = "no";
						}
					}
					if ($shouldInflectionBeAdded == "yes"){
						array_push($inflectionCheckArray, $currentInflection);
						$inflectionString = $inflectionString.", ".$currentInflection; // The variable is now only needed for debug since addInflection() was added instead of it
						addInflection($word, $currentInflection, $finalInflectionArray);
					}
				}
			}
}

// addInflection - $infArray is a two dimensional array, with the key being the word that's being inflected.
function addInflection($word, $inflection, &$infArray) {
	// Add tests if inflectionToAdd has a ',' or '.' or '"' or ';' or ')' or '(' #TODO
	// Throw out useless dupes
	if ($word == $inflection) {
		return;
	}
	// If the array with the word doesn't exist yet, just push it in.
	if (! isset($infArray[$word])) {
		$infArray[$word][] = $inflection;
		return;
	}
	// If the inflection we're trying to add is not already there, add it
	if (! in_array($inflection,$infArray[$word])) {
		array_push($infArray[$word], $inflection);
	}
}

function stripDefinitionGarbage($definitionToStrip, &$word, &$finalInflectionArray, &$parentWord) {
	$filterArray = ["indefinite singular form of ", "indefinite singular genitive of ","indefinite masculine plural of ", "definite masculine singular of ", "definite singular and plural of ", "definite neuter plural of ", "neuter past participle of ", "indefinite singular past participle of ", "alternative form of ",
	"Alternative form of ", "masculine and feminine past participle of ", "masculine, feminine and neuter past participle of ", "singular definite of ",
	"definite singular of ", "past participle of ", "past tense of ", "comparative of ", "stressed form of ",
	"simple past of ", "neuter singular of ", "definite feminine singular of ", "feminine singular of ", "Plural indefinite of ", "Plural definite of ", "indefinite plural of ", "plural indefinite of ", "Indefinite plural of ", "definite plural of ", "plural form of ", "present tense of ", "definite superlative of ", "plural of ", "imperative of "];
	if ($definitionToStrip == "") {
		return $definitionToStrip;
	}
//	echo "DefinitionToStrip is: $definitionToStrip\n";
	for($i = 0; $i < count($filterArray); $i++) {
		if(strpos($definitionToStrip, $filterArray[$i]) !== false) {
//			echo "Before: $definitionToStrip\n";
//			echo "filterArray[i] is $filterArray[$i]\n";
			//work around "(garbage) alternative of someword (more garbage)"
			$stripExplode = explode(") ", $definitionToStrip, 2);
			$stripExplode2 = explode(" (", $stripExplode[count($stripExplode)-1], 2);
			$definitionToStrip = $stripExplode2[0];
/*
definite singular veksa and vekse, vaksen
indefinite singular heimsøkja, heimsøkje, heimsøka and heimsøke, heimsøkt
indefinite singular kosa and kose, kost
indefinite singular vekkja, vekkje, vekka and vekke, vekt

masculine and feminine past participle of lita and lite singular definite of lit
definite singular past participle of veksa and vekse

NYNORSK
-dei    the (plural form of den and det, usually used in front of adjectives modifying plural nouns)    they    those
-somme  some (plural of som)
-somt   some (neuter singular of som)

Check these, check for `and` and `,` as a separator. Throw them out for now.
*/

			$strippedInflection = str_replace($filterArray[$i], '', $definitionToStrip);
//			echo "After: $strippedInflection\n";
			if ($filterArray[$i] == "Alternative form of " || $filterArray[$i] == "alternative form of ") {
				$parentWord = $strippedInflection;
			}
			addInflection($strippedInflection, $word, $finalInflectionArray);
			return "";
			// remove $filterArray[$i] from $definitionToStrip and do something with the remainder
		}
	}
	// No filter was applied
	return $definitionToStrip;
}
//fram	alternative form of frem
function removeAccents($str) {
	//This affects definitions, not inflections!
	# The full list makes this script run 20 times longer, so we just use å, æ, ø and the rare forms é and ô
#	$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å',  'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å',  'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı',  'Ĳ',  'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő',  'Œ',  'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ',  'Ǽ',  'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
#	$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
	$a = array('å', 'Å', 'æ',  'Æ',  'ø', 'Ø', 'é', 'É', 'ô', 'Ô');
	$b = array('a', 'A', 'ae', 'AE', 'o', 'O', 'e', 'E', 'o', 'O');
	return str_replace($a, $b, $str);
}
?>
