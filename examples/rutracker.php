<?php

	include '../Network.class.php';
	include '../HTTPReverseProxy.class.php';
	include '../domworker.php';

	$reverse_url = 'rutracker.local';

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
		if (strpos($data, '<body') !== false && strpos($data, '</body>') !== false && ($html = @loadHTML($data)) != null) {
			foreach (findAllDOMElements($html, 'iframe') as $iframe) {
				preg_match('#('.implode('|', [
					'vulkan',
					'kwork',
					'ads',
					'bet',
					'sport'
				]).')#', getAttribute($iframe, 'src'), $out);

				if (count($out) > 0) {
					$iframe->parentNode->removeChild($iframe);
				}
			}
			foreach (findDOMElementById($html, 'bn-top-right') as $ads_block) {
				$ads_block->parentNode->parentNode->removeChild($ads_block->parentNode);
			}
			foreach (findAllDOMElements($html, 'div') as $div) {
				if (strpos(getAttribute($div, 'style'), 'padding: 0 0 3px;') !== false) {
					$div->parentNode->removeChild($div);
				}
			}
			$data = $html->saveHTML();
		}
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








