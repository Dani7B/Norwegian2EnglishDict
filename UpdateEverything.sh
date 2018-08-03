#!/bin/bash
set -euo pipefail

mkdir -p ~/wiktionaryDump
if [[ ! -h 2_wiktionaryDump ]]; then
	ln -s ~/wiktionaryDump 2_wiktionaryDump
else
	rm 2_wiktionaryDump # If it exists remove and recreate the symlink. This is to make sure it's pointed to the right home folder in case someone clones this repo.
	ln -s ~/wiktionaryDump 2_wiktionaryDump
	rm -f ./2_wiktionaryDump/NB/*.json # While old entries get overwritten by scrap.py, some may have been removed totally from Wiktionary,
	rm -f ./2_wiktionaryDump/NN/*.json #	so remove all files before downloading
fi

cd 1_wordlists
node index.js
cd ..
./scrap.py
cd 3_definitionsAndInflections
php json2txt_inf.php > /dev/null
cd ../4_finalDictionary
./makemobi.sh
