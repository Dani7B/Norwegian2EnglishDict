# NorwegianToEnglishDict

[Original Reddit thread](https://www.reddit.com/r/norsk/comments/8woal0/nbnoen_dictionarynorsk_ordbok_for_kindle_and/)

Since I couldn't find ANY decent NO>EN dictionary, much less a dictionary that supports inflections, I decided to roll my own.

I cobbled together some projects, made some scripts and the results are Norwegian Bokmål/Nynorsk to English dictionaries that are using the database from Wiktionary(which is very decent) and support inflections.

Do not be surprised at the Kindle-specific esoteric workarounds used in this project - in the words of based Amazon support: **"Dictionary features are not used often so they are not a priority"**

Preview: [Kindle4PC[0.13]](https://i.imgur.com/JPE2LzQ.png), [Kindle[0.12]](https://i.imgur.com/oqwwjdA.png), [Kindle[0.1]](https://i.imgur.com/EJ23F8b.png)

[Changelog](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/tags)

## Downloads

**Current version: 0.15 (2018-08-03)**

* Norwegian Bokmål (nb-NO>EN)
   * [.mobi dictionary](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nb-NOtoENdictionary.mobi) \- This is what you want for your e-reader. For Kindle just drop it in ../documents/dictionaries/
   * [.txt which contains dictionary entries](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nb-NOtoENdictionary.txt)
   * [.inf which contains the inflections](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nb-NOtoENdictionary.inf)
* Norwegian Nynorsk (nn-NO>EN)
   * [.mobi dictionary](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nn-NOtoENdictionary.mobi) \- This is what you want for your e-reader. For Kindle just drop it in ../documents/dictionaries/
   * [.txt which contains dictionary entries](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nn-NOtoENdictionary.txt)
   * [.inf which contains the inflections](https://gitlab.com/C0rn3j/NorwegianToEnglishDict/blob/master/4_finalDictionary/nn-NOtoENdictionary.inf)

Notice: **When updating to a newer version of a dictionary, restart (NOT RESET) your Kindle after copying it**, or you will run into bugs like [this](https://i.imgur.com/Tj8twU9.png) or [this](https://i.imgur.com/MKUdcEB.png). This is due to a decade old firmware bug that was not fixed yet, if you want it fixed, call Amazon support and tell them about it. They seem to think people don't use dictionary features that much, prove them wrong.

## FAQ

* Formatting could be better
   * Am aware. I plan on making it prettier + including all the inflected forms in the definition. TODO.
* There is a missing/incorrect word definition!
   * The word is most likely missing on Wiktionary. Add it there or at least add it as a request here: [https://en.wiktionary.org/wiki/Wiktionary:Requested\_entries\_(Norwegian)](https://en.wiktionary.org/wiki/Wiktionary:Requested_entries_(Norwegian))
   * It may have been added to Wiktionary since the last update of my dictionary.
   * If you are sure it is neither, it might be an issue with the Wiktionary parser I am using or with one of my scripts. Tell me about it!
* Could you do an EN>NO dictionary too?
   * No, I have no interest in doing so.
* Genitive forms are not in the dictionary.
   * TODO.
* I can't use Kindle search bar to search in this dictionary!
   * This is a Kindle-specific issue, read the linked Reasoning below for a workaround.
* The dictionary doesn't register as a Norwegian one, I have to manually select it per book!
   * This is on purpose, both dictionaries are marked as en-us>en-us otherwise it is impossible to search through them via the Dictionary search function on Kindle. [Reasoning](https://www.mobileread.com/forums/showthread.php?t=305372)

## Dev notes
`UpdateEverything.sh` is the script to execute to generate the dictionary, it'll run all the needed subscripts.

Resulting dictionaries are going to end up in `4_finalDictionary`.

The script first runs `index.js` in `1_wordlists` via node to generate a list of words to scrap.

Then it runs `scrap.py` from the root folder which scraps the whole wordlist into separate .json files. This will take an hour or two, and has a bug - seems like from time to time wiktionary fails to load(?) and the entry for that word just does not get scrapped.

You need to change the venv in `scrap.py` to one of your own and have wiktionaryparser installed in the venv through pip.

Then it runs `json2txt_inf.php` in `3_definitionsAndInflections`, which is a PHP script that processes the scrapped .json files and turns them into definition(.txt) and inflection(.inf) files.

Then it runs `makemobi.sh` in `4_finalDictionary`, which runs some tests to detect broken unicode(shouldn't happen), `cat`s the .inf and .txt files from `3_definitionsAndInflections`, runs `tab2opf.py` which is an old modified Python2 script to generate .html and .opf files. Finally it runs `kindlegen.exe` through WINE against the .opf files to generate the final .mobi dictionary.

If a connected Kindle is detected, it'll copy the .mobi files on it too.