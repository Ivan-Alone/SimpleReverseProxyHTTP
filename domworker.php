<?php
	# Library: DOM Workers

	function loadHTML($page) {
		$document = new DOMDocument();

		libxml_use_internal_errors(true); 
		$document->loadHTML($page);
		libxml_clear_errors();

		return $document;
	}

	function findFirstDOMElement($dom_state, $tag) {
		try {
			foreach ($dom_state->childNodes as $node) {
				if ($tag == @$node->tagName) {
					return $node;
				}
				if (@$node->childNodes->length > 0) {
					$test = findFirstDOMElement($node, $tag);
					if ($test != null) {
						return $test;
					}
				}
			}
		} catch (Exception $e) {}
		return null;
	}

	function array2element($document, $array) {
		$id = randString(24, bindec(110));

		$element = $document->createElement($id);
		foreach ($array as $node) {
			$element->appendChild($node);
		}
		return $element;
	}

	function getNodeValue($node) {
		return getNodeData($node);
	}

	function getNodeData($node) {
		return trim(@$node->nodeValue);
	}

	function findDOMElementsByClass($dom_state, $class, $strict = false) {
		$elements = array();
		try {
			foreach (@$dom_state->childNodes as $node) {
				$attrs = array();
				if (@$node->attributes->length > 0) foreach ($node->attributes as $attr) $attrs[$attr->name] = $attr->value;

				if ($strict ? (@$attrs['class'] == $class) : (strpos(@$attrs['class'], $class) !== false)) {
					$elements[] = $node;
				}

				if (@$node->childNodes->length > 0) {
					$test = findDOMElementsByClass($node, $class, $strict);
					if ($test != array()) {
						foreach ($test as $dom) {
							$elements[] = $dom;
						}
					}
				}
			}
		} catch (Exception $e) {}
		return $elements;
	}

	function findDOMElementById($dom_state, $id) {
		$elements = array();
		try {
			foreach ($dom_state->childNodes as $node) {
				$attrs = array();
				if (@$node->attributes->length > 0) foreach ($node->attributes as $attr) $attrs[$attr->name] = $attr->value;

				if (strpos(@$attrs['id'], $id) !== false) {
					$elements[] = $node;
				}

				if (@$node->childNodes->length > 0) {
					$test = findDOMElementById($node, $id);
					if ($test != array()) {
						foreach ($test as $dom) {
							$elements[] = $dom;
						}
					}
				}
			}
		} catch (Exception $e) {}
		return $elements;
	}

	function getAttribute($element, $name) {
		if (@$element->attributes != null) {
			foreach ($element->attributes as $attr) {
				if ($attr->name == $name) return $attr->value;
			}
		}
		return null;
	}

	function findAllDOMElements($dom_state, $tag) {
		$elements = array();
		if (@$dom_state->childNodes->length > 0)
			foreach ($dom_state->childNodes as $node) {
				if ($tag == @$node->tagName) {
					$elements[] = $node;
				}
				if (@$node->childNodes->length > 0) {
					$test = findAllDOMElements($node, $tag);
					if ($test != array()) {
						foreach ($test as $dom) {
							$elements[] = $dom;
						}
					}
				}
			}
		return $elements;
	}