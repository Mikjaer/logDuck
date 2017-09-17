#!/usr/bin/php
<?php
	# Copyright (c) 2017 Mikkel Mikjær Christensen
	# Licensed under GNU GPL v2

	$tail_lines = 1000;		# This will result in an upper limit of 1000req/s	
	$sample_size = 10;		# Number of seconds to go back in time
	$top_n = 20;			# How many logs to show

	$cols = exec('tput cols');
	$lines = exec('tput lines');

	#$top_n = $lines -5;


	exec("ls /var/www/*/logs/access.log", $logFiles);


	function parseTimeStamp($logline)
	{
		if (preg_match("/\[([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9]+):([0-9]+):([0-9]+).+\]/",$logline,$matches))
		{
			$matches[7] = $months=array_flip(explode(",","Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec"))[$matches[2]]+1;
			return $timestamp = mktime($matches[4],$matches[5],$matches[6],$matches[7],$matches[1], $matches[3]);
		} else {
			return false;
		}
	}

	function parseLog($file)
	{
		global $tail_lines,$sample_size;

		exec("tail -n".$tail_lines." ".$file,$lines);

		if (count($lines) == 0)
			return "n/a";

		$now = time();

		foreach ($lines as $line)
		{
			if ($age = $now-parseTimeStamp($line) <= $sample_size)
			{
				$sample[]=$line;
			}
		}	
	
		if (!isset($sample))
			return 0;

		return count($sample)/$sample_size;
	}

	function parseLogFiles($logFiles)
	{
		foreach ($logFiles as $_)
		{
			$result[] = array("logfile" => $_, "reqsec" => parseLog($_));
		}
	
		return $result;
	}
	
	function cmp($a, $b)
	{
		return $a["reqsec"] < $b["reqsec"];
	}


	function getMaxLength($result)
	{
		global $top_n;
		
		for ($i=0; $i<=$top_n; $i++)
			if (($len = strlen($result[$i]["logfile"]))>@$max)
				$max = $len;
		return $max;
	}


	function run($logFiles)
	{
		global $top_n;

		$result = parseLogFiles($logFiles);
		usort($result, "cmp");
		for ($i=0; $i<=$top_n; $i++)
			printf("%-".(getMaxLength($result)+5)."s: %.2f req/s\n",$result[$i]["logfile"],$result[$i]["reqsec"]);

	}

	function microtime_float()
	{
    		list($usec, $sec) = explode(" ", microtime());
        	return ((float)$usec + (float)$sec);
	}


	$time_start = microtime_float();
	run($logFiles);
	
	$time_end = microtime_float();
	$time = $time_end - $time_start;

	printf("\n\nTime elapsed: %.2fsec\n",$time);

?>
