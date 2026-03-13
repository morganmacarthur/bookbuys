<?php
// Minimal price cache updater stub.
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

// Placeholder: in production, fetch prices from approved retailer APIs.
// For now, just update generated_at and keep existing prices.
$payload['generated_at'] = date('c');
$payload['ttl_seconds'] = 21600;

file_put_contents($authorPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

fwrite(STDOUT, "Updated {$authorPath}\n");
