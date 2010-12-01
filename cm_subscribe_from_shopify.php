<?php
/**
 * Process a WebHook from Shopify and subscribe the
 * purchaser to a specific Campaign Monitor list.
 *
 * @package   CMSubscribeFromShopify
 * @version   1.2
 * @author    Alex Dunae, Dialect <alex[at]dialect[dot]ca>
 * @copyright Copyright (c) 2008-09, Alex Dunae
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 * @link      http://dialect.ca/code/cm-subscribe-from-shopify/
 */


define('CM_API_KEY',       'xxxxxxxxxxxxxxxxxxxxxx');
define('CM_LIST_ID',      'xxxxxxxxxxxxxxxxxxxxxx');
define('SHOPIFY_SHOP_ID', '999999');

// Check for a valid Shopify shop ID
if ($_SERVER['HTTP_X_SHOPIFY_SHOP_ID'] != SHOPIFY_SHOP_ID) {
    header('HTTP/1.1 403 Forbidden');
    exit(0);
}

// Read the XML data from Shopify
$xml_str = '';

$xml_data = fopen('php://input' , 'rb');
while (!feof($xml_data)) $xml_str .= fread($xml_data, 4096);
fclose($xml_data);


$req_xml = new SimpleXMLElement($xml_str);

// Check for opt-in
if(strcasecmp($req_xml->{'buyer-accepts-marketing'}, 'true') != 0) {
    header('HTTP/1.1 200 OK');
    echo 'does not accept marketing';
    exit(0);
}

// Post to Campaign Monitor
$post_str = sprintf('ApiKey=%s&ListID=%s&name=%s&email=%s',
                     CM_API_KEY,
                     CM_LIST_ID,
                     urlencode(trim($req_xml->{'billing-address'}->name)),
                     urlencode(trim($req_xml->email))
                    );

$ch = curl_init('http://api.createsend.com/api/api.asmx/Subscriber.AddAndResubscribe');

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);

$post_result = curl_exec($ch);

curl_close($ch);

// Check if Campaign Monitor returned 'success'
$res_xml = new SimpleXMLElement($post_result);

if($res_xml->Code == '0') {
    header('HTTP/1.1 200 OK');
    print '200';
} else {
    header('HTTP/1.1 400 Bad request');
    print '400';
}

exit(0);