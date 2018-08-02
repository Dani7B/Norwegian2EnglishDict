#!/bin/bash
set -euo pipefail

mkdir -p ~/wiktionaryDump
if [[ ! -h 2_wiktionaryDump ]]; then
	ln -s ~/wiktionaryDump 2_wiktionaryDump
else
	rm ./2_wiktionaryDump/NB/*.json # While old entries get overwritten, some may have been removed totally, so remove all files before downloading
	rm ./2_wiktionaryDump/NN/*.json
fi

cd 1_wordlists
node index.js
cd ..
./scrap.py
cd 3_definitionsAndInflections
php json2txt_inf.php > /dev/null
cd ../4_finalDictionary
./makemobi.sh
