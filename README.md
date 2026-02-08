# MCX Elementor Editor Optimizer

[![GitHub](https://img.shields.io/badge/GitHub-miracuves%2FMCX--Elementor--Editor--Optimizer-181717?logo=github)](https://github.com/miracuves/MCX-Elementor-Editor-Optimizer)

**A product of [Miracuves.com](https://miracuves.com) — powered by [Miracuves](https://miracuves.com).**

Enterprise-grade WordPress plugin that speeds up the Elementor editor by loading only what you need for each session: choose **Build Mode** (full experience) or **Edit Mode** (fewer widgets and optional plugin stripping) so the editor stays fast without breaking your site or your frontend.

---

## Table of Contents

- [Purpose](#purpose)
- [Screenshots](#screenshots)
- [How It Works](#how-it-works)
- [Key Concepts](#key-concepts)
- [Installation](#installation)
- [Launch Flow (Step-by-Step)](#launch-flow-step-by-step)
- [Build Mode vs Edit Mode](#build-mode-vs-edit-mode)
- [Editor Firewall (Mu-Plugin)](#editor-firewall-mu-plugin)
- [Widget Diet](#widget-diet)
- [Settings Reference](#settings-reference)
- [Widget Usage Analytics](#widget-usage-analytics)
- [Remembering Your Choice](#remembering-your-choice)
- [Requirements](#requirements)
- [Troubleshooting](#troubleshooting)
- [License & Credits](#license--credits)

---

## Purpose

**Problem:** Elementor’s editor can be slow on large sites: many plugins and hundreds of widgets (core + addons) load on every open, even when you only need to tweak text or images.

**Goal:**  
- **Never change your frontend.** All optimizations apply only inside the Elementor editor.  
- **Let you choose per session:** full experience when building, lighter load when doing quick edits.  
- **Reduce editor load time and memory** by disabling unused widgets and optionally disabling selected plugins only in the editor.

**Audience:** Site owners, agencies, and developers who use Elementor and want faster editor loading without risking layout or frontend breakage.

---

## Screenshots

| Launch screen – Build / Edit mode | Settings – Widgets & Firewall |
|-----------------------------------|--------------------------------|
| ![MCX Elementor Launch Modes](Screenshots/Screenshot%202026-02-08%20at%209.17.47%E2%80%AFAM.png) | ![Settings – Disable Widgets & Editor Firewall](Screenshots/Screenshot%202026-02-08%20at%209.17.57%E2%80%AFAM.png) |

| Settings & options | Widget analytics | Performance info |
|--------------------|-------------------|-------------------|
| ![Settings options](Screenshots/Screenshot%202026-02-08%20at%209.18.13%E2%80%AFAM.png) | ![Widget usage analytics](Screenshots/Screenshot%202026-02-08%20at%209.18.23%E2%80%AFAM.png) | ![Performance information](Screenshots/Screenshot%202026-02-08%20at%209.18.51%E2%80%AFAM.png) |

---

## How It Works

1. **Intercept “Edit with Elementor”**  
   When you click “Edit with Elementor” in wp-admin, the plugin can redirect you to a **Launch** screen (unless you have a remembered choice).

2. **Launch screen**  
   You choose:
   - **Session type:** **Build Mode** (load everything) or **Edit Mode** (apply optimizations).
   - **Edit Mode options:** Editor Firewall, Snapshot Cache, Widget Diet (each explained below).
   - **Remember:** per-page, or globally for all pages.

3. **Editor loads with your choice**  
   A short-lived token carries your selection into the editor request. Optimizations run only for that request and only in the editor context.

4. **Frontend is untouched**  
   No hooks or filters from this plugin run on normal frontend page views. Your header, menus, and frontend behavior stay exactly as they are.

---

## Key Concepts

| Concept | Purpose |
|--------|---------|
| **Build Mode** | Load all widgets and all plugins in the editor. Use when creating new sections, using addon widgets, or when something is missing. |
| **Edit Mode** | Apply session-only optimizations: optional Editor Firewall, Snapshot Cache (placeholder), and Widget Diet. Use for quick content edits. |
| **Editor Firewall** | For the current editor session only, do not load plugins you selected in settings. Requires the mu-plugin. Elementor, Elementor Pro, and this optimizer are never disabled. |
| **Widget Diet** | For the current editor session only, unregister the widgets you marked as “disable” in settings. Core/essential widgets are never disabled. |
| **Snapshot Cache** | Reserved for future use (e.g. reusing precomputed Elementor data). Currently a no-op. |
| **Remembered choice** | Your last Build/Edit (and sub-options) can be stored per page or globally so you don’t see the Launch screen every time. |

---

## Installation

1. **Install the plugin**
   - **From GitHub (clone):**  
     `git clone https://github.com/miracuves/MCX-Elementor-Editor-Optimizer.git wp-content/plugins/MCX-Elementor-Editor-Optimizer`  
     Then in WordPress go to **Plugins** and activate **MCX Elementor Editor Optimizer**.
   - **Or:** Upload the plugin folder to `wp-content/plugins/` (e.g. `EEOptimizer` or `MCX-Elementor-Editor-Optimizer`), or install via WordPress admin → Plugins → Add New → Upload.
   - Activate **MCX Elementor Editor Optimizer**.

2. **Configure (optional but recommended)**
   - Go to **Settings → MCX Elementor Editor Optimizer**.
   - Set **Editor Memory Limit** if needed (e.g. `512M`).
   - If you will use **Editor Firewall**, choose which plugins to disable in the editor (see [Editor Firewall](#editor-firewall-mu-plugin)).
   - If you will use **Widget Diet**, run the widget scan and select which widgets to disable (see [Widget Diet](#widget-diet)).

3. **Editor Firewall (optional)**
   - To use Editor Firewall, copy `eeo-firewall.php` from the plugin directory to `wp-content/mu-plugins/`.
   - Create `wp-content/mu-plugins/` if it doesn’t exist. The main plugin does not copy the file automatically.

4. **Open the editor**
   - Edit any page with Elementor. The first time (or when no choice is remembered), you’ll see **MCX Elementor Launch Modes**. Choose **Build Mode** or **Edit Mode** and click **Launch Elementor Editor**.

---

## Launch Flow (Step-by-Step)

1. In wp-admin, open a page or template and click **“Edit with Elementor”**.
2. **If you have a remembered choice** (per-page or global): the editor opens directly with that choice. No Launch screen.
3. **If you don’t:** you are redirected to **MCX Elementor Launch Modes**.
4. **Session type**
   - **Build Mode** – Load everything. Best for building new layouts or when you need all widgets/plugins.
   - **Edit Mode (faster)** – Apply the options you check below.
5. **Edit Mode options** (only apply when Edit Mode is selected)
   - **Editor Firewall** – In this session, do not load the plugins you selected in Settings (requires mu-plugin).
   - **Snapshot Cache** – Reserved for future use.
   - **Widget Diet** – In this session, do not load the widgets you selected in Settings.
6. **Remember choice**
   - **Remember for this page** – Use this combination whenever you open the editor for this post/page.
   - **Remember globally** – Use this combination by default for all pages (until you change it).
7. Click **Launch Elementor Editor**. You are sent back to the editor URL with a token; the plugin applies your choices for that request only.

---

## Build Mode vs Edit Mode

- **Build Mode**
  - **Purpose:** Full editor: all plugins and all widgets load.
  - **When to use:** New pages, new sections, when you need a widget or addon that might be disabled in Edit Mode, or when something looks missing.
  - **Effect:** No Firewall, no Diet, no Snapshot. Only the configured editor memory limit (if any) is applied.

- **Edit Mode**
  - **Purpose:** Lighter editor for quick edits.
  - **When to use:** Changing text, images, or small layout tweaks when you know you don’t need disabled widgets or plugins.
  - **Effect:** You can enable Editor Firewall (fewer plugins), Widget Diet (fewer widgets), and Snapshot Cache (future). Memory limit still applies.

**Tactic:** Use **Build Mode** by default or for pages you’re still designing; switch to **Edit Mode** (and optionally “Remember globally”) once the page is stable and you mainly do content edits.

---

## Editor Firewall (Mu-Plugin)

**Purpose:** For editor sessions where Edit Mode has “Editor Firewall” enabled, selected plugins are not loaded at all. That reduces PHP work, hooks, and assets only in the editor, not on the frontend.

**How it works:**
- A must-use plugin (`eeo-firewall.php`) runs before normal plugins.
- On Elementor editor requests (with token or remembered modes including `firewall`), it filters `option_active_plugins` and removes the plugins you chose in Settings.
- Elementor core, Elementor Pro, and MCX Elementor Editor Optimizer are always kept.

**Installation:**
1. Copy `eeo-firewall.php` from the plugin folder (e.g. `wp-content/plugins/EEOptimizer/` or `elementor-editor-optimizer/`) to `wp-content/mu-plugins/`.
2. Create `wp-content/mu-plugins/` if needed.

**Configuration:**
- **Settings → MCX Elementor Editor Optimizer** → **Editor Firewall**.
- You see a list of active plugins (except Elementor, Elementor Pro, and this plugin). Check the ones you want **disabled in the editor** when Firewall is active.
- Save. The list is stored in `elementor_editor_optimizer_settings['firewall_plugins']`.

**Tactic:** Disable heavy or editor-irrelevant plugins (e.g. forms, SEO, backup) in the editor only; keep anything the editor or your theme relies on.

---

## Widget Diet

**Purpose:** In editor sessions where Edit Mode has “Widget Diet” enabled, the widgets you selected in Settings are unregistered so they don’t load. That cuts down editor JS/CSS and initialization time.

**How it works:**
- Only runs on Elementor editor requests and only when “Widget Diet” is in the active launch modes.
- On `elementor/widgets/widgets_registered`, the plugin unregisters each widget ID in `disable_widgets` (from settings).
- Core/essential widgets (e.g. heading, image, text-editor, button, section, column, nav-menu) are never unregistered.

**Configuration:**
- **Settings → MCX Elementor Editor Optimizer** → **Disable Widgets**.
- Use **Scan Widget Usage** (if you enabled widget tracking) to see which widgets are used vs unused.
- Check the widgets you want disabled in the editor. Save.

**Tactic:** Disable addon widgets you don’t use on that site first; then consider unused core widgets. Keep Build Mode for pages that still need those widgets.

---

## Settings Reference

Every option is stored under **Settings → MCX Elementor Editor Optimizer**. Purpose of each:

| Setting | Purpose |
|--------|---------|
| **Disable Widgets** | List of widget IDs to unregister in the editor when **Edit Mode** + **Widget Diet** is active. Core widgets cannot be disabled. |
| **Editor Firewall** | List of plugin basenames to exclude from loading when **Edit Mode** + **Editor Firewall** is active (requires mu-plugin). |
| **Editor Memory Limit** | PHP `memory_limit` set only in the Elementor editor (e.g. `512M`, `1G`). Helps avoid white screens on large pages. |
| **Optimize Fonts** | Reserved; currently no effect. |
| **Optimize Assets** | Reserved; frontend optimizations are intentionally not applied to avoid breaking the site. |
| **WordPress Optimizations** (Disable emojis, Remove jQuery Migrate) | Reserved; not applied site-wide by this plugin. |
| **Debug Mode** | When enabled, writes log lines prefixed “MCX Elementor Editor Optimizer:” to the PHP error log (e.g. launch mode, widget count). Use for troubleshooting. |
| **Enable widget tracking** | Opt-in. When “yes”, the plugin records which widgets are used when saving from the editor and on frontend in admin context. Used to power “Scan Widget Usage” and usage analytics. |

---

## Widget Usage Analytics

**Purpose:** See which widgets are actually used across your site so you can confidently add them to “Disable Widgets” for Edit Mode.

**How to use:**
1. In Settings, enable **Enable widget tracking** (opt-in) and save.
2. Run **Scan Widget Usage** (or equivalent) to scan posts/pages for Elementor data and aggregate widget IDs.
3. The dashboard shows used vs unused widgets; you can select unused ones and add them to **Disable Widgets** for Widget Diet.

**Data:** Stored in options such as `eeo_widget_usage_data`, `eeo_widget_usage_log`, `eeo_last_full_scan`. Reset options are available in the UI for a full reset.

---

## Remembering Your Choice

**Per-page:** On the Launch screen, check **Remember for this page** and submit. The chosen mode (and sub-options) are stored in post meta `_eeo_launch_modes` for that post. Next time you open the editor for that page, the Launch screen is skipped and the same combination is used.

**Global:** Check **Remember globally** and submit. The choice is stored in option `eeo_global_launch_modes`. It is used for any editor open unless the current page has its own per-page memory.

**Priority:** Per-page → user meta `eeo_launch_modes` → global option. First non-empty wins when loading a remembered choice.

---

## Requirements

- **WordPress:** 5.0+
- **PHP:** 7.4+
- **Elementor:** 3.0+ (Free or Pro)
- **Browser:** JavaScript enabled for the settings/analytics UI

---

## Troubleshooting

- **Editor doesn’t open / white screen / JS errors**  
  - Use **Build Mode** (and “Remember globally” if you want).  
  - Deactivate MCX Elementor Editor Optimizer temporarily; if the editor works, the issue is likely a combination of Edit Mode + Firewall or Diet. Re-enable and try without Firewall, then without Diet, to isolate.

- **Widget or feature missing in editor**  
  - You’re likely in Edit Mode with Widget Diet and that widget in “Disable Widgets”. Open the same page in **Build Mode** or remove that widget from the disable list.

- **Firewall doesn’t seem to apply**  
  - Confirm `eeo-firewall.php` is in `wp-content/mu-plugins/`.  
  - Confirm in Launch you chose Edit Mode and checked Editor Firewall, and that you have at least one plugin selected under **Editor Firewall** in Settings.

- **Launch screen every time**  
  - Use “Remember for this page” or “Remember globally” when submitting the Launch form so the next open skips the screen.

- **Debug**  
  - Enable **Debug Mode** in Settings and check your PHP error log for lines starting with `MCX Elementor Editor Optimizer:`.

---

## License & Credits

- **License:** GPL v2 or later.
- **Repository:** [github.com/miracuves/MCX-Elementor-Editor-Optimizer](https://github.com/miracuves/MCX-Elementor-Editor-Optimizer)
- **Branding:** MCX Elementor Editor Optimizer — a product of [Miracuves.com](https://miracuves.com), powered by [Miracuves](https://miracuves.com).

This plugin only alters behavior inside the Elementor editor session. Always test Build/Edit and Firewall/Diet choices on a staging or test site before relying on them in production.
