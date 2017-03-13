<?php

mb_language('ja');

$lines = file('edict.jdx');

foreach($lines as $lineNumber => $line)
{
	echo mb_detect_encoding($line);
	//echo $line . PHP_EOL;
}

//file_put_contents('deleteme.txt', 'adadasdsadsa');