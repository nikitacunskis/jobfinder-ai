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

Scanning LinkedIn jobs requires Playwright MCP browser automation. The scanner output should be saved back into `public/jobs.json`, which remains the canonical Jobsucher dataset.

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

## Agent Prompts

`AGENTS.md` defines two exact prompt triggers. Use the full prompt shape and include every required argument. If an argument is missing, the agent must stop and ask for the allowed arguments instead of guessing.

### Skill Extractor

```text
Skill Extractor: lang=<input> path_to_proj=<input> field_of_work=<input> output=<input>
```

Arguments:

- `lang`: Output language for the extracted skill report, for example `en`, `lv`, or `ru`.
- `path_to_proj`: Absolute or relative path to the project that should be analyzed.
- `field_of_work`: Work domain or role context used to categorize the skills, for example `Laravel/Filament job CRM`, `frontend`, `DevOps`, or `data engineering`.
- `output`: File path where the generated skill extraction result should be written.

Example:

```text
Skill Extractor: lang=en path_to_proj=/Users/nikita/git/personal/jobsucher field_of_work=Laravel/Filament job CRM output=/Users/nikita/Desktop/jobsucher-skills.md
```

### Auto Search

```text
Auto Search: keyword=<input> candidate_profile_path=<input> workplace_type=<input> date_posted=<input> salary=<input> output_file=<input>
```

Arguments:

- `keyword`: Search query or role keyword, for example `Laravel developer`, `PHP`, or `technical lead`.
- `candidate_profile_path`: Path to the candidate profile file used for matching jobs against the candidate's skills and preferences.
- `workplace_type`: Desired work setup, for example `remote`, `hybrid`, `onsite`, or `any`.
- `date_posted`: Freshness filter for job postings, for example `past 24 hours`, `past week`, `past month`, or `any`.
- `salary`: Salary requirement or filter, for example `5000 EUR`, `at least 6000 EUR`, or `any`.
- `output_file`: File path where the search results should be saved.

Example:

```text
Auto Search: keyword=Laravel developer candidate_profile_path=/Users/nikita/git/personal/jobsucher/public/profile.json workplace_type=remote date_posted=past week salary=5000 EUR output_file=/Users/nikita/Desktop/jobsucher-auto-search.md
```

## Recommended Workflow

1. Run `Skill Extractor` on one or more projects. Save the extracted skill reports and use them as candidate profile context for job search.
2. Run `Auto Search` to collect job vacancies. Use an unused LinkedIn account because automated activity can lead to account restrictions or bans over time. A VPN, proxy, or another IP-hiding tool is recommended when running automation.
3. Import the collected information into the Laravel CRM. The canonical import targets are `public/profile.json` for profile skills and `public/jobs.json` for jobs.
4. Optional: ask Codex to review the jobs database table and add the best-fit vacancies to favorites.
5. Analyze the jobs and apply to the best-fit vacancies.

Treat Jobsucher as a CRM. Each company is a lead, and each vacancy is an opportunity attached to that lead. Use the CRM to add comments and log every interaction: offers, questions from the company, topics discussed, salary ranges provided, follow-up dates, and other context needed to manage the application process.

## Filament Areas

- `Companies`: searchable CRM list with statuses, counts, matching skill totals, and relation tabs for vacancies, contact history, and offer records.
- `Vacancies`: searchable job list with title, keyword, description, company, location, salary, matching skills, missing skills, all extracted skills, and full description detail.
- `Profile skills`: imported skill dictionary used by vacancy matching.

Company search uses Elasticsearch across company names, job titles, job descriptions, and skills. Vacancy search works across title, keyword, description, and skills.
