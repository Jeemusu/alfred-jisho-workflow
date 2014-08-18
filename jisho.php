<?php

class Jisho {
	
	public function __construct() {}


	public function getResults($query) 
	{
		$wf = new Workflows("jwm.alfed.jisho");

		$query = trim($query);
		
		// Limit requests to > 2 characters
		if(strlen($query) < 3) {
			echo $wf->toxml();
		}

		// Check cache first
		if($this->isCached($query)) 
		{
			$html_data = $this->getCachedResults($query);
		} 
		else 
		{
			// Scrape HTML from jisho.org
			$html_data = $wf->request("http://beta.jisho.org/search/".rawurlencode($query));

			// Cache results
			$this->cacheResults(trim($query), $html_data);
		}
		
		// Parse HTML
		$search_results = $this->scrape($html_data);
		
		// Feed results to Alfred
		if (!empty($search_results)) 
		{
			foreach($search_results as $result) 
			{
				$definition = '';
				
				foreach($result['en'] as $word) {
					$definition.= $word . '・';
				}
				
				$definition = preg_replace('/[^,.\s;a-zA-Z0-9_-]|[,.;]$/s', '', $definition);
				$type = $result['type'] ? '[' . $result['type'] . '] ' : '';
				$definition = $type . str_replace('.','; ', $definition);

				$icon = $this->getIcon($result['type']);
				
				$wf->result(time(), $result['url'].','.$result['ja'], $result['ja'], $definition, $icon, "yes");
			}
		}	
		
		// Return results to Alfred as XML
		echo $wf->toxml();
	}

	// Scrape result HTML for data
	public function scrape($input) 
	{
	
		// Suppress warnings relating to XML markup
		libxml_use_internal_errors( true );
		libxml_clear_errors();
		
		$doc = new DOMDocument();
		$doc->loadHtml($input);
		$xpath = new DOMXPath($doc);

		$nodes = $xpath->query("//div[contains(concat(' ',@class,' '),' concept_light ')]");

		foreach($nodes as $i=>$result) 
		{


			// Get Japanese Word
			$readings = $xpath->query("div/div/div[contains(concat(' ',@class,' '),' concept_light-representation ')]/span[contains(concat(' ',@class,' '),' text ')]", $result);
			
			foreach($readings as $word) {
				$alfred_results[$i]['ja'] = trim($word->nodeValue);
			}


			// Get result details
			$definitions = $xpath->query("div[contains(concat(' ',@class,' '),' concept_light-meanings ')]/div[contains(concat(' ',@class,' '),' meanings-wrapper ')]", $result);

			foreach($definitions as $j=>$definition) 
			{
				// Get type (verb, noun, etc)
				$types = $xpath->query("div[contains(concat(' ',@class,' '),' meaning-tags ')]", $definition);
				
				$type_arr = array();
				
				foreach($types as $type){
					$alfred_results[$i]['type'] = $this->getType(trim(strtolower($type->nodeValue)));
					break;
				}
				

				// Get definitions
				$words = $xpath->query("div[contains(concat(' ',@class,' '),' meaning-wrapper ')]/div[contains(concat(' ',@class,' '),' meaning-definition ')]/span[contains(concat(' ',@class,' '),' meaning-meaning ')]", $definition);
		
				foreach($words as $z=>$word){
					$alfred_results[$i]['en'][] = trim($word->nodeValue);
				}
			}
			
			
			// Get Link
			$anchors = $xpath->query("a[contains(concat(' ',@class,' '),' light-details_link ')]", $result);

			foreach($anchors as $anchor) {
				$alfred_results[$i]['url'] = $anchor->getAttribute("href");
			}
		}

		return $alfred_results;
	}


    // Returns a shortened form of the word type
	public function getType($type) 
	{

		if(strpos($type, 'noun') !== false) {
			$res = 'noun';
		} else if(strpos($type,'i-adjective') !== false) {
			$res = 'i-adj';
		} else if(strpos($type,'na-adjective') !== false) {
			$res = 'na-adj';
		} else if(strpos($type,'adverb') !== false) {
			$res = 'adverb';
		} else if(strpos($type,'no-adjective') !== false) {
			$res = 'no-adj';
		} else if(strpos($type,'transitive verb') !== false) {
			$res = 'transitive verb';
		} else if(strpos($type,'intransitive verb') !== false) {
			$res = 'intransitive verb';
		} else if(strpos($type,'ichidan verb') !== false) {
			$res = 'ichidan verb';
		} else {
			$res = '';
		}

		return $res;
	}

    
    // Returns an icon for the word type
	public function getIcon($type) 
	{

		if($type == 'i-adj' || $type == 'na-adj') {

			$icon = 'icon_adj.png';

		} else if($type == 'noun') {

			$icon = 'icon_noun.png';

		} else if ($type == 'no-adj') {
			
			$icon = 'icon_adj.png';
			
		} else if($type == 'adverb') {

			$icon = 'icon_adverb.png';

		} else if(strpos($type,'verb') !== false) {

			$icon = 'icon_verb.png';

		} else {
			$icon = '';
		}

		return $icon;
	}


	public function getCachedResults($query) 
	{
		return file_get_contents(getcwd().'/cache/' . $query . '.html');
	}
	
	public function cacheResults($query, $results) 
	{
		file_put_contents(getcwd().'/cache/' . $query . '.html', $results);
	}
	
	public function isCached($query) 
	{
		return file_exists(getcwd().'/cache/' . $query . '.html');
	}
	
	// Clear cached search queries
	public function clearCache() 
	{
		$files = glob(getcwd().'/cache/*');
		foreach($files as $file){
		  	if(is_file($file))
		    	unlink($file); 
		}
	}	
	
}