<?php

/* 
 * Include the relevant WHMCS hooks in order to update the price on the user's cart and order
 */

require_once('tte_option_limiter.php');

// Updates the product price based on the number of configurable options chosen and the number of allowed free config options

function tte_update_product_price($vars){
    
    // Get product ID
    $pid = $vars['pid'];
    // Get max free options for that product
    $max_free = tte_get_max_options($pid);
 
    // Get selected options for that product
    $current_options = array_keys($vars['proddata']['configoptions']);
    
    // Get the options that should be given for free
    $current_free = tte_get_current_free_options($current_options, $pid);

    // Get the total discount price
    foreach($current_free as $price){
	$discount+=$price;
    }

    // Get current order price       
    $prod_info = localAPI('getproducts', array('pid' => $pid), 'tyfreeborn');
    $price = $prod_info['products']['product'][0]['pricing']['USD']['monthly'];    
    
    // Remove discount from setup fee
    return array("setup" =>  -$discount, "recurring" => $price);   
 
}

// Returns the options that should be marked as free for this particular product
function tte_get_current_free_options($current_options, $pid){
    
    // Get free options for that product
    $free_options = tte_get_free_options($pid);
    
    // Get options prices
    $prod_info = localAPI('getproducts', array('pid' => $pid), 'tyfreeborn');
    
    $config_options = $prod_info['products']['product'][0]['configoptions']['configoption'];
    
    // Get price for each of our current options
    foreach ($config_options as $option){
	
	// Get option ID
	$option_id = $option['options']['option'][0]['id'];
	

	// Skip if cannot be marked as free or is not selected
	if(!in_array($option_id, $free_options) || !in_array($option_id, $current_options))
	    continue;
	
	// Get option price and store in array
	$option_prices[$option_id] = $option['options']['option'][0]['pricing']['USD']['msetupfee'];
    }
    
    // Sort array by highest value
    arsort($option_prices, SORT_NUMERIC);
    
    // Get the most expensive addons that can be given for free
    $for_free = array_slice($option_prices, 0, tte_get_max_options($pid), true);

    return $for_free;
    
    
}
// Adds the corresponding hook
add_hook('OrderProductPricingOverride', 3, 'tte_update_product_price');