# MCX Elementor Editor Optimizer v1.0.1

**UI/UX refresh.** A product of [Miracuves.com](https://miracuves.com) — powered by [Miracuves](https://miracuves.com).

---

## What's new in v1.0.1

This release focuses on a **modern, consistent design** for the plugin’s admin and launch screens.

### Launch screen

- **Card-based layout** — Session type, Edit mode options, and “Remember choice” are grouped in clear cards with improved spacing and hierarchy.
- **Clearer options** — Radio and checkbox options use tappable blocks with short descriptions; “Editor Firewall” is marked as recommended.
- **Primary CTA** — “Launch Elementor Editor” is a prominent button; footer link styling is updated.

### Settings page

- **Unified card design** — Addons summary, Widget Usage Analytics, Unused widgets section, main settings form, and Performance Information use the same card style with headers and subtle shadows.
- **Analytics dashboard** — Used / Unused / Potential Speed Gain cards are restyled with distinct colors and typography.
- **Form and lists** — Disable Widgets list uses row styling (used vs unused); Editor Firewall plugin list and form inputs use the new design tokens.
- **Design system** — Shared tokens for colors, radius, and shadows; responsive behavior at 782px and 480px.

### Under the hood

- Admin CSS is enqueued on **both** the settings page and the launch screen so the launch screen uses the new styles.
- Version set to **1.0.1** in plugin header, constant, `package.json`, and install-test.

---

## Installation

**Option 1 — Clone from GitHub**
```bash
git clone https://github.com/miracuves/MCX-Elementor-Editor-Optimizer.git wp-content/plugins/MCX-Elementor-Editor-Optimizer
```
Then in WordPress: **Plugins** → activate **MCX Elementor Editor Optimizer**.

**Option 2 — Download zip**  
Download [Source code (zip)](https://github.com/miracuves/MCX-Elementor-Editor-Optimizer/archive/refs/tags/v1.0.1.zip), extract into `wp-content/plugins/`, and activate the plugin.

**Editor Firewall (optional)**  
To use Editor Firewall, copy `eeo-firewall.php` from the plugin folder to `wp-content/mu-plugins/`.

---

## Requirements

- **WordPress** 5.0 or higher  
- **PHP** 7.4 or higher  
- **Elementor** 3.0 or higher (Free or Pro)

---

## Links

- **Repository:** https://github.com/miracuves/MCX-Elementor-Editor-Optimizer  
- **Documentation:** see [README](https://github.com/miracuves/MCX-Elementor-Editor-Optimizer#readme) in the repo  
- **Miracuves.com:** https://miracuves.com
