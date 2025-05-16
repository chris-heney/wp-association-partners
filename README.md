# Association Partners WordPress Plugin

**Version:** 1.0.0
**Author:** Chris Heney
**License:** GPLv3 or later

---

## Description

The Association Partners plugin allows you to:

* Easily integrate Google Tag Manager into your theme’s `<head>` and `<body>` sections.
* Define multiple **AdZones** (name, slug, CSS classes, dimensions, FPO URL, custom ad source code).
* Display ads using a `[AssociationAd]` shortcode with flexible overrides.
* Use an **Elementor** widget to visually place AdZones within your page builder layouts.
* Toggle on/off AdZone accessors (Shortcode, Elementor) from a simple Settings page.

---

## Features

1. **GTM Integration**

   * Injects GTM script in `<head>` and `<noscript>` iframe after `<body>`.
   * Configure your GTM container ID on the settings screen.

2. **AdZones Management**

   * Create unlimited AdZones with:

     * **Name** & **Slug** (alpha-numeric, lowercase, dash)
     * **CSS Classes** for custom styling
     * **Width** & **Height** (px)
     * **FPO URL** for placeholder images
     * **Raw JS/HTML** ad source code
   * Add, edit, or remove AdZones via repeater interface in Settings.

3. **Shortcode Accessor**

   * Use `[AssociationAd slug="my-zone"]` to output the ad.
   * Override settings inline:

     ```html
     [AssociationAd slug="header" name="Alt Header" fpo="https://via.placeholder.com/728x90" width="728" height="90"]
       <script>/* custom JS ad code */</script>
     [/AssociationAd]
     ```

4. **Elementor Widget**

   * Drag & drop **Association AdZone** widget into any Elementor layout.
   * Select a defined AdZone or override individual properties with widget controls.

5. **Extensibility**

   * Hooks and filters available under `association_partners_` prefix.
   * Add custom Accessors (e.g., Beaver Builder) via `do_action( 'association_partners_init', $plugin )` and `apply_filters()`.

---

## Installation

1. Download the plugin ZIP and upload it to your WordPress site under **Plugins → Add New → Upload Plugin**.
2. Activate **Association Partners**.
3. Navigate to **Settings → Association Partners** and configure:

   * Google Tag Manager ID
   * Ad Impression and Click event names
   * Enable/Disable Shortcode & Elementor AdZones
   * Define your AdZones in the repeater.

---

## Usage

### Shortcode

Place ads anywhere in your content or templates:

```html
[AssociationAd slug="sidebar"]
```

Override specific properties:

```html
[AssociationAd slug="footer" width="300" height="250" fpo="https://via.placeholder.com/300x250"]
[/AssociationAd]
```

### Elementor Widget

1. Edit a page with Elementor.
2. Search for **Association AdZone** in the **Basic** category.
3. Drag it into your layout, select a zone, or override on the fly.

---

## Developer Guide

* **Plugin bootstrap:** `association-partners.php` initializes `AssociationPartners\Plugin`.
* **Settings storage:** All options stored in `association_partners_settings` option.
* **Hooks:**

  * `association_partners_init` fires on plugin load.
  * `association_partners_gtm_id` filter to override GTM ID.
* **Classes:**

  * `AssociationPartners\Plugin` – Core class.
  * `AssociationPartners\Elementor\AdZone_Widget` – Elementor adapter.

---

## Changelog

### 1.0.0

* Initial release with GTM injection, AdZones, Shortcode & Elementor support.

---

## License

This plugin is licensed under the GPLv2 or later. See [LICENSE](LICENSE) for details.
