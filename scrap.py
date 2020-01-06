#!/usr/local/bin/python
import os
import requests
import json

from wiktionaryparser import WiktionaryParser

NB = 'norwegian bokmål'
NN = 'norwegian nynorsk'

parser = WiktionaryParser()


#word = parser.fetch("klatrestativ", NB)
#file = open('./2_wiktionaryDump/NB/klatrestativ.json', 'w')
#file.write(json.dumps(word))
#file.close()
#exit(0)

def scrap(linesToScrap, languageToScrap):
	for wordToScrap in linesToScrap:
	#	print(wordToScrap+"AWOO")
		if wordToScrap == "": # Newline at the end of file
			continue
		if languageToScrap == NB:
			try:
				word = parser.fetch(wordToScrap, NB)
				file = open('./2_wiktionaryDump/NB/'+wordToScrap+'.json', 'w')
				file.write(json.dumps(word))
				file.close()
				print("Scraped word '"+wordToScrap+"' in language "+NB)
			except:
				print("Word '"+wordToScrap+"' does not appear to be defined in language "+NB)
		if languageToScrap == NN:
			try:
				word = parser.fetch(wordToScrap, NN)
				file = open('./2_wiktionaryDump/NN/'+wordToScrap+'.json', 'w')
				file.write(json.dumps(word))
				file.close()
				print("Scraped word '"+wordToScrap+"' in language "+NN)
			except:
				print("Word '"+wordToScrap+"' does not appear to be defined in language "+NN)
	return "yay"


scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_adjectives.txt')],   NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_adverbs.txt')],      NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_conjunctions.txt')], NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_determiners.txt')],  NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_nouns.txt')],        NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_numerals.txt')],     NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_prepositions.txt')], NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_pronouns.txt')],     NB)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nb-NO_verbs.txt')],        NB)

scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_adjectives.txt')],   NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_adverbs.txt')],      NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_conjunctions.txt')], NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_determiners.txt')],  NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_nouns.txt')],        NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_numerals.txt')],     NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_prepositions.txt')], NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_pronouns.txt')],     NN)
scrap([line.rstrip('\n') for line in open('./1_wordlists/nn-NO_verbs.txt')],        NN)

print("Finished scrapping entries!")
#parser.set_default_language(NB) # No real use for it here

#print(json.dumps(parser.fetch("alvorligere",NB)))
#exit(0)
#wordToScrap = "behagelig"


exit(0)







from bs4 import BeautifulSoup
# <!--
# https://www.crummy.com/software/BeautifulSoup/bs4/doc/
##url = "https://en.wiktionary.org/wiki/fly"
##response = requests.get(url)
##source_code = response.content
##soup = BeautifulSoup(source_code, "html.parser")

##textOnly = soup.get_text()

##separatorEnd = "Retrieved from \"https://en.wiktionary.org"
##rest = textOnly.split(separatorEnd, 1)[0]
##separatorStart = "skins.vector.js\"]);});"
##rest = rest.split(separatorStart, 1)[1]

url = "https://en.wiktionary.org/wiki/fly"
response = requests.get(url)

source_code = response.content.decode()
# Remove bottom of the page by the perf comment, everything including it and after is useless for us
separatorEnd = "<!-- \n"
rest = source_code.split(separatorEnd, 1)[0]

# Remove everything before and including the contents tab, it's useless for us. (first <h2> is contents so we skip it)
separatorStart = "<h2>"
rest = rest.split(separatorStart, 2)[2]

# 'rest' is now a block of HTML code per-language
rest = rest.split(separatorStart, 999)

# 'rest' is now an array of every <h2> block
for x in range(len(rest)):
	# Throw the current block in loop into BS4 parser
	soup = BeautifulSoup(rest[x], "html.parser")
	# Get Language of the block
	blockLanguage = soup.find(class_="mw-headline").get_text()
	print("Language is: "+blockLanguage)
	if blockLanguage == "Norwegian":
		print("WTF Block language is 'NORWEGIAN' - TEST FAIL, EXITING")
		exit(123)

#	if blockLanguage == "Norwegian Bokmål":
	if blockLanguage == "Swedish":
		print("Matches language NB")
#		print(rest[x])
#		print(soup.get_text())
		awoo = soup.findAll(id="Verb*")
		print(awoo)

	if blockLanguage == "Norwegian Nynorsk":
		print("Matches language NN")
#		print(rest[x])

#	print(rest[x])

exit(0)



soup = BeautifulSoup(source_code, "html.parser")

print(soup.find_all('h2')[0].get_text())

textOnly = soup.get_text()

separatorEnd = "Retrieved from \"https://en.wiktionary.org"
rest = textOnly.split(separatorEnd, 1)[0]
separatorStart = "skins.vector.js\"]);});"
rest = rest.split(separatorStart, 1)[1]
##print(rest)

# print(soup.prettify())
