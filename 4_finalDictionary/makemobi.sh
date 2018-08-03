#!/bin/bash
# Bash strict mode
set -euo pipefail

# Tests
if cat ../1_wordlists/nb-NO_*.txt | grep � >/dev/null; then
	echo "Failed test, Bokmål wordlist has the � symbol in it! Fix it and re-run the PHP script!"
	exit 1
fi
if cat ../1_wordlists/nn-NO_*.txt | grep � >/dev/null; then
	echo "Failed test, Nynorsk wordlist has the � symbol in it! Fix it and re-run the PHP script!"
	exit 1
fi
if ls ../2_wiktionaryDump/NB/* | grep � >/dev/null; then
	echo "Failed test, some Bokmål .json files have the � symbol in it! Fix it and re-run scrap.py!"
	exit 1
fi
if ls ../2_wiktionaryDump/NN/* | grep � >/dev/null; then
	echo "Failed test, some Nynorsk .json files have the � symbol in it! Fix it and re-run scrap.py!"
	exit 1
fi
# End of tests

cd ../3_definitionsAndInflections

NB="nb-NOtoENdictionary"
NN="nn-NOtoENdictionary"

# Since we may use multiple files (Wiki parser, custom definitions,...)
cat nb-NOtoENdictionary_*.txt > ../4_finalDictionary/nb-NOtoENdictionary.txt
cat nb-NOtoENdictionary_*.inf > ../4_finalDictionary/nb-NOtoENdictionary.inf
cat nn-NOtoENdictionary_*.txt > ../4_finalDictionary/nn-NOtoENdictionary.txt
cat nn-NOtoENdictionary_*.inf > ../4_finalDictionary/nn-NOtoENdictionary.inf
sort -o ../4_finalDictionary/nb-NOtoENdictionary.inf ../4_finalDictionary/nb-NOtoENdictionary.inf
sort -o ../4_finalDictionary/nn-NOtoENdictionary.inf ../4_finalDictionary/nn-NOtoENdictionary.inf

cd ../4_finalDictionary

echo "Converting tab-separated UTF-8 text file to .opf..."
./tab2opf.py ${NB}.txt >/dev/null
./tab2opf.py ${NN}.txt >/dev/null

# Do not mark them correctly as this makes it impossible to search in the dictionary on Kindle. See https://www.mobileread.com/forums/showthread.php?t=305372 for reasoning.
#echo "Marking the .opf files as nb-no and nn-no..."
#sed -i 's/<DictionaryInLanguage>en-us/<DictionaryInLanguage>nb-no/g' ./${NB}.opf
#sed -i 's/<DictionaryInLanguage>en-us/<DictionaryInLanguage>nn-no/g' ./${NN}.opf

echo "Generating Bokmål .mobi from the .opf..."
# The old Mobigen.exe seems to be faster and generate smaller files - tradeoffs?
wine kindlegen.exe ${NB}.opf &>/dev/null || true # This always fails because Amazon sucks - Warning(parser8):W26001: Index not supported for enhanced mobi.
echo "Generating Nynorsk .mobi from the .opf..."
wine kindlegen.exe ${NN}.opf &>/dev/null || true # This always fails because Amazon sucks - Warning(parser8):W26001: Index not supported for enhanced mobi.

# Remove useless files
#rm ${NB}0.html ${NB}.opf
#rm ${NB}1.html ${NB}2.html || true # In case the file is too long but that's not always the case

if [[ -e /run/media/${USER}/Kindle ]]; then
	echo "Detected connected kindle, copying dictionaries to it..."
	cp ${NB}.mobi /run/media/${USER}/Kindle/documents/dictionaries/
	cp ${NN}.mobi /run/media/${USER}/Kindle/documents/dictionaries/
	echo "Doing a 'sync' to ensure the files were copied correctly..."
	sync
fi
echo "Script done!"
