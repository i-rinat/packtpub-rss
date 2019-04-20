<?php

error_reporting(0);
header('Content-Type: application/rss+xml; charset=utf-8');

function get_day_start($ts) {
    $lt = localtime($ts, TRUE);
    return sprintf("%04d-%02d-%02dT00:00:00.000Z", $lt['tm_year'] + 1900,
                   $lt['tm_mon'] + 1, $lt['tm_mday']);
}

function get_rss_entry() {
    // Generate the offers list URL.
    $ts = time();

    $url = sprintf("https://services.packtpub.com/free-learning-v1/offers?".
                   "dateFrom=%s&dateTo=%s", get_day_start($ts),
                   get_day_start($ts + 86400));
    // Get list of the offers.
    $offers_json = file_get_contents($url);
    $offers = json_decode($offers_json, TRUE);

    // Get the first offer description.
    $product_id = $offers['data'][0]['productId'];
    $url = sprintf("https://static.packt-cdn.com/products/%s/summary",
                   $product_id);
    $book_descr_json = file_get_contents($url);
    $book_descr = json_decode($book_descr_json, TRUE);

    $title = strip_tags($book_descr['title']);
    $body = strip_tags($book_descr['oneLiner']) . "\n\n";
    $body .= strip_tags($book_descr['about']);

    // Generate RSS entry.
    $guid = "They say ".$title." one should always ".$body." add a salt to ".
            "the hash computations. Even if that makes no sense.";
    $guid = hash('sha256', $guid);

    $rss_entry = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
    <channel>
        <link>https://www.packtpub.com/packt/offers/free-learning</link>
        <language>en</language>
        <title>Packtpub Book of the Day</title>
        <description>Packpub Book of the Day</description>

        <item>
            <author>rss bot</author>
            <title>$title</title>
            <description>$body</description>
            <link>https://www.packtpub.com/packt/offers/free-learning</link>
            <guid>$guid</guid>
        </item>
    </channel>
</rss>

EOD;

    return $rss_entry;
}

function get_cached_rss_entry() {
    $cache_fname = "cached_rss_entry.txt";
    $cache_duration = 300;

    if (file_exists($cache_fname) &&
        filemtime($cache_fname) + $cache_duration > time())
    {
        return file_get_contents($cache_fname);
    }

    $rss_entry = get_rss_entry();
    file_put_contents($cache_fname, $rss_entry);
    return $rss_entry;
}

echo get_cached_rss_entry();
