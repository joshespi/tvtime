# TV Tracker — codebase polish pass

## Context
The app has grown feature by feature (shows → movies → rewatches → ratings → IMDb links) and has
never had a full sweep. Three read-only audits (backend `src/app`, frontend `resources/**`, and
tests/infra/docs) turned up: a handful of real bugs (phone overflow, Tailwind classes never
generated, a scheduler that notifies the evening *before* an episode airs), dead code from finished
experiments, drift between the parallel Show/Movie code paths, uneven button and a11y treatment,
and test-suite gaps (no stray-request guard, so tests can call the live TMDB key).

The goal is a cleaner, more consistent codebase with **no new features**. Work is split into
phases. Each phase is one or two commits that can ship independently, ordered so the riskier
visual changes come after the bug and dead-code fixes.

**Decisions already made with the user**
- Remove the `/client-errors` diagnostic beacon (force-close bug root-caused 2026-09-13).
- Remove the inert email-verification scaffolding.
- Unify all buttons on `App\Support\ButtonStyle`, and enlarge tap targets on phones.
- Run the scheduled jobs at 06:00 `America/Denver`.

**Working rules**
- The user commits by hand. At the end of each phase, give a caveman-style Conventional Commit
  message plus a space-delimited file list. Never run `git commit`.
- After Blade/JS/CSS edits: run `docker compose run --rm node sh -c "npm run build"` and
  `docker compose exec app php artisan view:clear`.
- Never use `@js()` inside `<x-component>` attributes. Use `{{ Js::from() }}` instead.
- All paths below are relative to `src/`.

---

## Phase 0: Finish the in-progress IMDb work (uncommitted)
Land the uncommitted IMDb-link changes first, so every later phase starts from a clean tree.
- `app/Services/Shows/TmdbClient.php`:
  - Fix the stale `DETAIL_APPEND` docblock (refreshes no longer pass an empty list).
  - Add a `REFRESH_APPEND = ['external_ids']` constant.
  - `ShowSyncService::refreshEpisodes()` should use that constant instead of the hardcoded array.
  - Drop `external_ids` from the movie detail fetch, which already gets `imdb_id` in its base
    payload.
- `app/Services/Concerns/RefreshesCastAndTrailer.php`: also save `imdb_id`, since the fetch
  already returns it. This way `app:backfill-cast-trailer` fills it for paused/dropped shows,
  which the daily refresh never touches.
- `resources/views/components/imdb-link.blade.php` and `trailer-link.blade.php`:
  - `@props(['imdbId' => null])` (likewise `trailerKey`).
  - Drop the redundant `variant="secondary"`.
  - Add a matching "↗"/icon so the pair looks alike.
- `resources/views/shows/show.blade.php`: move the "No dashboard entries…" caption below the
  action row so it doesn't wrap oddly on phones.
- `app/Support/ImdbLink.php`: fold `titleUrl`/`personUrl` into one private builder.
- Tests:
  - `ShowSyncServiceTest` / `MovieSyncServiceTest` assert that `imdb_id` is persisted.
  - `ShowControllerTest` asserts the link renders on `shows.show`.
  - Add `imdb_id` to `ShowFactory` / `MovieFactory`.

## Phase 1: Real bugs
**Frontend**
- `tailwind.config.js`: add `./resources/js/**/*.js` to `content`. Today `cursor-wait`, which
  `app.js` adds, is never generated.
- `resources/css/app.css`: add `[x-cloak]{display:none !important}`. `donate.blade.php` relies on
  it.
- Add `flex-wrap` to the button rows in `components/movie-card.blade.php:45`,
  `components/tracked-show-card.blade.php:32` and `catalog/movie.blade.php:8,15`. Three buttons
  overflow a 375px phone.
- `components/catalog-detail.blade.php:24`: "← Back" uses `url()->previous()`, which points at the
  page itself after a rating save. Use a fixed fallback route: `shows.index` / `movies.index` by
  type, or the dashboard for untracked titles.
- `resources/js/app.js`:
  - `sendJson()` callers (subscribe/unsubscribe ~L198/215) must check `response.ok` before
    flipping state.
  - The track button (~L101) must check `ok`/status before `response.json()`, and show
    "Session expired — reload" on 419.
  - `pushNotifications.init()`: add try/catch, plus a timeout on `serviceWorker.ready`.
- `profile/partials/update-notifications-form.blade.php`: hide the toggle when the VAPID public
  key is null (otherwise `atob(null)` throws).
- `donate.blade.php:28`:
  - Guard `navigator.clipboard`, since it is undefined over plain HTTP.
  - Set `copied` only in `.then()`.
- `manifest.blade.php`: use `@json(...)` for `name`/`short_name` instead of `{{ }}`, which
  HTML-escapes inside JSON.
- `shows/show.blade.php:97-105`:
  - The mark-through `confirm()` must abort on Cancel.
  - Move the inline `<script>` into `app.js`.
- `public/sw.js`: wrap `event.data.json()` in try/catch and add a default `badge`.

**Backend**
- `bootstrap/app.php`: both jobs become
  `->dailyAt('06:00')->timezone('America/Denver')->withoutOverlapping()`. The app timezone stays
  UTC, and `today()` in UTC equals the Denver date at 06:00 MT.
- `app/Services/Catalog/CatalogSearchService.php:28`: don't cache a failed search. Mirror what
  `RecommendationService` already does: TmdbClient returns `null` on failure, and that result is
  not cached.
- `app/Services/Shows/TmdbClient.php`:
  - `detail()` should log non-2xx responses, as `searchMulti()` already does.
  - `episodes()` should log failed season responses.
  - Make direct key reads (`$data['name']`, `$data['overview']` …) null-safe.
- `SearchController::store`: tell "TMDB unreachable" apart from "not found". Use either a
  distinct exception or a null-vs-failure return from `TmdbClient`, so users don't see "Could not
  find that show" during an outage.
- `MovieSyncService::refreshMetadata()`: log a warning on a failed fetch, matching
  `ShowSyncService`.
- `ShowController::updateStatus` (~L100-106):
  - Use the validated value rather than validating and then re-reading.
  - Call `refreshFinishedStatus()` after the update, so a manual Watching/Finished pick
    reconciles right away.
- `WatchStatusController::markSeason` (~L57): use `trackedOrFail()` (a 403 on writes) rather than
  `?? abort(404)`.
- `app/Models/Show.php:76`: `episodesWithWatchState()` sets fake attributes on Episode models.
  Return them as a wrapper or a keyed side-map instead, or at least document that these models
  must never be saved. Check whether any caller saves them first.
- Input bounds:
  - `PushSubscriptionController`: add `endpoint max:500`.
  - `RecommendationController`: `tmdb_id` gets `integer|min:1`.
  - `SearchController`: `external_id` gets `integer|min:1`.
  - `routes/web.php` `catalog.show`: add `->whereNumber('tmdbId')`.

## Phase 2: Dead code and leftovers
- **Client-error beacon**: delete the following.
  - `ClientErrorController`.
  - Its route plus the `use` line in `routes/web.php`.
  - The CSRF exception in `bootstrap/app.php`.
  - `reportClientError` and its two `window` listeners in `app.js` (~L273-305).
  - Keep `inBrowserTab()` if anything else uses it.
- **Email verification**: delete the following.
  - The verify routes in `routes/auth.php`.
  - `VerifyEmailController`, `EmailVerificationPromptController` and
    `EmailVerificationNotificationController`.
  - `auth/verify-email.blade.php` and `tests/Feature/Auth/EmailVerificationTest.php`.
  - The commented `MustVerifyEmail` import in `User.php`.
  - The `email_verified_at = null` reset in `ProfileController::update`.
  - The "unverified" block in `update-profile-information-form.blade.php`.
  - Adjust `ProfileTest`.
- `bootstrap/app.php`: remove the no-op `withExceptions` override. Laravel's default is already
  `expectsJson()`.
- Scaffolding:
  - Delete `routes/console.php` and the `commands:` line in `bootstrap/app.php`; commands are
    auto-discovered.
  - Delete the empty `AppServiceProvider::boot()` body and the default postmark, resend, ses and
    slack blocks in `config/services.php`.
  - Delete the Breeze `<!-- Email Address -->` HTML comments in the auth views and
    `layouts/app.blade.php`.
- Unreachable code:
  - The `tmdb_id` null guards in `RefreshesCastAndTrailer`, the sync services and
    `BackfillCastAndTrailer`.
  - Redundant `?? null` fallbacks on keys TmdbClient always sets.
  - The `?User` null branch in `CatalogSearchService::annotate()`.
- Unused code:
  - Relations `NotificationSent::subject()` and `DismissedRecommendation::user()`. Verify with
    grep first.
  - Make `GenreFilter::resolve/options/apply` private.
  - The redundant `Hash::make` alongside the `hashed` cast in the registration, password and
    new-password controllers.
- Stale comments:
  - `create_user_shows_table.php:15` (add `finished`).
  - `create_user_movies_table.php:15,20` (now `movie_watches`).
  - `[[WikiLinks]]` in `GenreFilter` / `MediaTypeStyle` docblocks become plain class names.
- `composer.json`:
  - Drop `laravel/breeze`, the `pestphp/pest-plugin` allow-plugin, the host-PHP `setup`/`dev`
    scripts and the sqlite `post-create-project-cmd`.
  - Add `"lint": "pint --test"` and `"format": "pint"`.
- `package.json`: drop the unused `@tailwindcss/vite` (v4). The app runs Tailwind v3 via PostCSS.
- `phpunit.xml`: drop the `PULSE_`, `TELESCOPE_` and `NIGHTWATCH_` env overrides.
- `.env.example` / `docker-compose.yml`: drop the dead `MAIL_ENCRYPTION`; config reads
  `MAIL_SCHEME`.

## Phase 3: Backend consistency
- **Show/Movie sync drift**:
  - Pull the shared attribute map out of `ShowSyncService::store()` / `MovieSyncService::store()`
    and their refresh updates into one helper on the existing
    `Services/Concerns/RefreshesCastAndTrailer` pattern (e.g. a `MapsTmdbMetadata` concern). Only
    the date column differs.
  - The refreshes should also update `title` and `poster_path`, which today are never refreshed
    after the first track.
- Merge `ShowNotFoundException` / `MovieNotFoundException` into one `TitleNotFoundException`, or
  keep both as subclasses of it.
- Add an `isWatched(Episode)` helper on `UserShow` next to `markWatched/markUnwatched`. Use it in
  `WatchStatusController::toggle` (~L22) instead of the inline query.
- `SearchController`:
  - Use `$request->user()->shows()->firstOrCreate(...)` (the relation idiom used elsewhere).
  - Reuse `CatalogSearchService`'s query normalisation.
  - Replace the bare `take(8)` with a named constant.
- Flash messages:
  - Add status flashes to `markThrough`, `MovieController::destroyWatch` and
    `RecommendationController::dismiss`.
  - Name the title in the "removed from your list" messages (`ShowController:122`,
    `MovieController:111`).
- `ProfileController`: `Redirect::` becomes `redirect()`.
- Types:
  - `SavesRatings` takes `UserShow|UserMovie`.
  - `DedupedNotifier` takes `User`.
  - Add types to the closure parameters in `RecommendationService` / `StatsService`.
- `UserMovie::watches()`: remove the built-in `orderByDesc` and order at the call sites that need
  it (the History and "last watched" reads). This drops a useless ORDER BY from every
  `withCount`/`whereHas` subquery.
- Move the `'w185'` / `'w92'` poster sizes and the `'/icon-192.png'` literal into constants on
  `TmdbImage`; notifications use them.
- `TmdbClient`:
  - Reorder: constants first, public methods, then private helpers.
  - In `CatalogSearchService`, move the constant above the constructor.
- `RecordNotificationSent:53`: map channel class to a label explicitly, instead of
  "not mail ⇒ push".
- `BackfillCastAndTrailer`: report rows that succeeded, not rows attempted.
- `bootstrap/app.php:16`: change `env('TRUSTED_PROXIES')` to `config('app.trusted_proxies')`, a
  new key in `config/app.php`.

*Out of scope, noted for later:*
- Renaming the `App\Services\Shows` → `App\Services\Tmdb` namespace.
- A `MediaType` enum for `'show'`/`'movie'`.
- Renaming `SearchController` → `TrackController`.
- Renaming `external_id` → `tmdb_id`.
- These touch many files for little gain; offer them as a follow-up session.

## Phase 4: Visual unification
- **Buttons** (`app/Support/ButtonStyle.php`):
  - Add `focus-visible:ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-gray-800` and
    `disabled:opacity-50 disabled:cursor-not-allowed` to `BASE`.
  - Make `BASE` about 40px tall on phones: `py-2.5 sm:py-2 text-sm sm:text-xs`, or `min-h-10` if
    the locked Tailwind is ≥3.4.
  - Add `danger`, `dark` (GitHub Sponsors) and an `lg` size, via `classes($variant, $size)`.
- Rewrite `primary-button`, `secondary-button` and `danger-button` to delegate to `ButtonStyle`.
  This drops Breeze's uppercase and `tracking-widest`.
- Replace hand-written button classes with `<x-action-button>` in:
  - `welcome.blade.php:19-31,51-65` (size lg).
  - `donate.blade.php:42` (dark).
  - The `navigation.blade.php:41` trigger.
- **Shared bits**:
  - `<x-status-banner>` gets an `error` variant, used by `catalog-search.blade.php:39`.
  - Extract `<x-saved-flash>` for the three duplicated "Saved." Alpine blocks in the profile
    partials.
  - The hand-rolled green status text in `update-profile-information-form` becomes
    `<x-auth-session-status>`.
- **Layout**:
  - Profile becomes `py-10 px-4`, like every other page.
  - Donate becomes `max-w-3xl`.
  - The layout header width should follow the page: pass a `$width` prop to `<x-app-layout>`, so
    detail pages' titles align with their `max-w-3xl` content.
  - `card.blade.php`: always `rounded-xl` (the pages already inset with `px-4`).
- Section headings: standardise on one size (`text-sm font-semibold uppercase tracking-wide`)
  across `up-next`, `profile/stats`, `cast-list`, `shows/index`, `movies/index` and
  `collapsible-section`.
- `up-next.blade.php`: rename the page title to "Dashboard" to match the nav and tabs, or rename
  the nav to "Up Next". Pick "Dashboard".
- `calendar/index.blade.php:23`: the legend swatches should use `MediaTypeStyle`'s classes.
- `dropdown.blade.php:31`: fix the invisible dark ring (`ring-opacity-5` applies in dark too).
- `media-type-badge`: remove the built-in `ms-1`; callers add spacing.
- Filter selects on `shows/index` and `movies/index`: add `flex-wrap`.
- `bottom-tabs.blade.php`:
  - The Dashboard tab also matches `airing-soon.*`.
  - Catalog pages highlight Shows or Movies by type.
- **Contrast**: sweep `text-gray-400` → `text-gray-500` and `dark:text-gray-500` →
  `dark:text-gray-400` for meta/caption text (~80 hits). Leave icons and placeholders alone.
  Drop the extra `opacity-60` on upcoming episodes in `shows/show.blade.php:55`, or keep it and
  use gray-500.
- Error pages:
  - `errors/layout.blade.php` uses `partials/head` (font, icons) and shows the logo and app name.
  - Add `errors/4xx.blade.php` and `5xx.blade.php` fallbacks.
  - On 419 the primary action becomes "Reload".
- `partials/head.blade.php`: use paired `theme-color` metas with `media="(prefers-color-scheme)"`,
  and have the theme toggle update them.
- Self-host Figtree, and drop the fonts.bunny.net link: it contradicts "no third-party
  connections" on the welcome and donate pages. Alternatively, soften that copy.

## Phase 5: Accessibility
- `catalog-search.blade.php`:
  - Add `aria-label` on the input.
  - Add combobox ARIA (`role=combobox/listbox/option`, `aria-expanded`, `aria-activedescendant`,
    driven from the existing Alpine `highlighted` index).
  - Add `alt=""` on the suggestion images.
- `navigation.blade.php`:
  - The hamburger gets `aria-label`, `aria-expanded` and `aria-controls`.
  - The logo links (also in `guest.blade.php`) get an `aria-label`.
  - The user dropdown gets `aria-haspopup` and `aria-expanded`, and Escape closes it.
  - Replace the bare `focus:outline-none` in `dropdown`/`nav-link` with `focus-visible` rings.
- `nav-link.blade.php`: add `aria-current="page"` when active, matching the bottom tabs.
- `calendar/index.blade.php:7,18`: give Prev/Next `sr-only` text on phones.
- `show-poster.blade.php`:
  - The poster link gets `tabindex="-1" aria-hidden="true"`, since it duplicates the title link.
  - Add `aria-hidden` on the placeholder SVGs (also in `cast-list`).
- `shows/show.blade.php:37-51`: move the "Mark season watched" form out of `<summary>` (no nested
  interactive content).
- `theme-toggle`: add `aria-pressed`.
- `modal`: add `role="dialog" aria-modal="true" aria-labelledby`.
- Donate QR canvas: add `role="img" aria-label`.
- Guest layout: pass a `$title` from each auth view so browser tabs are distinct.

## Phase 6: Tests
- `tests/TestCase.php`:
  - Call `Http::preventStrayRequests()` in `setUp()`.
  - Add `trackShow(User, Show)` / `trackedEpisode()` helpers.
  - Add a `FakesTmdb` trait with `fakeTmdbShow()` / `fakeTmdbMovie()`, built from the copies in
    `CheckNewEpisodesTest`, `ShowSyncServiceTest`, `SearchControllerTest`,
    `CheckMovieReleasesTest` and `MovieSyncServiceTest`.
- `phpunit.xml`: force `TMDB_API_KEY=test`, so a stray request can never use the real key.
- Split `tests/Feature/PolishTest.php` into the matching controller tests, then delete it:
  - Watch-status tests (incl. markSeason) → `WatchStatusControllerTest`.
  - Movie → `MovieControllerTest`.
  - Show → `ShowControllerTest`.
  - Dashboard → `UpNextControllerTest`.
  - History → `HistoryControllerTest`.
  - Calendar → `CalendarControllerTest`.
  - 404/419 → a new `ErrorPagesTest`.
- Remove the 11 bare `Http::fake()` calls in `UpNextControllerTest` (replace with one in `setUp`),
  and remove the redundant `image_base_url` config overrides (keep the trailing-slash case in
  `TmdbImageTest`).
- Add `DismissedRecommendation`: `HasFactory` + factory.
- New coverage:
  - `BackfillCastAndTrailer` command.
  - `RefreshesCastAndTrailer`.
  - `/manifest.webmanifest` (content type, escaped name).
  - `/donate` (BTC section hidden when blank).
  - `GenreFilter` and `WatchCount` units.
  - The Phase 1 fixes: `updateStatus` reconciles finished, search doesn't cache failures,
    markSeason 403, and the `catalog.show` numeric constraint.
- Run `vendor/bin/pint` once and commit the formatting on its own.

## Phase 7: Infra and docs
- `.env.example`:
  - `APP_URL=http://localhost:8085`, matching the nginx port.
  - Add `SEED_USER_PASSWORD`.
- `README.md`:
  - Add the APP_KEY step (`php artisan key:generate --show` → paste into root `.env`).
  - Update the feature list: movies and release notifications, recommendations, stats,
    ratings/notes, cast/trailer/IMDb, CSV export.
  - Explain how to run tests (`docker compose exec app php artisan test`).
  - Add VAPID setup (the tinker snippet, since `webpush:vapid` writes to the wrong `.env`).
  - Document the scheduled jobs and their 06:00 MT run time.
  - Document `app:backfill-cast-trailer`, the queue/scheduler containers and `DEPLOY_BRANCH`.
- `docker/php/entrypoint.sh` + `script/deploy.sh`: run the `chown` of `storage`/`bootstrap/cache`
  *after* `app-bootstrap` (or run bootstrap as www-data), so `laravel.log` can't end up
  root-owned.
- `script/deploy.sh`:
  - Use `flock` against two deploys running at once.
  - Parse the port with `${x##*:}` (IPv6-safe).
  - On a failed smoke test, print the rollback command (`git reset --hard $old && script/deploy.sh`).
- `docker/php/Dockerfile`:
  - Use `--no-install-recommends`.
  - Pin `composer:2`.
  - Drop `libxml2-dev`, `gd` and `exif` if grep confirms they are unused (check composer deps
    too, e.g. the QR or webpush libs).
- `docker-compose.yml`: pin the `nginx` image to a stable major tag.
- `composer.json`: align the `php` constraint with the 8.5 image (`config.platform.php`).

---

## Verification (after each phase)
1. `docker compose exec app php artisan test`: everything green. From Phase 6 on, any un-faked
   TMDB call fails loudly.
2. `docker compose exec app vendor/bin/pint --test`.
3. `docker compose run --rm node sh -c "npm ci && npm run build"`, then
   `docker compose exec app php artisan optimize:clear`.
4. Manual pass in the built-in browser at `http://localhost:8085`:
   - At 375px (mobile preset) and desktop, in light and dark mode.
   - Pages: dashboard, `/shows`, `/movies` (Finished shelf with three buttons: no overflow), show
     detail (season fold, mark-through Cancel aborts, IMDb + trailer buttons), catalog preview
     (Back link), calendar, history, profile (unified buttons, push toggle), donate (copy button),
     and a 404 / 419.
   - Keyboard-tab through the nav and search typeahead to check the focus rings.
5. Phase 1 scheduler: `docker compose exec app php artisan schedule:list` shows both jobs at
   06:00 America/Denver.
6. Phase 7: run `script/deploy.sh` against a scratch checkout, or dry-read it, and confirm
   `storage/logs` stays owned by www-data.
