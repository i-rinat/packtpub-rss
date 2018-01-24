<?php

error_reporting(0);
header('Content-Type: application/rss+xml; charset=utf-8');

require('simple_html_dom.php');

function get_rss_entry() {
    // Fetch and parse HTML.
    $html = file_get_contents("https://www.packtpub.com/packt/offers/free-learning");
    $doc = str_get_html($html);

    // Find interesting part.
    $res = $doc->find("div.dotd-main-book-summary");

    if (count($res) == 0) {
        $title = "Error";
        $body = "Can't get description. Date: " . date(DATE_RFC822);
    } else {

        // Take first result.
        $res = $res[0];

        // Title is in its own div.
        $title = trim($res->find('div.dotd-title')[0]->plaintext);

        // One of the div's without a class containing book description.
        $body = "";
        foreach ($res->children() as $div) {
            if ($div->tag != "div")
                continue;

            $cls = $div->getAttribute('class');
            if (trim($cls) != "")
                continue;

            $body = $body . trim($div->plaintext) . "\n";
        }

        $body = trim($body);
    }

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
