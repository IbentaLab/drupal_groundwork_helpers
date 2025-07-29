
# 🧩 Groundwork Helpers – Companion Module for the Groundwork Theme Framework

**Groundwork Helpers** is a modular, no-code-first companion module for the [Groundwork Theme Framework](https://github.com/IbentaLab/drupal_groundwork "null"). It supercharges your Drupal site by bringing design-system tools directly into the UI—accessible to developers, editors, and site builders alike.

> 📦 Install once. Enable only what you need.

## ✨ Features (Modular Submodules)

| **Submodule**             | **Status** | **Description**                                                                                                                                                                           |
| ------------------------------- | ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Block Styles UI**       | ✅ Implemented   | A visual UI for applying[Block Style Components (BSCs)](https://ibentalab.github.io/drupal_groundwork/docs/block-style-components/ "null")to any block. Features a categorized, searchable interface. |
| **Groundwork Components** | ✅ Implemented   | Provides configurable blocks based on the theme's Single Directory Components (SDCs), such as an Accordion and Dropdown.                                                                        |
| **Gwicons UI**            | 📝 Planned       | A UI-based SVG icon management and picker for Layout Builder, WYSIWYG, or blocks.                                                                                                               |
| **Layout Patterns**       | 📝 Planned       | A library of reusable layout recipes and presets for Layout Builder.                                                                                                                            |
| **DevTools**              | 📝 Planned       | Developer and accessibility audit tools (e.g., markup validation, color contrast checker).                                                                                                      |

## 🧰 Requirements

* Drupal 11+
* [Groundwork Theme](https://github.com/IbentaLab/drupal_groundwork "null") installed and enabled.

## 🚀 Installation

1. Install the module using Composer:
   ```
   composer require drupal/groundwork_helpers

   ```
2. Enable the modules you need using Drush or the Drupal UI (`/admin/modules`):
   ```
   # Enable the Block Styles UI and the Component blocks
   drush en groundwork_helpers_block_styles groundwork_helpers_components -y

   ```

You can now start using the features in your Drupal site. For example, the **Block Styles UI** will be available in the configuration form for any block, and the **Groundwork Accordion** and **Groundwork Dropdown** blocks will be available to place in the Block Layout UI or Layout Builder.
