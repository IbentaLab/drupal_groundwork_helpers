
# Groundwork Helpers DevTools

**The essential companion for anyone building with the [Groundwork Theme Framework](https://www.drupal.org/project/groundwork) on Drupal 11+.**
Unlock a new level of speed, insight, and flexibility in your frontend workflow—right from your live site.

---

## What is Groundwork Helpers DevTools?

Groundwork Helpers DevTools is a floating, always-accessible toolbox designed specifically for the Groundwork Theme Framework.
No more chasing settings, running Drush, or wondering why your layout is off.
With a single glance and a click, you and your team can supercharge your theming, debugging, and QA—without ever leaving the page.

Whether you're a developer, designer, or site builder, this module puts the most powerful tools exactly where you need them.

---

## Why You'll Love It

- **Purpose-built for Groundwork:** Seamless integration means every toggle and overlay is tailored to the Groundwork grid, regions, and breakpoints.
- **Work faster:** Instantly clear caches, preview breakpoints, debug layouts, or swap themes—right on the frontend, no context switching.
- **Empower your team:** With granular permissions, give the right people the right tools—designers, themers, builders, and site admins.
- **Zero bloat, zero risk:** Only loads for those with access; invisible and inert for everyone else.

---

## Features

The DevTools bar appears **centered at the bottom** of your site—bold and discoverable when expanded, out of your way when minimized (minimized button docks to the bottom left).
Every feature can be enabled or restricted by role, giving you unrivaled control over your site’s developer experience.

### 🚀 1. Theme Toggle

Switch between light/dark or any registered Groundwork themes at runtime.
**Perfect for visual QA and accessibility checks.**
**Permission:** `toggle theme`

### 🛠️ 2. Twig Developer Mode Toggle

Turn on/off Twig debugging and template suggestions in a single click.
**No need to edit settings files or clear caches manually.**
**Permission:** `toggle twig developer mode`

### ⚡ 3. Clear All Caches

One click, all caches—gone.
**No more command line, no more admin pages.**
**Permission:** `clear all caches`

### 🧩 4. Show Region/Block/Component Outlines

Instantly overlay color-coded outlines and labels for all Groundwork theme regions, blocks, and components.
**See your site’s true structure at a glance.**
**Permission:** `show region outlines`

### 📐 5. Grid Overlay

Activate a pixel-perfect overlay of the Groundwork grid for your theme.
**Debug alignment, spacing, and responsiveness with precision.**
**Permission:** `show grid overlay`

### 📱 6. Breakpoints Preview

Preview your site at any Groundwork-defined responsive breakpoint.
**No more resizing your browser—just click and check.**
**Permission:** `breakpoints preview`

### 🟦 7. CSS Debug Mode

Apply vivid debug styles to page elements—outlines, backgrounds, and more.
**Spot CSS bugs, hidden overflows, or layout quirks instantly.**
**Permission:** `css debug mode`

---

## Total Control: Per-Feature Permissions

Every button in the bar is protected by a dedicated Drupal permission.
**Enable only the tools each team member needs.**

| Permission                         | Controls                                  |
| ---------------------------------- | ----------------------------------------- |
| `access groundwork devtools bar` | See the DevTools bar at all               |
| `toggle theme`                   | Access the theme toggle button            |
| `toggle twig developer mode`     | Access the Twig developer mode toggle     |
| `clear all caches`               | Use the "Clear all caches" button         |
| `show region outlines`           | Use the outlines/component overlay toggle |
| `show grid overlay`              | Use the grid overlay toggle               |
| `breakpoints preview`            | Use breakpoint preview tools              |
| `css debug mode`                 | Use CSS debug mode toggle                 |

**How to use:**
Go to **People > Roles > [Role] > Edit** and assign permissions as needed.
If a user has `access groundwork devtools bar`, they see the bar—otherwise, it’s invisible.

---

## Smart Visibility & Configuration

- **Bar Position:**
  - **Expanded:** Horizontally centered at the bottom of the screen for maximum visibility and ease of access.
  - **Minimized:** Shrinks to a compact button, tucked away in the bottom left corner—out of the way, but always ready.
- **State is remembered per user** via localStorage—no need to keep clicking your preferred mode.
- **Admin config page:**
  - Choose which site paths or content types show the bar (wildcards supported).
  - Set the default state (expanded or minimized) for all users.

---

## How to Use

1. **Install and enable the module on your Drupal 11+ site (with Groundwork Theme Framework active).**
2. **Assign permissions** to each role as desired for every DevTools toggle.
3. (Optional) **Configure bar visibility** and default state on the module’s settings page.
4. **Log in as a user with access:**
   - The DevTools bar appears at the bottom center.
   - Use the tools you need; minimize the bar for a distraction-free view.
   - Your preference is always remembered.

---

## Security & Best Practices

- **Only trusted users should get powerful features** (cache clear, Twig debug, theme toggles).
- **Zero frontend impact** for end users or roles without permission.
- **No cookies are touched—only DevTools localStorage is used to remember bar state.**

---

## Built for Growth

Want more tools? The code is architected for easy extension.
See inline code comments and documentation to add your own toggles or overlays.

---

## Requirements

- [Groundwork Theme Framework](https://www.drupal.org/project/groundwork)
- Drupal 11 or higher

---

## License

GNU GPL 2.0

---

**Ready to experience the smoothest, fastest, most developer-friendly theming in Drupal?**
Install Groundwork Helpers DevTools and put the power of Groundwork in your hands—wherever you are, whenever you need it.
