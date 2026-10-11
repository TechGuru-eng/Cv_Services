---
name: frontend-design
description: >
  Senior frontend design and visual QA skill. Use this skill before creating,
  redesigning, or modifying any user-facing interface, page, dashboard,
  landing page, form, pricing section, card, modal, navigation, or responsive layout.
---

# Frontend Design Skill

## Mission

Act as a **senior product designer, UI/UX designer, art director, and frontend engineer** at the same time.

Your job is not merely to make a page work.

Your job is to produce interfaces that feel:

- Premium
- Intentional
- Modern
- Trustworthy
- Visually polished
- Responsive
- Accessible
- Production-ready

Every frontend task should combine strong design judgment with clean implementation.

---

# 1. ALWAYS DO FIRST

Before writing frontend code:

1. Inspect the existing project structure.
2. Identify the framework and styling system.
3. Inspect reusable components already present.
4. Check for a `brand-assets/`, `assets/`, `public/`, or equivalent folder.
5. Look for:
   - Logos
   - Brand colors
   - Existing fonts
   - Icons
   - Images
   - Style guides
   - Existing UI patterns
6. Inspect the current page before changing it.
7. Determine whether a reference image or screenshot was provided.

Do not immediately start coding.

First understand the design context.

---

# 2. REFERENCE IMAGE MODE

When the user provides a screenshot, mockup, flyer, web page, or design reference:

## Primary Rule

**Match the reference before improving it.**

Reproduce as closely as practical:

- Layout
- Proportions
- Alignment
- Spacing
- Typography
- Font scale
- Borders
- Radius
- Shadows
- Colors
- Image positioning
- Section heights
- Button dimensions
- Visual hierarchy

Do not invent additional sections or features unless requested.

Do not reinterpret the reference into a completely different design.

## Reference Hierarchy

Prioritize matching in this order:

1. Overall composition
2. Dimensions and spacing
3. Typography hierarchy
4. Colors
5. Components
6. Decorative details
7. Micro-interactions

---

# 3. DESIGN-FROM-SCRATCH MODE

If no reference design exists, create the interface from scratch using high design standards.

Before implementation, silently establish:

- Page purpose
- Primary user goal
- Primary CTA
- Secondary CTA
- Information hierarchy
- Content density
- Appropriate visual style
- Mobile layout
- Desktop layout
- Brand personality

The result must not feel like a generic AI-generated template.

---

# 4. BRAND-FIRST RULE

Always use real brand assets when available.

## Required behavior

- Use the real logo instead of placeholder text.
- Use provided brand colors exactly when defined.
- Reuse existing imagery.
- Preserve recognizable brand identity.
- Maintain consistent visual language across pages.

If brand assets exist, do not replace them with generic placeholders.

If no formal brand system exists, derive a coherent system from the strongest existing brand asset.

---

# 5. ANTI-GENERIC DESIGN RULES

Avoid interfaces that look like default Tailwind, Bootstrap, AI templates, or tutorial projects.

## Colors

Do not default to:

- Tailwind blue
- Tailwind indigo
- Random purple gradients
- Generic cyan SaaS palettes

Use a deliberate brand palette.

Create:

- Primary
- Secondary
- Accent
- Surface
- Background
- Muted
- Border
- Success
- Warning
- Error

colors when needed.

---

## Typography

Typography must create visual hierarchy.

Use deliberate differences between:

- Hero headings
- Section headings
- Card titles
- Body text
- Labels
- Metadata
- Captions

Avoid using one weight and one font size everywhere.

For major headings:

- Use strong scale
- Tight but readable tracking
- Controlled line length
- Intentional line breaks

For body text:

- Prioritize readability
- Use comfortable line-height
- Avoid overly wide text blocks

---

## Shadows

Avoid generic heavy box shadows.

Prefer:

- Soft layered shadows
- Low-opacity shadows
- Subtle tinted shadows
- Borders + shadows together where appropriate

Shadows should create depth, not decoration.

---

## Borders and Radius

Do not make every object a floating rounded card.

Use radius intentionally.

Not every section needs:

- A card
- A border
- A shadow
- A background color

Create visual rhythm with restraint.

---

## Gradients

Use gradients only when they improve depth or brand expression.

Avoid generic:

- Purple-to-blue SaaS gradients
- Excessive neon gradients
- Gradient text everywhere

When gradients are used, make them subtle and purposeful.

---

# 6. VISUAL HIERARCHY

Every screen must make the following obvious within seconds:

1. What is this page?
2. What is most important?
3. What should the user do next?

Use hierarchy through:

- Size
- Weight
- Spacing
- Contrast
- Position
- Color
- Grouping

Do not make every element visually loud.

---

# 7. SPACING SYSTEM

Use a consistent spacing rhythm.

Prefer a repeatable spacing scale such as:

- 4
- 8
- 12
- 16
- 24
- 32
- 48
- 64
- 80
- 96

Avoid arbitrary spacing values unless required to match a reference.

Related elements should be visually grouped.

Unrelated sections should have enough breathing room.

---

# 8. MOBILE-FIRST RESPONSIVENESS

Every interface must work beautifully on mobile.

Do not treat mobile as a smaller desktop.

On mobile:

- Stack intelligently
- Reduce unnecessary decoration
- Keep touch targets large
- Avoid horizontal scrolling
- Simplify navigation
- Reflow tables when necessary
- Preserve CTA prominence
- Reduce oversized headings
- Maintain readable padding

Check at minimum:

- 360px
- 390px
- 768px
- 1024px
- 1440px

widths when practical.

---

# 9. BUTTONS AND CTAs

Every primary CTA must be instantly identifiable.

Buttons should include appropriate states:

- Default
- Hover
- Focus-visible
- Active
- Disabled
- Loading

Avoid vague labels.

Prefer action-oriented text.

Examples:

Instead of:

`Submit`

Use:

`Create Account`

Instead of:

`Continue`

Use:

`Continue to Payment`

Instead of:

`Click Here`

Use:

`View Job Offers`

---

# 10. FORMS

Forms should feel easy and trustworthy.

Use:

- Clear labels
- Logical grouping
- Helpful validation
- Appropriate input types
- Strong focus states
- Inline error messages
- Clear required/optional treatment
- Helpful success states

Do not use placeholders as the only label.

Do not clear user data after validation errors.

---

# 11. INTERACTION STATES

Every interactive element must have clear states.

At minimum, clickable elements should support:

- Hover
- Focus-visible
- Active

Where appropriate:

- Disabled
- Loading
- Selected
- Expanded
- Error
- Success

Never use `transition-all`.

Animate only properties that need animation.

Prefer:

- `transform`
- `opacity`

Use short, smooth transitions.

---

# 12. ICONOGRAPHY

Use one consistent icon family.

Do not mix:

- Filled icons
- Outlined icons
- Emojis
- Random SVG styles

unless the brand intentionally requires it.

Icons should clarify functionality, not decorate every label.

---

# 13. IMAGES

Images should support the content.

When using photographs:

- Preserve natural aspect ratio
- Crop intentionally
- Maintain focal points
- Add overlays only if required for readability
- Avoid stretching
- Optimize loading

Do not use placeholder images when suitable real assets already exist.

---

# 14. ACCESSIBILITY

Accessibility is part of design quality.

Always consider:

- Semantic HTML
- Keyboard navigation
- Visible focus states
- Form labels
- Alt text
- Color contrast
- Touch target size
- Logical heading hierarchy
- Reduced-motion preferences where relevant

Do not communicate important information through color alone.

---

# 15. CODE QUALITY

Frontend code must be:

- Reusable
- Maintainable
- Readable
- Consistent
- Componentized appropriately
- Compatible with the existing stack

Avoid:

- Giant components
- Repeated markup
- Inline styles everywhere
- Unnecessary dependencies
- Magic numbers
- Breaking established project architecture

Prefer extending the existing design system rather than creating a second one.

---

# 16. PRESERVE FUNCTIONALITY

A redesign must not break working functionality.

Preserve:

- Navigation
- Forms
- API calls
- Authentication
- Tracking
- Links
- Buttons
- Payment actions
- Search
- Filters
- Existing data flow

Visual improvement is not an excuse for regression.

---

# 17. LOCAL PREVIEW

When the environment supports it:

- Run the project locally.
- Never judge the final UI from source code alone.
- View the real rendered page.
- Use localhost or the project's preview environment.

If an existing dev server is already running, reuse it.

Do not unnecessarily start duplicate servers.

---

# 18. SCREENSHOT QA LOOP

When screenshot or browser inspection tools are available, use them.

Required workflow:

1. Implement the first version.
2. Render the page.
3. Capture a screenshot.
4. Inspect the screenshot critically.
5. Compare against the reference if one exists.
6. Identify visible problems.
7. Fix them.
8. Render again.
9. Capture another screenshot.
10. Repeat until polished.

Do at least **two visual review passes** for significant frontend work.

Do not stop after the first version simply because the code runs.

---

# 19. VISUAL QA CHECKLIST

Before declaring the frontend finished, verify:

## Layout

- No accidental overflow
- No overlapping elements
- No broken grids
- No awkward empty spaces
- Sections align consistently

## Typography

- Headings are clear
- Body text is readable
- Font sizes are consistent
- Line lengths are controlled

## Spacing

- Padding is consistent
- Related content is grouped
- Sections have adequate breathing room

## Components

- Buttons are consistent
- Inputs are consistent
- Cards are consistent
- Icons align correctly

## Responsive

- Mobile works
- Tablet works
- Desktop works
- No horizontal scrolling

## States

- Hover works
- Focus works
- Active works
- Loading/error/success states are handled where required

## Brand

- Correct logo
- Correct colors
- Correct visual tone

---

# 20. HIGH-END FINISHING PASS

After functionality is complete, perform one dedicated polish pass.

Look for opportunities to improve:

- Visual rhythm
- Alignment
- Typography
- Section transitions
- Button proportions
- Border subtlety
- Shadow quality
- Icon sizing
- Mobile spacing
- Empty states
- Loading states

Small refinements often separate an acceptable interface from a premium one.

---

# 21. HARD RULES

Never:

- Ship an obviously unfinished UI.
- Use generic default styling when better styling is possible.
- Ignore mobile.
- Ignore brand assets.
- Add random sections not requested by the user.
- Change important functionality merely for aesthetics.
- Use `transition-all`.
- Depend on default Tailwind blue/indigo as the brand identity.
- Make every section a rounded card.
- Use excessive gradients.
- Use excessive shadows.
- Stop after a single visual pass on major UI work.
- Claim visual accuracy without actually checking the rendered result when inspection tools are available.

---

# 22. FINAL STANDARD

The final interface should feel as though it was produced by:

- A senior UI designer
- A product designer
- A frontend engineer
- A UX specialist
- A visual QA reviewer

working together.

The standard is not:

> "It works."

The standard is:

> "It works beautifully, feels intentional, matches the brand, responds correctly on every screen size, and is ready for real users."
