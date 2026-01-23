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

### 3. Filtering Publications on Team Member Page

```
Team member page loads
        │
        ▼
Bricks query for publications
        │
        ▼
bricks/query/run filter
        │
        ▼
PM_Bricks_Integration::filter_team_publications_query()
        │
        ├─► Check if query is for publications
        ├─► Check if on team member page
        │
        ▼
Get pm_publication_id meta values
        │
        └─► Modify query: post__in = [publication IDs]
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
- `bricks/query/run` - Modify publication queries

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

This architecture ensures clean separation of concerns, making the plugin maintainable and extensible.
