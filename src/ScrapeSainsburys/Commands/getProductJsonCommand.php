<?php

namespace ScrapeSainsburys\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class GetProductJsonCommand extends Command {

	protected function configure()
    {   
        $this->setName("scrapesainsburys:getproductjson")
             ->setDescription("Return Sainsburys product information in a Json format")
             ->setHelp(<<<EOT
Scrape Sainsbury's ripe fruits products from their website and display that in json format.

Usage:

<info>php console.php scrapesainsburys:getproductjson <env></info>

EOT
);
    }
	
	protected function execute(InputInterface $input, OutputInterface $output)
    {
		libxml_use_internal_errors(true);
		
		$xpath = self::getXpathOfWebsite('http://hiring-tests.s3-website-eu-west-1.amazonaws.com/2015_Developer_Scrape/5_products.html');
		
		//Initialising variables for scope
		$post_data = array();
		$productCount = 0;
		$totalPrice = 0;
		
		//Getting each product's HTML and looping through each one
		$products = $xpath->query('//div[@class="product "]');
		foreach($products as $product){

			//Sets Product Title
			$post_data['results'][$productCount]['title'] = self::getProductTitle($product, $xpath);
			
			//Sets product page's size in kb
			$productUrl = $xpath->query(".//a/@href", $product);
			$size = self::getSizeOfPageByUrl($productUrl->item(0)->textContent);
			$post_data['results'][$productCount]['size'] = $size.'kb';
			
			//Sets Price Per Unit
			$post_data['results'][$productCount]['unit_price'] = self::getPricePerUnit($product, $xpath);
			$totalPrice += $post_data['results'][$productCount]['unit_price'];
			
			//Sets Description
			$post_data['results'][$productCount]['description'] = self::getDescriptionFromProductUrl($productUrl->item(0)->textContent);
			
			$productCount++;
		}
		
		//Adding up prices for Total
		$post_data['results']['total'] = number_format($totalPrice,2);
		
		//Creating Json Object
		$post_data = json_encode($post_data, JSON_PRETTY_PRINT);
		
		//Printing Json Object to console
		print_r($post_data);

    }
	
	
	/*	Get DOMXpath object of webpage
	*
	*	@param String $url
	*	@return DOMXpath
	*/
	
	public static function getXpathOfWebsite($url){
	
		//Loading in HTML of url
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$html = curl_exec($ch);
		
		//Loading HTML into DOMDocument and Xpath
		$doc = new \DOMDocument();
		$doc->loadHTML($html);
		$xpath = new \DOMXpath($doc);
		
		return $xpath;
	}
	
	
	/*	Searches the DOMNodeList for the product title element and returns the value
	*
	*	@param DOMNodeList $product
	*	@param DOMXpath $xpath
	*	@return String
	*/
	
	private static function getProductTitle($product, $xpath){
	
		$titles = $xpath->query('.//h3', $product);
		$title = trim($titles->item(0)->textContent);
		
		return $title;
	}
	
	
	/*	Get webpage from url and return the size of the HTML page in Kb
	*
	*	@param String $url
	*	@return String
	*/
	
	public static function getSizeOfPageByUrl($url){
	 
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_exec($ch);
		$size = round(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD) / 1024,2);
		curl_close($ch);
		
		return $size;
	}
	
	
	/*	Get Price from HTML and removing unnecessary prefix and suffix
	*
	*	@param DOMNodeList $product
	*	@param DOMXpath $xpath
	*	@return String
	*/
	
	private static function getPricePerUnit($product, $xpath){
	 
		$pricePerUnits = $xpath->query('.//p[@class="pricePerUnit"]', $product);
		$pricePerUnitRaw = trim($pricePerUnits->item(0)->textContent);
		$pricePerUnitRaw = str_replace('&pound','', $pricePerUnitRaw);
		$pricePerUnitRaw = str_replace('/unit','', $pricePerUnitRaw);
		
		return $pricePerUnitRaw;
	}
	
	
	/*	Get Description from product url HTML
	*
	*	@param String $productUrl
	*	@return String
	*/
	
	private static function getDescriptionFromProductUrl($productUrl){
	 
		$ch = curl_init($productUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$productData = curl_exec($ch);
	 
		$productDoc = new \DOMDocument();
		$productDoc->loadHTML($productData);
		$productXpath = new \DOMXpath($productDoc);
		$productDescriptions = $productXpath->query('//h3[text()="Description"]/following-sibling::*[1]');
		$description = trim($productDescriptions->item(0)->textContent);
		
		return $description;
	}
			

}