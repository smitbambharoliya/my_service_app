# ServiceHub Futuristic Redesign Prompt

You are an expert UI/UX designer and Symfony/Twig frontend engineer.

Redesign the full frontend of this project, `ServiceHub`, into a futuristic 2026-style service marketplace portal. The app lets customers discover and book services, providers manage services/bookings/earnings, and admins manage users, providers, services, categories, bookings, revenue, audit logs, and featured services.

## Tech Stack

- Symfony 7.4
- Twig templates
- Bootstrap 5 currently used
- Font Awesome currently used
- Chart.js used in dashboards
- Three.js used on the homepage
- CSS lives mainly in `assets/styles/app.css` plus inline Twig styles
- Keep Symfony AssetMapper/importmap compatibility

## Critical Constraints

- Do not change backend business logic, routes, controllers, entities, forms, security, CSRF tokens, Twig variables, database logic, or authorization checks.
- Preserve all `path()` route calls.
- Preserve all `form_start`, `form_widget`, `form_errors`, `form_rest`, and `form_end` logic.
- Preserve login, register, OTP, booking, provider, admin, notification, chat, billing, review, and payment flows.
- Preserve role-based navigation for customer, provider, and admin.
- Do not introduce a new frontend framework.
- Do not remove existing user actions.

## Main Files To Redesign

- `templates/base.html.twig`
- `templates/dashboard_base.html.twig`
- `templates/admin/admin_base.html.twig`
- `templates/home/index.html.twig`
- `templates/service/*.html.twig`
- `templates/booking/*.html.twig`
- `templates/dashboard/*.html.twig`
- `templates/billing/*.html.twig`
- `templates/security/login.html.twig`
- `templates/registration/*.html.twig`
- `templates/message/*.html.twig`
- `templates/notification/*.html.twig`
- `templates/profile/*.html.twig`
- `templates/admin/*.html.twig`
- `templates/partials/*.html.twig`
- `assets/styles/app.css`

## Design Direction

Make the app feel less like a normal website and more like a futuristic service operating portal.

Use:

- Bento Grid layouts for dashboards and home sections.
- Spatial UI: cards and panels should feel layered, floating, and dimensional.
- Real Glassmorphism with strong blur, translucent borders, ambient shadows, and subtle depth.
- Neomorphic soft surfaces where appropriate for controls and metric blocks.
- Micro-interactions on buttons, service cards, sidebar links, filters, and dashboard widgets.
- Scroll reveal animations so panels feel like they rise into view in 3D.
- Subtle teal/cyan neon glow for active states, CTAs, live status, and tracking.
- Clean futuristic typography using `Plus Jakarta Sans` or similar.

Avoid:

- Old Bootstrap-looking plain cards.
- Overused flat gradients.
- Admin pages that look like a generic template.
- Marketing-only hero sections that do not help users act.
- Excessively confusing copy. Keep labels clear and useful.

## CSS System Requirements

Create or clean a shared design system in `assets/styles/app.css`.

Add and use tokens similar to:

```css
:root {
    --neon-glow: 0 0 15px rgba(0, 255, 213, 0.4);
    --glass-blur: blur(25px);
    --font-cyber: 'Plus Jakarta Sans', sans-serif;
    --transition-warp: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
```

Also define consistent tokens for:

- background layers
- glass surfaces
- borders
- cards
- buttons
- inputs
- tables/data cards
- badges
- sidebars
- charts
- empty states
- focus states
- mobile spacing

## Specific UI Requirements

### Global Layout

- Redesign the public navbar into a futuristic floating/sticky header.
- Add subtle depth, active nav glow, compact icons, and mobile-friendly behavior.
- Flash messages should use the same glass/spatial design system.
- Keep layout accessible, readable, and responsive.

### Home Page

Update `templates/home/index.html.twig`.

- Keep the Three.js idea, but make the homepage more dynamic and immersive.
- Redesign the AI discovery/search section as a "Holographic Window".
- Add a Border Beam effect using `border-image`, pseudo-elements, or animated gradient borders.
- Service discovery should be clear: users must quickly understand how to search and book.
- Highlight categories, trending services, trust stats, and call-to-action buttons.
- Buttons should expand slightly on hover and show teal glow behind them.

### Bento Dashboard Layouts

Update customer and provider dashboards.

- Convert KPI widgets into Bento Grid layouts with mixed card sizes.
- Important metrics should get larger cards.
- Smaller insights/actions should sit in compact cards.
- Use layered glass cards, ambient glow, live status badges, and animated counters where reasonable.
- Charts should sit inside spatial panels, not plain Bootstrap cards.

### Admin Area

Update `templates/admin/admin_base.html.twig` and admin pages.

- Remove the old `Crowz` visual identity and replace it with ServiceHub Admin.
- Make the sidebar floating with rounded corners and spacing from the viewport edges.
- Use a dense futuristic admin shell that still feels professional and easy to scan.
- Replace plain horizontal tables with responsive "Data Cards" where appropriate.
- For large admin lists, either keep accessible tables but visually modernize them heavily, or provide card-style rows on mobile.
- Use status chips, avatars, compact action buttons, and search/filter surfaces.

### Navigation And Breadcrumbs

- Make sidebars float instead of being glued to the body.
- Use rounded corners, translucent surfaces, and active glow states.
- Modernize breadcrumbs. Prefer icon-led breadcrumbs instead of long text chains.
- Keep navigation clear for customer/provider/admin roles.

### Tracking Page

Update `templates/booking/track.html.twig`.

- Add a radar pulse effect around the live location marker.
- Use animated status cards for "on the way", ETA, provider details, and booking progress.
- The tracking view should feel live, not static.

### Buttons And Micro-Interactions

For `.btn-luxury` and primary actions:

- On hover, slightly expand the button.
- Add teal/cyan glow behind or around it.
- Add smooth icon movement.
- Keep transitions polished and not distracting.

For cards:

- Slight 3D lift on hover.
- Soft neon edge or beam on active/featured cards.
- Avoid layout shift.

### Scroll Reveal

- Use Intersection Observer, CSS `view-timeline`, or lightweight CSS/JS already suitable for this project.
- Elements should fade, rise, and slightly rotate/scale into view.
- Do not add heavy dependencies unless absolutely necessary.

### Forms

- Login, register, OTP, booking, service creation, profile, and admin forms should share one input design.
- Inputs should look futuristic but readable.
- Include clear focus rings and validation/error states.

### Mobile

- Must work well on mobile.
- Bento grids should collapse into stacked cards.
- Floating sidebars should become drawers or horizontal compact nav.
- Buttons, cards, and forms must not overflow.

## Copy Direction

Keep a futuristic feel, but do not make the app confusing.

Use clear labels like:

- Find a Service
- Available Services
- My Bookings
- Provider Dashboard
- Revenue
- Service Details
- Book Now
- Track Booking
- Admin Dashboard

Avoid overdone labels like:

- AI Sommelier Protocol
- Heritage Archive
- Artifact
- Protocol
- Exchequer
- Relinquish Session

## Verification

After redesigning, run:

```bash
php bin/console lint:twig templates
php bin/phpunit
```

Fix any Twig syntax errors, broken layouts, missing variables, or visual regressions.

## Goal

Raise the futuristic feel from 6/10 to 9/10 while keeping the product usable, fast, responsive, and fully functional.

