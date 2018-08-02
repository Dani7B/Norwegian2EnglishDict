#!/usr/bin/python2.7
# -*- coding: utf-8 -*-
#
# Script for conversion of Stardict tabfile (<header>\t<definition>
# per line) into the OPF file for MobiPocket Dictionary
#
# For usage of dictionary convert it by:
# (wine) kindlegen.exe DICTIONARY.opf
#
# Get kindlegen.exe here - https://www.amazon.com/gp/feature.html?ie=UTF8&docId=1000765211
#
# Copyright (C) 2007 - Petr 'Klokan' Přidal (www.klokan.cz)
# Modified by Krzysiek Palka (2013)
# Modified by C0rn3j (2018)
#
# Version history:
# 0.1 (19.7.2007) Initial version
# 0.1.1 (21.2.2013) Added inflection support
# 0.1.2 (2018) Minor edits related to usability, upgraded license to GPLv3(or later)
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Library General Public
# License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Library General Public License for more details.
#
# You should have received a copy of the GNU Library General Public
# License along with this library; if not, write to the
# Free Software Foundation, Inc., 59 Temple Place - Suite 330,
# Boston, MA 02111-1307, USA.

VERSION = "0.1.3"

# Necessary files:
# file.txt - dictionary in format headword 1[tab]definition 1[new line]headword 2[tab]definiton2
# file.inf - inflections in format: base of word 1, inflection 1, inflection 2[new line]base of word 2, inflection 1
#   Example: "one, oneself, somethingelse
#             blah, ble, blu"
# Script requires a single file parameter(either the .inf or .txt, it'll work with either)
# Encoding as UTF-8 is forced.

import sys
import re
import os

from unicodedata import normalize, decomposition, combining
import string
from exceptions import UnicodeEncodeError

# Hand-made table from PloneTool.py
mapping_custom_1 = {
138: 's', 142: 'z', 154: 's', 158: 'z', 159: 'Y' }

# UnicodeData.txt does not contain normalization of Greek letters.
mapping_greek = {
912: 'i', 913: 'A', 914: 'B', 915: 'G', 916: 'D', 917: 'E', 918: 'Z',
919: 'I', 920: 'TH', 921: 'I', 922: 'K', 923: 'L', 924: 'M', 925: 'N',
926: 'KS', 927: 'O', 928: 'P', 929: 'R', 931: 'S', 932: 'T', 933: 'Y',
934: 'F', 936: 'PS', 937: 'O', 938: 'I', 939: 'Y', 940: 'a', 941: 'e',
943: 'i', 944: 'y', 945: 'a', 946: 'b', 947: 'g', 948: 'd', 949: 'e',
950: 'z', 951: 'i', 952: 'th', 953: 'i', 954: 'k', 955: 'l', 956: 'm',
957: 'n', 958: 'ks', 959: 'o', 960: 'p', 961: 'r', 962: 's', 963: 's',
964: 't', 965: 'y', 966: 'f', 968: 'ps', 969: 'o', 970: 'i', 971: 'y',
972: 'o', 973: 'y' }

# This may be specific to German...
mapping_two_chars = {
140 : 'O', 156: 'o', 196: 'A', 246: 'o', 252: 'u', 214: 'O',
228 : 'a', 220: 'U', 223: 's', 230: 'e', 198: 'E' }

mapping_latin_chars = {
192 : 'A', 193 : 'A', 194 : 'A', 195 : 'a', 197 : 'A', 199 : 'C', 200 : 'E',
201 : 'E', 202 : 'E', 203 : 'E', 204 : 'I', 205 : 'I', 206 : 'I', 207 : 'I',
208 : 'D', 209 : 'N', 210 : 'O', 211 : 'O', 212 : 'O', 213 : 'O', 215 : 'x',
216 : 'O', 217 : 'U', 218 : 'U', 219 : 'U', 221 : 'Y', 224 : 'a', 225 : 'a',
226 : 'a', 227 : 'a', 229 : 'a', 231 : 'c', 232 : 'e', 233 : 'e', 234 : 'e',
235 : 'e', 236 : 'i', 237 : 'i', 238 : 'i', 239 : 'i', 240 : 'd', 241 : 'n',
242 : 'o', 243 : 'o', 244 : 'o', 245 : 'o', 248 : 'o', 249 : 'u', 250 : 'u',
251 : 'u', 253 : 'y', 255 : 'y' }

# Feel free to add new user-defined mapping. Don't forget to update mapping dict
# with your dict.

mapping = {}
mapping.update(mapping_custom_1)
mapping.update(mapping_greek)
mapping.update(mapping_two_chars)
mapping.update(mapping_latin_chars)

# On OpenBSD string.whitespace has a non-standard implementation
# See http://plone.org/collector/4704 for details
whitespace = ''.join([c for c in string.whitespace if ord(c) < 128])
allowed = string.ascii_letters + string.digits + string.punctuation + whitespace

def normalizeUnicode(text, encoding='humanascii'):
    """
    This method is used for normalization of unicode characters to the base ASCII
    letters. Output is ASCII encoded string (or char) with only ASCII letters,
    digits, punctuation and whitespace characters. Case is preserved.
    """
    unicodeinput = True
    if not isinstance(text, unicode):
        text = unicode(text, 'utf-8')
        unicodeinput = False

    res = ''
    global allowed
    if encoding == 'humanascii':
        enc = 'ascii'
    else:
        enc = encoding
    for ch in text:
        if (encoding == 'humanascii') and (ch in allowed):
            # ASCII chars, digits etc. stay untouched
            res += ch
            continue
        else:
            try:
                ch.encode(enc,'strict')
                res += ch
            except UnicodeEncodeError:
                ordinal = ord(ch)
                if mapping.has_key(ordinal):
                    # try to apply custom mappings
                    res += mapping.get(ordinal)
                elif decomposition(ch) or len(normalize('NFKD',ch)) > 1:
                    normalized = filter(lambda i: not combining(i), normalize('NFKD', ch)).strip()
                    # normalized string may contain non-letter chars too. Remove them
                    # normalized string may result to  more than one char
                    res += ''.join([c for c in normalized if c in allowed])
                else:
                    # hex string instead of unknown char
                    res += "%x" % ordinal
    if unicodeinput:
        return res
    else:
        return res.encode('utf-8')

OPFTEMPLATEHEAD1 = """<?xml version="1.0"?><!DOCTYPE package SYSTEM "oeb1.ent">

<!-- the command line instruction 'prcgen dictionary.opf' will produce the dictionary.prc file in the same folder-->
<!-- the command line instruction 'kindlegen dictionary.opf' will produce the dictionary.mobi file in the same folder-->

<package unique-identifier="uid" xmlns:dc="Dublin Core">

<metadata>
	<dc-metadata>
		<dc:Identifier id="uid">%s</dc:Identifier>
		<!-- Title of the document -->
		<dc:Title><h2>%s</h2></dc:Title>
		<dc:Language>EN</dc:Language>
	</dc-metadata>
	<x-metadata>
"""
OPFTEMPLATEHEADNOUTF = """		<output encoding="Windows-1252" flatten-dynamic-dir="yes"/>"""
OPFTEMPLATEHEAD2 = """
		<DictionaryInLanguage>en-us</DictionaryInLanguage>
		<DictionaryOutLanguage>en-us</DictionaryOutLanguage>
	</x-metadata>
</metadata>

<!-- list of all the files needed to produce the .prc file -->
<manifest>
"""

OPFTEMPLATELINE = """ <item id="dictionary%d" href="%s%d.html" media-type="text/x-oeb1-document"/>
"""

OPFTEMPLATEMIDDLE = """</manifest>


<!-- list of the html files in the correct order  -->
<spine>
"""

OPFTEMPLATELINEREF = """	<itemref idref="dictionary%d"/>
"""

OPFTEMPLATEEND = """</spine>

<tours/>
<guide> <reference type="search" title="Dictionary Search" onclick= "index_search()"/> </guide>
</package>
"""

################################################################
# MAIN
################################################################

#UTFINDEX = False
#if len(sys.argv) > 1:
    #FILENAME = sys.argv[1]
    #if sys.argv[1] == '-utf':
        #UTFINDEX = True
        #FILENAME = sys.argv[2]
    #else:
        #FILENAME = sys.argv[1]
#else:
    #print "tab2opf (Stardict->MobiPocket)"
    #print "------------------------------"
    #print "Version: %s" % VERSION
    #print "Copyright (C) 2007 - Klokan Petr Pridal"
    #print
    #print "Usage: python tab2opf.py [-utf] DICTIONARY.tab"
    #print
    #print "ERROR: You have to specify a .tab file"
    #sys.exit(1)
if len(sys.argv) > 1:
    # Use first argument and strip extension from it
    FILENAME = os.path.splitext(sys.argv[1])[0]

else:
    print("tab2opf (Stardict->MobiPocket)")
    print("------------------------------")
    print("Version: %s" % VERSION)
    print("Copyright (C) 2007 - Petr 'Klokan' Pridal")
    print()
    print("Usage: python2.7 tab2opf.py filename.txt")
    print()
    print("ERROR: You have to specify a .txt or .inf file")
    sys.exit(1)
UTFINDEX = True

fr = open(FILENAME+".txt",'rb')
name = os.path.splitext(os.path.basename(FILENAME+".txt"))[0]

odm = {}
odmianay = open(FILENAME+".inf")
for line in odmianay:
    try:
        line = re.search('(?P<hea>[^,]*?), (?P<def>.*)', line)
        podstawa = line.group('hea')
        odmiany = line.group('def')
        odmiana = '<idx:infl inflgrp="odmiany">\n'
        for slowo in odmiany.split(', '):
            odmiana += "            <idx:iform name=\"odmiana\" value=\"%s\" exact=\"yes\" />\n" % (slowo)
        odmiana += '</idx:infl>'
        odm[podstawa.lower()] = odmiana
    except:
        pass

i = 0
to = False

for r in fr.xreadlines():

    if i % 10000 == 0:
        if to:
            to.write("""
                </mbp:frameset>
              </body>
            </html>
            """)
            to.close()
        to = open("%s%d.html" % (name, i / 10000), 'w')

        to.write(u"""<?xml version="1.0" encoding="utf-8"?>
<html xmlns:idx="www.mobipocket.com" xmlns:mbp="www.mobipocket.com" xmlns:xlink="http://www.w3.org/1999/xlink">
  <body>
    <mbp:pagebreak/>
    <mbp:frameset>
      <mbp:slave-frame display="bottom" device="all" breadth="auto" leftmargin="0" rightmargin="0" bottommargin="0" topmargin="0">
        <div align="center" bgcolor="yellow"/>
        <a onclick="index_search()">Dictionary Search</a>
        </div>
      </mbp:slave-frame>
      <mbp:pagebreak/>
""")

    dt, dd = r.split('\t',1)
    if not UTFINDEX:
        dt = normalizeUnicode(dt,'cp1252')
        dd = normalizeUnicode(dd,'cp1252')
    dtstrip = normalizeUnicode( dt )
    if not dt.lower() in odm:
        odm[dt.lower()] = ''
    dd = dd.replace("\\\\","\\").replace("\\n","<br/>\n").replace('\t',"<br/>\n")
    to.write("""      <idx:entry name="word" scriptable="yes">
        <h2>
          <idx:orth>%s %s</idx:orth>
        </h2>
        %s
      </idx:entry>
      <hr />
""" % (dt, odm[dt.lower()], dd))
    print(dt)
    i += 1

to.write("""
    </mbp:frameset>
  </body>
</html>
""")
to.close()
fr.close()
lineno = i - 1

to = open("%s.opf" % name, 'w')
to.write(OPFTEMPLATEHEAD1 % (name, name))
if not UTFINDEX:
    to.write(OPFTEMPLATEHEADNOUTF)
to.write(OPFTEMPLATEHEAD2)
for i in range(0,(lineno/10000)+1):
    to.write(OPFTEMPLATELINE % (i, name, i))
to.write(OPFTEMPLATEMIDDLE)
for i in range(0,(lineno/10000)+1):
    to.write(OPFTEMPLATELINEREF % i)
to.write(OPFTEMPLATEEND)
