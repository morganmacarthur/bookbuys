<?php
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bookbuys Embed Demo</title>
    <style>
      body {
        font-family: "Helvetica Neue", Arial, sans-serif;
        padding: 30px;
        background: #f3f0ea;
        color: #1b1b1b;
      }
      .card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      }
      .code {
        background: #111;
        color: #f5f5f5;
        padding: 12px;
        border-radius: 8px;
        font-size: 12px;
        overflow: auto;
      }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Bookbuys Embed Demo</h1>
      <p>This is a local demo using the sample author data.</p>
      <div>
        <script src="/embed.js" data-author="jane-doe" data-theme="light"></script>
      </div>
      <h3>Embed snippet</h3>
      <pre class="code">&lt;script src="https://bookbuys.com/embed.js" data-author="jane-doe" data-theme="light"&gt;&lt;/script&gt;</pre>
    </div>
  </body>
</html>
