(() => {
  const script = document.currentScript;
  if (!script) {
    return;
  }

  const author = script.dataset.author;
  if (!author) {
    return;
  }

  const theme = script.dataset.theme || "light";
  const origin = new URL(script.src, window.location.href).origin;

  const container = document.createElement("div");
  container.className = "bb-embed-root";
  script.parentNode.insertBefore(container, script);

  const shadow = container.attachShadow({ mode: "open" });
  shadow.innerHTML = `
    <style>
      :host {
        all: initial;
      }
      .bb-shelf {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        padding: 10px 8px 12px;
        border-radius: 10px;
        background: ${theme === "dark" ? "#1f1f1f" : "#f7f4ee"};
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        font-family: "Georgia", "Times New Roman", serif;
        position: relative;
      }
      .bb-book {
        width: 34px;
        height: 180px;
        border-radius: 4px;
        background: #ddd;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        outline: none;
        border: 0;
        padding: 0;
      }
      .bb-book img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
      .bb-book-label {
        position: absolute;
        left: 4px;
        bottom: 6px;
        right: 4px;
        color: #fff;
        font-size: 10px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        line-height: 1.1;
        pointer-events: none;
      }
      .bb-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.25);
      }
      .bb-overlay.active {
        display: flex;
      }
      .bb-frame {
        width: min(420px, 94%);
        height: min(260px, 80%);
        border: 0;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
      }
      .bb-loading {
        font-size: 12px;
        color: ${theme === "dark" ? "#f0f0f0" : "#333"};
        padding: 6px 2px;
      }
    </style>
    <div class="bb-shelf">
      <div class="bb-loading">Loading books...</div>
    </div>
  `;

  const shelf = shadow.querySelector(".bb-shelf");

  const overlay = document.createElement("div");
  overlay.className = "bb-overlay";
  const iframe = document.createElement("iframe");
  iframe.className = "bb-frame";
  iframe.setAttribute("loading", "lazy");
  overlay.appendChild(iframe);
  shelf.appendChild(overlay);

  const hideOverlay = () => {
    overlay.classList.remove("active");
    iframe.src = "";
  };

  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) {
      hideOverlay();
    }
  });

  const toAbsolute = (url) => {
    if (!url) {
      return "";
    }
    if (url.startsWith("http://") || url.startsWith("https://")) {
      return url;
    }
    return `${origin}${url}`;
  };

  fetch(`${origin}/api/author.php?slug=${encodeURIComponent(author)}`, { mode: "cors" })
    .then((res) => {
      if (!res.ok) {
        throw new Error("Failed to load author data");
      }
      return res.json();
    })
    .then((data) => {
      shelf.innerHTML = "";
      shelf.appendChild(overlay);

      const books = Array.isArray(data.books) ? data.books : [];
      books.forEach((book) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "bb-book";
        button.setAttribute("aria-label", book.title || "Book");

        const img = document.createElement("img");
        img.alt = book.title || "Book";
        img.src = toAbsolute(book.spine_url);

        const label = document.createElement("div");
        label.className = "bb-book-label";
        label.textContent = book.title || "";

        button.appendChild(img);
        button.appendChild(label);

        const showOverlay = () => {
          const overlayUrl = toAbsolute(book.overlay_url);
          if (!overlayUrl) {
            return;
          }
          iframe.src = overlayUrl;
          overlay.classList.add("active");
        };

        button.addEventListener("mouseenter", showOverlay);
        button.addEventListener("focus", showOverlay);
        button.addEventListener("click", () => {
          if (overlay.classList.contains("active")) {
            hideOverlay();
          } else {
            showOverlay();
          }
        });

        button.addEventListener("mouseleave", hideOverlay);
        shelf.appendChild(button);
      });
    })
    .catch(() => {
      shelf.innerHTML = "<div class=\"bb-loading\">Unable to load books.</div>";
    });
})();
