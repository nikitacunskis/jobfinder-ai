# Jobsucher

Jobsucher is a Laravel and Filament CRM for reviewing the collected job dataset, tracking companies, logging outreach, and recording offers.

## Run

```bash
docker compose up -d --build
```

Open `http://localhost:8765`.

Seeded admin login:

```text
Email: nikita@cunskis.lv
Password: admin
```

## Data Flow

The canonical source files are:

- `public/profile.json`
- `public/jobs.json`

On container startup the app:

1. Runs Laravel migrations.
2. Imports profile skills from `public/profile.json`.
3. Imports companies and vacancies from `public/jobs.json`.
4. Rebuilds the Elasticsearch company search index.
5. Serves Filament on port `8765`.

If an existing database has the old pre-Laravel schema and no Laravel migrations have run, startup performs a one-time `migrate:fresh` and then imports from the canonical JSON files.

## Useful Commands

```bash
docker compose exec jobsucher php artisan jobsucher:import-profile public/profile.json
docker compose exec jobsucher php artisan jobsucher:import-jobs public/jobs.json
docker compose exec jobsucher php artisan jobsucher:reindex
docker compose exec jobsucher php artisan route:list
```

## Filament Areas

- `Companies`: searchable CRM list with statuses, counts, matching skill totals, and relation tabs for vacancies, contact history, and offer records.
- `Vacancies`: searchable job list with title, keyword, description, company, location, salary, matching skills, missing skills, all extracted skills, and full description detail.
- `Profile skills`: imported skill dictionary used by vacancy matching.

Company search uses Elasticsearch across company names, job titles, job descriptions, and skills. Vacancy search works across title, keyword, description, and skills.
