<?php

class Jisho 
{
	public function getResults($query)
	{

		$wf = new Workflows("jwm.alfed.jisho");

		// limit requests to > 2 characters
		// * this kills alfreds placeholder, but improves performance
		if(strlen($query) < 3) {
			echo $wf->toxml();
			exit;
		}

		$url = "http://beta.jisho.org/search/".rawurlencode(trim($query));

		// make a CURL request using the workflows utility class
		$html_data = $wf->request($url);
		
		// scrape the resulting HTML
		$search_results = $this->scrapeHTML($html_data);
		
		if (!empty($search_results)) 
		{
			foreach($search_results as $result) 
			{
				$icon = $this->getIcon($result['type']);

				// params: uid, arg, title, subtitle, icon, valid, autocomplete[optional]
				$wf->result(time(), $result['url'].','.$result['ja'], $result['ja'], $result['meaning'], 'icons/'.$icon, "yes");
			}
		}

		echo $wf->toxml();
	}

	public function scrapeHTML($input) 
	{
		// suppress warnings or xml isn't accepted by alfred
		libxml_use_internal_errors( true );
		libxml_clear_errors();
		
		$html = new DOMDocument();
		$html->loadHtml($input);
		$xpath = new DOMXPath($html);

		// results array for alfred
		$alfred_results = array();

		$results = $xpath->query("//div[@id='primary']/div/div[@class='concept_light']");

		foreach ($results as $i=>$result) {
			
			// scrape the Japanese Word
			$readings = $xpath->query("div/div[@class='concept_light-representation']", $result);

			foreach($readings as $j=>$reading) {

				$words = $xpath->query("span[@class='text']", $reading);
				
				$temp = '';
				foreach($words as $word){
					$temp.=trim($word->nodeValue);
					if($j>0) {
						$temp.=' ・ '.trim($word->nodeValue);
					}
					
				}
				$alfred_results[$i]['ja'] = $temp;
			}

			// scrape the meaning
			$meanings = $xpath->query("div[@class='concept_light-meanings']", $result);

			foreach($meanings as $j=>$meaning) {

				// scrape the type of word (verb, noun, etc)
				$types = $xpath->query("p/span[@class='meaning-wrapper']/span[@class='meaning-tags']", $meaning);
	
				foreach($types as $type){

					$type = $this->getType(trim(strtolower($type->nodeValue)));
					$alfred_results[$i]['type'] = $type;

					if($type) {
						$type = "[$type] ";
					}

					// only get the first one, or things gets complex
					break;
				}

				// scrape the meaning
				$words = $xpath->query("p/span[@class='meaning-wrapper']/span[@class='meaning-meaning']", $meaning);
	
				foreach($words as $word){
					$alfred_results[$i]['meaning'] = $type . trim($word->nodeValue);
				}
			}

			// scrape the 'read more' url link
			$anchors = $xpath->query("div/a[@class='light-details_link']", $result);

			foreach($anchors as $j=>$anchor) {
				$alfred_results[$i]['url'] = $anchor->getAttribute("href");
			}

		}
		
		return $alfred_results;
	}

	public function getType($type) {

		if(strpos($type,'i-adjective') !== false) {
			$res = 'i-adj';
		} else if(strpos($type,'na-adjective') !== false) {
			$res = 'na-adj';
		} else if(strpos($type,'noun') !== false) {
			$res = 'noun';
		} else if(strpos($type,'adverb') !== false) {
			$res = 'adverb';
		} else if(strpos($type,'no-adjective') !== false) {
			$res = 'no-adj';
		} else if(strpos($type,'transitive verb') !== false) {
			$res = 'transitive verb';
		} else if(strpos($type,'intransitive verb') !== false) {
			$res = 'intransitive verb';
		} else if(strpos($type,'intransitive verb') !== false) {
			$res = 'intransitive verb';
		} else if(strpos($type,'ichidan verb') !== false) {
			$res = 'ichidan verb';
		}

		return $res;
	}

	public function getIcon($type) {

		if($type == 'i-adj' || $type == 'na-adj' || $type == 'no-adj') {
			$icon = 'icon_adj.png';
		} else if($type =='noun') {
			$icon = 'icon_noun.png';
		} else if(strpos($type,'adverb') !== false) {
			$icon = 'icon_adverb.png';
		} else if(strpos($type,'verb') !== false) {
			$icon = 'icon_verb.png';
		} else {
			$icon = '';
		}

		return $icon;
	}
}