# PowerMoneyPlan Design System

## 1. Atmosphere & Identity

PowerMoneyPlan should feel like a stable blue-bank desk with a sharper digital front door. The signature is "registered confidence": clear registration details, sober editorial spacing, restrained data panels, a strong POWER MONEYPLAN wordmark, and documentary business imagery that makes the service feel accountable before it feels fast.

## 2. Color

### Palette

| Role | Token | Light | Dark | Usage |
|------|-------|-------|------|-------|
| Surface/primary | --surface-primary | #f5f8fc | #08172d | Page background |
| Surface/paper | --surface-paper | #ffffff | #0e2342 | Cards, forms, tables |
| Surface/muted | --surface-muted | #eaf2ff | #142b50 | Alternate sections |
| Surface/deep | --surface-deep | #0e2342 | #071429 | Header/footer/deep bands |
| Surface/paper RGB | --surface-paper-rgb | 255, 255, 255 | 255, 255, 255 | Alpha surfaces |
| Surface/deep RGB | --surface-deep-rgb | 9, 22, 44 | 9, 22, 44 | Hero and modal overlays |
| Surface/deep alt RGB | --surface-deep-alt-rgb | 14, 35, 66 | 14, 35, 66 | Scrolled header overlay |
| Surface/deep mid RGB | --surface-deep-mid-rgb | 10, 32, 70 | 10, 32, 70 | Hero overlay ramp |
| Surface/deep soft RGB | --surface-deep-soft-rgb | 11, 57, 125 | 11, 57, 125 | Hero overlay ramp |
| Text/primary | --text-primary | #0f2540 | #f4f8ff | Headlines and important labels |
| Text/secondary | --text-secondary | #44556c | #c9d6ec | Body copy |
| Text/muted | --text-muted | #7b8aa0 | #8fa5c6 | Captions and help text |
| Text/primary RGB | --text-primary-rgb | 15, 37, 64 | 15, 37, 64 | Tinted shadows and deep panels |
| Border/default | --border-default | #dbe6f5 | #243d63 | Cards, tables, controls |
| Border/strong | --border-strong | #9fb8dc | #5e7fb0 | Legal and compliance separators |
| Accent/primary | --accent-primary | #1b64f2 | #7db3ff | Main CTA, links, active states |
| Accent/hover | --accent-hover | #0e4fd1 | #4d86ff | CTA hover state |
| Accent/secondary | --accent-secondary | #37c8ff | #9be7ff | Licensed/verified highlights |
| Accent/light | --accent-light | #7db3ff | #cfe5ff | Brand logo and header highlights |
| Accent/bright | --accent-bright | #4d86ff | #74a6ff | CTA gradient stop |
| Accent/frost | --accent-frost | #cfe5ff | #e7f2ff | Hero text highlight |
| Accent/soft | --accent-soft | #dce9ff | #17396a | Focus rings and pale blue surfaces |
| Accent/primary RGB | --accent-primary-rgb | 27, 100, 242 | 27, 100, 242 | Alpha accents |
| Accent/secondary RGB | --accent-secondary-rgb | 55, 200, 255 | 55, 200, 255 | Cyan alpha accents |
| Accent/light RGB | --accent-light-rgb | 125, 179, 255 | 125, 179, 255 | Header border and hero light |
| Text/on-deep secondary | --text-on-deep-secondary | #c9d6ec | #c9d6ec | Header and deep-section secondary text |
| Status/success | --status-success | #12b76a | #35bf82 | Approval and safe states |
| Status/warning | --status-warning | #ffab1f | #ffd166 | Caution and required notices |
| Status/warning RGB | --status-warning-rgb | 255, 171, 31 | 255, 171, 31 | Alpha warning panels |
| Status/error | --status-error | #b42318 | #df6b62 | Error states |
| Shadow/deep RGB | --shadow-deep-rgb | 6, 19, 43 | 6, 19, 43 | Deep blue shadows |

### Rules

- Blue is the dominant financial trust color, inherited from the original site CSS.
- Cyan is reserved for the POWER MONEYPLAN mark, active states, and verified highlights.
- Amber is used only for warnings or legally required caution notices.
- Avoid green-dominant pages, purple-blue AI gradients, floating glow orbs, and decorative animation that does not communicate state.
- Raw hex and RGB channel values belong in `:root` token declarations only. Alpha colors outside `:root` must use `rgba(var(--token-rgb), alpha)`.

## 3. Typography

### Scale

| Level | Size | Weight | Line Height | Tracking | Usage |
|-------|------|--------|-------------|----------|-------|
| Display | clamp(40px, 7vw, 84px) | 700 | 1.08 | 0 | Hero headline |
| H1 | clamp(32px, 5vw, 56px) | 700 | 1.15 | 0 | Major section heading |
| H2 | clamp(26px, 3.6vw, 40px) | 700 | 1.22 | 0 | Section heading |
| H3 | 22px | 700 | 1.35 | 0 | Card heading |
| Body/lg | 18px | 500 | 1.65 | 0 | Lead copy |
| Body | 16px | 400 | 1.65 | 0 | Default copy |
| Body/sm | 14px | 400 | 1.55 | 0 | Secondary copy |
| Caption | 12px | 600 | 1.45 | 0.04em | Labels and metadata |

### Font Stack

- Primary: "Pretendard Variable", Pretendard, "Noto Sans KR", system-ui, -apple-system, sans-serif
- Mono: "SFMono-Regular", Consolas, "Liberation Mono", monospace

### Rules

- Korean copy uses `word-break: keep-all` and `text-wrap: pretty` where supported.
- Numeric data uses tabular figures.
- Avoid all-caps English labels except short metadata labels.

## 4. Spacing & Layout

### Base Unit

All spacing derives from a base of 4px.

| Token | Value | Usage |
|-------|-------|-------|
| --space-1 | 4px | Icon-to-label gaps |
| --space-2 | 8px | Tight inline spacing |
| --space-3 | 12px | Compact control padding |
| --space-4 | 16px | Default small gaps |
| --space-5 | 20px | Form field rhythm |
| --space-6 | 24px | Card padding |
| --space-8 | 32px | Grid gaps |
| --space-10 | 40px | Section inner rhythm |
| --space-12 | 48px | Major blocks |
| --space-16 | 64px | Section padding |
| --space-20 | 80px | Hero and major sections |

### Grid

- Max content width: 1180px
- Desktop: 12-column grid with 24px gutter
- Tablet: 2-column grids where content density supports it
- Mobile: single column with 20px page margins

### Rules

- Product information uses tables or definition lists, not decorative cards alone.
- Header and bottom quick actions must stay stable and not shift on scroll.
- Hero must reveal the next section on common desktop and mobile viewports.

## 5. Components

### Header
- **Structure**: POWER MONEYPLAN wordmark, registration summary, primary phone CTA, anchor navigation, scroll progress.
- **States**: sticky default, compact scrolled state, active anchor, focus-visible, hover on anchors and phone CTA.
- **Accessibility**: semantic `header` and `nav`, clear phone link label.
- **Motion**: transform/opacity/color transitions only; scroll progress and active anchor state must not shift layout.

### Brand Wordmark
- **Structure**: dimensional blue emblem plus original `POWER · MONEYPLAN` wordmark and short service descriptor.
- **States**: hover sheen on the emblem, scrolled navy contrast state, mobile compact state.
- **Accessibility**: single home link with the Korean brand label.
- **Motion**: one hover/scroll response only; no endless decorative pulse.

### Button
- **Variants**: primary, secondary, text-link, fixed-bar.
- **Spacing**: `--space-3` to `--space-6`, 48px minimum tap height.
- **States**: default, hover, active, focus, disabled.
- **Accessibility**: visible focus ring using `--accent-primary`.
- **Motion**: transform on active only; no shine sweeps or pulse loops.

### Consultation Form
- **Structure**: grouped labels, product select, amount select, name, phone, consent.
- **States**: default, focus, invalid, disabled, submitted.
- **Accessibility**: explicit labels and `aria-describedby` for legal helper text.
- **Motion**: submit state changes copy without layout jump.

### Product Table Card
- **Structure**: image, badge, heading, body, table rows, CTA.
- **Variants**: personal, corporate, short-term.
- **States**: default, hover, focus within.
- **Accessibility**: meaningful image alt text and link labels.
- **Motion**: subtle border and background shift only.

### Evidence Panel
- **Structure**: registration details, warning notice, fee notice, process checklist.
- **States**: static; no fake live urgency.
- **Accessibility**: uses `dl`, `ol`, and readable contrast.
- **Motion**: none.

### Bottom Quick Bar
- **Structure**: phone CTA, form anchor, top anchor.
- **States**: hover, active, focus.
- **Accessibility**: clear labels, no icon-only actions without labels.
- **Motion**: transform on active only.

## 6. Motion & Interaction

### Timing

| Type | Duration | Easing | Usage |
|------|----------|--------|-------|
| Micro | 120ms | ease-out | Button active feedback |
| Standard | 180ms | ease-in-out | Hover/focus surface shifts |
| Emphasis | 360ms | cubic-bezier(0.16, 1, 0.3, 1) | Reveal on first viewport entry |

### Rules

- Prefer `transform`, `opacity`, color, and box-shadow transitions. Do not animate layout dimensions for primary motion.
- Respect `prefers-reduced-motion`.
- No decorative infinite animation except a tiny status dot when it communicates availability.
- Form submission should update a visible status region instead of using `alert()`.
- JavaScript may add scroll progress, active navigation, pointer-responsive hero lighting, count-up facts, and product-to-form selection because each communicates page state or user action.

## 7. Depth & Surface

### Strategy

Use mixed depth: mostly tonal shifts and borders, with small tinted shadows only for fixed UI and high-priority forms.

| Level | Value | Usage |
|-------|-------|-------|
| Subtle | 0 1px 2px rgba(var(--text-primary-rgb), 0.06) | Table rows and small cards |
| Default | 0 16px 40px rgba(var(--text-primary-rgb), 0.12) | Main consultation form |
| Fixed | 0 -10px 30px rgba(var(--text-primary-rgb), 0.14) | Bottom quick bar |

### Rules

- Deep sections use `--surface-deep` plus transparent white borders and controlled blue light accents.
- Cards keep radius at 8px to 14px unless a form needs stronger separation.
- Images are documentary and grounded: no fake UI text, no magic glows, no unreadable Korean signs.
