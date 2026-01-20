# Publications Manager

A comprehensive WordPress plugin for managing academic publications using Custom Post Types. This plugin mirrors the functionality of teachPress but uses WordPress Custom Post Types instead of custom database tables, with full Crossref import support.

## Features

### 📚 Complete Publication Management

- **Custom Post Type**: "Publications" - No Gutenberg, classic editor interface
- **24 Publication Types**: All teachPress publication types included
  - Journal Articles, Books, Book Chapters, Conference Papers
  - Theses (Bachelor, Master, PhD, Diploma)
  - Technical Reports, Presentations, Workshops
  - And many more...

### 🔍 All teachPress Fields Included

The plugin includes **ALL** the same fields as teachPress:

**Core Fields:**

- Title, Type, BibTeX Key, Authors, Editors
- Publication Date, Award

**Publication Details:**

- Journal, Book Title, Issue Title
- Volume, Number, Issue, Pages, Chapter
- Publisher, Address, Edition, Series
- Institution, Organization, School

**Technical Fields:**

- DOI, ISBN/ISSN, URL
- How Published, Tech Type, Cross Reference, Key

**Content:**

- Abstract, Notes, Internal Comments

**Additional:**

- Cover Image URL, Related Page
- URL Access Date, Publication Status
- Import ID (for tracking imported publications)

### 🌐 Crossref Import

- **DOI-based Import**: Import publications directly from api.crossref.org
- **Batch Import**: Import multiple publications at once
- **Auto-fill Fields**: Automatically populates all available fields from Crossref data
- **Smart Mapping**: Automatically maps Crossref publication types to our types
- **Unique BibTeX Keys**: Automatically generates unique citation keys

### 📊 Dynamic Field Recommendations

- Fields are **highlighted as recommended** based on publication type
- Matches teachPress default field sets exactly
- Visual indicators show which fields are important for each publication type

### 📈 Import/Export Features

- **Import from Crossref**: Using DOI numbers
- **Export Formats**: BibTeX, CSV, JSON (ready for implementation)
- **Statistics Dashboard**: View publication counts by type
- **Batch Operations**: Import multiple publications simultaneously

## Installation

1. Upload the `publications-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Publications" in the admin menu

## Usage

### Adding a Publication Manually

1. Go to **Publications > Add New**
2. Enter the publication title
3. Select the **Publication Type** (this will highlight recommended fields)
4. Fill in required fields:
   - BibTeX Key (auto-suggested based on author and date)
   - Authors
   - Publication Date
5. Fill in recommended fields (highlighted in blue)
6. Add any additional details as needed
7. Publish

### Importing from Crossref

1. Go to **Publications > Import/Export**
2. Enter one or more DOIs in the text area (separated by spaces or new lines)
   - Example: `10.1038/nature12373`
   - Example: `10.1126/science.1259855`
3. Click **Import Publications**
4. Review the results and edit publications as needed

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
    return $types;
});

// Modify import data
add_filter('pm_crossref_import_data', function($data, $work) {
    // Modify imported data
    return $data;
}, 10, 2);
```

### Helper Functions

```php
// Get publication metadata
$author = pm_get_meta($post_id, 'author');

// Format authors
$formatted = pm_format_authors($author, 3); // Max 3 authors, then "et al."

// Get citation
$citation = pm_get_citation($post_id, 'apa');

// Display publication
echo pm_display_publication($post_id, array(
    'show_abstract' => true,
    'show_links' => true,
    'citation_style' => 'apa'
));
```

## Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.2+
- **teachPress Compatible**: Uses the same field structure and logic
- **Crossref API**: v1 REST API

## Technical Details

### File Structure

```
publications-manager/
├── publications-manager.php (Main plugin file)
├── includes/
│   ├── class-publication-types.php (Publication types registry)
│   ├── class-post-type.php (CPT registration)
│   ├── class-meta-boxes.php (Meta box handling)
│   ├── class-crossref-import.php (Crossref import)
│   ├── admin-pages.php (Admin interface)
│   └── functions.php (Helper functions)
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       ├── admin.js
│       └── import-export.js
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

- Uses WordPress CPT instead of custom tables
- No Gutenberg (classic editor only)
- Modern admin interface
- AJAX-powered import
- Better WordPress integration

## Translation Support

Publications Manager is **fully translatable** and ready for internationalization (i18n).

### Available Languages

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

## FAQ

**Q: Can I migrate from teachPress to this plugin?**
A: A migration tool can be developed. All fields are compatible.

**Q: Does it support BibTeX export?**
A: Export functionality structure is in place, ready for implementation.

**Q: Can I customize publication types?**
A: Yes, use the `pm_publication_types` filter to add custom types.

**Q: Is it compatible with the REST API?**
A: Yes, the CPT can be exposed to REST API if needed (currently disabled).

## Support

For issues, questions, or contributions, please contact the plugin author.

## License

GPL v2 or later - Same as teachPress

## Credits

Built based on the excellent teachPress plugin by Michael Winkler.
Crossref integration uses the Crossref REST API.
