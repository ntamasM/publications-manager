# Translation Files Update Guide

The plugin now has many new translatable strings that need to be added to the .pot and .po files.

## New Strings Added

The following files have been updated with translation functions:

1. **tools/bulk-process.php** - Standalone bulk process tool (fully translated)
2. **includes/admin/admin-pages.php** - Settings, Bulk Process, and Analytics tabs (already had translations, added more)

### New Strings in bulk-process.php:

- "Publications Manager - Bulk Process"
- "Publications Manager - Bulk Process Relationships"
- "Team CPT Slug:"
- "Processing Complete!"
- "Publications processed successfully."
- "Publications with team member links:"
- "Results"
- "ID", "Publication Title", "Authors", "Terms", "Links Before", "Links After", "New Links"
- "Next Steps:"
- "Review the results above to ensure relationships were created correctly."
- "Check a few publication pages to verify author links are working."
- "Test your Bricks Builder Query Loops on team member pages."
- "Important:"
- "Delete this file (bulk-process.php) for security."
- "Go to Publications"
- "Go to Settings"
- "Configuration Required"
- "Please configure your Team CPT Slug first:"
- "Go to <strong>Publications → Settings</strong>"
- "Enter your Team CPT Slug (e.g., team_member)"
- "Click Save Settings"
- "Return to this page"
- "Current Configuration:"
- "Total Publications:"
- "Total Team Members:"
- "No Team Members Found"
- "No published posts found for post type: <strong>%s</strong>"
- "Please check:"
- "The Team CPT Slug is correct in Settings"
- "You have published team member posts"
- "Start Processing Publications"

### Updated Strings in admin-pages.php:

All the user-facing documentation sections were also updated with proper translation functions.

## How to Update Translation Files

### Option 1: Use WP-CLI (Recommended)

If you have WP-CLI installed:

```bash
cd c:\Users\Ntamas\Desktop\Personal\Wordpress\Wordpress-local\wp-content\plugins\publications-manager
wp i18n make-pot . languages/publications-manager.pot
wp i18n update-po languages/publications-manager.pot languages/
```

### Option 2: Use Poedit

1. Download and install **Poedit** (https://poedit.net/)
2. Open Poedit
3. Go to File → New from POT/PO file
4. Select `languages/publications-manager.pot`
5. For each language:
   - Open the existing .po file (e.g., `publications-manager-el.po`)
   - Go to Catalog → Update from POT file
   - Select `publications-manager.pot`
   - Translate the new strings
   - Save (this will also generate the .mo file)

### Option 3: Use Online Tools

1. Go to https://localise.biz/free/poeditor
2. Upload your .pot file
3. Extract strings
4. Update your .po files with the new strings

### Option 4: Use extract-strings.php Script

If you have PHP CLI available:

```bash
cd languages
php extract-strings.php
php compile-translations.php
```

This will:

1. Extract all translatable strings from the plugin
2. Generate a new .pot file
3. Update all existing .po files while preserving existing translations
4. Only add new untranslated strings

## Manual Update (Last Resort)

If none of the above tools are available, you can manually add the new strings to each .po file by copying the format from the .pot file:

```
#: tools/bulk-process.php:42
msgid "Publications Manager - Bulk Process"
msgstr ""
```

For each language, replace the empty `msgstr ""` with the translated text.

## Compiling .mo Files

After updating the .po files, you need to compile them to .mo format:

### Using compile-translations.php:

```bash
php languages/compile-translations.php
```

### Using Poedit:

Poedit automatically compiles .mo files when you save.

### Using msgfmt (if gettext tools are installed):

```bash
cd languages
msgfmt -o publications-manager-el.mo publications-manager-el.po
msgfmt -o publications-manager-es_ES.mo publications-manager-es_ES.po
# ... repeat for each language
```

## Testing

After updating translations:

1. Clear WordPress cache
2. Go to Dashboard → Settings → General
3. Change Site Language to test each translation
4. Visit Publications → Tools to see the translated admin pages
5. Run the bulk-process.php tool to see translations there

## Current Translation Status

Languages available:

- German (de_DE)
- Greek (el)
- Spanish (es_ES)
- French (fr_FR)
- Italian (it_IT)
- Portuguese Brazil (pt_BR)

**All these files need to be updated with the new strings added in this session.**
