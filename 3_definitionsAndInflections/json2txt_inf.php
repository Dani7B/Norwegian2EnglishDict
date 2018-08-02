<?php
//	$data = file_get_contents('fly.json');
//$word="abandonere";

scrapShit("NB");
scrapShit("NN");


function scrapShit($languageToScrap) {
	$files = scandir("../2_wiktionaryDump/$languageToScrap/");
	$finalInflectionArray = array();
	$finalWordDefinitionArray = array();
	
	foreach($files as $file) {
		//do your work here
		if ($file != "." || $file != "..") {
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

	$arrayTempNumber = count($finalInflectionArray); // count() can't be the for rule if we're gonna unset from within
	for ($i = 0; $i < $arrayTempNumber; $i++) {
		$testExplode = explode(", ", $finalInflectionArray[$i]);
		if (count($testExplode) == 1){
			echo "ERROR - WORD HAS NO INFLECTIONS BUT IS IN .inf FILE: ".$finalInflectionArray[$i]."\n";
			unset($finalInflectionArray[$i]);
		}
	}
	// End of tests

	// This is a hack for a Kindle-specific firmware-level issue, when there's a direct definition for a word, it takes that and ignores inflections. It also ignores everything but the first inflected form if there are multiple.
	// This is why the script runs so long, this part is unoptimized as hell.
	// https://www.mobileread.com/forums/showthread.php?t=309147
	$finalInflectionArray     = array_values($finalInflectionArray);     // Sort arrays since unset() was used on them
	$finalWordDefinitionArray = array_values($finalWordDefinitionArray); // Sort arrays since unset() was used on them
	$tempCatWordDefArray = array();

	// For every word definition line
	for($i = 0; $i < count($finalWordDefinitionArray); $i++) {
		$wordDefExplode = explode("	", $finalWordDefinitionArray[$i], 2);
		// For every inflection line
		for ($j = 0; $j < count($finalInflectionArray); $j++) {
			$inflectionExplode = explode(", ", $finalInflectionArray[$j]);
			// For every inflection line member except the first one
			for ($k = 1; $k < count($inflectionExplode);$k++) {
				// If current inflection member matches the current word in a word definition line
				if ($inflectionExplode[$k] == $wordDefExplode[0]){
					// For every word definition line
					for($l = 0; $l < count($finalWordDefinitionArray); $l++) {
						$wordDefExplode2 = explode("	", $finalWordDefinitionArray[$l], 2);
						if($wordDefExplode2[0] == $inflectionExplode[0]) {
							echo "Definition for $inflectionExplode[0] will be copied to word $wordDefExplode[0]; Which means it'll look like |$wordDefExplode[0]	$wordDefExplode2[1]| COWABUNGA\n";
							array_push($tempCatWordDefArray, $wordDefExplode[0]."	".$wordDefExplode2[1]);
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
	for ($j = 0; $j < count($finalInflectionArray); $j++) {
		$inflectionExplode = explode(", ", $finalInflectionArray[$j]);
		// For every inflection line member except the first one
		for ($k = 1; $k < count($inflectionExplode);$k++) {
			if (in_array($inflectionExplode[$k], $tempTestInflectionArray)) {
				if ( ! in_array($inflectionExplode[$k], $finalTestInflectionArray)) {
					array_push($finalTestInflectionArray, $inflectionExplode[$k]);
//					echo count($finalTestInflectionArray)." - added $inflectionExplode[$k] to final\n";
				}
			}
			else {
				array_push($tempTestInflectionArray, $inflectionExplode[$k]);
//				echo count($tempTestInflectionArray)." - test\n";
			}
		}
	}

	for ($i = 0; $i < count($finalTestInflectionArray); $i++){
		// For every inflection line
		for ($g = 0; $g < count($finalInflectionArray); $g++) {
			$inflectionExplode = explode(", ", $finalInflectionArray[$g]);
			// For every inflection line member except the first one
			for ($k = 1; $k < count($inflectionExplode);$k++) {
				// If current inflection member matches the conflicting inflection list
				if ($inflectionExplode[$k] == $finalTestInflectionArray[$i]){
					// For every word definition line
					for($l = 0; $l < count($finalWordDefinitionArray); $l++) {
						$wordDefExplode2 = explode("	", $finalWordDefinitionArray[$l], 2);
						if($wordDefExplode2[0] == $inflectionExplode[0]) {
							echo "[DUPE] Definition for $inflectionExplode[0] will be copied to word $finalTestInflectionArray[$i]; Which means it'll look like |$finalTestInflectionArray[$i]	$wordDefExplode2[1]| COWABUNGA\n";
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

	//var_dump($finalInflectionArray);
}

function getDefinitions($word, &$finalInflectionArray, &$finalWordDefinitionArray, &$languageToScrap) {
	$verboseMode = "0";
	$data = file_get_contents("./$languageToScrap/$word.json");
	$inflectionString = $word;
	$wordDefinitionString = $word;
	$inflectionCheckArray = array($word);
	$json = json_decode($data);
	for ($i = 0; $i < count($json); $i++) { // There can be multiple $json's inside each other each with a number of definitions[]
		for ($z = 0; $z < count($json[$i]->definitions); $z++) {
			$defString = $json[$i]->definitions[$z]->text;
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
				$fuck[$x] = stripDefinitionGarbage($fuck[$x], $word, $finalInflectionArray);
				if($fuck[$x] != ""){
					if ($verboseMode == "1"){
						echo $fuck[$x]."\n";
					}
					$wordDefinitionString = $wordDefinitionString."	".$fuck[$x];
				}
			}
/*			if (count(explode(" ",$word)) > 1 ){
				echo "WARNING: DETECTED SPACE, WORD IS A PHRASE, IGNORING INFLECTIONS FOR WORD: ".$word;
				continue;
			}*/
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
			"(mostly used as a past participle)", "(inflections as for vin, musserende is indeclinable)", "(the drink itself)", "(a glass, bottle or can of beer)",
			$word." f, m", $word." f", $word." m",$word." n"];
			// ^ Since the inflection multiline definition always starts with "WORD m" for example, we remove those.

			for ($xy = 0; $xy < count($filterArrayFirst); $xy++) {
				$fuck[0] = str_replace($filterArrayFirst[$xy], '', $fuck[0]);
			}
			// Some rare cases like `skula`
			if ($word != "bie"){ // bie is retarded, maybe the 'or' in there should be removed?
				$fuck[0] = str_replace($word." (", '(', $fuck[0]);
			}
			// explode by ( so first element is "fly n " and second is "definite singular flyet, indefinite plural fly, definite plural flya or flyene)"
			$me = explode("(", $fuck[0]);
			// Trim the ending ) so we end up with "imperative fly, present tense flyr, simple past fløy, past participle flydd or fløyet"
			if (count($me) < 2) {
				if ($verboseMode == "1"){
					fwrite(STDOUT, "NOTICE: NO INFLECTIONS FOUND FOR THE WORD: ".$word.", JUMPING OUT\n");
				}
				continue;
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
					"present participle ", "not declined", "not inflected", "no gender", "gender indeterminate", "singular masculine ", "genitive form ", "masculine ", "imperative ", "passive ", "comparative ", "superlative ", "accusative ", "genitive ",
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

//			var_dump($inflectionArray);
			if ($verboseMode == "1"){
				echo "\n-----------------------------------------------------------------------------------------\n";
			}
	}
	if ($inflectionString != ""){ // Ehh why is this shit happening? First two lines are blank
		if ($verboseMode == "1"){
			echo "Inflections: $inflectionString\n\n";
		}
		//array_push($finalInflectionArray, $inflectionString);
	}
	if ($wordDefinitionString != ""){ // Ehh why is this shit happening? First two lines are blank
		array_push($finalWordDefinitionArray, $wordDefinitionString);
	}
}
function addInflection($inflectedWord, $inflectionToAdd, &$finalInflectionArray) {
	// If the array is fresh
	if (count($finalInflectionArray) == 0) {
		array_push($finalInflectionArray, $inflectedWord.", ".$inflectionToAdd);
		return;
	}
	// If not fresh, loop over it
	for($i = 0; $i < count($finalInflectionArray); $i++) { // If the array started from the end the code should be slightly faster but the perf difference is negligible at this scale
		$testExplode = explode(", ", $finalInflectionArray[$i]);
		if($testExplode[0] == $inflectedWord) {
			// Inflection already exists
			if (in_array($inflectionToAdd, $testExplode)){
				return;
			}
			$finalInflectionArray[$i] = $finalInflectionArray[$i].", ".$inflectionToAdd;
			return;
		}
	}
	// In case we're still here, the for loop did not find a result, so just create a new entry
	array_push($finalInflectionArray, $inflectedWord.", ".$inflectionToAdd);

}
function stripDefinitionGarbage($definitionToStrip, &$word, &$finalInflectionArray){
	$filterArray = ["indefinite masculine plural of ", "definite masculine singular of ", "definite singular and plural of ", "definite neuter plural of ", "neuter past participle of ", "indefinite singular past participle of ", "alternative form of ",
	"Alternative form of ", "masculine and feminine past participle of ", "masculine, feminine and neuter past participle of ", "singular definite of ",
	"definite singular of ", "past participle of ", "past tense of ", "comparative of ", "stressed form of ",
	"simple past of ", "neuter singular of ", "definite feminine singular of ", "feminine singular of ", "indefinite plural of ", "plural indefinite of ", "Indefinite plural of ", "definite plural of ", "plural form of ", "present tense of ", "definite superlative of ", "plural of ", "imperative of "];
	
	if ($definitionToStrip == ""){
		return $definitionToStrip;
	}
	echo "DefinitionToStrip is: $definitionToStrip\n";
	for($i = 0; $i < count($filterArray); $i++) {
		if(strpos($definitionToStrip, $filterArray[$i]) !== false) {
			echo "Before: $definitionToStrip\n";
			echo "filterArray[i] is $filterArray[$i]\n";
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
			echo "After: $strippedInflection\n";
			addInflection($strippedInflection, $word, $finalInflectionArray);
			return "";
			// remove $filterArray[$i] from $definitionToStrip and do something with the remainder
		}
	}
	// No filter was applied
	return $definitionToStrip;
}
//fram	alternative form of frem
?>
