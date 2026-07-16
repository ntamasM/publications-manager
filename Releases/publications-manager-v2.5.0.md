# Publications Manager — v2.5.0

**Release date:** 2026-07-16
**Package:** `publications-manager-v2.5.0.zip`

## New features

- **Author order is now preserved** — authors display in the exact order they were entered or imported, instead of being alphabetized. Previously authors were stored only as `pm_author` taxonomy terms, which WordPress returns sorted by name; the intended order was lost on read. Order is now recorded per publication in a `pm_author_order` post meta (an ordered list of term IDs) and honored everywhere authors are shown.
- **Drag-and-drop author reordering** — the **Authors & Editors** meta box now has a drag handle on each author row (jQuery UI sortable, bundled with WordPress — no external dependency). Drag to reorder, then save with the normal **Update** button. Add/Remove behavior is unchanged.
- **Import order fidelity** — **File import** (JSON, CSV, BibTeX) and **Crossref import** (create and update) now record author order at import time, so an imported publication matches the order of its source.
- **Bricks Builder author ordering** — a new `bricks/terms/query_vars` filter makes a **Terms** query loop over the **Authors (Publication)** taxonomy follow the stored order when it runs on a single publication. It also scopes the loop to that publication's authors. Set the loop's **Order by** to **Include list** to make the intent explicit (the filter enforces it regardless).

## How it works

- A single central helper, `PM_Author_Taxonomy::get_ordered_author_terms()`, reconciles the terms actually assigned to a publication (authoritative set) with the stored order (ordering only). Renamed/deleted terms and duplicates self-heal, and publications with no saved order fall back to the previous alphabetical behavior.
- Order is written at every author-assignment site (meta-box save, file import, Crossref create/update) and read through the helper at every display site (frontend/shortcode, admin list column, exports, Bricks).

## Fixes

None — this release is purely additive.

## Translations

- Bumped catalog version to 2.5.0. No new user-facing strings require translation beyond existing coverage (the new meta-box hint and drag handle title reuse the standard i18n functions).

## Upgrade notes

Drop-in upgrade — no migration or configuration required.

- Existing publications keep their current (alphabetical) author order until you re-save or re-import them, at which point the entered/import order is captured. To set a custom order, open a publication, drag the authors into place, and click **Update**.
- **Bricks:** if you render authors with a Terms query loop over the Authors taxonomy on a single publication, set the loop's **Order by** to **Include list** so the stored order is used. Rendering authors via the `{post_meta:pm_authors}` dynamic tag / `[pm_authors]` shortcode already reflects the order with no changes.

## Breaking changes

None.
