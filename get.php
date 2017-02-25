<?php

require 'vendor/autoload.php';

// ----------------------------------------------

$url = filter_input(INPUT_GET, 'url');
$next = filter_input(INPUT_GET, 'next') ? true : false;

if(!$url)
{
	exit('Error, URL missing!');
}

// ----------------------------------------------

if(!is_dir('cache/'))
{
	mkdir('cache', 0777, true);
}

$html = '';
$cacheFile = 'zingtv-' . sha1($url) . '.html';

if(!is_file('cache/' . $cacheFile))
{	
	$client = new GuzzleHttp\Client();
	$res = $client->request('GET', $url);

	if($res->getStatusCode() != 200)
	{
		exit('Error, HTTP code ' . $res->getStatusCode());
	}

	$html = $res->getBody();

	if(!$html)
	{
		exit('Error, HTTP return empty body');
	}
	
	file_put_contents('cache/' . $cacheFile, $html);
}
else
{
	$html = file_get_contents('cache/' . $cacheFile);
}

// ----------------------------------------------

$m = [];

preg_match_all('/<source src="http:\/\/(.*?)" type="video\/mp4" data-res="(.*?)"/i', $html, $m);

//print_r($m);
if(!isset($m[1][2]))
{
	exit('Error, Regx not match any thing!');
}

$m = $m[1];

// $m[2] -> 480p
$downloadLink = 'http://' . $m[2];

$old_shell = is_file('zingtv.sh') ? file_get_contents('zingtv.sh') : '';
file_put_contents('zingtv.sh', $old_shell . 'aria2c -x8 ' . $downloadLink . PHP_EOL);

// ----------------------------------------------
$m = [];

preg_match('/customVideo\[\'nextVideoHint\'\] = (.*);/i', $html, $m);
//print_r($m);
if(isset($m[1]))
{
	$m = json_decode($m[1], true);
	//print_r($m);
	
	$found = 0;
	$_url = str_replace('http://tv.zing.vn', '', $url);
	foreach($m[1] as $v)
	{
		if(!empty($m[$found]['media']['linkDetail']) && $m[$found]['media']['linkDetail'] == $_url)
			continue;
		
		$found++;
	}
	
	$found++;
	if(!empty($m[$found]['media']['linkDetail']))
	{
		$nextUrl = 'http://tv.zing.vn' . $m[$found]['media']['linkDetail'];
	}
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ZingTV - Auto get link</title>
</head>
<body>
	<p>
		ZingTV URL: <?php echo $url; ?><br>
		480p: <a href="<?php echo $downloadLink; ?>"><?php echo $downloadLink; ?></a>
		
		<?php if($next && !empty($nextUrl)): ?>
		<hr>
		Tìm thấy tập tiếp theo, tự chuyển hướng sau 1s...<br>
		Tập tiếp theo: <?php echo htmlspecialchars($nextUrl); ?>...<br>
		Nếu không tự chuyển vui lòng bấm vào <a href="get.php?next=on&url=<?php echo urlencode($nextUrl); ?>">đây</a>.
		
		<script>
			setTimeout(function(){
				location.href = 'get.php?next=on&url=<?php echo urlencode($nextUrl); ?>'} , 1000
			);
		</script>
		<?php endif; ?>
	</p>
</body>
</html>
