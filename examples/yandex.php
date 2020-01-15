<?php

	# You can test this proxy on your localhost via "php -S 127.0.0.1:80 yandex.php"

	include '../Network.class.php';
	include '../HTTPReverseProxy.class.php';
	
	$reverse_url = '127.0.0.1';
	
	$proxy = new HTTPReverceProxy('https://yandex.ru');
	
	# Add host spoof rules - we want redirect them all to $reverse_url, right?
	$proxy->addHostSpoofRule("yandex.ru", $reverse_url);
	$proxy->addHostSpoofRule("yastatic.net", $reverse_url);
	$proxy->addHostSpoofRule("https", "http");
	
	$proxy->addAlternativeHostMapper(function (HTTPReverceProxy $proxy, int $status, string $url, string $request_url) {
		if ($status == 404) {
			$test_url = "https://yastatic.net";
			if ($url != $test_url.$request_url) {
				$f = $proxy->fakeProxy($test_url, ['Referer'=> 'https://yandex.ru']);

				if ($f['status'] != 404) {
					header('');
					$proxy->draw($f['status'], $f['headers'], $f['data']);
					return true;
				}
			}
		}
		return false;
	});
	
	# Run proxification (display requested page & etc.)
	$proxy->proxy();
	
	
	
	
	
	
	
	