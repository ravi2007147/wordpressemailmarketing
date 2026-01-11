<?php
	if(isset($_POST['indexurl'])){
		$url="https://api.indexnow.org/indexnow?url=".urlencode($_POST['url'])."&key=f30dc69c2a024f90aeedcf7312be2664";
		file_get_contents($url);
		echo "URL submitted!";
	}

	if(isset($_POST['googleurl'])){
		echo $url="https://www.google.com/webmasters/tools/ping?sitemap=".urlencode($_POST['url']);die;
		file_get_contents($url);
		echo "URL submitted!";
	}
?>
<form method="post" action="">
	<p>Index Now Submit URL (It will ask the search eangine IndexNow,Microsoft Bing,Naver,Seznam.cz,Yandex,Yep) to crawl my page</p>
	<div>
		<input type="text" name="url" placeholder="Enter URL"> <input type="submit" name="indexurl" value="Submit">
	</div>
</form>

<form method="post" action="">
	<p>Sitemap Submit To Google</p>
	<div>
		<input type="text" name="url" placeholder="Enter Sitemap URL"> <input type="submit" name="googleurl" value="Submit">
	</div>
</form>