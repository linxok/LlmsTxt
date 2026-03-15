# MyCompany_LlmsTxt

Magento 2 module that generates and serves `llms.txt` at the exact URL `/llms.txt`.

## Features

- Per-store configuration
- Exact `/llms.txt` route
- Physical file generation in `var/mycompany/llmstxt/`
- Manual priority links and optional links
- Automatic CMS page, category, and product inclusion
- Curated CMS/category/product selection
- Admin file status display
- Manual regenerate action from admin
- Automatic regeneration via cron
- Cache lifetime configuration
- Per-domain file generation
- Multi-language domain aggregation
- Language labels based on store locale

## Domain and Language Behavior

- Stores are grouped by the host from their `base_url`.
- Each host gets its own generated file in `var/mycompany/llmstxt/`.
- If a host has one enabled store view, the file contains only that store view content.
- If a host has multiple enabled store views, the file is generated once for the domain and includes:
  - a `Language Versions` section with one entry per language/store view
  - nested sections for each related store view on that domain
- Language labels are derived from the store locale and fall back to the store name if locale is unavailable.

## Installation

### Option 1: app/code

Copy the module to:

```bash
app/code/MyCompany/LlmsTxt
```

Then run:

```bash
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Option 2: Composer VCS repository

Add the repository to your project `composer.json` and require:

```bash
composer require mycompany/module-llms-txt
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuration

Go to:

`Stores -> Configuration -> MyCompany -> LLMS.txt`

Main settings:

- Enable LLMS.txt
- Site Title
- Short Summary
- Intro Text
- Include Categories Automatically
- Include Products Automatically
- Include CMS Pages Automatically
- Maximum Categories
- Maximum Products
- Maximum CMS Pages
- Featured Category IDs
- Featured Product SKUs
- Featured CMS Page Identifiers
- Manual Priority Links
- Optional Links
- Cache Lifetime (seconds)
- Enable Automatic File Generation
- Generation Frequency
- Day of Week
- Generation Time
- Generated File Status
- Regenerate Files

## Manual Link Format

Use one entry per line:

```text
Label|URL|Description
```

Description is optional.

## Notes

- The module creates physical files in `var/mycompany/llmstxt/`.
- `/llms.txt` serves the already generated file content as `text/plain; charset=UTF-8`.
- Files can be regenerated manually from admin or automatically by cron.
- Cron schedule is configured from admin using frequency, weekday, and time fields.
- For a host with language prefixes like `/uk/` and `/en/`, one domain-level `/llms.txt` is generated and includes both languages.
- For separate domains, each domain serves its own generated file.
