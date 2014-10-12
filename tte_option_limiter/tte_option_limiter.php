<?php
/**
 * Free Option Limiter Module
 *
 * This module allows you to limit the number of free options per product
 *
 * @package    WHMCS
 * @author     Samer Bechara <sam@thoughtengineer.com>
 * @copyright  Copyright (c) Courtix Hosting LLC 2014
 * @link       http://thoughtengineer.com/
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

if (!function_exists('tte_option_limiter_config')) {
    

    function tte_option_limiter_config() {
	$configarray = array(
	"name" => "Free Option Limiter",
	"description" => "This module allows you to limit the number of free configurable options every product in WHMCS can have",
	"version" => "1.0",
	"author" => "Samer Bechara",
	"language" => "english",
    );
	return $configarray;
    }

}

if (!function_exists('tte_option_limiter_activate')) {
function tte_option_limiter_activate() {

    // Create max options table
    $query = "CREATE TABLE IF NOT EXISTS `tte_max_free_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prod_id` int(11) NOT NULL,
  `max_free_options` int(11) NOT NULL,
  PRIMARY KEY (`id`)
);";
    $result = full_query($query);

    // Create free options table
    $query = "CREATE TABLE IF NOT EXISTS `tte_free_product_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
);";
   
    $result = full_query($query);
    
    # Return Result
    return array('status'=>'success','description'=>'Module successfully activated.');
   

}

}

if (!function_exists('tte_option_limiter_deactivate')) {
function tte_option_limiter_deactivate() {

    // Drop only during testing phase
    $query = 'DROP TABLE `tte_max_free_options`';
    
    //$result = full_query($query);
    
    $query = 'DROP TABLE `tte_free_product_options`';
    
    //$result = full_query($query);    

}
}

if (!function_exists('tte_option_limiter_upgrade')) {
function tte_option_limiter_upgrade($vars) {

}
}

if (!function_exists('tte_option_limiter_output')) {
function tte_option_limiter_output($vars) {

    $modulelink = $vars['modulelink'];
    
    // Get current operation
    $operation = isset($_POST['operation'])?$_POST['operation']:false;
    
    // perform operation
    switch($operation){
	case 'product_options':
	    tte_config_product_options((int) $_POST['pid']);
	    break;
	case 'save_changes':
	    tte_save_product_options();
	    break;
	default:
	    // Display product form
	    tte_select_product_form();
    }


}
}

if (!function_exists('tte_save_product_options')) {
// Saves the product options
function tte_save_product_options(){
    
    // Delete all existing product options
    $query = 'DELETE FROM tte_free_product_options where product_id = '.(int) $_POST['pid'];
    $result = full_query($query);
    
    // Insert new values
    foreach($_POST['free_options'] as $key => $option){
	$values = array('product_id' => (int) $_POST['pid'], 'option_id' => $option);
	insert_query('tte_free_product_options', $values);
    }
    
    // Update free option limit
    $query = 'DELETE FROM tte_max_free_options where product_id = '.(int) $_POST['pid'];
    $result = full_query($query);
    $insert_values = array('product_id' => (int) $_POST['pid'], 'max_free_options' => (int) $_POST['num_options']);
    insert_query('tte_max_free_options', $insert_values);
    
    tte_config_product_options((int) $_POST['pid']);
    
}
}

if (!function_exists('tte_option_limiter_sidebar')) {
function tte_option_limiter_sidebar($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $option3 = $vars['option3'];
    $option4 = $vars['option4'];
    $option5 = $vars['option5'];
    $LANG = $vars['_lang'];

    $sidebar = '<span class="header"><img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" /> Example</span>
<ul class="menu">
        <li><a href="#">Demo Sidebar Content</a></li>
        <li><a href="#">Version: '.$version.'</a></li>
    </ul>';
    return $sidebar;

}
}

if (!function_exists('tte_select_product_form')) {
// Displays the select product for
function tte_select_product_form(){
    
    $results = localAPI('getproducts');
    $products = $results['products']['product'];
    //var_dump($products);die();
    //var_dump($results);die();
    echo '<p>Please select the product you would like to limit the number of configurable options for</p>';
    
    // output form header
    echo '<form action="#" method="POST">
	<select name="pid">';
    foreach($products as $product){
	echo '<option value="'.$product['pid'].'">'.$product['name'].'</option>';
    }

    // Output form footer
echo '    </select>
    <input type="hidden" name="operation" value="product_options" />
	    <input type="submit" value="Select Product" /></form>';    
}
}

if (!function_exists('tte_config_product_options')) {
// Configure product options - param is product id
function tte_config_product_options($pid){
 
    $option_ids = tte_get_free_options($pid);
    
	// Get product details
	$params['pid'] = $pid;
        $results = localAPI('getproducts', $params);
    $name = $results['products']['product'][0]['name'];
    $options = $results['products']['product'][0]['configoptions']['configoption'];
    
    
    //echo "<pre>"; var_dump($options);die(); echo "</pre>";
    
?>
<form action="#" method="POST">
    <table class="datatable">
	<tbody>
	<tr class="product">	    
	    <td>Maximum number of free options for this product</td>
	    <td><input type="text" name="num_options" value="<?=  tte_get_max_options($pid)?>" /></td>
	</tr>
	<tr><td colspan="2">Please select the options that can be given as free. Other options will use their normal price as configured under the product's configurable options page. Only yes/no configurable option types can be given as free. Others will not be shown here and will be charged for.</td></tr>
<?php
    foreach($options as $option){
	
	// Only allow yes/no option to be given away as free
	if($option['type']!='3'){
	    continue;	    
	}

	// Check if the option is already marked as checked in db
	$checked = (in_array($option['id'], $option_ids))?'checked':''; 
?>
	<tr>
	    <td colspan="2"><input type="checkbox" name="free_options[]" value="<?=$option['id']?>" <?=$checked?>><?=$option['name']?></td>
	   
	</tr>
<?php
    }
?>
	<tr>
	    <td></td>
	    <td><input type='hidden' name='pid' value='<?=$pid?>' /><input type="submit" value="Save Changes" />
	    <input type='hidden' name='operation' value='save_changes' /><input type="submit" value="Save Changes" />
	    </td>
	</tr>	
	</tbody>

    </table>
</form>    
<?php 

    //return array('status'=>'info','description'=>'Changes have been successfully saved.');
    
}
}

if (!function_exists('tte_get_max_options')) {
// Get max options for a certain product
function tte_get_max_options($pid){
    $where = array('product_id' => $pid);
    $result = select_query('tte_max_free_options', 'max_free_options', $where);
    $data = mysql_fetch_array($result);
    $max_options = $data['max_free_options'];
    
    return $max_options;
}
}

if (!function_exists('tte_get_free_options')) {
function tte_get_free_options ($pid){
    $where = array('product_id' => $pid);
    $result = select_query('tte_free_product_options', 'option_id', $where);
    
    $option_ids = array();
    while($row = mysql_fetch_array($result)){
	$option_ids[] = $row['option_id'];
    }
    
    return $option_ids;
}

}