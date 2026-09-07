# Salltak V9 UI Redesign Design

## Goal
Redesign the entire Salltak public website, authentication flow, customer cart experience, and admin dashboard while preserving the existing Laravel business logic and SHEIN V8 import behavior.

## Visual direction
Use a non-purple palette built around dark navy, teal, sky blue, warm gold, white, and pale blue-gray surfaces.

- Navy: `#0F2744`
- Deep navy: `#091827`
- Teal: `#14B8A6`
- Teal dark: `#0F8F83`
- Sky: `#38BDF8`
- Gold: `#F4B942`
- Background: `#F4F8FB`
- Surface: `#FFFFFF`
- Ink: `#122033`
- Muted: `#66788A`

Purple must not be used as a primary, secondary, gradient, chart, focus, button, active-navigation, or decorative color.

## Scope
1. Public landing page: premium external homepage with navbar, animated hero, product workflow explanation, feature cards, platform preview, supported stores, CTA, and footer.
2. Authentication pages: modern split-card login/register screens using the V9 palette.
3. Customer pages: create cart, preview cart, saved carts list, and saved cart details all receive the V9 visual system and responsive behavior without changing routes or form names.
4. Admin shell: fixed desktop sidebar, mobile offcanvas sidebar, top admin header, search-style chrome, user chip, modern navigation, and responsive layout.
5. Admin dashboard: upgraded overview composition using existing `$stats` and `$latestCarts` only. Do not invent historical business data. Use decorative CSS mini-visuals and current totals rather than fabricated analytics.
6. Admin management pages: carts, users, stores, exchange rates, settings styled consistently through shared classes.
7. Motion: CSS-based reveal, floating decorative elements, hover lift, button motion, and reduced-motion accessibility fallback. JavaScript may add an `is-visible` class through IntersectionObserver only; no external animation library.
8. Responsive: Bootstrap 5 layout, mobile-first breakpoints, horizontal overflow protection, touch-friendly controls, sticky/compact admin mobile topbar, and tables wrapped responsively.

## Functional constraints
- Preserve all existing route names.
- Preserve all POST/PATCH/PUT form actions, names, validation, and CSRF handling.
- Preserve SHEIN Playwright importer and V8 USD pricing behavior unchanged.
- Preserve color/size extraction from V7.
- Do not introduce a build dependency; assets remain plain CSS/JS in `public/` and existing Bootstrap remains available.
- Keep Arabic RTL as the default document direction.
- Do not require remote fonts or remote JavaScript for the redesign.

## Accessibility and quality
- Visible focus states using teal/sky rather than purple.
- Respect `prefers-reduced-motion`.
- Maintain readable contrast for navbar/sidebar and buttons.
- Mobile menu/offcanvas must remain keyboard accessible through Bootstrap behavior.
- Avoid layout shifts from decorative assets by using CSS shapes instead of remote hero images.

## Testing
- Add a V9 UI static checker that verifies the new palette, landing sections, motion hooks, responsive admin shell, and absence of legacy purple hex tokens.
- Extend feature UI assertions only where useful; do not make brittle pixel tests.
- Run existing PHP smoke, UI checks, PHPUnit suite when dependencies permit, Node syntax/tests, and PHP lint.
