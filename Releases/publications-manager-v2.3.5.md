# Publications Manager — v2.3.5

**Release date:** TBD
**Package:** `publications-manager-v2.3.5.zip`

## Changes

### From v2.3.4 (rolled into this release)

- **Simplified Bricks integration** — removed custom "Team Member Publications" query loop type in favor of standard WP_Query with auto-filtering
- **Auto-filter on team member pages** — any Bricks query loop for publications on a team member single page is automatically filtered via `tax_query` on `pm_author` taxonomy
- **Smart pm_url fallback** — `{cf_pm_url}` now returns: external URL → DOI link → permalink (always a valid URL)
- **Fixed link URL in loops** — `{cf_pm_url}` now works correctly in Bricks link URL fields via global `$post` fallback
- **Bricks editor preview support** — auto-detection works in the Bricks editor, not just on the frontend
- **Cleaner codebase** — removed ~250 lines of custom query loop code (controls, loop object handlers, custom query runner)

### v2.3.5 fixes

- **Fixed auto-filter hook** — switched from `bricks/query/run` to `bricks/posts/query_vars` which is the correct hook for modifying WP_Query args before execution (`bricks/query/run` only fires for non-post query types)
- **Fixed post_type detection** — now handles both string and array post_type values from Bricks
- **Improved team member detection** — uses `get_queried_object_id()` for more reliable ID retrieval on singular pages

## Upgrade notes

If you used the dedicated "Team Member Publications" query loop type from v2.3.3, replace it with a standard Bricks **Posts** query loop targeting the **Publication** post type. Auto-filtering kicks in on team member single pages with no extra configuration.
