# SimpleReverseProxyHTTP
Simple Reverse Proxy for HTTP(s) resources written on PHP

# Example
You can test this proxy on your localhost via running "php -S 117.0.0.1:80 rutracker.php" in examples folder

```php
<?php

	include 'Network.class.php';
	include 'HTTPReverseProxy.class.php';

	$reverse_url = '127.0.0.1';

	$proxy = new HTTPReverceProxy('http://rutracker.org');

	# Add host spoof rules - we want redirect them all to $reverse_url, right?
	$proxy->addHostSpoofRule("rutracker.org", $reverse_url);
	$proxy->addHostSpoofRule("https://rutrk.org", "http://" . $reverse_url);

	# We want proxify rutracker.org and rutrk.org both, because second has
	# Logo and other? RuTracker data
	$proxy->addAlternativeHostMapper(function (HTTPReverceProxy $proxy, int $status, string $url, string $request_url) {
		if ($status == 404) {
			$test_url = "https://rutrk.org";
			if ($url != $test_url.$request_url) {
				$f = $proxy->fakeProxy($test_url);

				if ($f['status'] != 404) {
					$proxy->draw($f['status'], $f['headers'], $f['data']);
					return true;
				}
			}
		}
		return false;
	});

	# Proxy inspection of page and "AdBlock"ing for RuTracker
	$proxy->addCustomInspector(function (&$data) {
		preg_match('#vulkan/(.+)/index.html#U', $data, $out);
		preg_match('#iframe/kwork(.+).html#U', $data, $out2);

		$data = str_replace([
			@$out[0],
			@$out2[0],
			"bn-top-right",
			'style="padding: 0 0 3px;"',
			'id="bn-top-block"'
		], [
			@$out[0].'" style="display: none;',
			@$out2[0].'" style="display: none;',
			'bn-top-right" style="display: none;',
			'style="display: none;"',
			'id="bn-top-block" style="display: none;"'
		], $data);

	});

	# Trying to spoof window.location in JS for cancel wrong client Host
	# detection via replacing window object link and injecting custom script
	$proxy->addCustomInspector(function (&$data) {
		$request_url = $_SERVER['REQUEST_URI'];

		$path = pathinfo($request_url);

		if (@$path['extension'] == 'js') {
			$data = str_replace('window', 'fakeWindow', $data);
			$data = file_get_contents('windowInjector.js').$data;
		}
	});

	# Run proxification (display requested page & etc.)
	$proxy->proxy();
```
