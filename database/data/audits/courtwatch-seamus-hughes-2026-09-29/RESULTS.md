# Import results

Imported on September 29, 2026 with `php artisan prisoners:add-court-watch-prisoners`.

- All 36 audited people were added as published prisoner profiles.
- Every profile has one sourced custody case and a nonzero imprisonment counter.
- All unresolved allegations are described as allegations or pending charges.
- Profiles without a verified portrait retain a null photo field so the site's standard database placeholder is used.
- The import is idempotent: rerunning it updates the same slugs and replaces only their case row.
- `verify-import.php` checks database presence, case count, counter days, status flags, photo fallback, and the public HTTP response for every slug.

Research corrections made during the import:

- Ahmad Khalil Elshazly received 92 months on September 28, 2026, not the 25 years shown in the preliminary CSV.
- Gokhan Gun received 18 months on June 17, 2025.
- Abdulrahman Mohammed Hafedh Alqaysi received 12 years on October 9, 2025.
- Samir Ousman Alsheikh received 60 years on September 17, 2026.
- Clift Seferlis received 37 months on March 16, 2026.
