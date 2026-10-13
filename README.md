# TV Tracker

Self-hostable replacement for TV Time. Track what you're watching, get notified when a new episode airs.

- Track shows and movies, sourced from TMDB
- "Up Next" dashboard, a calendar view of upcoming air dates/releases, and a combined watch history
- Mark episodes/movies watched individually, or a whole run of episodes at once
- Email + web push notifications when a tracked show airs a new episode
- Installable as a PWA

## Local setup

```bash
cp .env.example .env
# fill in TMDB_API_KEY (free — see .env.example for the link)

docker compose build
docker compose up -d
```

The `app` container's entrypoint runs `app-bootstrap` on every start —
`composer install`, `migrate`, the front-end build, and (when
`APP_ENV=production`) `optimize`. No manual steps needed. To rebuild just the
assets after editing views/CSS/JS:

```bash
docker exec tv-tracker-app npm run build
```

## Deploying

On the server, from the repo root:

```bash
bash script/deploy.sh
```

Pulls `main`, brings the stack up to date, and checks `/up` — see the header
of `script/deploy.sh`.
