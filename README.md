# Bookbuys Embed Prototype

This is a minimal prototype for a bookshelf embed that loads cached book data and shows a price overlay on hover/tap.

## Embed snippet

```html
<script
  src="https://bookbuys.com/embed.js"
  data-author="jane-doe"
  data-theme="light">
</script>
```

`data-author` is the author slug. The script fetches JSON from `/api/author.php?slug=...` and renders the shelf in a Shadow DOM to avoid CSS collisions. The overlay is an iframe that loads `/embed/offer.php?book_id=...`.

## API response shape (author JSON)

```json
{
  "author": { "id": "au_001", "name": "Jane Doe", "slug": "jane-doe" },
  "generated_at": "2026-03-12T22:34:00-08:00",
  "ttl_seconds": 21600,
  "books": [
    {
      "id": "bk_001",
      "title": "Last Light",
      "isbn13": "9781234567890",
      "release_date": "2025-10-10",
      "spine_url": "/assets/spines/last-light.png",
      "overlay_url": "/embed/offer.php?book_id=bk_001",
      "price_summary": { "best_new": 14.99, "currency": "USD" }
    }
  ]
}
```

## Offer overlay HTML

`/embed/offer.php?book_id=bk_001` renders a lightweight, cacheable HTML fragment with price comparisons and CTA buttons. It is embedded in an iframe for isolation.

## Spine workflow (suggested)

1. Normalize each cover to a consistent aspect ratio.
2. Generate a spine image from the cover with a fixed width and height.
3. Store in `assets/spines/` and cache aggressively (versioned filenames are best).

For a production workflow, prefer pre-rendering spines and storing them on a CDN.
