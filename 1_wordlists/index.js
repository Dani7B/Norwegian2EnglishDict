"use strict"

//Packages
const https = require('https');
const fs = require('fs');
const qs = require('querystring');
const path = require('path');

//Args
const HOST = 'en.wiktionary.org';
const PATH = '/w/api.php?';
const DIR = '';

if(DIR)
	fs.mkdirSync(DIR);

//Request function
const request_wiki = (cmtitle, file, cmcontinue, total = 0) => {
	
	if(!(file instanceof fs.WriteStream))
		file = fs.createWriteStream(path.join(DIR, file));
	
	const url = PATH + qs.stringify({
		'action': 'query',
		'list': 'categorymembers',
		'cmtitle': cmtitle,
		'cmlimit': '500',
		'cmtype': 'page',
		'format': 'json',
		'cmcontinue': cmcontinue,
	});
	
	//Make options
	const options = {
		host: HOST,
		path: url,
		method: 'GET',
		encoding: 'utf8',
	};
			
	//Make request
	const req = https.get(options, res => {
		
		let body = '';
		
		//Register listeners
		res.on('data', chunk => body += chunk);
		res.on('end', () => {
			
			//Parse body from JSON
			console.log(url);
			body = JSON.parse(body);
			
			//Get all words
			console.log(`[PROGRESS] Reading ${HOST + url}`);
			
			const words = body.query.categorymembers.map(e => e.title);
			
			//Check for broken character(s) in word(s)
		    if(words.some(e => e.includes('�')))
		        return console.log(`[ERROR] Error while reading page '${url}', retrying...`), request_wiki(cmtitle, file, cmcontinue, total);
		    
		    //Write words to file
		    words.forEach(e => file.write(`${e}\n`));
			
			const len = words.length;
			
			total += len;
			console.log(`[PROGRESS] Page read: ${len} words`);
			
			//Get new page link
			const next_page = (body.continue || {}).cmcontinue;

			if(!next_page)
				return console.log(`[SUCCESS] Read completed, parsed ${total} words, output file '${file.path}'`), file.end();
			
			//Query new page
			request_wiki(cmtitle, file, next_page, total);
		});
	});
};

//Query all pages
request_wiki('Category:Norwegian_Bokmål_adjectives', 'nb-NO_adjectives.txt');
request_wiki('Category:Norwegian_Bokmål_adverbs', 'nb-NO_adverbs.txt');
request_wiki('Category:Norwegian_Bokmål_conjunctions', 'nb-NO_conjunctions.txt');
request_wiki('Category:Norwegian_Bokmål_determiners', 'nb-NO_determiners.txt');
request_wiki('Category:Norwegian_Bokmål_nouns', 'nb-NO_nouns.txt');
request_wiki('Category:Norwegian_Bokmål_numerals', 'nb-NO_numerals.txt');
request_wiki('Category:Norwegian_Bokmål_prepositions', 'nb-NO_prepositions.txt');
request_wiki('Category:Norwegian_Bokmål_pronouns', 'nb-NO_pronouns.txt');
request_wiki('Category:Norwegian_Bokmål_verbs', 'nb-NO_verbs.txt');

request_wiki('Category:Norwegian_Nynorsk_adjectives', 'nn-NO_adjectives.txt');
request_wiki('Category:Norwegian_Nynorsk_adverbs', 'nn-NO_adverbs.txt');
request_wiki('Category:Norwegian_Nynorsk_conjunctions', 'nn-NO_conjunctions.txt');
request_wiki('Category:Norwegian_Nynorsk_determiners', 'nn-NO_determiners.txt');
request_wiki('Category:Norwegian_Nynorsk_nouns', 'nn-NO_nouns.txt');
request_wiki('Category:Norwegian_Nynorsk_numerals', 'nn-NO_numerals.txt');
request_wiki('Category:Norwegian_Nynorsk_prepositions', 'nn-NO_prepositions.txt');
request_wiki('Category:Norwegian_Nynorsk_pronouns', 'nn-NO_pronouns.txt');
request_wiki('Category:Norwegian_Nynorsk_verbs', 'nn-NO_verbs.txt');
