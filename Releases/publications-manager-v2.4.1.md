# Publications Manager — v2.4.1

**Release date:** 2026-06-10
**Package:** `publications-manager-v2.4.1.zip`

## New features

- **File import** — new **Import from File** panel on **Publications → Import/Export** accepts the plugin's own **JSON, CSV, and BibTeX** exports. It imports the full field set (the inverse of the export, so an export → import round-trip preserves every field) and matches existing publications by **DOI → BibTeX key → slug** (matches are updated, the rest are created).
- **DOI text export** — new **DOIs (.txt, one per line)** export format that streams just the DOIs of the selected publications.
- **Author URL re-sync tool** — new **Re-sync Author URLs** button under **Publications → Tools → Settings**. After you change the Team CPT, it re-links each author to the matching team member in the current Team CPT (by name) and refreshes the cached `pm_author_team_url` used by Bricks Builder; links whose team member no longer exists are cleared.

## Fixes

- **BibTeX Key and Award no longer lost on save** — the editor saved the BibTeX Key under the wrong meta key (`pm_bibtex_key`) and never saved the Award field. Both now persist correctly.
- **Export/import now cover the full field set (DRY)** — all field definitions live in a single canonical registry (`PM_Fields`) read by save, REST, export (CSV/JSON/BibTeX), the custom-export checkboxes, and import. This guarantees JSON/CSV round-trip losslessly (BibTeX also round-trips within the plugin) and that the BibTeX citation key now uses the **BibTeX Key** field instead of the post slug.
- **"BibTeX Key" vs "Sort Key"** — the two distinct fields (`pm_bibtex` vs `pm_key`) now have unambiguous labels.
- **Crossref duplicate-key check fixed** — it now queries the correct meta key (`pm_bibtex`), so duplicate keys are detected.
- **"Author is required" save bug** — saving a publication no longer fails with "Author is required" when authors are present. Validation now checks the real author inputs; the BibTeX-key auto-suggest was repointed to them.
- **Imported publications always get a date** — file imports reconstruct `pm_date` from year/month (BibTeX has no full date) so imported publications appear and sort correctly in the admin list.
- **Admin list no longer hides date-less publications** — and the **Statistics** box no longer reports the grand total for every publication type.
- **Tools page label** — replaced the stale "How it works (v2.2.1):" heading.

## Translations

- Bumped catalog version to 2.4.1 and **completed all translations** (de, el, es, fr, it, pt): added every previously-missing string (new 2.4.x UI plus older un-extracted strings) and filled all remaining untranslated entries. All `.mo` files recompiled.

## Upgrade notes

Drop-in upgrade — no migration or configuration required.

- After upgrading, the **Import from File** panel and **DOIs (.txt)** export appear automatically on **Publications → Import/Export**.
- If you previously edited publications and the BibTeX Key or Award looked empty, re-open and re-save those publications to persist the values now that saving is fixed.
- If you changed your Team CPT, use **Tools → Settings → Re-sync Author URLs** to re-link authors and refresh the cached URLs.

## Breaking changes

None.
