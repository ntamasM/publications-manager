# Publications Manager — v2.3.3

**Release date:** TBD
**Package:** `publications-manager-v2.3.3.zip`

## Changes

- **Custom Bricks Query Loop Type** — added "Team Member Publications" as a dedicated query loop type in Bricks Builder
- **Query Loop Controls** — Team Member ID, Publications Per Page, Order By, Order, and Publication Type filter
- **Auto-detect team member** — on single team member pages, the query loop automatically fetches that member's publications
- **Fixed taxonomy-based query filtering** — `filter_team_publications_query()` now uses `pm_author` taxonomy (`tax_query`) instead of deprecated `pm_publication_id` meta
- **Loop object setup** — proper `setup_postdata()` so all dynamic data tags (`{post_title}`, `{cf_pm_*}`) work inside the custom query loop
- **Bricks version check** — integration only loads when Bricks Builder is active
- **Custom filter hook** — `pm/bricks/team_publications_query_args` allows developers to modify query args

## Upgrade notes

Requires Bricks Builder for the new query loop type. The integration silently skips loading if Bricks is not active.

**Note:** Version 2.3.4 was an internal-only release (no shipped zip). Its changes (simplified Bricks integration, `bricks/posts/query_vars` switch, etc.) are folded into v2.3.5.
