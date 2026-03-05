# Publications Manager - Architecture Overview

## Plugin Architecture (v2.0.4)

```
┌─────────────────────────────────────────────────────────────┐
│                  publications-manager.php                    │
│                    (Main Plugin File)                        │
│                                                              │
│  - Defines constants (PM_VERSION, PM_PLUGIN_DIR, etc.)      │
│  - Loads all components                                     │
│  - Initializes plugin on 'init' hook                        │
└──────────────────────────┬──────────────────────────────────┘
                           │
           ┌───────────────┴───────────────┐
           │                               │
    ┌──────▼──────┐              ┌────────▼────────┐
    │   CORE      │              │     ADMIN       │
    │ Components  │              │   Components    │
    └──────┬──────┘              └────────┬────────┘
           │                               │
           │                               │
    ┌──────▼─────────────────────┐  ┌─────▼──────────────────┐
    │                            │  │                        │
    │  class-publication-types   │  │   class-meta-boxes     │
    │  ─────────────────────     │  │   ─────────────────    │
    │  Defines all publication   │  │   Renders and saves    │
    │  types (article, book,     │  │   meta fields:         │
    │  conference, etc.)         │  │   - Authors (multiple) │
    │                            │  │   - Year, DOI, etc.    │
    └────────────────────────────┘  │   - jQuery handlers    │
                                    └────────────────────────┘
    ┌────────────────────────────┐  ┌────────────────────────┐
    │                            │  │                        │
    │    class-post-type         │  │    admin-pages         │
    │    ──────────────          │  │    ───────────         │
    │  Registers CPT:            │  │  Admin interface:      │
    │  - Custom columns          │  │  - Import/Export       │
    │  - Filters (Type,          │  │  - Settings page       │
    │    Authors, Year)          │  │  - Bulk actions        │
    │  - Sortable columns        │  │                        │
    │  - Default sorting         │  │                        │
    └────────────────────────────┘  └────────────────────────┘
```

## Integration Layer

```
┌────────────────────────────────────────────────────────────┐
│               INTEGRATIONS LAYER                           │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  ┌──────────────────────────┐  ┌────────────────────────┐ │
│  │  class-crossref-import   │  │ class-bricks-integration││
│  │  ──────────────────────  │  │ ───────────────────────│ │
│  │                          │  │                        │ │
│  │  Crossref API:           │  │  Bricks Builder:       │ │
│  │  - Import by DOI         │  │  - Dynamic data filters│ │
│  │  - Parse metadata        │  │  - Post meta filters   │ │
│  │  - Extract authors       │  │  - Query filters       │ │
│  │  - Create/update pubs    │  │  - Team member links   │ │
│  │                          │  │                        │ │
│  └──────────────────────────┘  └────────────────────────┘ │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## Helper Functions & Tools

```
┌────────────────────────────────────────────────────────────┐
│                   functions.php                            │
│                  (Core Helpers)                            │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  Team Member Relationships:                                │
│  ├─ pm_find_team_member_by_name()                         │
│  ├─ pm_create_team_relationship()                         │
│  └─ pm_process_author_relationships()                     │
│                                                            │
│  Author Display:                                           │
│  ├─ pm_get_authors_with_links()                           │
│  ├─ pm_format_authors()                                   │
│  └─ pm_parse_authors()                                    │
│                                                            │
│  Type Formatting:                                          │
│  └─ pm_get_formatted_type()                               │
│                                                            │
│  REST API:                                                 │
│  └─ pm_register_meta_fields_for_rest()                    │
│                                                            │
│  Shortcodes:                                               │
│  └─ [pm_authors]                                          │
│                                                            │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│                      TOOLS                                 │
├────────────────────────────────────────────────────────────┤
│  - debug.php (General debugging)                           │
│  - debug-connections.php (Relationship debugging)          │
│  - bulk-process.php (Batch operations)                     │
└────────────────────────────────────────────────────────────┘
```

## Data Flow

### 1. Saving a Publication

```
User saves publication
        │
        ▼
class-meta-boxes.php::save_meta_boxes()
        │
        ├─► Delete old pm_authors values
        ├─► Save new pm_authors (multiple meta entries)
        ├─► Extract pm_year from pm_date
        │
        ▼
save_post_publication hook triggers
        │
        ▼
pm_process_author_relationships()
        │
        ├─► Get all pm_authors
        ├─► For each author:
        │   ├─► Find team member by name
        │   ├─► Create bidirectional link
        │   └─► Store author link data
        │
        └─► Save pm_author_links meta
```

### 2. Displaying Authors in Bricks

```
Bricks renders {cf_pm_authors}
        │
        ▼
bricks/dynamic_data/post_meta filter
        │
        ▼
PM_Bricks_Integration::filter_post_meta()
        │
        ├─► Check if meta_key === 'pm_authors'
        │
        ▼
pm_get_authors_with_links()
        │
        ├─► Get pm_authors (array)
        ├─► Get pm_author_links (array)
        ├─► For each author:
        │   ├─► Check if linked to team member
        │   ├─► If yes: wrap in <a> tag
        │   └─► If no: plain text
        │
        └─► Return comma-separated HTML
```

### 3. Querying Publications on Team Member Page (Custom Query Loop)

```
Team member page loads with Bricks template
        │
        ▼
Query loop element uses "Team Member Publications" type
        │
        ▼
bricks/query/run filter (priority 10)
        │
        ▼
PM_Bricks_Integration::run_team_publications_query()
        │
        ├─► Get team member ID (from control or auto-detect)
        ├─► Find pm_author terms linked to team member
        │   ├─► Primary: get_post_meta(pm_author_term_id)
        │   └─► Fallback: query terms with pm_team_member_id meta
        │
        ▼
Build WP_Query with tax_query on pm_author
        │
        ├─► Apply orderby (pm_year, date, title, modified)
        ├─► Apply publication type filter if set
        └─► Return array of WP_Post objects
```

### 3b. Auto-Filtering Standard Publication Query on Team Member Page

```
Team member page loads with standard publication WP_Query
        │
        ▼
bricks/query/run filter (priority 15)
        │
        ▼
PM_Bricks_Integration::filter_team_publications_query()
        │
        ├─► Skip if custom query type (pm_team_publications)
        ├─► Check post_type === 'publication'
        ├─► Check is_singular(team_cpt_slug)
        │
        ▼
Find pm_author terms for team member
        │
        └─► Add tax_query to filter publications by author terms
```

## Meta Field Storage

### Publication Meta Fields

| Field           | Type   | Storage             | Example                    |
| --------------- | ------ | ------------------- | -------------------------- |
| pm_authors      | array  | Multiple entries    | ['John Doe', 'Jane Smith'] |
| pm_year         | string | Single              | '2024'                     |
| pm_type         | string | Single              | 'article'                  |
| pm_doi          | string | Single              | '10.1234/example'          |
| pm_journal      | string | Single              | 'Nature'                   |
| pm_date         | string | Single              | '2024-01-15'               |
| pm_team_members | array  | Single (serialized) | [123, 456]                 |
| pm_author_links | array  | Single (serialized) | {'John Doe': {...}}        |

### Team Member Meta Fields

| Field               | Type    | Storage          | Example                      |
| ------------------- | ------- | ---------------- | ---------------------------- |
| pm_publication_id   | integer | Multiple entries | [789, 790, 791]              |
| pm*publication*{id} | array   | Single           | {publication_id, title, url} |
| pm_name_variations  | string  | Single           | 'J. Doe, John D., Doe J.'    |

## Hooks & Filters

### Actions

- `init` - Initialize plugin components
- `save_post_publication` - Process author relationships
- `admin_enqueue_scripts` - Load admin CSS/JS

### Filters (Bricks Builder)

- `bricks/dynamic_data/render_content` - Filter rendered content
- `bricks/dynamic_data/render_tag` - Filter tag rendering
- `bricks/dynamic_data/post_meta` - Filter post meta values
- `bricks/dynamic_data/term_meta` - Filter term meta for author URLs
- `bricks/setup/control_options` - Register custom "Team Member Publications" query loop type
- `bricks/query/run` (priority 10) - Execute custom Team Member Publications query
- `bricks/query/run` (priority 15) - Auto-filter standard publication queries on team member pages
- `bricks/query/loop_object` - Set WP_Post as loop object in custom query
- `bricks/query/loop_object_id` - Set post ID for custom query loop iteration
- `bricks/elements/container/controls` - Add query controls to Container elements
- `bricks/elements/div/controls` - Add query controls to Div elements
- `bricks/elements/block/controls` - Add query controls to Block elements

## File Loading Order

1. `publications-manager.php` (main file)
2. `includes/core/class-publication-types.php`
3. `includes/core/class-post-type.php`
4. `includes/admin/class-meta-boxes.php`
5. `includes/admin/admin-pages.php`
6. `includes/integrations/class-crossref-import.php`
7. `includes/integrations/class-bricks-integration.php`
8. `includes/functions.php`

Then on `init` hook:

- PM_Publication_Types::register_all()
- PM_Post_Type::init()
- PM_Meta_Boxes::init()
- PM_Admin_Pages::init()
- PM_Bricks_Integration::init()

## Key Concepts

### Multiple Meta Values (pm_authors)

```php
// Saving
delete_post_meta($post_id, 'pm_authors');
foreach ($authors as $author) {
    add_post_meta($post_id, 'pm_authors', $author);
}

// Reading
$authors = get_post_meta($post_id, 'pm_authors', false); // Returns array
```

### Bidirectional Relationships

```
Publication 123          Team Member 456
├─ pm_team_members: [456]  ←──┐
└─ pm_author_links: {...}     │
                              │
                              │
                              └─ pm_publication_id: 123
                                 pm_publication_123: {data}
```

### Bricks Builder Query Loop Usage

Two ways to display team member publications in Bricks:

**Method 1: Custom Query Loop Type (Recommended)**

1. Add a Container/Div element with Query Loop enabled
2. Select query type: "Team Member Publications"
3. Controls available: Team Member ID, Per Page, Order By, Order, Type Filter
4. Use dynamic data: `{post_title}`, `{cf_pm_authors}`, `{cf_pm_year}`, `{cf_pm_type}`, `{cf_pm_doi}`

**Method 2: Standard WP_Query (Auto-filtered)**

1. Add a Container/Div element with Query Loop enabled
2. Use standard Posts query with post type "Publication"
3. On team member single templates, publications are auto-filtered

This architecture ensures clean separation of concerns, making the plugin maintainable and extensible.
