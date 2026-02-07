# MCX Elementor Editor Optimizer v1.0.0

**First stable release.** A product of [Miracuves.com](https://miracuves.com) — powered by [Miracuves](https://miracuves.com).

---

## What it does

MCX Elementor Editor Optimizer speeds up the **Elementor editor** by letting you choose how heavy each session is: **Build Mode** loads everything (all widgets and plugins), **Edit Mode** loads a lighter editor with optional **Editor Firewall** (fewer plugins) and **Widget Diet** (fewer widgets). All of this applies **only inside the editor**—your live site and frontend are never changed.

---

## Features in this release

| Feature | Description |
|--------|-------------|
| **Build Mode** | Full editor: all widgets and plugins load. Use when building new layouts or when you need every addon. |
| **Edit Mode** | Lighter editor for quick content edits. You can enable Editor Firewall, Snapshot Cache (placeholder), and Widget Diet. |
| **Editor Firewall** | For the current editor session only, selected plugins are not loaded. Requires copying `eeo-firewall.php` to `wp-content/mu-plugins/`. Elementor, Elementor Pro, and this plugin are never disabled. |
| **Widget Diet** | For the current session only, selected widgets are disabled in the editor. Core widgets (heading, image, button, section, etc.) are always available. |
| **Launch screen** | When you click “Edit with Elementor,” you can choose Build or Edit and which options to use. You can **remember** your choice for the current page or globally. |
| **Widget usage analytics** | Opt-in scan to see which widgets are used across your site, so you can safely add unused ones to Widget Diet. |
| **Frontend-safe** | No hooks or filters run on normal frontend page views. Headers, menus, and frontend behavior stay unchanged. |

---

## Installation

**Option 1 — Clone from GitHub**
```bash
git clone https://github.com/miracuves/MCX-Elementor-Editor-Optimizer.git wp-content/plugins/MCX-Elementor-Editor-Optimizer
```
Then in WordPress: **Plugins** → activate **MCX Elementor Editor Optimizer**.

**Option 2 — Download zip**  
Download [Source code (zip)](https://github.com/miracuves/MCX-Elementor-Editor-Optimizer/archive/refs/tags/v1.0.0.zip), extract into `wp-content/plugins/`, and activate the plugin.

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
