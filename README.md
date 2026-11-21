# Growth Hub Events

A lightweight WordPress plugin for managing events with automated WooCommerce ticketing, direct-to-checkout flows, dynamic color theming, and Elementor integration.

---

## Features

* **Event Management (`gh_event`)**: Dedicated custom post type with meta fields for event date, start time, venue, ticket price, capacity, hero subtitle, and custom branding color.
* **Automated WooCommerce Ticketing**: Automatically creates and synchronizes a hidden virtual WooCommerce product for each published event. Stock quantities, pricing, and status stay in sync automatically.
* **Direct-to-Checkout Purchasing**: Ticket purchase buttons bypass standard cart redirects, empty stale cart items, and take attendees directly to the WooCommerce checkout page.
* **Dynamic Per-Event Branding**: Pick an accent color in WP Admin per event (`gh_event_color`) to dynamically brand the hero section, badges, and CTA buttons.
* **Elementor & Shortcode Support**: Reusable shortcodes for displaying individual field values, full-width responsive event sales pages, or multi-column upcoming event grids.
* **Theme-Agnostic Layout**: Full-width viewport breakout for single events designed to integrate smoothly with Astra, Elementor, and modern block themes without distorting site navigation or header containers.

---

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```bash
   wp-content/plugins/growth-hub-events
   ```
2. Activate the plugin via **WP Admin > Plugins**.
3. Permalinks will flush automatically on activation. Single events will be accessible under `/event/{event-slug}/`.

---

## Shortcode Reference

### 1. `[gh_single_event]`
Renders a complete, responsive single event sales page layout with a full-width hero banner and dark floating ticket card.

```text
[gh_single_event]
```

**Attributes:**
* `id` *(int)*: Post ID of the event (defaults to current post ID in the loop).
* `tag` *(string)*: Small uppercase category badge text (default: `COMMUNITY EVENT, THE GROWTH HUB`).
* `tier` *(string)*: Ticket tier name shown above the price (default: `GENERAL ADMISSION`).
* `button_label` *(string)*: CTA button label (default: `Buy Ticket, Get Instant QR Code`).
* `sold_out_label` *(string)*: Disabled button text when capacity is reached (default: `Sold Out`).

---

### 2. `[gh_events]`
Displays a responsive grid of upcoming published events, ordered chronologically by event date.

```text
[gh_events limit="12" columns="4"]
```

**Attributes:**
* `limit` *(int)*: Maximum number of events to display (default: `12`).
* `columns` *(int)*: Number of grid columns on desktop (1 to 6, default: `4`).
* `button` *(string)*: Button text on each card (default: `Get Ticket`).
* `sold_out` *(string)*: Text shown when an event is out of stock (default: `Sold Out`).

---

### 3. `[gh_event]`
Outputs individual event meta fields anywhere in post content or page builder templates.

```text
[gh_event field="date" format="jS F Y"]
```

**Available `field` values:**
* `title`: Event title.
* `subtitle`: Hero subtitle or excerpt.
* `date`: Event date formatted with PHP date strings (e.g. `format="jS F Y"`).
* `time`: Event start time (e.g. `format="g:i A"`).
* `venue`: Location / venue text.
* `price`: Formatted WooCommerce currency price, or `Free`.
* `tickets_left`: Remaining available stock quantity.
* `color`: Hex color code for the event.
* `url`: Permalink to the single event page.

---

### 4. `[gh_event_ticket]`
Outputs a standalone direct-to-checkout ticket purchase button.

```text
[gh_event_ticket label="Get Ticket" class="custom-btn-class"]
```

---

## Elementor Integration

### Setting Up a Single Event Template
1. Go to **Templates > Theme Builder** in Elementor.
2. Create a new **Single Post** template and set the Display Condition to:
   ```text
   Include: Events > All
   ```
3. Add a **Shortcode** widget into the template and enter:
   ```text
   [gh_single_event]
   ```
4. Save and publish. The layout automatically handles full-width banner breakout and adapts to 1180px boxed layouts.

### Elementor Loop Grid
To order an Elementor Loop Grid or Posts widget by upcoming event date, use the custom query ID:
```text
gh_upcoming_events
```

---

## Requirements

* **WordPress**: 5.8 or higher
* **PHP**: 7.4 or higher
* **WooCommerce**: 6.0 or higher (required for ticket product sync and checkout)

---

## Author

Developed for **The Growth Hub** by **Shina Adeleye**.
