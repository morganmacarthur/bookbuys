<?php
$bookId = isset($_GET['book_id']) ? strtolower(trim($_GET['book_id'])) : '';
$bookId = preg_replace('/[^a-z0-9_\-]/', '', $bookId);

if ($bookId === '') {
    http_response_code(400);
    echo 'Missing book_id';
    exit;
}

$path = __DIR__ . '/../data/offers/' . $bookId . '.json';
if (!file_exists($path)) {
    http_response_code(404);
    echo 'Book not found';
    exit;
}

$payload = json_decode(file_get_contents($path), true);
if (!is_array($payload)) {
    http_response_code(500);
    echo 'Invalid data';
    exit;
}

$book = isset($payload['book']) ? $payload['book'] : ['title' => 'Untitled', 'author' => 'Unknown'];
$bestOffer = isset($payload['best_offer']) ? $payload['best_offer'] : null;
$offers = isset($payload['offers']) ? $payload['offers'] : [];

if ($bestOffer === null && count($offers) > 0) {
    usort($offers, function ($a, $b) {
        return $a['price'] <=> $b['price'];
    });
    $bestOffer = $offers[0];
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=21600');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bookbuys Offer</title>
    <style>
      body {
        margin: 0;
        font-family: "Helvetica Neue", Arial, sans-serif;
        background: #ffffff;
        color: #1d1d1d;
      }
      .wrap {
        padding: 16px 18px 14px;
      }
      .title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 4px;
      }
      .author {
        font-size: 12px;
        color: #666;
        margin-bottom: 12px;
      }
      .offer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-top: 1px solid #eee;
        font-size: 14px;
      }
      .offer:first-of-type {
        border-top: 0;
      }
      .cta {
        background: #121212;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
      }
      .note {
        font-size: 10px;
        color: #777;
        margin-top: 10px;
      }
      .empty {
        font-size: 12px;
        color: #666;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="title"><?php echo htmlspecialchars($book['title']); ?></div>
      <div class="author">by <?php echo htmlspecialchars($book['author']); ?></div>

      <?php if ($bestOffer): ?>
        <div class="offer">
          <div>
            <div><?php echo htmlspecialchars($bestOffer['retailer']); ?></div>
            <div>$<?php echo number_format((float)$bestOffer['price'], 2); ?></div>
          </div>
          <a class="cta" href="<?php echo htmlspecialchars($bestOffer['url']); ?>" target="_blank" rel="noopener">Buy new</a>
        </div>
      <?php else: ?>
        <div class="empty">No offers available.</div>
      <?php endif; ?>

      <div class="note">Prices updated every few hours. New copies only.</div>
    </div>
  </body>
</html>
