# Publications Manager — v2.3.2

**Release date:** TBD
**Package:** `publications-manager-v2.3.2.zip`

## Changes

- **Enhanced admin columns** — reordered and optimized publication list columns
- **Added Date column** — now displays full `pm_date` value in admin list
- **Improved column layout** — Title takes remaining space, Authors (max 20% / 300px), Type (max 12% / 150px), Date (max 8% / 100px)
- **Removed Year column** — replaced with more useful full Date column
- **Increased author display** — admin list now shows up to 5 authors before "et al."
- **Default sorting** — publications now automatically ordered by date (newest first)
- **Better column sorting** — all columns remain sortable with improved query handling

## Upgrade notes

Drop-in upgrade. Admin list view will re-render with the new column layout automatically.
