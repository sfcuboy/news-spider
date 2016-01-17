<?php

include 'phpQuery-onefile.php';
$continue = true;
$pn = 0;
$word = isset($argv[1]) ? $argv[1] : 'ucloud';
while($continue){

	$url = 'http://news.baidu.com/ns?word=intitle%3A%28'.urlencode($word).'%29&pn='.$pn.'&cl=2&ct=0&tn=newstitle&rn=20&ie=utf-8&bt=0&et=0';
	echo "\n",$url,"\nsleep 1.5s...\n\n";
	usleep(15000);
	phpQuery::newDocumentFile($url);
	$list = pq("#content_left .result");
	foreach ($list as $key => $value) {
		$str = array();
		$ustr = "";
		$author = pq($value)->find('.c-title-author')->text();
		list($authorDate, $hour) = explode(' ', $author);

		list($authorName, $publishTime) = explode("201", $authorDate);

		$publishTime = str_replace(array('年', '月', '日'), array('-','-',''), '201'.$publishTime);

		$publishTime_unix = strtotime($publishTime.' 00:00:00');

		if($publishTime_unix < strtotime('2015-01-01 00:00:00')){
			echo "\ntime less then 2015-01-01，so it stoped!\ncurrent:".pq($value)->find('.c-title')->text().date('Y-m-d H:i:s', $publishTime_unix)."\n";
			$continue = false;
			break;
		}

		if( $publishTime_unix <= strtotime('2015-12-31 23:59:59')){
			$title = trim(pq($value)->find('.c-title')->text());
			$str[] = $title;
			$str[] = trim($authorName);
			$str[] = $publishTime;
			$showstr = implode("\t", $str)."\n";
			echo $showstr;

			for($j=0;$j<count($str);$j++){
				$estr.= mb_convert_encoding($str[$j], 'gbk', 'utf-8')."\t";
				
			}
			$estr.="\n";
			file_put_contents($word.'.xls', $estr, FILE_APPEND);
		}

	}
	$pn+=20;
}
