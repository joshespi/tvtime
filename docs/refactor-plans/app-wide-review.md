# App-wide review refactors (2026-11-26)

These came out of an `/espi-review` of the whole app. They were too big, too risky or too behaviour-changing to do as part of that cleanup pass. Each section stands alone unless it says otherwise. All paths are under `src/`.

The same pass already did a lot of small work, so the line numbers below may have moved:
- `Show::detailUrl()`.
- The `<x-episode-watched-button>`, `<x-remove-show-button>`, `<x-calendar-chip>`, `<x-genre-list>`, `<x-tracked-label>` and `<x-checkbox>` components.
- `.form-control` / `.eyebrow` and the `font-display` token.
- The slate alias removed.
- `WatchStatus::scopeWatched()`.
- `OwnedByUserPolicy`.
- `TitleNotFoundException::for()`.
- `resources/paper.json`.

---

## 1. `MediaType` enum

**Why**: `'show'|'movie'` strings and ternaries on them are spread across about 12 files, and nothing owns them. Adding a third type, or changing one type's wording, TMDB path or relation, means editing all of them. `MediaTypeStyle` is already half an enum. This was also deferred in `polish-plan.md`.

**Where**:
- `Http/Controllers/CatalogController.php` (the type branch)
- `SearchController.php` (`'in:show,movie'` validation and branch)
- `RecommendationController.php` (`'in:show,movie'`)
- `routes/web.php` (`'show|movie'` route constraint)
- `Services/Shows/TmdbClient.php` (`tv`↔`show`, `/tv` vs `/movie`, `name` vs `title`, `first_air_date` vs `release_date`)
- `Services/Catalog/CatalogSearchService.php` (`trackedByTmdbId($relation, 'movie', …)` relies on the type string matching the relation name)
- `RecommendationService.php`
- `Support/MediaTypeStyle.php`
- `views/components/track-button.blade.php` (labels)
- `layouts/bottom-tabs.blade.php`

**Proposed approach**: Add a backed enum `App\Support\MediaType { Show = 'show'; Movie = 'movie'; }`.
- It owns `tmdbPath()`, `fromTmdb('tv'|'movie')`, `titleKey()`, `dateKey()`, `relation()`, `indexRoute()`, `label()`, `badgeClasses()`, `trackLabel()` and `trackedLabel()`.
- Fold `MediaTypeStyle` into it.
- Use `Rule::enum` in validation and enum route binding for `catalog/{type}`.

**Risks / open questions**:
- Client-side code (the typeahead) still receives plain strings, which is fine.
- Route enum binding changes the 404 behaviour for bad types. Check `CatalogControllerTest`.

**Suggested scope**: In: the enum, folding `MediaTypeStyle` into it, validation, routes and TmdbClient mapping. Out: anything that needs a third type.

---

## 2. Shared Title contract and `TitleDetails` DTO

**Why**: Show and Movie share no interface, so callers work out the "release date" and "detail URL" for each type themselves. Show and Movie have already drifted; `detailUrl` was only just added to Show. The title detail header also has three data paths:
- `CatalogController::detailsFromMovie()` turns a `Movie` model back into a TMDB-shaped array.
- `shows/show.blade.php` passes scalars from `Show`.
- Untracked titles pass the raw TMDB array.

So every new field (the IMDb id, for example) has to be threaded through all three.

**Where**:
- `Models/Show.php`, `Models/Movie.php`
- `Http/Controllers/CatalogController.php` (`detailsFromMovie()` and its array-shape docblock)
- `views/components/catalog-detail.blade.php` (`$details['first_air_date'] ?? $details['release_date']`)
- `views/components/title-summary.blade.php`
- `views/shows/show.blade.php`
- `Services/Shows/TmdbClient.php` (`show()` / `movie()` payloads)

**Proposed approach**:
- Add a `Title` interface or trait with `detailUrl()`, `releaseDate()` and `posterUrl()`.
- Add a readonly `TitleDetails` value object with `fromModel(Show|Movie)` and `fromTmdb(array, MediaType)`.
- `<x-title-summary>` and `<x-catalog-detail>` take that one object.
- `TmdbClient` emits a normalised `date` key, as `mapResult()` already does.

**Risks / open questions**: Depends on #1 for `MediaType`, or a string can stand in until then. The cached TMDB payload shape has a `PAYLOAD_VERSION` key, so bump it if the shape changes.

**Suggested scope**: In: the DTO, the two header components and the three callers. Out: list cards (see #3).

---

## 3. `<x-media-card>` shell for every list card

**Why**: The poster, `media-title` link, meta row and action row are rebuilt in six places. The copies have already drifted:
- The restyle changed `show-poster`'s default size to `sm:w-20 sm:h-[7.5rem]`, but `movie-card` still passes the old `w-16 h-24`. On sm+ screens, movie cards now get smaller posters than show cards.
- The gaps differ (`gap-2` vs `gap-3 self-stretch`).
- The "★ x · Your rating" row is written three different ways.

**Where**:
- `views/components/movie-card.blade.php`
- `tracked-show-card.blade.php`
- `catalog-result-card.blade.php`
- `show-episode-card.blade.php`
- `title-summary.blade.php` (maybe)
- inline in `views/history/index.blade.php`

**Proposed approach**: Add `<x-media-card :href :poster-url :title poster-size="sm|md">` with `meta` and `actions` slots. The callers keep only their meta and action content. This absorbs these items from `ui-consistency.md`:
- #1 (`.media-meta`)
- #8 (the shared rating row; also add the copies in `movie-card` and `tracked-show-card`, and a `userRating` prop)
- #9 (the poster size prop)

**Risks / open questions**: Visual changes, so check light and dark mode at mobile and sm+. Decide whether the movie card's poster should match the show card's (it probably should).

**Suggested scope**: In: the 5 card files plus history. Out: the detail-page header (see #2).

---

## 4. Rename the `indigo` scale to `brand`

**Why**: `tailwind.config.js` remaps `indigo` to the brand burgundy, so about 100 `*-indigo-*` classes describe a colour that isn't indigo. Reviewers can't tell brand-coloured elements from ones that are coloured by accident. The `success` token has one user (`completion-check`), while about 24 sites use raw `emerald-*`.

**Where**: `tailwind.config.js`, `Support/ButtonStyle.php`, `resources/css/app.css` and every view (find them with `grep -rn "indigo-" resources app`).

**Proposed approach**:
- Add a `brand` scale and sed `indigo-` to `brand-` across views, CSS and `ButtonStyle`.
- Optionally keep `indigo` as an alias for one release.
- Do the same for `success`: make it a full scale, or alias it to emerald, and migrate the `emerald-*` sites.

**Risks / open questions**: Mechanical but wide, so it causes merge pain with any other UI branch. Do it when nothing else is in flight. Confirm the built CSS diff is empty apart from the class names.

**Suggested scope**: In: the rename only. Out: any colour value changes.

---

## 5. One nav registry for desktop and phone

**Why**: `layouts/navigation.blade.php` and `layouts/bottom-tabs.blade.php` each keep their own list of nav items, and their active-state rules have drifted:
- The bottom tabs light Dashboard on `airing-soon.*` and map `catalog.show` to Shows or Movies. The desktop nav does neither, so `/airing-soon` and `/catalog/*` highlight nothing on desktop.
- Active styling uses two mechanisms: `aria-current` utilities in `nav-link`, and PHP ternaries in `bottom-tabs` and `responsive-nav-link`.

**Where**:
- `views/layouts/navigation.blade.php`, `views/layouts/bottom-tabs.blade.php`
- `views/components/nav-link.blade.php`, `responsive-nav-link.blade.php`

**Proposed approach**:
- Add a `NavItems` list in `App\Support` or a view composer: label, route, icon, active patterns, and the catalog-type rule.
- All three navs render from it.
- Style the active state with `aria-[current=page]:` everywhere.

**Risks / open questions**: This changes behaviour, because desktop starts highlighting on more pages. Confirm that's wanted.

**Suggested scope**: In: the list, the 3 nav renderers and the shared active rule. Out: adding nav items.

---

## 6. Brand photo should respect `brand_assets`

**Why**: `asset('brand/cinema.webp')` is hard-coded in three places, while `application-logo` respects `config('app.brand_assets')`. That flag exists so that renamed self-hosted copies drop the TV Tracker artwork. A renamed copy keeps the stock cinema photo on the login, dashboard and landing pages.

**Where**: `views/layouts/guest.blade.php`, `views/up-next.blade.php` (the `.cinema-banner`) and `views/welcome.blade.php` (the hero).

**Proposed approach**: Add one `<x-brand-image>` (or `<x-cinema-banner>`) component that checks `brand_assets`. When it's off, fall back to a plain `bg-gray-900` panel with no `<img>`.

**Risks / open questions**: This changes behaviour for renamed installs. The welcome hero layout needs a sensible look without the photo.

**Suggested scope**: In: the component and the 3 call sites. Out: new artwork.

---

## 7. TMDB sync cost in the daily jobs

**Why**: Each daily run makes about 1 + S TMDB calls per active show and 1 per tracked movie, one after another. That includes long-ended shows, seasons that can't change and decade-old movies. For example, 50 shows averaging 6 seasons is about 350 calls a day, plus an upsert of every episode row. `synced_at` is always rewritten, so every movie row is written daily.

**Where**:
- `Services/Shows/TmdbClient.php` (season fetch)
- `Services/Shows/ShowSyncService.php` (`refreshEpisodes`)
- `Console/Commands/CheckNewEpisodes.php`
- `CheckMovieReleases.php`
- `Services/Concerns/MapsTmdbMetadata.php` (`synced_at`)
- `Services/Movies/MovieSyncService.php`
- `SearchController::store` (tracking a title always does a full fetch, even when the catalog row is fresh)

**Proposed approach**, in order of payoff:
1. Use `/tv/{id}?append_to_response=season/1,…,season/N` (up to 20 seasons), which folds the detail call and all season calls into one request. This is local to `TmdbClient` and doesn't change behaviour.
2. Only fetch seasons at or above the highest stored season, plus any new ones. Skip Ended or Canceled shows that were synced after they ended. This changes behaviour.
3. Movies: refresh only where `release_date` is null or within ±30 days, or `synced_at` is more than 7 days old.
4. Pool the detail fetches across shows and movies in chunks of about 10.
5. On track, reuse an existing fresh catalog row or `cachedDetails()` before doing a full fetch.
6. `refreshFinishedStatus()` only for shows that had something air today.

**Risks / open questions**:
- TMDB rate limits when pooling.
- Freshness trade-offs: metadata corrections on old titles would arrive weekly instead of daily.
- `BackfillCastAndTrailer` shares the same code path.

**Suggested scope**: In: steps 1 to 3 first, since they're the biggest win. Steps 4 to 6 are optional follow-ups.

---

## 8. Stream the history CSV export

**Why**: Both full histories (episodes and movies) and their relations are loaded into memory, mapped to arrays and sorted in PHP before the first byte is sent. Memory and time grow with how long the account has existed.

**Where**: `Http/Controllers/HistoryController.php` (`entries()` and the export action).

**Proposed approach**: One SQL `UNION ALL` (movie_watches ⋈ user_movies ⋈ movies, and watch_status ⋈ episodes ⋈ shows), ordered by `watched_at` and streamed with `lazy()` / `cursor()` into `response()->streamDownload`.

**Risks / open questions**: Keep the on-page history (which is limited) on the current path. Write the SQL so it works on both SQLite (tests) and MariaDB.

**Suggested scope**: In: the export only. Out: the history page.

---

## 9. Dashboard recommendation actions without a full reload

**Why**: Tracking a recommendation runs `location.reload()`, and dismissing one returns `back()`. Both re-run the whole dashboard (about 12 queries). `RecommendationService` re-randomises its seeds each time, so a single click can cost up to 10 pooled TMDB calls on a cache miss.

**Where**:
- `views/up-next.blade.php` (the `@tracked` handler)
- `Http/Controllers/RecommendationController.php` (`dismiss`)
- `Services/Catalog/RecommendationService.php`

**Proposed approach**: Either return JSON and remove the card client-side, or pin the seed sample per session so a reload hits the cache.

**Risks / open questions**: This changes behaviour: the shelf would no longer refill straight away. The no-JS path still needs to work.

**Suggested scope**: Pick one of the two approaches. Out: changing the recommendation algorithm.

---

## 10. Smaller structural items

These are each small, but worth doing together when touching the area:
- **Header slot suffix** (`layouts/app.blade.php`). The `header` slot is all-or-nothing, so `catalog-detail` copies `masthead page-title` just to add a badge. Add a `headingSuffix` slot rendered inside the default `<h1>`.
- **Row-action shape**. `HistoryController` emits `{label,url,method?,fields?,variant?}` and `CatalogSearchService` emits `{label,url,post}`, and each has its own view loop. Add one `<x-row-action :action>` and a single shape.
- **Notification subject passed twice**. `DedupedNotifier::notifyOnce(..., $notification, $subject)` takes the subject even though every notification already exposes `subject()`. Type the parameter as `Notification&TracksNotificationSubject` and derive it.
- **CatalogController tracked-movie lookup**. It takes 3 queries (EXISTS, then the eager `movie:id,tmdb_id`, then `load('movie')`) and can be 1–2.
- **Column selects**. These eager loads pull `overview` / `cast` / `trailer_key` that the views never render: `ShowController::index`, `UpNextController`, `CalendarController` and `HistoryController`. Selecting columns only is safe once accessors and casts are checked.
- **Season disclosure chevron**. `shows/show.blade.php` hand-rolls the chevron from `collapsible-section` (and uses a different rotation transition). Extract `<x-disclosure-chevron>`.
- **`BackfillCastAndTrailer`**. It's a one-off upgrade command (README:76) with its own concern and tests. Delete it, or turn it into a data migration, once self-hosters are past it.

---

## Notes on `ui-consistency.md`

- **#4**: the slate fold is done (the alias is removed and `slate-*` became `gray-*`), and the paper hex values now live in `resources/paper.json`. What's left is the `$cellBg` / theme-token part.
- **#5**: `layouts/guest.blade.php` also uses `.app-panel` directly. Switch it to `<x-card padding="none">` before deleting the class.
- **#7**: the profile partials now use `<x-section-heading>`. The `.eyebrow` class exists for the non-heading small-caps labels.

## Considered and dropped

- **Looping the 3 `shows/index` shelves.** The shelves differ too much (the first isn't collapsible, has a conditional heading and has an empty state).
- **Hoisting `today()` in the episode loop.** The gain is negligible for the API churn.
- **`detailUrl()` in `CatalogController` / `CatalogSearchService`.** These build the URL from `show_id` on purpose, to avoid loading the relation.
