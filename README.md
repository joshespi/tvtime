# TV Tracker

Self-hostable replacement for TV Time. Track what you're watching, get notified when a new episode airs or a movie is released.

- Track shows and movies, sourced from TMDB, with IMDb links, cast and trailers
- "Dashboard" of what to watch next, a calendar of upcoming air dates/releases, and a combined watch history
- Mark episodes/movies watched individually, a season, or a whole run of episodes at once
- Personal ratings and notes, plus rewatch tracking for movies
- Recommendations based on what you track (can be switched off per user)
- Viewing stats and a CSV export
- Email + web push notifications when a tracked show airs a new episode or a movie is released
- Installable as a PWA

## Local setup

```bash
cp .env.example .env
# fill in TMDB_API_KEY (free — see .env.example for the link)

# generate an app key and paste it into .env as APP_KEY=
docker compose run --rm app php artisan key:generate --show

docker compose build
docker compose up -d
```

The `app` container's entrypoint runs `app-bootstrap` on every start —
`composer install`, `migrate`, the front-end build, and (when
`APP_ENV=production`) `optimize`. No other manual steps needed. To rebuild just
the assets after editing views/CSS/JS:

```bash
docker exec tv-tracker-app npm run build
```

The app is served on <http://localhost:8085>.

## Tests and formatting

```bash
docker compose exec app php artisan test
docker compose exec app composer lint     # pint --test
docker compose exec app composer format   # pint
```

The suite blocks real HTTP (`Http::preventStrayRequests()`), so a test that
forgets to fake TMDB fails instead of calling it.

## Web push (optional)

`php artisan webpush:vapid` writes to `src/.env`, which doesn't exist in this
setup. Generate a keypair in tinker instead and paste the values into the root
`.env` (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, plus a `VAPID_SUBJECT` that is a
`mailto:` or `https:` URL):

```bash
docker compose exec app php artisan tinker
>>> Minishlink\WebPush\VAPID::createVapidKeys()
```

Without a public key the push toggle on the profile page is hidden.

## Background jobs

The stack runs two extra containers next to `app`:

- `scheduler` (`schedule:work`) fires the daily jobs, both at **06:00
  America/Denver** (the app timezone stays UTC):
  - `app:check-new-episodes` refreshes tracked shows and notifies about episodes airing today.
  - `app:check-movie-releases` refreshes tracked movies and notifies about releases today.
- `queue` (`queue:work`) delivers the mail and push notifications.

There is also a one-off command, `app:backfill-cast-trailer`, which fills
cast, trailer and IMDb ids for titles tracked before those columns existed
(including paused/dropped shows the daily refresh skips):

```bash
docker compose exec app php artisan app:backfill-cast-trailer
```

## Deploying

On the server, from the repo root:

```bash
bash script/deploy.sh
```

Pulls the `main` branch (override with `DEPLOY_BRANCH=other-branch`), brings the
stack up to date, restarts the queue workers and checks `/up` — see the header
of `script/deploy.sh`. Only one deploy runs at a time, and a failed health
check prints the command to roll back to the previous commit.
