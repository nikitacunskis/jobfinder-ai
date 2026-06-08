# Auto Search

## Trigger

```text
Auto Search: keyword=<input> candidate_profile_path=<input> workplace_type=<input> date_posted=<input> salary=<input> output_file=<input>
```

All arguments are required. Treat every value after an argument name as user input for this pipeline.

## Arguments

- `keyword`: Search query or role keyword, for example `Laravel developer`, `PHP`, or `technical lead`.
- `candidate_profile_path`: Path to the candidate profile file used for matching jobs against the candidate's skills and preferences.
- `workplace_type`: Desired work setup, for example `remote`, `hybrid`, `onsite`, or `any`.
- `date_posted`: Freshness filter for job postings, for example `past 24 hours`, `past week`, `past month`, or `any`.
- `salary`: Salary requirement or filter, for example `5000 EUR`, `at least 6000 EUR`, or `any`.
- `output_file`: File path where the search results should be saved.

## Execution

1. Validate that all required arguments are present.
2. Use `keyword`, `workplace_type`, `date_posted`, and `salary` as search filters.
3. Use `candidate_profile_path` as the matching profile context.
4. Search for matching vacancies and save results to `output_file`.

LinkedIn automation can lead to account restrictions or bans over time. Use an unused LinkedIn account for automation runs. A VPN, proxy, or another IP-hiding tool is recommended when running automated searches.

If any required argument is missing, stop execution and respond with the allowed prompt format and the example below.

## Example

```text
Auto Search: keyword=Laravel developer candidate_profile_path=/Users/nikita/git/personal/jobsucher/public/profile.json workplace_type=remote date_posted=past week salary=5000 EUR output_file=/Users/nikita/Desktop/jobsucher-auto-search.md
```
