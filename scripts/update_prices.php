<?php
// Price cache updater for Bookbuys.
// Usage: php scripts/update_prices.php --author=jane-doe

$options = getopt('', ['author::']);
$author = isset($options['author']) ? $options['author'] : 'jane-doe';
$author = preg_replace('/[^a-z0-9-]/', '', strtolower($author));

if ($author === '') {
    fwrite(STDERR, "Invalid author\n");
    exit(1);
}

$authorPath = __DIR__ . '/../data/authors/' . $author . '.json';
if (!file_exists($authorPath)) {
    fwrite(STDERR, "Author not found\n");
    exit(1);
}

$payload = json_decode(file_get_contents($authorPath), true);
if (!is_array($payload)) {
    fwrite(STDERR, "Invalid author JSON\n");
    exit(1);
}

$retailers = [
    [
        'name' => 'BiggerBooks',
        'price_url' => function ($isbn) {
            return 'https://www.biggerbooks.com/botprice?isbn=' . urlencode($isbn);
        },
        'buy_url' => function ($isbn) {
            return 'https://www.biggerbooks.com/search?query=' . urlencode($isbn);
        },
    ],
    [
        'name' => 'eCampus',
        'price_url' => function ($isbn) {
            return 'https://www.ecampus.com/botprice?isbn=' . urlencode($isbn);
        },
        'buy_url' => function ($isbn) {
            return 'https://www.ecampus.com/search?query=' . urlencode($isbn);
        },
    ],
];

$books = isset($payload['books']) && is_array($payload['books']) ? $payload['books'] : [];
$now = date('c');

foreach ($books as $index => $book) {
    $isbn = isset($book['isbn13']) ? preg_replace('/[^0-9Xx]/', '', $book['isbn13']) : '';
    if ($isbn === '') {
        continue;
    }

    $offers = [];

    foreach ($retailers as $retailer) {
        $priceUrl = $retailer['price_url']($isbn);
        $body = bb_http_get($priceUrl);
        if ($body === null) {
            continue;
        }

        $price = bb_extract_price($body);
        if ($price === null) {
            continue;
        }

        $offers[] = [
            'retailer' => $retailer['name'],
            'price' => $price,
            'url' => $retailer['buy_url']($isbn),
            'last_checked' => $now,
        ];
    }

    usort($offers, function ($a, $b) {
        return $a['price'] <=> $b['price'];
    });

    $best = count($offers) > 0 ? $offers[0] : null;

    $bookId = isset($book['id']) ? $book['id'] : 'book_' . ($index + 1);
    $offerPayload = [
        'book' => [
            'id' => $bookId,
            'title' => isset($book['title']) ? $book['title'] : 'Untitled',
            'author' => isset($payload['author']['name']) ? $payload['author']['name'] : 'Unknown',
        ],
        'best_offer' => $best,
        'offers' => $offers,
        'generated_at' => $now,
    ];

    $offerPath = __DIR__ . '/../data/offers/' . $bookId . '.json';
    file_put_contents($offerPath, json_encode($offerPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($best) {
        $payload['books'][$index]['price_summary'] = [
            'best_new' => $best['price'],
            'currency' => 'USD',
        ];
    } else {
        $payload['books'][$index]['price_summary'] = [
            'best_new' => null,
            'currency' => 'USD',
        ];
    }
}

$payload['generated_at'] = $now;
$payload['ttl_seconds'] = 21600;

file_put_contents($authorPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

fwrite(STDOUT, "Updated {$authorPath}\n");

function bb_http_get($url)
{
    $timeout = 8;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BookbuysBot/0.1');
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return null;
        }
        return $body;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header' => "User-Agent: BookbuysBot/0.1\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return null;
    }

    return $body;
}

function bb_extract_price($body)
{
    $trim = trim($body);
    if ($trim === '') {
        return null;
    }

    if (is_numeric($trim)) {
        return (float)$trim;
    }

    $data = json_decode($trim, true);
    if (is_array($data)) {
        $price = bb_find_price_in_array($data);
        if ($price !== null) {
            return $price;
        }
    }

    if (preg_match('/(\d+\.\d{2})/', $trim, $match)) {
        return (float)$match[1];
    }

    return null;
}

function bb_find_price_in_array($data)
{
    $priorityKeys = [
        'new',
        'new_price',
        'price',
        'sale_price',
        'bestprice',
        'botprice',
        'best_price',
        'total',
        'amount',
    ];

    foreach ($priorityKeys as $key) {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return (float)$data[$key];
        }
        if (isset($data[$key]) && is_string($data[$key])) {
            $str = preg_replace('/[^0-9.]/', '', $data[$key]);
            if ($str !== '' && is_numeric($str)) {
                return (float)$str;
            }
        }
    }

    foreach ($data as $value) {
        if (is_array($value)) {
            $price = bb_find_price_in_array($value);
            if ($price !== null) {
                return $price;
            }
        }
    }

    return null;
}
