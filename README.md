# Publications Manager

**Version:** 2.0.5  
**Author:** Ntamadakis  
**License:** GPL v2 or later

A modern WordPress plugin for managing academic publications with team member integration and advanced page builder support. Designed for research institutions, academic departments, and individual researchers who need to showcase publications with automatic author-to-team member linking.

## Key Features

### 📚 Publication Management

- **Custom Post Type**: Dedicated "Publications" post type
- **24 Publication Types**: Journal articles, books, conference papers, theses, and more
- **Multiple Authors**: Each author stored separately for better querying and filtering
- **Publication Year**: Automatically extracted from publication date
- **Rich Metadata**: DOI, journal, volume, pages, publisher, abstract, and more

### 👥 Team Member Integration

The plugin's standout feature is **automatic linking between publications and team members**:

- **Automatic Matching**: Authors are automatically matched to team members using name variations
- **Bidirectional Links**: Publications link to team members, and team members link to publications
- **Flexible Name Matching**: Supports multiple name variations (e.g., "John Doe", "J. Doe", "Doe, John")
- **Page Builder Ready**: Works seamlessly with Bricks Builder and other page builders

**How it works:**

1. Create team members (using any CPT - configurable via settings)
2. Add name variations to each team member (e.g., "Smith, J.", "John Smith", "J. Smith")
3. When saving a publication, authors are automatically matched to team members
4. Team member pages automatically show their publications
5. Publication author names become clickable links to team member profiles

### 🎨 Bricks Builder Integration

Full integration with Bricks Builder:

- **Dynamic Fields**: Display authors with automatic team member links
- **Formatted Types**: Publication types show as readable names (e.g., "Journal Article" instead of "article")
- **Query Filters**: Team member pages automatically filter publications
- **Custom Fields**: All publication fields available as dynamic data

### 🌐 Crossref Import

- Import publications directly from Crossref using DOI
- Batch import multiple publications at once
- Automatically populates all fields from Crossref metadata
- Authors automatically stored as separate values

### 📊 Admin Features

- **Filterable Columns**: Filter by publication type, author, or year
- **Sortable Columns**: Click column headers to sort
- **Smart Defaults**: Publications sorted by date (newest first)
- **Clean Interface**: Intuitive meta boxes with repeatable author fields
- **Import/Export**: Import from Crossref, export functionality ready

## Installation

1. Upload the `publications-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Publications" in the admin menu

## Usage

### Setting Up Team Members

Before adding publications, set up your team members for automatic linking:

1. **Configure Team Member CPT** (if not using default):
   - Go to **Publications > Settings**
   - Set the team member post type slug (default: `team_member`)

2. **Add Name Variations to Team Members**:
   - Edit each team member
   - Add a custom field: `pm_name_variations`
   - Enter all name variations, separated by commas
   - Example: `John Smith, Smith J., J. Smith, Smith, John`

### Adding Publications Manually

1. Go to **Publications > Add New**
2. Enter the publication title
3. Select the **Publication Type**
4. **Add Authors** (repeatable fields):
   - Click "Add Author" to add fields
   - Enter each author's name exactly as it appears in the publication
   - If the name matches a team member variation, it will be linked automatically
   - Click "Remove" to delete author fields
5. Fill in other fields:
   - **Publication Date**: Date of publication (format: YYYY-MM-DD)
   - **Publication Year**: Auto-filled from date
   - **DOI**, **Journal**, **Volume**, etc.
6. Publish

### Importing from Crossref

1. Go to **Publications > Import/Export**
2. Enter one or more DOIs (one per line):
   ```
   10.1038/nature12373
   10.1126/science.1259855
   ```
3. Click **Import Publications**
4. Authors are automatically saved as separate values
5. Edit publications to refine author matching if needed

### Using in Bricks Builder

**Display authors with team member links:**

```
{cf_pm_authors}
```

This will output: `<a href="/team/john-smith">John Smith</a>, Jane Doe, <a href="/team/mike-jones">Mike Jones</a>`

**Display formatted type:**

```
{cf_pm_type}
```

This will output: "Journal Article" instead of "article"

**Show publications on team member pages:**
Create a query loop for publications - it will automatically filter to show only that team member's publications. 4. Review the results and edit publications as needed

### Publication Types

The plugin includes all teachPress publication types with the same default fields:

- **article**: Journal Article (journal, volume, number, issue, pages)
- **book**: Book (volume, number, publisher, address, edition, series)
- **booklet**: Booklet (volume, address, howpublished)
- **collection**: Collection (booktitle, volume, pages, publisher, etc.)
- **conference**: Conference (booktitle, organization, publisher, etc.)
- **bachelorthesis**: Bachelor Thesis (address, school, techtype)
- **diplomathesis**: Diploma Thesis (address, school, techtype)
- **inbook**: Book Chapter (volume, pages, publisher, chapter, etc.)
- **incollection**: Book Section (volume, pages, publisher, etc.)
- **inproceedings**: Proceedings Article (booktitle, pages, organization, etc.)
- **manual**: Technical Manual (address, edition, organization, series)
- **mastersthesis**: Masters Thesis (address, school, techtype)
- **media**: Medium (publisher, address, howpublished)
- **misc**: Miscellaneous (howpublished)
- **online**: Online (howpublished)
- **patent**: Patent (howpublished)
- **periodical**: Periodical (howpublished)
- **phdthesis**: PhD Thesis (school, address)
- **presentation**: Presentation (howpublished, address)
- **proceedings**: Proceedings (organization, publisher, address)
- **techreport**: Technical Report (institution, address, techtype, number)
- **unpublished**: Unpublished (howpublished)
- **workingpaper**: Working Paper (howpublished)
- **workshop**: Workshop (booktitle, organization, address)

## Field Descriptions

### Required Fields

- **Title**: Publication title (set as post title)
- **Publication Type**: Type of publication
- **BibTeX Key**: Unique citation key
- **Authors**: List of authors (format: "Smith, John and Doe, Jane")
- **Publication Date**: Date of publication

### Recommended Fields (vary by type)

Automatically highlighted based on publication type selection

### Optional Fields

All other fields can be filled as needed

## Developer Notes

### Data Storage

- Uses WordPress Custom Post Types (CPT)
- All metadata stored in post meta (with `_pm_` prefix)
- No custom database tables required
- Compatible with standard WordPress queries

### Hooks & Filters

Available for extending functionality:

```php
// Modify publication types
add_filter('pm_publication_types', function($types) {
    // Add or modify types
   How It Works

### Author Matching System

When you save a publication:

1. **Authors are stored individually** (not as comma-separated string)
   - Each author gets their own meta entry: `pm_authors`
   - Stored with `single=false` for proper WordPress multiple values

2. **Team member matching runs automatically**:
   - Plugin searches all team members
   - Checks `pm_name_variations` custom field
   - Case-insensitive matching
   - Falls back to team member title if no variations

3. **Bidirectional relationships created**:
   - Publication stores: `pm_team_members` (array of IDs)
   - Publication stores: `pm_author_links` (URLs for each matched author)
   - Team member stores: `pm_publication_id` (individual entries)
   - Team member stores: `pm_publication_{id}` (publication data)

4. **Links appear automatically**:
   - In Bricks Builder dynamic fields
   - In admin columns
   - In shortcodes

### Technical Architecture

```

publications-manager/
├── publications-manager.php (Main plugin file)
├── includes/
│ ├── admin/
│ │ ├── class-meta-boxes.php (Meta boxes & field handling)
│ │ └── admin-pages.php (Import/export interface)
│ ├── core/
│ │ ├── class-post-type.php (CPT, columns, filters)
│ │ └── class-publication-types.php (Type definitions)
│ ├── integrations/
│ │ ├── class-crossref-import.php (Crossref API)
│ │ └── class-bricks-integration.php (Bricks filters)
│ └── functions.php (Core helper functions)
├── tools/ (Debug utilities)
└── assets/ (CSS & JS)

````

See [ARCHITECTURE.md](ARCHITECTURE.md) for complete technical documentation.

### Data Storage

**Publications (post type: `publication`)**
- `pm_authors` - Multiple meta entries (one per author)
- `pm_year` - Extracted from `pm_date`
- `pm_type` - Publication type slug
- `pm_team_members` - Array of linked team member IDs
- `pm_author_links` - Array mapping author names to URLs
- All other fields as standard post meta

**Team Members (configurable CPT)**
- `pm_name_variations` - Comma-separated name variations
- `pm_publication_id` - Multiple entries (one per linked publication)
- `pm_publication_{id}` - Individual publication data arrays

## Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.2+
- **teachPress Compatible**: Uses the same field structure and logic
Publications Manager is **inspired by teachPress** but takes a different approach to publication management.

### Similarities with teachPress ✅

- All 24 publication types (article, book, conference, etc.)
- Complete field set for academic publications
- Crossref import functionality
- BibTeX-compatible structure
- Same publication type definitions

### Key Differences ⚡

**Architecture:**
- ✅ Uses WordPress Custom Post Types (not custom database tables)
- ✅ Native WordPress admin interface
- ✅ Works with all page builders
- ✅ Standard WordPress queries
API & Shortcodes

### Shortcodes

**Display authors with links:**
```php
[pm_authors id="123"]  // Specific publication
[pm_authors]           // Current publication
````

### Helper Functions

```php
// Get authors with team member links (HTML)
$authors_html = pm_get_authors_with_links($post_id);

// Get formatted publication type name
$type_name = pm_get_formatted_type($post_id);

// Get all publications for a team member
$publications = pm_get_team_member_publications($team_member_id);

// Process author relationships manually
pm_process_author_relationships($post_id);
```

### REST API

All meta fields are registered for REST API access:

```
GET /wp-json/wp/v2/publication
GET /wp-json/wp/v2/publication/{id}
```

Fields available:

- `pm_authors` (array)
- `pm_type` (string)
- `pm_year` (string)
- `pm_doi` (string)
- And all other publication fieldsgration
- Want built-in citation export
- Need the author management interface
- Prefer a more mature, battle-tested soluper functions)
  ├── assets/
  │ ├── css/
  │ │ └── admin.css
  │ └── js/
  │ ├── admin.js
  │ └── import-export.js
  └── README.md

```

### Database Schema

All data is stored in WordPress post meta:

- Meta key format: `_pm_[field_name]`
- Example: `_pm_author`, `_pm_doi`, `_pm_bibtex`

## Comparison with teachPress

### Same as teachPress ✅

- All 24 publication types
- All publication fields
- Same field recommendations per type
- Same BibTeX structure
- Crossref import functionality
- Field-level compatibility

### Different from teachPress ⚡

- UseHow do I set up team member linking?**
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
A: Use Bricks Builder (recommended) or create custom templates using the helper functions and shortcodes

- **English** (default)
- **Greek (Ελληνικά)** - Complete translation
- **Spanish (Español)** - Complete translation
- **French (Français)** - Complete translation
- **German (Deutsch)** - Complete translation
- **Italian (Italiano)** - Complete translation
- **Portuguese (Português-BR)** - Complete translation

### Adding Your Language

1. **Using Loco Translate** (Recommended):
   - Install the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin
   - Go to **Loco Translate → Plugins → Publications Manager**
   - Create a new translation for your language
   - Save and you're done!

2. **Using Poedit**:
   - Download [Poedit](https://poedit.net/)
   - Open `languages/publications-manager.pot`
   - Create a translation for your language
   - Save as `publications-manager-{locale}.po` in the `/languages` folder

3. **Manual**:
   - See `/languages/README.md` for detailed instructions

The plugin follows the same translation approach as **teachPress**, prioritizing plugin translations over WordPress.org translations.

**Text Domain**: `publications-manager`
**Domain Path**: `/languages`

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
## License

GPL v2 or later - Same as teachPress

## Credits

Built based on the excellent [teachPress](https://github.com/winkm89/teachPress) plugin by Michael Winkler.
Crossref integration uses the Crossref REST API.

## Related Links

- **teachPress Plugin**: [https://github.com/winkm89/teachPress](https://github.com/winkm89/teachPress)
- **teachPress on WordPress.org**: [https://wordpress.org/plugins/teachpress/](https://wordpress.org/plugins/teachpress/)
- **Crossref REST API**: [https://www.crossref.org/documentation/retrieve-metadata/rest-api/](https://www.crossref.org/documentation/retrieve-metadata/rest-api/)
```
