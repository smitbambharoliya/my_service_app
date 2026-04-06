# ServiceHub Redesign Handoff Report

Last audited: 2026-04-04

This document is written so it can be pasted into another AI or shared with a designer/developer who needs to redesign the frontend without breaking the current product logic.

## 1. Project Summary

**Project name:** ServiceHub  
**Type:** Multi-role home/service marketplace  
**Framework:** Symfony 7.4 + Twig + Doctrine ORM + Asset Mapper  
**Backend language:** PHP 8.2+  
**Frontend:** Twig templates, vanilla CSS, Bootstrap utilities, small amounts of custom JS, Chart.js, Leaflet  
**Payments:** Stripe Checkout  
**PDF invoices:** Knp Snappy / wkhtmltopdf  
**Auth:** Session-based auth with custom authenticator  
**Registration security:** Email OTP verification

The product connects customers with service providers. Customers can browse services, book online or request an in-person visit, receive estimates, track dispatched providers, chat with providers, and pay bills. Providers can publish services, manage bookings, send estimates, dispatch jobs, complete work, generate custom bills, and build reputation points. Admins manage users, services, bookings, providers, and homepage featured sections.

## 2. Core Roles And Flows

### Customer
- Register with OTP email verification
- Browse and search services
- View service details
- Book a service
- Choose booking type: `online` or `visit`
- Cancel pending bookings
- Accept or reject visit estimates
- Track provider when booking is `on-the-way`
- View billing records
- Pay unpaid bills through Stripe
- Download invoice PDF
- Chat with providers
- View personal profile/dashboard

### Provider
- Register as provider during signup
- Complete profile before publishing first service
- Create services
- View provider dashboard
- Manage own services
- View customer bookings for owned services
- Send visit estimates
- Dispatch provider for live tracking
- Complete jobs
- Generate final bills
- Create manual/custom bills for past customers
- Upgrade services to premium
- Earn reputation points and automatic tier upgrades

### Admin
- View admin dashboard stats
- Manage users
- Promote users to admin
- Suspend/activate users
- Delete users
- Manage providers
- Manage services
- Toggle service premium / visibility
- Manage bookings and booking statuses
- Manage homepage featured services via mini CMS

## 3. Main Business Entities

### `User`
- Email, password, full name, mobile
- Roles: `ROLE_USER`, `ROLE_PROVIDER`, `ROLE_ADMIN`
- Verification flag, active flag
- Address, city, pincode, date of birth, gender
- Latitude/longitude
- OTP code
- Reputation points and tier

### `Service`
- Title, description, price, category
- Premium flag
- Active flag
- Linked provider

### `Booking`
- Customer
- Service
- Status
- Booking type: `online` or `visit`
- Notes
- Estimated cost
- Estimation status
- Booking date
- Tracking latitude/longitude

### `Billing`
- User
- Amount
- Payment status
- Transaction ID
- Category
- Service name
- Description
- Optional itemized line items
- Stripe session ID

### `Message`
- Sender
- Receiver
- Content
- Read/unread
- Created timestamp

### `FeaturedService`
- Homepage placement record
- Section (`hero`, `trending`, `premium`, `seasonal`)
- Display order
- Active flag
- Schedule window

### `Review`
- Exists in the data model, but the UI around reviews is not yet built out in a complete way

## 4. Current Functional Areas By File

### Public / shared layout
- `templates/base.html.twig`
- `public/styles/app.css`
- `assets/styles/app.css`

### Marketing and discovery
- `templates/home/index.html.twig`
- `templates/service/index.html.twig`
- `templates/service/show.html.twig`
- `templates/service/book_by_category.html.twig`

### Auth and onboarding
- `templates/security/login.html.twig`
- `templates/registration/register.html.twig`
- `templates/registration/verify_otp.html.twig`
- `templates/dashboard/provider_onboarding.html.twig`

### Customer area
- `templates/dashboard/customer.html.twig`
- `templates/booking/new.html.twig`
- `templates/booking/track.html.twig`
- `templates/billing/index.html.twig`
- `templates/profile/index.html.twig`

### Provider area
- `templates/dashboard_base.html.twig`
- `templates/dashboard/provider.html.twig`
- `templates/dashboard/my_services.html.twig`
- `templates/service/new.html.twig`
- `templates/dashboard/provider_billing_new.html.twig`

### Messaging
- `templates/message/inbox.html.twig`
- `templates/message/chat.html.twig`

### Billing / invoice
- `templates/billing/history.html.twig`
- `templates/billing/invoice_pdf.html.twig`

### Admin area
- `templates/admin/admin_base.html.twig`
- `templates/admin/dashboard.html.twig`
- `templates/admin/users.html.twig`
- `templates/admin/providers.html.twig`
- `templates/admin/services.html.twig`
- `templates/admin/bookings.html.twig`
- `templates/admin/featured_services/manager.html.twig`

## 5. Current Frontend Direction

The project does not use one single design system yet. It currently mixes multiple visual styles:

### Style A: "Luxury dark" public UI
- Used by `base.html.twig`, homepage, service listing, service detail, login, register
- Dark background
- Gold, teal, violet accent colors
- Playfair Display + Plus Jakarta Sans
- Glassmorphism cards
- Heavy editorial / premium wording

### Style B: "Red operator dashboard"
- Used by `dashboard_base.html.twig`, provider dashboard, parts of customer dashboard
- Dark dashboard shell with red admin-style accent
- Sidebar layout
- Different tone from marketing site

### Style C: "Clean Bootstrap light pages"
- Used by `templates/service/new.html.twig`
- Used by `templates/booking/new.html.twig`
- Used by `templates/billing/history.html.twig`
- Used by `templates/dashboard/my_services.html.twig`
- These pages look like a different app

### Style D: "Aurora cyber UI"
- Used by `templates/message/*`
- Used by `templates/billing/index.html.twig`
- Used by `templates/admin/featured_services/manager.html.twig`
- This style references CSS tokens that are not consistently defined globally

## 6. Important Frontend Problems To Fix In The Redesign

### Visual inconsistency
- The app feels like 4 different products stitched together
- Marketing, customer dashboard, provider dashboard, admin, billing, and chat do not share one visual system

### Too much inline styling
- Many Twig templates contain large inline `<style>` blocks and inline style attributes
- This makes the UI harder to maintain and redesign consistently

### Duplicate stylesheet sources
- `public/styles/app.css` and `assets/styles/app.css` both exist
- They appear to duplicate the same design layer
- Styling should be consolidated into one clear source of truth

### Undefined or mismatched design tokens
- Some templates use tokens like `--bg-secondary`, `--border-light`, `--aurora-purple`, `--text-primary`, `--glow-cyan`, `btn-ghost`, `btn-violet`, `badge-aurora`, etc.
- These are not consistently defined in the shared stylesheet
- Messaging, billing dashboard, and featured-service CMS are especially affected

### Status naming is inconsistent
- Backend often stores booking status as lowercase values like `pending`, `completed`
- Some templates check for uppercase variants like `Pending`, `Completed`, `Confirmed`
- This can break counts, button visibility, and state-based UI

### Route duplication
- `app_booking_track` is defined in two controllers:
  - `src/Controller/CustomerController.php`
  - `src/Controller/ServiceController.php`
- This should be cleaned up during redesign/refactor work

### Demo / placeholder content
- Dashboard charts use static demo data
- Some service cards use fixed Unsplash images
- Tracking coordinates are simulated
- Some labels are highly decorative and may confuse real users

### Overwritten product tone
- The current copy is stylish but sometimes too abstract for a real service marketplace
- Example tone includes phrases like "heritage protocol", "estate manifest", "AI sommelier", "digital heritage"
- A redesign should preserve premium quality only if it still keeps flows simple and trustworthy

## 7. Current UX Structure That Must Be Preserved

Do not remove these product capabilities while redesigning:

- Multi-role login and role-based dashboards
- OTP verification after registration
- Provider onboarding gate before first service publish
- Service listing with search / category / price filtering
- Service detail page with provider information
- Booking flow with `online` and `visit` modes
- Estimate workflow for visit bookings
- Customer accept/reject estimate flow
- Dispatch and live tracking flow
- Billing list, payment flow, and invoice PDF download
- Internal chat between users
- Admin management pages
- Featured service homepage CMS
- Reputation points and member/provider tier display

## 8. Recommended Product Additions

These are good additions to include in the redesign if feasible:

- Real service thumbnails or uploaded images instead of repeated stock images
- Provider public profile / portfolio page
- Review and rating UI, because the entity already exists
- Better booking timeline component showing: booked -> estimate sent -> accepted -> dispatched -> completed -> paid
- Notification center or header alerts using unread messages / booking state changes
- Better profile editing flow (there is a profile page, and an edit template exists but is not wired into a complete workflow)
- Better mobile-first layout across dashboards and admin views
- Strong empty states with direct next actions
- Consistent CTA system across public, customer, provider, and admin screens
- Better service category cards with icons/images from `public/images/categories/`
- Better invoice / bill detail screen before checkout

## 9. Suggested Redesign Direction

My recommendation is:

- Keep the app premium and modern, but make it easier to understand
- Reduce metaphor-heavy copy
- Unify around one design system
- Use one shared set of variables, buttons, cards, tables, badges, form controls, and page shells
- Keep admin visually distinct, but clearly part of the same product family
- Make customer and provider dashboards feel related, not unrelated
- Preserve dark premium styling if desired, but with clearer usability and fewer random style jumps

### Good redesign goals
- Consistent typography
- Clear spacing scale
- Reusable component classes
- Better mobile responsiveness
- Fewer inline styles
- Fewer per-page style blocks
- More predictable colors for statuses and actions
- Clear distinction between public pages and internal dashboards
- Better form UX and error display

## 10. Concrete File-Level Guidance For The Redesign

### Likely layouts to keep and improve
- `templates/base.html.twig`
- `templates/dashboard_base.html.twig`
- `templates/admin/admin_base.html.twig`

### Pages that need the most cleanup
- `templates/booking/new.html.twig`
- `templates/service/new.html.twig`
- `templates/dashboard/my_services.html.twig`
- `templates/billing/history.html.twig`
- `templates/message/inbox.html.twig`
- `templates/message/chat.html.twig`
- `templates/admin/featured_services/manager.html.twig`

### Styles to consolidate
- `public/styles/app.css`
- `assets/styles/app.css`

### UX logic files to preserve while redesigning
- `src/Controller/ServiceController.php`
- `src/Controller/BillingController.php`
- `src/Controller/CustomerController.php`
- `src/Controller/RegistrationController.php`
- `src/Controller/OtpController.php`
- `src/Controller/MessageController.php`
- `src/Controller/AdminController.php`
- `src/Controller/AdminFeaturedServiceController.php`

## 11. Cleanup Candidates

These should be reviewed during redesign work:

- `templates/service/list.html.twig`
  - Looks like an older listing page; current route renders `templates/service/index.html.twig`
- `templates/dashboard/index.html.twig`
  - Default scaffold page, likely not part of the real product
- `templates/profile/edit.html.twig`
  - Exists but current profile controller only renders the main profile page
- Duplicate booking tracking route name
- Mixed booking status casing in templates and controllers
- Inline JS and CSS scattered through many Twig files

## 12. Recommended Output For The Next AI

If another AI is redesigning this project, the best output is:

1. A unified frontend design system
2. Updated base, dashboard, and admin layouts
3. Refactored shared CSS with reusable utility/component classes
4. Redesigned versions of all active Twig pages
5. Cleanup of inconsistent status labels and duplicated styling logic
6. Preservation of all current backend flows

## 13. Paste-Ready Prompt For Another AI

Use this prompt with another AI:

> I have a Symfony 7.4 + Twig service marketplace project called ServiceHub. Please redesign the frontend without removing any existing business logic. Keep all current features working: OTP registration, role-based dashboards (customer/provider/admin), service listing and detail pages, booking flow (online + visit), estimate acceptance/rejection, live tracking, billing and Stripe checkout, invoice PDF download, internal chat, featured homepage CMS, and provider reputation tiers.  
>  
> The current frontend is inconsistent and mixes multiple styles: a dark luxury marketing UI, a red-accent dashboard UI, several old light Bootstrap pages, and some aurora/cyber pages with inconsistent CSS tokens. I want one cohesive design system across the whole app.  
>  
> Please redesign these key layouts and screens first:  
> - base layout  
> - dashboard layout  
> - admin layout  
> - homepage  
> - service listing  
> - service detail  
> - booking form  
> - customer dashboard  
> - provider dashboard  
> - my services  
> - billing list/history  
> - chat inbox and conversation  
> - admin dashboard and featured-services manager  
>  
> Requirements:  
> - keep Twig structure compatible with Symfony  
> - prefer reusable CSS classes over inline styling  
> - make the UI responsive on mobile and desktop  
> - use one consistent palette, spacing system, and typography approach  
> - keep the product premium and modern, but make the UX clearer and less overly abstract  
> - preserve route names and form actions unless absolutely necessary  
> - do not break controller logic  
> - if needed, normalize status handling for booking/payment badges and actions  
> - consolidate duplicate CSS and avoid undefined CSS variables  
>  
> Nice-to-have improvements if you can include them: provider public portfolio page, reviews UI, better booking timeline, stronger empty states, category image usage, and better notification patterns.

## 14. Short Technical Notes For The Next AI

- Search/filter logic lives in `src/Repository/ServiceRepository.php`
- Messaging conversation + inbox logic lives in `src/Repository/MessageRepository.php`
- Homepage featured services logic lives in `src/Repository/FeaturedServiceRepository.php`
- Stripe checkout session creation lives in `src/Service/StripeService.php`
- Reputation point upgrades live in `src/Service/GamificationService.php`
- Tier upgrade logic is in `src/Entity/User.php`

## 15. Final Recommendation

Do the redesign as a UI-system refactor, not as isolated page beautification. The biggest win here is not just better visuals, but making the entire app feel like one product.
