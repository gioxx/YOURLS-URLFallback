# &#8618; YOURLS URL Fallback

**Redirect visitors to a configurable fallback URL whenever they hit a non-existent short URL or the YOURLS root page.**  
Valid short URLs continue to work normally — no interference with your existing links.

[![Latest Release](https://img.shields.io/github/v/release/gioxx/YOURLS-URLFallback)](https://github.com/gioxx/YOURLS-URLFallback/releases)
[![License](https://img.shields.io/github/license/gioxx/YOURLS-URLFallback)](LICENSE)

---

## 🚀 Features

- **Automatic fallback redirect** for unknown short URL keywords and the YOURLS root page
- **Choice of redirect type**: 301 Permanent or 302 Temporary
- **Stats pages unaffected**: URLs ending with `+` (native YOURLS stats) are always passed through normally
- **Instant disable**: leave the fallback URL empty to restore default YOURLS behavior
- **Update notifications**: checks GitHub for new releases and shows a badge in the admin panel
- **Simple single-file plugin** — no composer, no npm, no build step

---

## 🛠️ Installation

1. Download the plugin from [the latest release](https://github.com/gioxx/YOURLS-URLFallback/releases).
2. Unzip the contents into the `user/plugins/url-fallback/` directory.
3. Activate the plugin in the YOURLS admin panel under **Plugins**.
4. Go to **Plugins → URL Fallback** and enter the destination URL.

> **Requires YOURLS 1.9+ and PHP 7.4+**

---

## ⚙️ Usage

### Basic Configuration

1. Open the **URL Fallback** settings page from the Plugins menu.
2. Enter the **Fallback URL** — the destination visitors are sent to when no matching short URL is found.
3. Choose the **Redirect Type**:
   - **302 Temporary** — use this while testing or when the destination may change.
   - **301 Permanent** — use this once the destination is stable. Browsers cache 301 redirects, so switching later requires visitors to clear their cache.
4. Click **Save Settings**.

### How the redirect works

| Visitor request | Result |
|---|---|
| Valid short URL (e.g. `/abc`) | YOURLS handles it normally |
| Unknown keyword (e.g. `/xyz`) | Redirected to fallback URL |
| YOURLS root page (`/`) | Redirected to fallback URL |
| Stats page (e.g. `/abc+`) | YOURLS handles it normally |
| No fallback URL configured | Default YOURLS behavior (404 page) |

### Disabling the plugin without deactivating

Clear the **Fallback URL** field and save. The plugin stays active but does not intercept any requests.

---

## 🔧 Stored Options

| Option key | Type | Notes |
|---|---|---|
| `url_fallback_url` | string | Full URL including scheme; empty string = disabled |
| `url_fallback_redirect_type` | int | `301` or `302`; defaults to `302` if not set |

---

## 🌐 Translation

This plugin is ready for internationalization via `.po`/`.mo` files inside the `languages/` folder.  
Available languages:
- 🇬🇧 English (default)

Contributions for additional languages are welcome.

---

## 📄 License

This plugin is licensed under the [MIT License](LICENSE).

---

## 💬 About

Lovingly developed by the usually-on-vacation brain cell of [Gioxx](https://github.com/gioxx), with assistance from Claude AI.

---

## 🤝 Contributing

Pull requests and feature suggestions are welcome!  
If you find bugs or have feature requests, [open an issue](https://github.com/gioxx/YOURLS-URLFallback/issues).  
If you find it useful, leave a ⭐ on GitHub! ❤️
