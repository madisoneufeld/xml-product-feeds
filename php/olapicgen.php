<?php
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;

$feed = $xml->createElement('Feed');
$categories = $xml->createElement('Categories');
$products = $xml->createElement('Products');

$xml->appendChild($feed);
$feed->appendChild($categories);
$feed->appendChild($products);

// BUILD BRAND OBJS
class Brand {
    public $name;
    public $id;
    public $url;
}

$awapuhiWildGinger = new Brand();
$awapuhiWildGinger->name = 'Awapuhi Wild Ginger';
$awapuhiWildGinger->id = 41;
$awapuhiWildGinger->url = 'https://www.paulmitchell.com/awapuhi-wild-ginger/';

$marulaOil = new Brand();
$marulaOil->name = 'MarulaOil';
$marulaOil->id = 43;
$marulaOil->url = 'https://www.paulmitchell.com/marulaoil/';

$mitch = new Brand();
$mitch->name = 'MITCH';
$mitch->id = 40;
$mitch->url = 'https://www.paulmitchell.com/mitch/';

$neon = new Brand();
$neon->name = 'Neon';
$neon->id = 45;
$neon->url = 'https://www.paulmitchell.com/neon/';

$neuro = new Brand();
$neuro->name = 'Neuro';
$neuro->id = 39;
$neuro->url = 'https://www.paulmitchell.com/neuro/';

$pm = new Brand();
$pm->name = 'Paul Mitchell';
$pm->id = 38;
$pm->url = 'https://www.paulmitchell.com/paul-mitchell/';

$protools = new Brand();
$protools->name = 'Pro Tools';
$protools->id = 44;
$protools->url = 'https://www.paulmitchell.com/pro-tools/';

$teatree = new Brand();
$teatree->name = 'Tea Tree';
$teatree->id = 42;
$teatree->url = 'https://www.paulmitchell.com/tea-tree/';

// CREATE ARRAY OF BRAND OBJS
$brands = array($awapuhiWildGinger, $marulaOil, $mitch, $neon, $neuro, $pm, $protools, $teatree);

// LOOP THRU AND APPEND TO XML
foreach ($brands AS $brand) {
    global $xml, $categories;
    $category = $xml->createElement('Category');
    $category_uid = $xml->createElement('CategoryUniqueID', $brand->id);
    $category_name = $xml->createElement('Name', $brand->name);
    $category_url = $xml->createElement('CategoryUrl', $brand->url);

    $categories->appendChild($category);
    $category->appendChild($category_uid);
    $category->appendChild($category_name);
    $category->appendChild($category_url);
};

// GET ALL PRODUCTS
$url = '';
$curl = curl_init();

while (isset($url) && $url != '') {
    global $url, $xml;
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Cache-Control: no-cache"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    // Decode the JSON response
    $jsonDecoded = json_decode($response);

    foreach($jsonDecoded->data AS $key) {
        global $xml, $products;
        $baseUrl = 'https://www.paulmitchell.com';

        if ($key->is_visible) {
            $product = $xml->createElement('Product');
            $products->appendChild($product);
            $name = $xml->createElement('Name', htmlspecialchars($key->name));
            $product->appendChild($name);
            $productId = $xml->createElement('ProductUniqueID', $key->id);
            $product->appendChild($productId);
            $productUrl = $xml->createElement('ProductUrl', $baseUrl . $key->custom_url->url);
            $product->appendChild($productUrl);

            foreach($key->images AS $image) {
                if ($image->is_thumbnail) {
                    $productImg = $xml->createElement('ImageUrl', $image->url_zoom);
                    $product->appendChild($productImg);
                }
            }

            $category_id = $xml->createElement('CategoryID', $key->brand_id);
            $product->appendChild($category_id);
        } //end if visible
    } //end foreach

    // Pagination tracking
    $currentPage = $jsonDecoded->meta->pagination->current_page;
    $totalPages = $jsonDecoded->meta->pagination->total_pages;

    if ($currentPage < $totalPages) {
        $url = "https://api.bigcommerce.com/stores/u6pyv0n7lf/v3/catalog/products" . $jsonDecoded->meta->pagination->links->next;
    } else {
        $url = '';
    }
}

curl_close($curl);

date_default_timezone_set('America/Los_Angeles');

if (error_get_last() === NULL) {
    $validation = $xml->schemaValidate('../xml/olapic.v1.1.xsd');
    if ($validation) {
        echo "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] Validated! Writing " . $xml->save('../xml/jpms-product-feed.xml') . " bytes to your Olapic-friendly product feed.";
        $logmsg = "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] Olapic XML file generated and saved successfully.\n";
        file_put_contents('../log/olapic-log.txt', $logmsg, FILE_APPEND);
    } else {
        echo 'Error validating product feed against Olapic Schema.';
        $logmsg = "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] Error validating product feed against Olapic XML schema.\n";
        file_put_contents('../log/olapic-log.txt', $logmsg, FILE_APPEND);
    }
} else {
    echo 'PHP Error! Check file.';
    $logmsg = "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] PHP Error:\n" . var_export(error_get_last()) . "\n";
    file_put_contents('../log/olapic-log.txt', $logmsg, FILE_APPEND);
};
?>