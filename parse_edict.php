<?php

mb_language('ja');

/*
$lines = file('edict.jdx');

foreach($lines as $lineNumber => $line)
{
	echo mb_detect_encoding($line);
	//echo $line . PHP_EOL;
}
*/

//file_put_contents('deleteme.txt', 'adadasdsadsa');
//

//kill previous log file
unlink('parse_edict.log');

//what to do with "oddball" entries?
$oddballPolicy = 'include-in-parsing';	//can be 'include-in-parsing' or 'exclude-from-parsing'

//reporting counters
$oddballs = 0;
$parsed = 0;
$multiplSenseEntries = 0;

$xml = simplexml_load_file('edict/JMdict_e');

foreach($xml->entry as $entry)
{
	
	
	//NOTE sometimes multiple k_ele and r_ele elements per entry!

	if(entry_is_oddball($entry))
	{
		log_to_file("ent_seq {$entry->ent_seq} is oddball");
		$oddballs++;
		
		if($oddballPolicy == 'exclude-from-parsing')
		{
			continue;
		}

		else
		{
			//NOP (below logic will simply pull out the first k_ele and first r_ele)
		}
	}
	
	//NOTE not every entry has a kanji element (ie. it's a punctuation mark or katakana loanword etc)

	$k_eles = count($entry->k_ele);
	$r_eles = count($entry->r_ele);

	//VAR_DUMP(implode('X', (array)$entry->sense->gloss));
	//error_log(implode(' ', (array)$entry->sense->gloss) . PHP_EOL, 3, 'gloss.log');
	
	$desc = implode('; ', (array)$entry->sense->gloss);
	$kana = $entry->r_ele->reb;
	$kanji = $entry->k_ele->keb;
	
	if(is_null($kanji))
	{
		$kanji = $kana;
	}

	//a <sense> can have multiple <gloss>es and an entry can have multiple <sense>s!
	if(count($entry->sense) != 1)
	{
		$multipleSenseEntries++;
		log_to_file("Multiple <sense> nodes in ent_seq {$entry->ent_seq}");
	}

	ECHO "[k_eles: {$k_eles}, r_eles: {$r_eles}] kanji: {$kanji}, kana: {$kana}, desc: {$desc}" . PHP_EOL;
	
	ECHO '--------------------------------------' . PHP_EOL;

	$parsed++;

}

echo "Oddball policy: {$oddballPolicy}". PHP_EOL;
echo "Parsed: {$parsed} entries". PHP_EOL;
echo "Oddballed on: {$oddballs} entries (see parse_edict.log)". PHP_EOL;
echo "Entries with multiple <sense> nodes: {$multipleSenseEntries} (see parse_edict.log)". PHP_EOL;

//is <entry> an oddball?
//oddball means:
//[i] unmatching number of k_ele and r_ele elements (excluding 1 r_ele and 0 k_ele which simply means a katakana only word (or what-have-you))
//[ii] more than 1 k_ele or r_ele elements having inconsistent text contents
//
function entry_is_oddball(SimpleXMLElement $entry)
{
	$k_eles = count($entry->k_ele);
	$r_eles = count($entry->r_ele);

	//exception to [i]
	if($k_eles == 0 and $r_eles == 1)	//ie. simply a missing kanji because the word is a katakana loanword or what-have-you
	{
		return false;
	}

	//[i] (actual)
	if($k_eles != $r_eles)
	{	
		//log_to_file("uneven element count for ent_seq {$entry->ent_seq}. Taking first elements gives: kanji:{$entry->k_ele->keb}, kana:{$entry->r_ele->reb}");	//43K instances [sometimes multiple r_eles and no k_eles. sometimes multiple k_eles with the same single r_ele. sometimes one k_ele with a hiragana and katakana r_ele]
		return true;
	}

	//[ii]
	$rebs = [];
	$kebs = [];

	foreach($entry->k_ele as $k_ele)
	{
		$kebs[] = $k_ele->keb;
	}

	foreach($entry->r_ele as $r_ele)
	{
		$rebs[] = $r_ele->reb;
	}

	$oddball = count(array_unique($rebs)) != 1 or count(array_unique($kebs)) != 1;

	if($oddball)
	{
		//log_to_file("differing element text for ent_seq {$entry->ent_seq}");	//3K instances [a bit fiddlier than the above uneven thing]
	}

	return $oddball;
}




function log_to_file($message)
{
	error_log($message . PHP_EOL, 3, 'parse_edict.log');
}