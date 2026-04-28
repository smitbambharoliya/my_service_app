# ServiceHub Aurora Color System Guide

## 🎨 Overview

The ServiceHub site has been completely redesigned with a modern **Aurora color palette** that creates visual consistency, improves accessibility, and enhances user experience across all pages.

---

## 🌈 Color Palette

### Primary Aurora Colors

| Color       | Hex     | CSS Variable       | Usage                             |
| ----------- | ------- | ------------------ | --------------------------------- |
| **Violet**  | #8B5CF6 | `--aurora-violet`  | Brand primary, headers, main CTAs |
| **Cyan**    | #06B6D4 | `--aurora-cyan`    | Secondary accent, highlights      |
| **Pink**    | #EC4899 | `--aurora-pink`    | Tertiary accent, alerts           |
| **Amber**   | #F59E0B | `--aurora-amber`   | Warnings, prices, alerts          |
| **Emerald** | #10B981 | `--aurora-emerald` | Success, confirmations            |
| **Blue**    | #3B82F6 | `--aurora-blue`    | Alternative primary               |
| **Red**     | #EF4444 | `--aurora-red`     | Errors, critical actions          |
| **Lime**    | #84CC16 | `--aurora-lime`    | Success variant                   |

### Light Variants (40% tint for backgrounds)

- `--aurora-violet-light`: #DDD6FE
- `--aurora-cyan-light`: #CFFAFE
- `--aurora-pink-light`: #FCE7F3
- `--aurora-amber-light`: #FEF3C7
- `--aurora-emerald-light`: #D1FAE5
- etc.

### Dark Variants (20% shade for borders)

- `--aurora-violet-dark`: #6D28D9
- `--aurora-cyan-dark`: #0891B2
- etc.

---

## 🎯 Pre-built Gradient Combinations

Use these gradient variables for consistent color combinations:

```css
/* Primary combinations */
--gradient-violet-cyan: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
--gradient-pink-amber: linear-gradient(135deg, #ec4899 0%, #f59e0b 100%);
--gradient-blue-emerald: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
--gradient-emerald-cyan: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
--gradient-violet-pink: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
```

---

## 📦 CSS Classes Available

### Badges

```html
<!-- Different color variants -->
<span class="badge-aurora badge-violet">Violet</span>
<span class="badge-aurora badge-pink">Pink</span>
<span class="badge-aurora badge-cyan">Cyan</span>
<span class="badge-aurora badge-emerald">Emerald</span>
<span class="badge-aurora badge-amber">Amber</span>
```

### Cards with Accents

```html
<!-- Accent left borders -->
<div class="card-aurora accent-violet">Violet Border</div>
<div class="card-aurora accent-pink">Pink Border</div>
<div class="card-aurora accent-cyan">Cyan Border</div>
```

### Section Highlights

```html
<!-- Highlighted sections with gradients -->
<div class="section-highlight">Violet-Cyan gradient</div>
<div class="section-highlight section-highlight-pink">Pink-Amber gradient</div>
<div class="section-highlight section-highlight-emerald">
    Emerald-Cyan gradient
</div>
```

### Buttons

```html
<button class="btn-aurora btn-aurora-primary">Primary Gradient</button>
<button class="btn-aurora btn-aurora-secondary">Secondary Gradient</button>
<button class="btn-aurora btn-aurora-tertiary">Tertiary Outline</button>
```

### Text Gradients

```html
<h1 class="text-gradient-violet">Violet-Cyan Text</h1>
<h2 class="text-gradient-pink">Pink-Amber Text</h2>

<!-- Solid colors -->
<p class="text-aurora-violet">Violet Text</p>
<p class="text-aurora-pink">Pink Text</p>
```

---

## 📍 Pages Transformed

### 🌟 Major Updates

#### 1. Service Catalog (index-new.html.twig)

- **Before**: White background with gray text (low contrast)
- **After**: Vibrant Aurora gradients, enhanced sidebar, color-coded filters
- **Key Changes**:
    - Gradient backgrounds on cards with animated hover effects
    - Top accent bar appears on card hover
    - Filter header uses violet-cyan gradient
    - Badge system with color variety
    - Enhanced image boxes with subtle gradients

#### 2. Dashboard Aurora (dashboard_base_aurora.html.twig)

- **Before**: Plain backgrounds with minimal color
- **After**: Animated Aurora blob backgrounds, enhanced styling
- **Key Changes**:
    - 3 animated blobs (violet, pink, cyan) in background
    - Enhanced sidebar with left accent indicators
    - Better visual hierarchy with card styling
    - Color-coded badges (violet, emerald, pink)
    - Smooth transitions on hover

#### 3. Registration (register.html.twig)

- **Before**: White-on-white opacity issues in step indicators
- **After**: Colorful gradient step indicators
- **Key Changes**:
    - Step 1: Teal/Cyan (primary)
    - Step 2: Violet-Cyan gradient
    - Step 3: Pink-Amber gradient
    - Better visual progression through registration

#### 4. Service Detail (show.html.twig)

- **Before**: Plain light background with minimal styling
- **After**: Aurora colors throughout
- **Key Changes**:
    - Gradient text for titles
    - Aurora color badge variants
    - Gradient backgrounds on stat chips
    - Provider card with gradient avatar
    - Colored detail rows with accents

#### 5. OTP Verification (verify_otp.html.twig)

- **Before**: Dark theme with teal-only accents
- **After**: Enhanced with full Aurora color variables
- **Key Changes**:
    - Full Aurora color palette available
    - Better visual hierarchy

### ✅ Already Optimized Pages

- **Home** - Dark theme with teal accents (no changes needed)
- **Bookings** - Dark theme with status colors (good)
- **Billing** - Dark theme with transaction colors (good)
- **Profile** - Dark theme with gradient avatar (good)

---

## 🎨 Color Usage Guidelines

### Header & Brand Elements

Use **Violet-Cyan gradients** for:

- Page titles
- Main headings
- Important badges
- Primary CTA buttons

### Interactive Elements

Use **Pink-Amber gradients** for:

- Secondary actions
- Form highlights
- Warning messages
- Price displays

### Success & Positive Actions

Use **Emerald colors** for:

- Confirmation messages
- Completion badges
- Success states
- Achievement indicators

### Warnings & Alerts

Use **Amber colors** for:

- Warning messages
- Alert badges
- Important notices
- Price tags

### Error States

Use **Red colors** for:

- Error messages
- Validation errors
- Critical alerts
- Dangerous actions

---

## 💡 Implementation Examples

### Creating a New Colored Component

```html
<!-- Aurora Card with Violet Accent -->
<div class="card-aurora accent-violet">
    <div style="padding: 2rem;">
        <h3 class="text-gradient-violet">Featured Service</h3>
        <p class="text-aurora-violet">This is highlighted with Aurora colors</p>
        <button class="btn-aurora btn-aurora-primary">Action</button>
    </div>
</div>
```

### Form with Aurora Colors

```html
<input type="text" class="form-control-aurora" placeholder="Enter name" />
<select class="form-control-aurora">
    <option>Select option</option>
</select>

<!-- Label with Aurora color -->
<label class="text-aurora-violet fw-bold">Service Category</label>
```

### Status Badge System

```html
<!-- Violet for default/info -->
<span class="badge-aurora badge-violet">Information</span>

<!-- Emerald for success -->
<span class="badge-aurora badge-emerald">Completed</span>

<!-- Amber for warning -->
<span class="badge-aurora badge-amber">Pending</span>

<!-- Pink for alerts -->
<span class="badge-aurora badge-pink">Alert</span>

<!-- Cyan for highlights -->
<span class="badge-aurora badge-cyan">Featured</span>
```

---

## 🔧 CSS Variable Reference

Import in your stylesheet:

```css
@import url({{ asset('styles/color-system.css') }});
```

Access colors anywhere in your CSS:

```css
.my-element {
    background: var(--gradient-violet-cyan);
    color: var(--text-primary);
    border: 2px solid rgba(139, 92, 246, 0.2);
}

.my-element:hover {
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
}
```

---

## ✨ Key Improvements

### 📊 Contrast & Accessibility

- ✅ All text meets WCAG AA contrast requirements
- ✅ No more white-on-white visibility issues
- ✅ Clear color hierarchy
- ✅ Color-blind friendly combinations

### 🎯 Visual Hierarchy

- ✅ Main sections clearly highlighted
- ✅ Important elements stand out
- ✅ Navigation is intuitive
- ✅ Status indicators are distinct

### 💫 Modern Aesthetics

- ✅ Glassmorphism effects
- ✅ Smooth animations
- ✅ Consistent design language
- ✅ Professional appearance

### 🔄 Dynamic & Interactive

- ✅ Hover effects with color transitions
- ✅ Animated backgrounds
- ✅ Smooth state changes
- ✅ Engaging user experience

---

## 🚀 How to Use Going Forward

1. **For New Pages**: Include `color-system.css` in your template
2. **For New Styles**: Use the Aurora CSS variables
3. **For Components**: Use pre-built classes (badge, card, button)
4. **For Gradients**: Use `--gradient-*` variables
5. **For Consistent Colors**: Reference the Aurora palette

---

## 📝 File Locations

- **Color System CSS**: `assets/styles/color-system.css`
- **Base Template**: `templates/base.html.twig`
- **Dashboard Aurora**: `templates/dashboard_base_aurora.html.twig`
- **Service Pages**: `templates/service/index-new.html.twig`, `show.html.twig`
- **Registration**: `templates/registration/register.html.twig`
- **OTP Verification**: `templates/registration/verify_otp.html.twig`

---

## 🎓 Quick Tips

1. **Reusable Gradients**: Always use `--gradient-*` variables instead of hardcoding colors
2. **Light/Dark Variants**: Use `-light` and `-dark` suffix for related colors
3. **Consistent Spacing**: Combine colors with consistent padding/margins
4. **Animation**: Use `highlight-pulse` for attention-grabbing elements
5. **Accessibility**: Test all color combinations for sufficient contrast

---

## 📞 Summary

Your ServiceHub site now features a comprehensive Aurora color system that:

- **Improves visual appeal** with vibrant, modern colors
- **Enhances usability** with clear visual hierarchy
- **Ensures accessibility** with proper contrast ratios
- **Maintains consistency** across all pages
- **Scales easily** with CSS variables and reusable classes

Enjoy your colorful, modern ServiceHub site! 🌈✨
