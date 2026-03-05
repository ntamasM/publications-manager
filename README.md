# Publications Manager

**Version:** 2.3.4  
**Author:** Ntamadakis  
**License:** GPL v2 or later

A modern WordPress plugin for managing academic publications with automatic author-to-team member linking and advanced page builder support. Designed for research institutions, academic departments, and individual researchers.

## Key Features

### 📚 Publication Management

- **Custom Post Type**: Dedicated "Publications" post type with full WordPress integration
- **24 Publication Types**: Journal articles, books, conference papers, theses, and more
- **Author Taxonomy**: Authors stored as custom taxonomy (`pm_author`) for:
  - Better querying and filtering
  - Seamless page builder integration
  - Individual author pages/archives
  - Term meta for team member links
- **Rich Metadata**: DOI, journal, volume, pages, publisher, abstract, ISBN, ISSN, keywords, and more
- **Automatic Year Extraction**: Publication year automatically extracted from post date

### 👥 Team Member Integration

The plugin's standout feature is **bidirectional linking between authors and team members**:

- **Author Taxonomy**: Each author is a reusable taxonomy term
- **Manual Linking**: Link author terms to team members via the Authors taxonomy admin
- **Bidirectional Relationships**:
  - Authors link to team member profiles
  - Team member pages show all their publications
- **Bricks Builder Ready**: Full integration via custom fields and query loops

**How it works:**

1. Configure your Team CPT slug in Publications → Tools
2. Create publications with authors (authors are saved as taxonomy terms)
3. In Publications → Authors, link each author term to a team member
4. Author names automatically become clickable links to team member pages
5. Team member pages show their publications via Bricks query loops

### 🎨 Bricks Builder Integration

Full integration with Bricks Builder for maximum design flexibility:

**Dynamic Fields Available:**

- `{post_meta:pm_type}` - Formatted publication type (e.g., "Journal Article")
- `{post_meta:pm_authors}` - Authors with automatic team member links
- `{term_meta:pm_author_team_url}` - Team member URL for an author term
- All publication meta fields (pm_doi, pm_journal, pm_volume, etc.)

**Query Loops:**

- Team member pages automatically show their publications
- Filter by publication type, year, or author
- Full support for Bricks' dynamic data system

### 🌐 Crossref Import

Import publications directly from Crossref using DOI:

- Batch import multiple DOIs at once
- Automatically populates all metadata fields
- Authors automatically created as taxonomy terms
- Smart field mapping for optimal data quality

### 📊 Admin Features

- **Filterable Columns**: Filter by publication type, author, or year
- **Sortable Columns**: Click column headers to sort
- **Smart Defaults**: Publications sorted by date (newest first)
- **Clean Interface**: Intuitive meta boxes with repeatable author fields
- **Import/Export**: Import from Crossref, export functionality ready

## Installation

1. Upload the `publications-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Publications → Tools and configure your Team CPT slug
4. Start creating publications!

## Quick Start Guide

### 1. Configure Team CPT Slug

1. Go to **Publications → Tools**
2. Enter your Team CPT Slug (e.g., `team_member`, `staff`, `researcher`)
3. Save settings

### 2. Create Publications

1. Go to **Publications → Add New**
2. Enter the publication title and content
3. Select the **Publication Type** from the dropdown
4. **Add Authors** using the repeatable fields:
   - Enter each author's full name
   - Click "Add Author" for additional authors
   - Authors are automatically saved as taxonomy terms
5. Fill in metadata fields (DOI, Journal, Volume, Pages, etc.)
6. Set the publication date (year is auto-extracted)
7. Publish

### 3. Link Authors to Team Members

1. Go to **Publications → Authors**
2. Click "Edit" on an author term
3. Select the linked team member from the dropdown
4. Save - the plugin will automatically store the team member URL
5. Author names will now link to team member pages on your site

### 4. Import from Crossref (Optional)

1. Go to **Publications → Import/Export**
2. Enter DOI(s) in the textarea (one per line or space-separated)
3. Click **Import Publications**
4. Authors are automatically created as taxonomy terms
5. Link authors to team members as described in step 3

## Bricks Builder Usage

### Display Authors with Links

In any Bricks element:

```
{post_meta:pm_authors}
```

Output: `<a href="/team/john-smith">John Smith</a>, Jane Doe`

### Get Author Term URL

When looping through author terms:

```
{term_meta:pm_author_team_url}
```

Returns the team member permalink for the current author.

### Display Publication Fields

All fields available as dynamic data:

- `{post_meta:pm_type}` - Formatted type name
- `{post_meta:pm_doi}` - DOI
- `{post_meta:pm_journal}` - Journal name
- `{post_meta:pm_volume}` - Volume
- `{post_meta:pm_pages}` - Pages
- `{post_meta:pm_year}` - Year
- `{post_meta:pm_abstract}` - Abstract

### Team Member Publications

On a team member single template, simply add a standard **Posts** query loop for the **Publication** post type. The plugin automatically filters to show only that team member's publications — no special configuration needed.

1. Add a Container/Div with **Query Loop** enabled
2. Set post type to **Publication**
3. Use Bricks' built-in controls for per page, order, pagination
4. Inside the loop use: `{post_title}`, `{cf_pm_url}` (link URL), `{cf_pm_type}`, `{cf_pm_year}`, etc.

**How it works:** The plugin detects you're on a team member page, finds all `pm_author` taxonomy terms linked to that member, and adds a taxonomy filter so only their publications appear.

## Developer Information

### Database Schema

**Post Meta (Publications)**

- `pm_type` - Publication type slug
- `pm_doi`, `pm_journal`, `pm_volume`, `pm_pages`, etc. - Publication fields
- `pm_year` - Auto-extracted from post date
- `pm_abstract` - Abstract text

**Term Meta (Author Taxonomy)**

- `pm_team_member_id` - Linked team member post ID
- `pm_author_team_url` - Team member permalink (cached for performance)

**Post Meta (Team Members)**

- `pm_author_term_id` - Connected author term IDs (bidirectional link)

### Filters & Hooks

**Bricks Builder Filters:**

- `bricks/dynamic_data/render_content` - Filters pm_type, pm_authors, pm_url output
- `bricks/dynamic_data/render_tag` - Filters dynamic tag rendering
- `bricks/dynamic_data/post_meta` - Filters post meta values (with global $post fallback for loops)
- `bricks/dynamic_data/term_meta` - Filters author term meta
- `bricks/posts/query_vars` - Auto-filters publication queries on team member pages via tax_query (before WP_Query)

### Helper Functions

```php
// Get formatted publication type name
$type = pm_get_formatted_type($post_id);

// Find team member by author name
$team_member_id = pm_find_team_member_by_name($author_name);

// Get authors HTML with team member links
$authors_html = PM_Author_Taxonomy::get_authors_html($post_id);

// Get team member URL for an author term
$url = PM_Author_Taxonomy::get_author_team_url($term_id);
```

### Shortcodes

```php
// Display authors for current or specific publication
[pm_authors]
[pm_authors id="123"]
```

## Publication Types

The plugin includes all teachPress publication types:

- **article**: Journal Article
- **book**: Book
- **booklet**: Booklet
- **collection**: Collection
- **conference**: Conference Paper
- **bachelorthesis**: Bachelor Thesis
- **diplomathesis**: Diploma Thesis
- **inbook**: Book Chapter
- **incollection**: Book Section
- **inproceedings**: Proceedings Article
- **manual**: Technical Manual
- **mastersthesis**: Masters Thesis
- **media**: Medium
- **misc**: Miscellaneous
- **online**: Online Publication
- **patent**: Patent
- **periodical**: Periodical
- **phdthesis**: PhD Thesis
- **presentation**: Presentation
- **proceedings**: Proceedings
- **techreport**: Technical Report
- **unpublished**: Unpublished Work
- **workingpaper**: Working Paper
- **workshop**: Workshop

## Changelog

### 2.3.5 (Current)

- **Fixed auto-filter hook** — switched from `bricks/query/run` to `bricks/posts/query_vars` which is the correct hook for modifying WP_Query args before execution (`bricks/query/run` only fires for non-post query types)
- **Fixed post_type detection** — now handles both string and array post_type values from Bricks
- **Improved team member detection** — uses `get_queried_object_id()` for more reliable ID retrieval on singular pages

### 2.3.4

- **Simplified Bricks integration** — removed custom "Team Member Publications" query loop type in favor of standard WP_Query with auto-filtering
- **Auto-filter on team member pages** — any Bricks query loop for publications on a team member single page is automatically filtered via `tax_query` on `pm_author` taxonomy
- **Smart pm_url fallback** — `{cf_pm_url}` now returns: external URL → DOI link → permalink (always a valid URL)
- **Fixed link URL in loops** — `{cf_pm_url}` now works correctly in Bricks link URL fields via global `$post` fallback
- **Bricks editor preview support** — auto-detection works in the Bricks editor, not just on the frontend
- **Cleaner codebase** — removed ~250 lines of custom query loop code (controls, loop object handlers, custom query runner)

### 2.3.3

- **Custom Bricks Query Loop Type** — added "Team Member Publications" as a dedicated query loop type in Bricks Builder
- **Query Loop Controls** — Team Member ID, Publications Per Page, Order By, Order, and Publication Type filter
- **Auto-detect team member** — on single team member pages, the query loop automatically fetches that member's publications
- **Fixed taxonomy-based query filtering** — `filter_team_publications_query()` now uses `pm_author` taxonomy (`tax_query`) instead of deprecated `pm_publication_id` meta
- **Loop object setup** — proper `setup_postdata()` so all dynamic data tags (`{post_title}`, `{cf_pm_*}`) work inside the custom query loop
- **Bricks version check** — integration only loads when Bricks Builder is active
- **Custom filter hook** — `pm/bricks/team_publications_query_args` allows developers to modify query args

### 2.3.2

- **Enhanced admin columns** - reordered and optimized publication list columns
- **Added Date column** - now displays full pm_date value in admin list
- **Improved column layout** - Title takes remaining space, Authors (max 20%/300px), Type (max 12%/150px), Date (max 8%/100px)
- **Removed Year column** - replaced with more useful full Date column
- **Increased author display** - admin list now shows up to 5 authors before "et al."
- **Default sorting** - publications now automatically ordered by date (newest first)
- **Better column sorting** - all columns remain sortable with improved query handling

### 2.3.1

- **Fixed translation loading timing** - resolved WordPress 6.7+ compatibility warnings
- **Improved plugin initialization** - translations now load in constructor for early availability
- **Code optimization** - streamlined hook registration and initialization process

### 2.3.0

- **Removed deprecated Bulk Process feature** - manual linking via Authors taxonomy is more reliable
- **Cleaned up translation files** - reduced from 348 to 248 unique strings
- **Updated uninstall process** - removed deprecated meta field references
- **Code cleanup** - removed old bulk processing functions and references
- **Translation improvements** - fixed Greek and other language files
- **Performance improvements** - streamlined codebase by removing unused features

### 2.2.1

- Fixed bulk process link counting and display
- Improved team member matching algorithm in bulk process
- Added old data migration from `pm_authors` meta to taxonomy
- Added "Terms" column to bulk process results
- Deprecated old relationship functions (now use taxonomy system)
- Updated documentation and README
- Cleaned up deprecated code

### 2.2.0

- Changed term meta field from `cf_author_team_url` to `pm_author_team_url`
- Added `cf_pm_author_team_url` Bricks Builder accessor for consistency
- Updated Bricks integration for proper term meta handling
- Renamed "Settings and Info" menu to "Tools"
- Improved author URL migration system with transient flag
- Added migration from v2.1 to ensure all term meta is updated

### 2.1.0

- **Major Update**: Migrated from `pm_authors` meta field to `pm_author` custom taxonomy
- Added author taxonomy with full term meta support
- Implemented manual author-to-team member linking via WordPress admin
- Added bidirectional relationship tracking between authors and team members
- Removed automatic name-based matching (now manual via Authors admin)
- Updated Bricks Builder integration for taxonomy support
- Added term meta fields for storing team member links and URLs
- Improved admin UI with custom columns for linked team members

### 2.0.4

- Added Bricks Builder integration with dynamic data filters
- Improved team member relationship handling
- Added REST API support for all meta fields
- Enhanced query filtering on team member pages

### 1.0.0

- Initial release with CPT-based architecture
- 24 publication types compatible with teachPress
- Crossref import functionality
- Basic team member integration

## Tools & Utilities

### Bulk Process Tool

Located in `tools/bulk-process.php` - processes all publications to create/update author-team member relationships.

**Features:**

- Migrates old `pm_authors` meta to taxonomy terms
- Matches author names to team member titles
- Creates bidirectional links
- Shows detailed before/after statistics

**Usage:**

1. Access via: `your-site.com/wp-content/plugins/publications-manager/tools/bulk-process.php`
2. Requires admin privileges
3. Delete after use for security

### Debug Tools

- `tools/debug.php` - General plugin debugging
- `tools/debug-connections.php` - Debug author-team member connections

## FAQ

**Q: How do I set up team member linking?**
A: Add a custom field `pm_name_variations` to each team member with comma-separated name variations. The plugin handles the rest automatically.

**Q: Can I use a different team member post type?**
A: Yes! Go to Publications > Settings and change the team member CPT slug.

**Q: Why don't authors link automatically?**
A: Make sure the author name in the publication exactly matches one of the name variations in the team member's `pm_name_variations` field.

**Q: Can I use this with Elementor/other page builders?**
A: Yes, all fields are registered for REST API. Bricks Builder has dedicated integration. Other builders should work with dynamic fields.

**Q: Can I migrate from teachPress?**
A: A migration tool would need to be developed. The fields are compatible, but the storage structure is different.

**Q: Does it work with Gutenberg?**
A: The publication post type uses the classic editor for better control over meta fields.

**Q: How do I display publications on my site?**
A: Use Bricks Builder (recommended) or create custom templates using the helper functions and shortcodes.

## Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Bricks Builder**: Full integration
- **Other Page Builders**: Compatible via REST API

## About

### Author & Development

**Publications Manager** is developed by **Ntamadakis** as part of the [ntamadakis.gr](https://ntamadakis.gr) program - a collection of WordPress tools and plugins designed for academic institutions and research organizations.

The ntamadakis.gr program focuses on creating practical, well-architected WordPress solutions that bridge the gap between academic needs and modern web development practices.

### Support the Project

If you find this plugin useful, please consider supporting its development:

**[Support Me](https://ntamadakis.gr/support-me)** ☕

Your support helps maintain and improve this plugin, add new features, and develop more tools for the WordPress academic community.

## Credits & Inspiration

This plugin is **inspired by the excellent [teachPress](https://wordpress.org/plugins/teachpress/)** plugin by Michael Winkler.

**teachPress** has been the go-to solution for academic publication management in WordPress for many years. Publications Manager takes a different architectural approach (Custom Post Types vs. custom tables) while maintaining compatibility with teachPress's comprehensive field structure and publication types.

### Key Differences from teachPress

While teachPress focuses on simplicity and standalone functionality, Publications Manager focuses on:

- Deep integration with modern page builders
- Team member relationship management
- WordPress-native architecture
- Advanced admin filtering and sorting

Both plugins are excellent choices depending on your specific needs. See the comparison section above for details.

### Related Resources

- **teachPress**: [https://wordpress.org/plugins/teachpress/](https://wordpress.org/plugins/teachpress/)
- **teachPress GitHub**: [https://github.com/winkm89/teachPress](https://github.com/winkm89/teachPress)
- **Crossref REST API**: [https://www.crossref.org/documentation/retrieve-metadata/rest-api/](https://www.crossref.org/documentation/retrieve-metadata/rest-api/)
- **Bricks Builder**: [https://bricksbuilder.io/](https://bricksbuilder.io/)

## License

GPL v2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
