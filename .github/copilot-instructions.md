# Copilot instructions for the RACINE BY GANDA codebase

This file tells AI coding agents the minimal, concrete knowledge needed to be productive in this PHP/MySQL storefront.

1) Project type & run basics
- Stack: PHP (plain PHP files), MySQL, frontend assets (Bootstrap, jQuery, OwlCarousel).
- Run locally with XAMPP: place the `Racine by GANDA/` folder in `htdocs` and import the DB `base_donnée/by_ganda (1).sql`.
- DB credentials (used in `config.php`): host=`localhost`, user=`root`, password=`""`, database=`by_ganda`.
- Useful import command (PowerShell/XAMPP):
  - `C:\xampp\mysql\bin\mysql.exe -u root by_ganda < "C:\xampp\htdocs\richnique\base_donnée\by_ganda (1).sql"`

2) Big picture / architecture
- PHP pages are monolithic templates (no framework). Key pages:
  - `index.php` — product listing (queries `produits`).
  - `shop.php`, `product-single.html` — product browsing (same conventions).
  - `cart.php`, `ajouter_panier.php`, `retirer.php`, `get_totaux_panier.php` — session-based cart logic using `$_SESSION['panier']`.
  - `place_order.php` — final order POST handler; writes `commandes` and `commande_details` using a transaction.
  - `admin/*` — admin UI; `admin/includes/header.php` checks `$_SESSION['admin']` for auth.
- Frontend: assets in `css/`, `js/`, and `images/`. Cart interactions are handled by anchors with `data-*` attributes and JS in `js/main.js` / `perfectionnement.js`.

3) Data flow & conventions (important examples)
- Database access: `include('./config.php')` provides `$conn` (mysqli). Set charset via `$conn->set_charset('utf8')`.
- Cart model: `$_SESSION['panier']` is an associative array keyed by product id. Each item has at least `['name','price','image','quantity']`.
  - Example reading: `array_sum(array_column($_SESSION['panier'], 'quantity'))` (see `index.php` cart-count).
- Order flow: `place_order.php` expects POST and the session cart; it:
  - calculates subtotal, delivery, discount,
  - begins a transaction, inserts into `commandes`, then `commande_details` per item, commits or rollbacks on exception.
  - Note: `place_order.php` uses prepared statements — watch `bind_param` types (there's a TODO comment in file about types).

4) Project-specific patterns & gotchas
- Sessions: many files call `session_start()` in-page. When adding new pages, ensure `session_start()` is present before `$_SESSION` use.
- DB connection: `config.php` is included directly; do not change variable names (`$conn`) without updating usages.
- Admin security: admin pages check `$_SESSION['admin']`; login is handled in `admin/login.php`.
- Cart additions: links often use both data-attributes for JS and fallback GET parameters (e.g., `ajouter_panier.php?id=...&name=...`). Support both when changing cart behavior.
- Error handling: codebase often dies on error (e.g., `die(...)`) — prefer to preserve that behavior when making quick changes unless explicitly refactoring.

5) Testing / debugging guidance
- No automated tests present. For quick debugging enable errors in PHP or add at top of script:
  - `ini_set('display_errors', 1); error_reporting(E_ALL);`
- Reproduce orders locally by:
  1. Starting Apache/MySQL in XAMPP.
  2. Importing SQL and visiting `http://localhost/richnique/Racine%20by%20GANDA/index.php`.
  3. Add products then POST to `place_order.php` via the checkout UI.

6) Files to inspect for common changes
- `config.php` — DB connection & charset.
- `index.php`, `shop.php` — product queries and listing markup.
- `ajouter_panier.php`, `retirer.php`, `get_totaux_panier.php` — cart operations.
- `place_order.php` — order insertion transaction; look for `bind_param` types if you see errors.
- `admin/includes/header.php` and `admin/login.php` — admin auth flow.

7) Typical PR requests the agent will get (examples)
- Add server-side input validation when inserting orders (`place_order.php`): check numeric types and set explicit bind types.
- Fix cart edge cases: missing `image` fallback `default.png` is used in `cart.php` — keep that pattern when changing display code.
- Improve i18n-safe output: prefer `htmlspecialchars()` around any `<?= ?>` echo of DB text (already used in `index.php`).

8) When you are uncertain
- Ask for the desired behavior first (e.g., change to APIs vs server-rendered pages). State whether changes must maintain backward-compatible GET fallbacks (many links rely on them).

If anything here is unclear or you'd like more specifics (example: sample rows from the `produits` table, exact admin login flow, or the contents of `ajouter_panier.php`), tell me which part to inspect and I'll expand the instructions.
