<?php
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;

$feed = $xml->createElement('feed');
$xmlns_atom = $xml->createAttribute('xmlns');
$xmlns_atom->value = 'http://www.w3.org/2005/Atom';
$xmlns_google = $xml->createAttribute('xmlns:g');
$xmlns_google->value = 'http://base.google.com/ns/1.0';
$feed->appendChild($xmlns_atom);
$feed->appendChild($xmlns_google);

$title = $xml->createElement('title', 'John Paul Mitchell Systems');

$link = $xml->createElement('link');
$link_href = $xml->createAttribute('href');
$link_href->value = 'https://www.paulmitchell.com/';
$link_rel = $xml->createAttribute('rel');
$link_rel->value = 'alternate';
$link->appendChild($link_href);
$link->appendChild($link_rel);

date_default_timezone_set('America/Los_Angeles');
$updated = $xml->createElement('updated', date('Y-m-d') . 'T' . date('H:i:s') . '-0800');

$xml->appendChild($feed);
$feed->appendChild($title);
$feed->appendChild($link);
$feed->appendChild($updated);

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

    $baseUrl = 'https://www.paulmitchell.com';
    $g_condition = $xml->createElement('g:condition', 'new');

    foreach($jsonDecoded->data AS $key) {
      if ($key->is_visible) {
        if (sizeof($key->variants) > 1) {
          foreach($key->variants AS $variant) {
            $entry = $xml->createElement('entry');
            $feed->appendChild($entry);

            $g_id = $xml->createElement('g:id', $variant->sku);
            $g_title = $xml->createElement('g:title', htmlspecialchars($key->name) . " " . $variant->option_values[0]->label);
            $g_description = $xml->createElement('g:description', htmlspecialchars($key->meta_description));
            $g_link = $xml->createElement('g:link', $baseUrl . $key->custom_url->url);
            $g_image_link = $xml->createElement('g:image_link', $variant->image_url);

            if ($variant->inventory_level == 0) {
              $g_availability = $xml->createElement('g:availability', 'out of stock');
            } else {
              $g_availability = $xml->createElement('g:availability', 'in stock');
            }

            $g_price = $xml->createElement('g:price', $variant->price . " USD");
            $g_size = $xml->createElement('g:size', $variant->option_values[0]->label);

            // Shipping node
            // $g_shipping = $xml->createElement('g:shipping');
            // $g_shipping->appendChild($xml->createElement('g:country', 'US'));
            // $g_shipping->appendChild($xml->createElement('g:region', 'CA'));
            // $g_shipping->appendChild($xml->createElement('g:service', 'Ground'));
            // $g_shipping->appendChild($xml->createElement('g:price', '7.99 USD'));

            $g_gtin = $xml->createElement('g:gtin', $variant->upc);

            switch ($key->brand_id) {
              case 41:
                $g_brand = $xml->createElement('g:brand', 'Awapuhi Wild Ginger');
                break;
              case 43:
                $g_brand = $xml->createElement('g:brand', 'MarulaOil');
                break;
              case 40:
                $g_brand = $xml->createElement('g:brand', 'MITCH');
                break;
              case 45:
                $g_brand = $xml->createElement('g:brand', 'Neon');
                break;
              case 39:
                $g_brand = $xml->createElement('g:brand', 'Neuro');
                break;
              case 38:
                $g_brand = $xml->createElement('g:brand', 'Paul Mitchell');
                break;
              case 44:
                $g_brand = $xml->createElement('g:brand', 'Pro Tools');
                break;
              case 42:
                $g_brand = $xml->createElement('g:brand', 'Tea Tree');
                break;
              default:
                  $g_brand = $xml->createElement('g:brand', 'John Paul Mitchell Systems');
            }

            $g_item_group_id = $xml->createElement('g:item_group_id', $key->id);

            // Google Categories based on "Product Type" Category
            // Instead use Custom Fields to accomplish this
            foreach($key->categories AS $category) {
              switch ($category) {
                // Health & Beauty > Personal Care > Hair Care > Shampoo & Conditioner
                case 104:
                case 105:
                  $g_google_category = $xml->createElement('g:google_product_category', 2441);
                  break;
                // Health & Beauty > Personal Care > Hair Care > Hair Styling Products
                case 106:
                case 112:
                case 110:
                case 111:
                  $g_google_category = $xml->createElement('g:google_product_category', 1901);
                  break;
                // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools > Combs & Brushes
                case 108:
                  $g_google_category = $xml->createElement('g:google_product_category', 487);
                  break;
                // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools > Hair Dryers
                case 107:
                  $g_google_category = $xml->createElement('g:google_product_category', 490);
                  break;
                // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools
                case 109:
                  $g_google_category = $xml->createElement('g:google_product_category', 6019);
                  break;
                // Default to Health & Beauty > Personal Care > Hair Care
                default:
                  $g_google_category = $xml->createElement('g:google_product_category', 486);
              }
            }

            // Append everything to the ENTRY node
            $entry->appendChild($g_id);
            $entry->appendChild($g_title);
            $entry->appendChild($g_description);
            $entry->appendChild($g_link);
            $entry->appendChild($g_image_link);
            $entry->appendChild($g_availability);
            $entry->appendChild($g_price);
            $entry->appendChild($g_size);
            // $entry->appendChild($g_shipping);
            $entry->appendChild($g_gtin);
            $entry->appendChild($g_brand);
            $entry->appendChild($g_item_group_id);
            $entry->appendChild($g_google_category);
          }
        } else {
          // create nodes for products without variants here
          $entry = $xml->createElement('entry');
          $feed->appendChild($entry);

          $g_id = $xml->createElement('g:id', $key->sku);
          $g_title = $xml->createElement('g:title', htmlspecialchars($key->name));
          $g_description = $xml->createElement('g:description', htmlspecialchars($key->meta_description));
          $g_link = $xml->createElement('g:link', $baseUrl . $key->custom_url->url);
          $g_image_link = $xml->createElement('g:image_link', $key->variants[0]->image_url);

          if ($key->inventory_level == 0) {
            $g_availability = $xml->createElement('g:availability', 'out of stock');
          } else {
            $g_availability = $xml->createElement('g:availability', 'in stock');
          }

          $g_price = $xml->createElement('g:price', $key->price . " USD");

          // Shipping node
          // $g_shipping = $xml->createElement('g:shipping');
          // $g_shipping->appendChild($xml->createElement('g:country', 'US'));
          // $g_shipping->appendChild($xml->createElement('g:region', 'CA'));
          // $g_shipping->appendChild($xml->createElement('g:service', 'Ground'));
          // $g_shipping->appendChild($xml->createElement('g:price', '7.99 USD'));

          $g_gtin = $xml->createElement('g:gtin', $key->upc);

          switch ($key->brand_id) {
            case 41:
              $g_brand = $xml->createElement('g:brand', 'Awapuhi Wild Ginger');
              break;
            case 43:
              $g_brand = $xml->createElement('g:brand', 'MarulaOil');
              break;
            case 40:
              $g_brand = $xml->createElement('g:brand', 'MITCH');
              break;
            case 45:
              $g_brand = $xml->createElement('g:brand', 'Neon');
              break;
            case 39:
              $g_brand = $xml->createElement('g:brand', 'Neuro');
              break;
            case 38:
              $g_brand = $xml->createElement('g:brand', 'Paul Mitchell');
              break;
            case 44:
              $g_brand = $xml->createElement('g:brand', 'Pro Tools');
              break;
            case 42:
              $g_brand = $xml->createElement('g:brand', 'Tea Tree');
              break;
            default:
                $g_brand = $xml->createElement('g:brand', 'John Paul Mitchell Systems');
          }

          // Google Categories based on "Product Type" Category
          // Instead use Custom Fields to accomplish this
          foreach($key->categories AS $category) {
            switch ($category) {
              // Health & Beauty > Personal Care > Hair Care > Shampoo & Conditioner
              case 104:
              case 105:
                $g_google_category = $xml->createElement('g:google_product_category', 2441);
                break;
              // Health & Beauty > Personal Care > Hair Care > Hair Styling Products
              case 106:
              case 112:
              case 110:
              case 111:
                $g_google_category = $xml->createElement('g:google_product_category', 1901);
                break;
              // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools > Combs & Brushes
              case 108:
                $g_google_category = $xml->createElement('g:google_product_category', 487);
                break;
              // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools > Hair Dryers
              case 107:
                $g_google_category = $xml->createElement('g:google_product_category', 490);
                break;
              // Health & Beauty > Personal Care > Hair Care > Hair Styling Tools
              case 109:
                $g_google_category = $xml->createElement('g:google_product_category', 6019);
                break;
              // Default to Health & Beauty > Personal Care > Hair Care
              default:
                $g_google_category = $xml->createElement('g:google_product_category', 486);
            }
          }

          // Append everything to the ENTRY node
          $entry->appendChild($g_id);
          $entry->appendChild($g_title);
          $entry->appendChild($g_description);
          $entry->appendChild($g_link);
          $entry->appendChild($g_image_link);
          $entry->appendChild($g_availability);
          $entry->appendChild($g_price);
          // $entry->appendChild($g_shipping);
          $entry->appendChild($g_gtin);
          $entry->appendChild($g_brand);
          $entry->appendChild($g_google_category);
        }
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

if(error_get_last() === NULL) {
  echo "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] Success! Writing " . $xml->save('../xml/jpms-product-feed-google.xml') . " bytes to your Google Shopping product feed.";
    $logmsg = "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] Google Shopping XML file generated and saved successfully.\n";
    file_put_contents('../log/google-log.txt', $logmsg, FILE_APPEND);
} else {
  echo 'PHP Error! Check file.';
  $logmsg = "[" . date('Y-m-d') . 'T' . date('H:i:s') . "-0800] PHP Error:\n" . var_export(error_get_last()) . "\n";
  file_put_contents('../log/google-log.txt', $logmsg, FILE_APPEND);
};
?>