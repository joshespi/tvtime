# UI consistency refactors

Came out of a review of the restyle diff (brand palette, `.app-panel` / `.page-title` /
`.section-label` / `.media-title` component classes in `src/resources/css/app.css`).
Each section is independent unless noted. All paths are under `src/`.

---

## 1. Shared `.media-meta` class for card meta rows

**Why**: `.media-title` was extracted but the meta line under it was not. Five copies are already drifting (`gap-2` vs `gap-x-2 gap-y-1` vs `gap-y-0.5`, `text-xs` vs `text-sm`).

**Where**:
- `resources/views/components/catalog-result-card.blade.php` (meta `<p>` under the title)
- `resources/views/components/movie-card.blade.php`
- `resources/views/components/tracked-show-card.blade.php`
- `resources/views/components/show-episode-card.blade.php` (text-sm variant)
- `resources/views/history/index.blade.php`

**Proposed approach**: Add `.media-meta { @apply mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs tabular-nums text-gray-600 dark:text-gray-400; }` next to `.media-title`. Swap every copy over. show-episode-card adds `text-sm leading-snug` on top.

**Risks / open questions**: Tiny spacing shifts where gaps differ today. Check in light and dark mode.

**Suggested scope**: In: the 5 files and app.css. Out: the rating row (see 8).

---

## 2. `<x-page-section>` component

**Why**: `up-next.blade.php` hand-writes `<section aria-labelledby="X"><x-section-heading id="X">` three times. Each id is typed twice and has to match by hand.

**Where**: `resources/views/up-next.blade.php` (Episodes, Movies to Watch, Airing Soon).

**Proposed approach**: Add `components/page-section.blade.php` with props `id` and `title`, plus an optional `action` slot for links like "See all". It renders `<section aria-labelledby>`, the heading row and `space-y-3`.

**Risks / open questions**: Should other pages with section headings adopt it (profile/stats, shows/show)?

**Suggested scope**: In: the component and up-next. Out: other pages unless trivial.

---

## 3. Episode label slot on show-episode-card

**Why**: Both callers add `class="basis-full"` to the first span in the `meta` slot to put the episode label on its own line. To do that, every caller has to know the card's internal flex layout.

**Where**: `components/show-episode-card.blade.php`, `components/airing-soon-grid.blade.php`, `up-next.blade.php`.

**Proposed approach**: Add a `label` prop or named slot that the card renders on its own line above the `meta` row. Callers pass the label separately and drop `basis-full`.

**Risks / open questions**: None significant.

**Suggested scope**: The 3 files.

---

## 4. One neutral scale and theme tokens for hex colours

**Why**: The diff mixes `slate-*` (stock Tailwind) with `gray-*` (retinted in tailwind.config.js to mirror `config('app.theme_colors')`). The worst case is show-poster, where the placeholder uses `dark:bg-slate-700` and the img uses `dark:bg-gray-700`. Hard-coded hex values also sit outside the theme: `#e5efff` / `#1b2b49` in `.app-canvas`, and `dark:bg-[#1c2d4b]` on today's cell in the calendar (almost the same as `#1b2b49`).

**Where**: `tailwind.config.js`, `resources/css/app.css`, `views/calendar/index.blade.php`, `components/catalog-search.blade.php`, `components/collapsible-section.blade.php`, `components/show-poster.blade.php`, `.app-panel`.

**Proposed approach**: Decide whether slate stays as a second, cooler neutral or folds into gray. If it folds, replace `slate-*` with `gray-*` (or alias slate to the gray scale in config). Add named tokens for the glow and highlight colours (e.g. `surface-glow`, `surface-today`) and use them through `theme()` in CSS and as utilities in the calendar. While there, move the calendar day-cell bg ternary into a `@php $cellBg`.

**Risks / open questions**: This is a visible colour change. Compare screenshots before and after in both modes. Is slate used intentionally anywhere for contrast?

**Suggested scope**: In: neutral and hex cleanup in files the restyle touched. Out: a full palette redesign.

---

## 5. Card: named padding sizes, stale overrides, `.app-panel` placement

**Why**:
- Callers pass free-form padding strings (`p-3 sm:px-5`, `p-2 sm:p-3`, `px-6 py-6`, `px-6 py-10`), so panels look different from page to page.
- `welcome.blade.php` passes `class="rounded-xl"` to 3 cards. That silently overrides the new `rounded-2xl` because utilities beat `@layer components`.
- `.app-panel` has one consumer (card.blade.php), which already exists to centralise these classes. The card comment still mentions "a ring".

**Where**: `components/card.blade.php`, `resources/css/app.css`, `views/welcome.blade.php`, `views/calendar/index.blade.php`, `components/catalog-search.blade.php`, `views/donate.blade.php`, `components/empty-state.blade.php`, `views/shows/show.blade.php` (its summary's `rounded-xl` no longer matches).

**Proposed approach**: Give the `padding` prop named sizes (`compact|default|roomy|none`) that map to classes inside card.blade.php. Remove the stale `rounded-xl` overrides. Move the `.app-panel` utilities inline into card.blade.php and delete the class, unless something outside Blade needs it. Update the comment.

**Risks / open questions**: Some one-off paddings may be deliberate. Map each to the nearest named size and eyeball it.

**Suggested scope**: Card component and its callers.

---

## 6. Ghost button variant and `<x-text-input>` for search

**Why**: The "Not interested" button styles its tap height and focus by hand. `App\Support\ButtonStyle` exists to own those. The search input writes its full class string inline instead of using `<x-text-input>`, so the two already disagree (slate vs gray borders, `rounded-xl` vs `rounded-md`).

**Where**: `app/Support/ButtonStyle.php`, `components/action-button.blade.php`, `components/catalog-result-card.blade.php` ("Not interested"), `components/catalog-search.blade.php`, `components/text-input.blade.php`.

**Proposed approach**: Add a `ghost` (link-style) variant to `ButtonStyle::VARIANTS` and render `<x-action-button variant="ghost">`. Give text-input a size prop (or update its defaults) and use it in search, merging `h-12 ps-10 rounded-xl`. While there, replace the CSS-drawn search magnifier (a span plus an `after:` pseudo-element) with an inline heroicon SVG to match the other icons.

**Risks / open questions**: Changing text-input defaults affects every auth/profile form. A size prop is safer.

**Suggested scope**: ButtonStyle, the two components and their direct callers.

---

## 7. `<x-section-heading>` level prop; migrate old heading copies

**Why**: section-heading is now `h2.section-label`, but hand-rolled copies of the old small-caps `h3` style remain, so two heading styles now exist side by side. The component also hard-codes `h2`, even where it labels something inside a card (the calendar month).

**Where**: `components/section-heading.blade.php`, `components/cast-list.blade.php`, `views/profile/stats.blade.php`, `views/calendar/index.blade.php`.

**Proposed approach**: Add an `as` prop (default `h2`). Switch the old copies to `<x-section-heading>`. Check the heading outline on each page.

**Risks / open questions**: Visual change on stats and cast pages (small caps become the larger label). Confirm that's wanted.

**Suggested scope**: The listed files.

---

## 8. Shared rating/year meta row

**Why**: catalog-result-card rebuilds the year + rating row from `components/title-summary.blade.php`. The restyle gave only the catalog copy the amber star and the sr-only "Community rating" label, so a search result and its detail page now render the same data differently.

**Where**: `components/catalog-result-card.blade.php`, `components/title-summary.blade.php`.

**Proposed approach**: Extract `<x-title-meta :date :rating :type>` (or similar) and use it in both.

**Risks / open questions**: title-summary keeps `&middot;` separators. Pick one style.

**Suggested scope**: The two components.

---

## 9. show-poster size prop

**Why**: catalog-result-card overrides the poster with `w-16 h-24 sm:w-[4.5rem] sm:h-[6.75rem]`. That repeats the default size, skips the `18` spacing token added for posters, and recomputes the 2:3 ratio by hand.

**Where**: `components/show-poster.blade.php` and its callers (`w-12 h-18` appears in several).

**Proposed approach**: Add `size="sm|md|lg"`, or take a width and apply `aspect-[2/3]`.

**Risks / open questions**: None significant.

**Suggested scope**: show-poster and its callers.

---

## 10. Shared badge/pill style

**Why**: The count pill in collapsible-section, the countdown pill and media-type-badge each define their own pill. They now look visibly different side by side.

**Where**: `components/collapsible-section.blade.php`, `components/countdown.blade.php`, `components/media-type-badge.blade.php`.

**Proposed approach**: Add a `.badge` class (or `<x-badge tone>`) and use it in all three.

**Risks / open questions**: media-type-badge's colours come from `App\Support\MediaTypeStyle`. Keep that as the tone source.

**Suggested scope**: The 3 components.
