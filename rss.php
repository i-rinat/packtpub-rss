<?php

error_reporting(0);
header('Content-Type: application/rss+xml; charset=utf-8');

function get_rss_entry() {
    $doc = new DOMDocument();

    // Fetch and parse HTML.
    $doc->loadHTML(file_get_contents("https://www.packtpub.com/packt/offers/free-learning"));

    // Find interesting part.
    $xpath = new DOMXPath($doc);
    $res = $xpath->query("//*[@class='dotd-main-book-summary float-left']");

    if ($res->length == 0) {
        $title = "Error";
        $body = "Can't get description. Date: " . date(DATE_RFC822);
    } else {

        // Take first result.
        $item = $res->item(0);

        // Collect text nodes.
        $to_delete = array();
        foreach ($item->childNodes as $child) {
            if ($child->nodeName == "#text")
                $to_delete[] = $child;
        }

        // And delete them.
        foreach ($to_delete as $entry)
            $item->removeChild($entry);

        // Remove timer line.
        $item->removeChild($item->childNodes->item(0));

        // Extract title of the book and then remove the node.
        $title = trim($item->childNodes->item(0)->textContent);
        $item->removeChild($item->childNodes->item(0));

        $body = "";
        foreach ($item->childNodes as $child)
            $body = $body . trim($child->textContent);
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
