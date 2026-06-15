<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * Google Merchant Center product feed (RSS 2.0 + g: namespace).
     * Public, no auth. Merchant Center fetches this URL on a schedule.
     */
    public function google()
    {
        $items = '';
        foreach (Product::orderBy('id')->get() as $p) {
            $title = trim($p->name_ar ?: $p->name_en ?: 'تمر');
            $desc = trim($p->description_ar ?: $p->description_en ?: '');
            if ($desc === '') {
                $desc = $title . ' — تمر سعودي فاخر من تمرات. توصيل سريع داخل السعودية.';
            }
            $link = 'https://tamratdates.com/products/' . rawurlencode($p->slug);
            $image = preg_replace('#^http://#', 'https://', (string) $p->image_path);
            $price = number_format((float) $p->price, 2, '.', '') . ' SAR';
            $avail = ((int) $p->countInStock) > 0 ? 'in_stock' : 'out_of_stock';

            $e = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            $items .= "    <item>\n"
                . '      <g:id>' . $e($p->slug) . "</g:id>\n"
                . '      <g:title>' . $e($title) . "</g:title>\n"
                . '      <g:description>' . $e($desc) . "</g:description>\n"
                . '      <g:link>' . $e($link) . "</g:link>\n"
                . '      <g:image_link>' . $e($image) . "</g:image_link>\n"
                . '      <g:availability>' . $avail . "</g:availability>\n"
                . '      <g:price>' . $e($price) . "</g:price>\n"
                . '      <g:brand>تمرات</g:brand>' . "\n"
                . '      <g:condition>new</g:condition>' . "\n"
                . '      <g:identifier_exists>no</g:identifier_exists>' . "\n"
                . '      <g:product_type>' . $e('تمور > ' . $title) . "</g:product_type>\n"
                . "    </item>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n"
            . "  <channel>\n"
            . "    <title>تمرات | Tamrat Dates</title>\n"
            . "    <link>https://tamratdates.com</link>\n"
            . "    <description>تمور سعودية فاخرة — سكري، عجوة، مجدول، مبروم، صقعي</description>\n"
            . $items
            . "  </channel>\n"
            . "</rss>\n";

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
