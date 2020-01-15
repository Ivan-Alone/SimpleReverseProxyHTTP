<?php

	class HTTPReverceProxy {
		private $hostSpoofRules, $customInspectors, $customSpoofers, $serverCookiesMode, $alternativesMappers;

		private $main_url;

		private $net;

		private $fake_mode, $customHeaders;

		public function __construct($main_url, $cookes_file = (PHP_OS == 'WINNT' ? '' : '/tmp/') . 'cookies.txt') {
			$this->main_url = $main_url;

			$this->hostSpoofRules = [];
			$this->customInspectors = [];
			$this->customSpoofers = [];
			$this->alternativesMappers = [];
			$this->customHeaders = [];

			$this->fake_mode = false;

			$this->net = new Network($cookes_file);

			$this->setServerCookiesMode(false);
		}

		public function getNetwork() {
			return $this->net;
		}

		public function addHostSpoofRule(string $search, string $replace) {
			array_push($this->hostSpoofRules, [$search, $replace]);
			return true;
		}

		public function setServerCookiesMode(bool $enabled) {
			$this->serverCookiesMode = $enabled;
			if ($enabled) {
				$this->net->enableCookies();
			} else {
				$this->net->disableCookies();
			}
		}

		public function addCustomHostSpoofer($spoofer) {
			if (is_string($spoofer) && function_exists($spoofer)) {
				// String function
				array_push($this->customSpoofers, $spoofer);
			} elseif ($spoofer instanceof \Closure) {
				// Closure function
				array_push($this->customSpoofers, $spoofer);
			} else {
				return false;
			}
			return true;
		}

		public function addCustomInspector($inspector) {
			if (is_string($inspector) && function_exists($inspector)) {
				// String function
				array_push($this->customInspectors, $inspector);
			} elseif ($inspector instanceof \Closure) {
				// Closure function
				array_push($this->customInspectors, $inspector);
			} else {
				return false;
			}
			return true;
		}

		public function addAlternativeHostMapper($mapper) {
			if (is_string($mapper) && function_exists($mapper)) {
				// String function
				array_push($this->alternativesMappers, $mapper);
			} elseif ($mapper instanceof \Closure) {
				// Closure function
				array_push($this->alternativesMappers, $mapper);
			} else {
				return false;
			}
			return true;
		}

		public function fakeProxy($fake_url, $custom_headers = []) {
			$this->fake_mode = true;
			$real_url = $this->main_url;
			$this->main_url = $fake_url;
			$this->customHeaders = $custom_headers;

			$data = $this->proxy();

			$this->customHeaders = [];
			$this->main_url = $real_url;
			$this->fake_mode = false;

			return $data;
		}

		public function proxy() {
			$request_url = $_SERVER['REQUEST_URI'];

			$url = $this->main_url . $request_url;

			$rqst = [
				CURLOPT_URL => $url,
				CURLOPT_HEADER => true
			];

			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$rqst[CURLOPT_POST] = true;
				$rqst[CURLOPT_POSTFIELDS] = http_build_query($_POST);
			}

			$rqst_headers = [];

			if (!$this->serverCookiesMode) {
				$cookies_gen = [];

				foreach ($_COOKIE as $key => $value) {
					array_push($cookies_gen, $key . "=" . urlencode($value));
				}

				if (count($cookies_gen) > 0) {
					$rqst_headers['Cookie'] = implode("; ", $cookies_gen);
				}
			}

			foreach ($this->customHeaders as $key=> $value) {
				$rqst_headers[$key] = $value;
			}

			$res = $this->net->Request($rqst, $rqst_headers, true);
			$res = explode("\r\n\r\n", $res);

			$headers = explode("\r\n", array_shift($res));
			$data = implode("\r\n\r\n", $res);

			$rqst_stats = explode(" ", array_shift($headers));
			$status = (int)$rqst_stats[1];

			if ($this->fake_mode) {
				return [
					'status'=> $status,
					'headers'=> $headers,
					'data'=> $data
				];
			} else {
				foreach ($this->alternativesMappers as $mapper) {
					if ($mapper($this, $status, $url, $request_url)) {
						return true;
					}
				}
			}

			$this->draw($status, $headers, $data);
			return true;
		}

		private function set_header($text) {
			header($text);
		}

		private function spoofHost($data) {
			foreach ($this->hostSpoofRules as $rule) {
				$data = str_replace($rule[0], $rule[1], $data);
			}
			foreach ($this->customSpoofers as $spoofer) {
				$spoofer($data);
			}
			return $data;
		}

		public function draw($status, $headers, $data) {
			http_response_code($status);

			foreach ($headers as $header) {
				$h = explode(":", $header);

				$key = array_shift($h);
				$value = trim(implode(":", $h));

				switch (mb_strtolower($key)) {
					case 'content-length':
					case 'content-encoding':
					case 'transfer-encoding':
					case 'host':
					case 'server':
					case 'date':
					case 'connection':
					case 'vary':
						break;
					case 'origin':
					case '_origin':
					case 'referer':
					case 'location':
					case 'content-security-policy':
						$this->set_header($key.": ".$this->spoofHost($value));
						break;
					case 'set-cookie':
						if (!$this->serverCookiesMode) {
							$this->set_header($key.": ".$this->spoofHost($value));
						}
						break;
					default:
						$this->set_header($key.": ".$value);
				}
			}

			$data = $this->spoofHost($data);

			foreach ($this->customInspectors as $inspector) {
				$inspector($data);
			}

			echo $data;

			return true;
		}
	}