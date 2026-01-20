# Translations for Publications Manager

This directory contains translation files for the Publications Manager plugin.

## File Structure

- `publications-manager.pot` - Template file containing all translatable strings
- `publications-manager-{locale}.po` - Translation files for specific languages
- `publications-manager-{locale}.mo` - Compiled translation files (binary)

## How to Translate

### Using Loco Translate (Recommended for WordPress)

1. Install the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin from WordPress.org
2. Go to **Loco Translate → Plugins → Publications Manager**
3. Click **"New language"** or **"Edit"** for an existing translation
4. Select your language and create the translation
5. Translate the strings in the WordPress admin interface
6. Click **"Save"** - Loco will automatically generate both .po and .mo files
7. The translations will be immediately active

### Using Poedit (Desktop Application)

1. Download and install [Poedit](https://poedit.net/)
2. Open the `publications-manager.pot` file
3. Create a new translation for your language
4. Translate all strings
5. Save the file as `publications-manager-{locale}.po` (e.g., `publications-manager-es_ES.po`)
6. Poedit will automatically generate the `.mo` file

### Using Loco Translate (WordPress Plugin)

1. Install the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin
2. Go to Loco Translate → Plugins → Publications Manager
3. Click "New language"
4. Select your language and create the translation
5. Translate the strings in the WordPress admin
6. Sync and save

### Compiling PO to MO Files

**Using Docker (if using this WordPress setup):**

```bash
docker exec -it <container-name> bash
cd /var/www/html/wp-content/plugins/publications-manager/languages
msgfmt publications-manager-{locale}.po -o publications-manager-{locale}.mo
```

**Using the included PHP script:**

```bash
# If PHP CLI is available on your system
php compile-translations.php
```

**Using Loco Translate:**

- Loco Translate automatically compiles PO to MO when you save
- No manual compilation needed!

### Manual Translation

1. Copy `publications-manager.pot` to `publications-manager-{locale}.po`
2. Edit the file and add translations for each `msgstr` entry
3. Compile the `.po` file to `.mo` using gettext tools:
   ```bash
   msgfmt publications-manager-{locale}.po -o publications-manager-{locale}.mo
   ```

## Locale Codes

Common locale codes:

- English (US): `en_US`
- Spanish (Spain): `es_ES`
- French (France): `fr_FR`
- German: `de_DE`
- Italian: `it_IT`
- Greek: `el`
- Portuguese (Brazil): `pt_BR`

## Translation Priority

The plugin loads translations in this order:

1. Plugin's `/languages` directory (this folder) - **highest priority**
2. WordPress's `/wp-content/languages/plugins/` directory
3. Default English strings

This means you can override WordPress.org translations by placing your custom translations in this directory.

## Updating Translations

When the plugin is updated with new translatable strings:

1. Update the `.pot` file with the new strings
2. Open your `.po` file in Poedit
3. Go to Catalog → Update from POT file
4. Select the updated `publications-manager.pot`
5. Translate any new strings
6. Save (this will regenerate the `.mo` file)

## Contributing Translations

If you've created a translation and would like to share it:

1. Ensure your `.po` and `.mo` files are complete
2. Test the translation in your WordPress installation
3. Submit the files via GitHub pull request or contact the plugin author

## Notes

- Always keep the `.pot` file up to date when adding new translatable strings to the code
- Use translation functions in PHP code:
  - `__('Text', 'publications-manager')` - Returns translated text
  - `_e('Text', 'publications-manager')` - Echoes translated text
  - `_x('Text', 'Context', 'publications-manager')` - Text with context
  - `_n('Singular', 'Plural', $count, 'publications-manager')` - Plural forms
- The text domain is: `publications-manager`

## Tools for Generating POT Files

If you need to regenerate the `.pot` file:

### Using WP-CLI

```bash
wp i18n make-pot /path/to/publications-manager /path/to/publications-manager/languages/publications-manager.pot --domain=publications-manager
```

### Using Poedit

1. Open Poedit
2. File → New from source code
3. Select the plugin directory
4. Save as `publications-manager.pot`

### Using Loco Translate

1. Go to Loco Translate → Plugins → Publications Manager
2. Click "Sync" to update the template
3. Export the POT file

## Support

For translation-related questions or issues, please contact the plugin author or open an issue on GitHub.
