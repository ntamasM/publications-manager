# Publications Manager — v2.3.6

**Release date:** TBD
**Package:** `publications-manager-v2.3.6.zip`

## Changes

- **New export feature** — added a working "Export Publications" backend handler that streams BibTeX, CSV, or JSON downloads, with optional filtering by publication type
- **BibTeX cite key** — uses the publication's post slug as the BibTeX cite key, with entry type derived from `bibtex_key_ext`
- **All Fields / Custom buttons** — Import/Export page now offers two export buttons: one exports every field, the other lets the user tick which fields to include via a collapsible checkbox panel (with Select all / Select none shortcuts)
- **Custom export validation** — Custom export rejects submissions with zero selected fields; `id` and `slug` are always preserved in CSV/JSON for record identity

## Upgrade notes

Drop-in upgrade. The new export controls appear automatically on **Publications → Import/Export**.
