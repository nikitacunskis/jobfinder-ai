# Skill Extractor

## Trigger

```text
Skill Extractor: lang=<input> path_to_proj=<input> field_of_work=<input> output=<input>
```

All arguments are required. Treat every value after an argument name as user input for this pipeline.

## Arguments

- `lang`: Output language for the extracted skills report, for example `en`, `lv`, or `ru`.
- `path_to_proj`: Absolute or relative path to the project that should be analyzed.
- `field_of_work`: Work domain or role context used to categorize skills, for example `Laravel/Filament job CRM`, `frontend`, `DevOps`, or `data engineering`.
- `output`: File path where the generated skill extraction result should be written.

## Execution

1. Validate that all required arguments are present.
2. Inspect the project at `path_to_proj`.
3. Extract skills relevant to `field_of_work`.
4. Write the result in `lang` to `output`.

If any required argument is missing, stop execution and respond with the allowed prompt format and the example below.

## Example

```text
Skill Extractor: lang=en path_to_proj=/Users/nikita/git/personal/jobsucher field_of_work=Laravel/Filament job CRM output=/Users/nikita/Desktop/jobsucher-skills.md
```
