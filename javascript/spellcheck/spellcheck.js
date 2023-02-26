/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
/*
	ORIGINAL LICENCE INFORMAION:
	Pungo Spell Copyright (c) 2003 Billy Cook, Barry Johnson

	Permission is hereby granted, free of charge, to any person obtaining a copy of
	this software and associated documentation files (the "Software"), to deal in
	the Software without restriction, including without limitation the rights to
	use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
	the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
	FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
	COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
	IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
	CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function spellCheck( formName, fieldName, spelltext ) {
	var h_spellform						= document.forms["spell_form"];
	h_spellform.spell_formname.value	= formName;
	h_spellform.spell_fieldname.value	= fieldName;
	h_spellform.spellstring.value		= document.forms[formName][fieldName].value;

	openSpellWin();
	h_spellform.submit();
	
	return;
}

var wordindex		= -1;
var offsetindex		= 0;
var ignoredWords	= Array();

function misp(word, start, end, suggestions) {
	this.word		= word;		// the word
	this.start		= start;		// start index
	this.end			= end;		// end index
	this.suggestions	= suggestions;	// array of suggestions
}

function replaceWord() {
	var frm			= document.fm1;
	var strstart	= '';
	var strend;

	if(misps[wordindex].start != 0 ) {
		strstart = mispstr.slice( 0, misps[wordindex].start + offsetindex);
	}

	strend			= mispstr.slice( misps[wordindex].end + 1 + offsetindex);
	mispstr			= strstart +  frm.changeto.value  + strend;

	offsetindex += frm.changeto.value.length - misps[wordindex].word.length;
	misps[wordindex].word	= frm.changeto.value;

	nextWord(false);
}

function replaceAll() {
	var frm				= document.fm1;
	var strstart			= '';
	var strend;
	var idx;
	var origword;
	var localoffsetindex	= offsetindex;

	origword				= misps[wordindex].word;

	for(idx = wordindex; idx < misps.length; idx++) {
		misps[idx].start += localoffsetindex;
		misps[idx].end += localoffsetindex;
	}

	localoffsetindex		= 0;

	for(idx = 0; idx < misps.length; idx++) {
		if(misps[idx].word == origword) {
			if(misps[idx].start != 0) {
				strstart = mispstr.slice(0, misps[idx].start + localoffsetindex);
			}

			strend		= mispstr.slice( misps[idx].end + 1 + localoffsetindex);
			mispstr		= strstart +  frm.changeto.value  + strend;

			localoffsetindex += frm.changeto.value.length - misps[idx].word.length;
		}
		misps[idx].start += localoffsetindex;
		misps[idx].end += localoffsetindex;
	}

	ignoredWords[origword]	= 1;
	offsetindex			= 0;

	nextWord(false);
}

function hilightWord() {
	var strstart = '';
	var strend;

	if(misps[wordindex].start != 0) {
		strstart = mispstr.slice( 0, misps[wordindex].start + offsetindex);
	}
	
	// get the end of the string after the word we are replacing
	strend = mispstr.slice(misps[wordindex].end + 1 + offsetindex);

	// rebuild the string with a span wrapped around the misspelled word 
	// so we can hilight it in the div the user is viewing the string in

	//var divptr = document.getElementById("strview");
	var divptr = iFrameBody;

	divptr.innerHTML = '';
	divptr.innerHTML = strstart;

	divptr.innerHTML +=  ' <span class="hilight" id="h1">' + misps[wordindex].word + '</span>' + htmlToText(strend);

	//if (document.getElementById("h1").scrollIntoView)
	//document.getElementById("h1").scrollIntoView();

	divptr.innerHTML = divptr.innerHTML.replace(/_\|_/g, '<br />');
}


// called by onLoad handler to start the process of evaluating misspelled
// words
//
function startsp() {
	nextWord(false);
}

function getCorrectedText() {
	return mispstr;
}

// display the next misspelled word to the user and populate the suggested
// spellings box
//
function nextWord(ignoreall) {
	var frm = document.fm1;
	var sug = document.fm1.suggestions;
	var sugidx = 0;
	var newopt;
	var isselected = 0;

	// push ignored word onto ingoredWords array
	if(ignoreall) {
		ignoredWords[misps[wordindex].word] = 1;
	}
	
	// update the index of all words we have processed
	// This must be done to accomodate the replaceAll function.
	if(wordindex >= 0) {
		misps[wordindex].start += offsetindex;
		misps[wordindex].end += offsetindex;
	}

	// increment the counter for the array of misspelled words
	wordindex++;

	// draw it and quit if there are no more misspelled words to evaluate
	if(misps.length <= wordindex) {
		iFrameBody.innerHTML = mispstr;
		iFrameBody.innerHTML = iFrameBody.innerHTML.replace(/_\|_/g, '<br />');

		clearBox(sug);
		alert('Spell checking is complete.');
		frm.change.disabled		= true;
		frm.changeall.disabled	= true;
		frm.ignore.disabled		= true;
		frm.ignoreall.disabled	= true;

		// put line feeds back
		mispstr = mispstr.replace(/_\|_/g, '\n');

		// get a handle to the field we need to re-populate
		window.opener.document.forms[spell_formname][spell_fieldname].value = mispstr;
		window.close();
		return true;
	}

	// check to see if word is supposed to be ignored
	if(ignoredWords[misps[wordindex].word] == 1) {
		nextWord(false);
		return;
	}

	// clear out the suggestions box
	clearBox(sug);

	// re-populate the suggestions box if there are any suggested spellings for the word
	if(misps[wordindex].suggestions.length) {
		for(sugidx = 0; sugidx < misps[wordindex].suggestions.length; sugidx++) {
			if(sugidx == 0) {
				isselected = 1;
			} else {
				isselected = 0;
			}
			newopt = new Option(misps[wordindex].suggestions[sugidx], misps[wordindex].suggestions[sugidx], 0, isselected); 
			sug.options[sugidx] = newopt;
			if(isselected) {
				frm.changeto.value = misps[wordindex].suggestions[sugidx];
				frm.changeto.select();
			}
		}
	}
	hilightWord();
}

function htmlToText(thetext) {
	return thetext;
}

// remove all items from the suggested spelling box
// 
function clearBox( box ) {
	var length = box.length;

	// delete old options -- rememeber that select
	// boxes automatically re-index
	for (i = 0; i < length; i++) {
		box.options[0] = null;
	}
}

function openSpellWin() {
	var windowW = 640;
	var windowH = 425;

	var windowX = (screen.width / 2) - (windowW / 2);
	var windowY = (screen.height / 2) - (windowH / 2);
	
	spellDialog = window.open('', 'spellDialogBox', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=yes, width='+windowW+', height='+windowH);
	spellDialog.blur();
	window.focus();

	spellDialog.resizeTo(windowW, windowH);
	spellDialog.moveTo(windowX, windowY);

	spellDialog.focus();
}