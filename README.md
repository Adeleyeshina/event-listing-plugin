# Growth Hub Events

A WordPress plugin for managing events with automated WooCommerce ticketing, direct-to-checkout flows, dynamic color theming, QR code passes, and door check-in verification.

---

## Features

### Event Management (`gh_event`)
* Dedicated custom post type with structured meta boxes:
  * Event date (for chronological sorting)
  * Start and end time
  * Venue / location
  * Ticket price (supports free events)
  * Ticket capacity / stock limits
  * Custom branding color picker (`gh_event_color`)
  * Hero subtitle / summary

### Automated WooCommerce Product Synchronization
* Automatically creates and updates a hidden, virtual WooCommerce product for every published event.
* Synchronizes price, stock quantity/status, and featured image.
* When an event is trashed or deleted, its associated ticket product is automatically trashed or deleted.

### Streamlined Direct-to-Checkout Purchasing
* Direct checkout buttons bypass the standard WooCommerce cart page and redirect immediately to checkout.
* Automatically empties prior cart items when adding an event ticket to ensure clean single-event transactions.
* **Smart Checkout Simplification**: When the cart contains only event tickets:
  * Guest checkout is automatically enabled (no account required).
  * Account creation fields and prompts are bypassed.
  * Extraneous billing fields (address lines, company, city, state, postal code, order comments) are stripped.
  * Returning-customer login notices (e.g. CartFlows login blocks) are hidden.

### QR Code Ticketing & Mobile Check-in
* **Cryptographic Ticket Generation**: Every purchased ticket generates a unique, 24-character hexadecimal security token (`random_bytes(12)`).
* **Automatic Issuance**: Tickets are generated when an order transitions to `processing` or `completed`.
* **Automatic Invalidation**: Tickets are marked `void` if an order is `refunded`, `cancelled`, or `failed` (already checked-in tickets remain protected).
* **Email Delivery**: Automated HTML confirmation email sent via the WooCommerce mailer containing event details, venue, time, and one-click ticket pass links.
* **Buyer Ticket Pass (`/?gh_pass=TOKEN`)**:
  * Standalone, fast-loading, mobile-friendly page displaying event details and live QR code.
  * Out-of-the-box fallback displaying ticket number if JavaScript/QR fails to render.
  * Includes no-index and no-cache headers.
* **Staff Door Check-in (`/?gh_verify=TOKEN`)**:
  * Scanning the QR code directs staff to the verification screen.
  * Restricted to users with the `gh_check_in` capability or administrators.
  * Atomic MySQL update ensures tickets cannot be double-scanned or checked in simultaneously by multiple staff members.
  * Records exact check-in timestamp and staff user ID.
  * Date mismatch warning if the ticket is scanned on a date other than the event date.
* **Custom Staff Role**:
  * Registers a dedicated `gh_event_staff` role with check-in permissions.
  * Automatically grants `gh_check_in` capability to `administrator` and `shop_manager`.
* **Admin Attendee Management**:
  * Manage and review attendees at **Events > Tickets (Attendees)** (`gh_ticket` post type).
  * Filter attendee list by event.
  * View ticket code, buyer email, check-in status, and timestamp.
  * Manual status override meta box for administrative corrections.
* **Direct Post-Payment Redirect**:
  * Filters `woocommerce_get_return_url` to redirect event ticket buyers straight to their digital ticket pass upon checkout completion.

### Dynamic Branding & Theme Compatibility
* Per-event color picker controls the hero banner background, badges, and CTA button colors.
* Viewport breakout layout ensures edge-to-edge hero banners while matching standard 1180px boxed content widths without breaking parent theme headers, logos, or navigation.

---

## Installation

1. Copy or clone this plugin repository into your WordPress plugins directory:
   ```bash
   wp-content/plugins/growth-hub-events
   ```
2. Activate the plugin via **WP Admin > Plugins**.
3. Permalinks will flush automatically upon activation. Single events will be accessible under `/event/{event-slug}/`.

---

## Shortcode Reference

### 1. `[gh_single_event]`
Renders a complete, responsive single event sales layout with a full-width hero banner and floating ticket details card.

```text
[gh_single_event]
```

**Attributes:**
* `id` *(int)*: Post ID of the event (defaults to current post in the loop).
* `tag` *(string)*: Uppercase category badge text (default: `COMMUNITY EVENT, THE GROWTH HUB`).
* `tier` *(string)*: Ticket tier label (default: `GENERAL ADMISSION`).
* `button_label` *(string)*: CTA button label (default: `Buy Ticket, Get Instant QR Code`).
* `sold_out_label` *(string)*: Disabled button text when capacity is reached (default: `Sold Out`).

---

### 2. `[gh_events]`
Displays a responsive grid of upcoming published events, sorted chronologically by event date.

```text
[gh_events limit="12" columns="4"]
```

**Attributes:**
* `limit` *(int)*: Maximum number of events to display (default: `12`).
* `columns` *(int)*: Number of grid columns on desktop (1 to 6, default: `4`).
* `button` *(string)*: Card CTA button text (default: `Get Ticket`).
* `sold_out` *(string)*: Out-of-stock badge label (default: `Sold Out`).

---

### 3. `[gh_event]`
Outputs individual event meta fields for use inside custom layouts, builders, or post content.

```text
[gh_event field="date" format="jS F Y"]
```

**Available `field` values:**
* `title`: Event title.
* `subtitle`: Hero subtitle or summary.
* `date`: Event date formatted with PHP date formatting (e.g. `format="jS F Y"`).
* `time`: Event start time (e.g. `format="g:i A"`).
* `venue`: Location or venue text.
* `price`: Formatted WooCommerce currency price, or `Free`.
* `tickets_left`: Remaining inventory stock.
* `color`: Hex color value.
* `url`: Single event permalink.

---

### 4. `[gh_event_ticket]`
Outputs a standalone direct-to-checkout purchase button for a specific event.

```text
[gh_event_ticket label="Get Ticket" class="custom-button-class"]
```

---

## Ticketing & Verification Workflow

### 1. Buyer Purchase & Ticket Pass
1. Attendee selects **Buy Ticket** on the event page.
2. The user is redirected directly to WooCommerce checkout with streamlined guest fields (Name, Email, optional Phone).
3. Upon payment completion, tickets are automatically generated.
4. The buyer receives an automated email with a direct link to their ticket pass (`/?gh_pass=TOKEN`).
5. The pass displays event details, attendee name, reference number, and a scannable QR code.

### 2. Door Check-in for Staff
1. Event staff members log in to WordPress using their staff account (role: `gh_event_staff`).
2. Staff scans the attendee's QR code using any smartphone camera or QR reader app.
3. The scan opens `/?gh_verify=TOKEN`:
   * **Valid**: Displays green banner with event and attendee details, plus a one-click **Check in** button.
   * **Already Used**: Displays warning banner showing the exact time and staff member who previously admitted the attendee.
   * **Void**: Displays alert that the ticket has been refunded or cancelled.
   * **Date Warning**: Alerts staff if the ticket is for a date other than today.

### 3. Resending Ticket Emails
If an attendee loses their email or mistypes their address:
1. Open the order under **WooCommerce > Orders**.
2. Select **Resend event ticket email** from the **Order actions** dropdown.
3. Click **Update** to trigger an immediate resend.

---

## Elementor Integration

### Setting Up a Single Event Template
1. Navigate to **Templates > Theme Builder** in Elementor.
2. Create a new **Single Post** template and set the Display Condition to:
   ```text
   Include: Events > All
   ```
3. Add a **Shortcode** widget into the template container and insert:
   ```text
   [gh_single_event]
   ```
4. Save and publish. The shortcode handles the hero banner breakout, typography, colors, and responsive card sizing automatically.

### Upcoming Events Query in Elementor Loop Grid
To display upcoming events in an Elementor Loop Grid or Posts widget sorted chronologically, assign the following Custom Query ID:
```text
gh_upcoming_events
```

---

## Technical Specifications

* **WordPress Compatibility**: 5.8+
* **PHP Compatibility**: 7.4 to 8.3
* **Dependencies**: WooCommerce 6.0+
* **Database**: Uses standard WordPress post types and post meta (`gh_event`, `gh_ticket`).
* **Security**: Nonce validation on check-in submissions, capability checks (`gh_check_in`), prepared SQL queries, and 96-bit cryptographic tokens.

---

## Author

Developed for **The Growth Hub** by **Shina Adeleye**.
