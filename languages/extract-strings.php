#!/usr/bin/env php
<?php
/**
 * Extract translatable strings from PHP files and update POT/PO files
 * 
 * Usage: php extract-strings.php
 */

$plugin_dir = dirname(__DIR__);
$languages_dir = __DIR__;
$text_domain = 'publications-manager';

echo "Extracting translatable strings from Publications Manager...\n\n";

// Find all PHP files
$php_files = [];
$directories = [
    'includes',
    'tools'
];

foreach ($directories as $dir) {
    $path = $plugin_dir . '/' . $dir;
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $php_files[] = $file->getPathname();
            }
        }
    }
}

// Add main plugin file
$php_files[] = $plugin_dir . '/publications-manager.php';

echo "Found " . count($php_files) . " PHP files\n";

// Extract strings
$strings = [];
$total_extracted = 0;

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    $relative_path = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file);
    $relative_path = str_replace('\\', '/', $relative_path);

    // Match __('string', 'text-domain')
    preg_match_all("/__\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches1, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match _e('string', 'text-domain')
    preg_match_all("/_e\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches2, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match _n('singular', 'plural', count, 'text-domain')
    preg_match_all("/_n\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"](.+?)['\"]\s*,.*?,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches3, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match esc_html__('string', 'text-domain')
    preg_match_all("/esc_html__\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches4, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match esc_attr__('string', 'text-domain')
    preg_match_all("/esc_attr__\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches5, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match esc_html_e('string', 'text-domain')
    preg_match_all("/esc_html_e\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches6, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Match esc_attr_e('string', 'text-domain')
    preg_match_all("/esc_attr_e\s*\(\s*['\"](.+?)['\"]\s*,\s*['\"]" . $text_domain . "['\"]\s*\)/", $content, $matches7, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $all_matches = array_merge($matches1, $matches2, $matches4, $matches5, $matches6, $matches7);

    foreach ($all_matches as $match) {
        $string = $match[1][0];
        $line = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;

        if (!isset($strings[$string])) {
            $strings[$string] = [];
        }
        $strings[$string][] = ['file' => $relative_path, 'line' => $line];
        $total_extracted++;
    }

    // Handle _n() separately (plural forms)
    foreach ($matches3 as $match) {
        $singular = $match[1][0];
        $plural = $match[2][0];
        $line = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;

        $key = $singular . '|||' . $plural;
        if (!isset($strings[$key])) {
            $strings[$key] = [];
        }
        $strings[$key][] = ['file' => $relative_path, 'line' => $line];
        $total_extracted++;
    }
}

echo "Extracted $total_extracted translatable strings\n";
echo "Unique strings: " . count($strings) . "\n\n";

// Generate POT file
$pot_file = $languages_dir . '/publications-manager.pot';
$pot_content = generate_pot_header();

ksort($strings);

foreach ($strings as $string => $references) {
    // Check if it's a plural form
    if (strpos($string, '|||') !== false) {
        list($singular, $plural) = explode('|||', $string);

        foreach ($references as $ref) {
            $pot_content .= "#: {$ref['file']}:{$ref['line']}\n";
        }
        $pot_content .= 'msgid "' . addcslashes($singular, '"\\') . "\"\n";
        $pot_content .= 'msgid_plural "' . addcslashes($plural, '"\\') . "\"\n";
        $pot_content .= "msgstr[0] \"\"\n";
        $pot_content .= "msgstr[1] \"\"\n\n";
    } else {
        foreach ($references as $ref) {
            $pot_content .= "#: {$ref['file']}:{$ref['line']}\n";
        }
        $pot_content .= 'msgid "' . addcslashes($string, '"\\') . "\"\n";
        $pot_content .= "msgstr \"\"\n\n";
    }
}

file_put_contents($pot_file, $pot_content);
echo "✓ Generated: publications-manager.pot\n\n";

// Update PO files
$po_files = glob($languages_dir . '/publications-manager-*.po');

foreach ($po_files as $po_file) {
    $locale = basename($po_file, '.po');
    $locale = str_replace('publications-manager-', '', $locale);

    echo "Updating: " . basename($po_file) . "\n";

    // Read existing translations
    $existing = parse_po_file($po_file);

    // Generate new PO content
    $po_content = generate_po_header($locale);

    foreach ($strings as $string => $references) {
        // Check if it's a plural form
        if (strpos($string, '|||') !== false) {
            list($singular, $plural) = explode('|||', $string);

            foreach ($references as $ref) {
                $po_content .= "#: {$ref['file']}:{$ref['line']}\n";
            }
            $po_content .= 'msgid "' . addcslashes($singular, '"\\') . "\"\n";
            $po_content .= 'msgid_plural "' . addcslashes($plural, '"\\') . "\"\n";

            // Keep existing translation if available
            $key = $singular . '|||' . $plural;
            if (isset($existing[$key])) {
                $po_content .= 'msgstr[0] "' . addcslashes($existing[$key][0], '"\\') . "\"\n";
                $po_content .= 'msgstr[1] "' . addcslashes($existing[$key][1], '"\\') . "\"\n\n";
            } else {
                $po_content .= "msgstr[0] \"\"\n";
                $po_content .= "msgstr[1] \"\"\n\n";
            }
        } else {
            foreach ($references as $ref) {
                $po_content .= "#: {$ref['file']}:{$ref['line']}\n";
            }
            $po_content .= 'msgid "' . addcslashes($string, '"\\') . "\"\n";

            // Keep existing translation if available
            if (isset($existing[$string])) {
                $po_content .= 'msgstr "' . addcslashes($existing[$string], '"\\') . "\"\n\n";
            } else {
                $po_content .= "msgstr \"\"\n\n";
            }
        }
    }

    file_put_contents($po_file, $po_content);
    echo "  ✓ Updated with existing translations preserved\n";
}

echo "\n✓ All translation files updated!\n";
echo "\nNext steps:\n";
echo "1. Translate the new strings in each .po file\n";
echo "2. Run: php compile-translations.php\n";

/**
 * Generate POT header
 */
function generate_pot_header()
{
    $date = date('Y-m-d H:i') . '+0000';

    return <<<POT
# Copyright (C) 2026 Publications Manager
# This file is distributed under the GPL v2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Publications Manager 2.2.1\\n"
"Report-Msgid-Bugs-To: https://ntamadakis.gr\\n"
"POT-Creation-Date: {$date}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"Language: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n"

POT;
}

/**
 * Generate PO header
 */
function generate_po_header($locale)
{
    $date = date('Y-m-d H:i') . '+0000';

    $plural_forms = [
        'de_DE' => 'nplurals=2; plural=(n != 1);',
        'el' => 'nplurals=2; plural=(n != 1);',
        'es_ES' => 'nplurals=2; plural=(n != 1);',
        'fr_FR' => 'nplurals=2; plural=(n > 1);',
        'it_IT' => 'nplurals=2; plural=(n != 1);',
        'pt_BR' => 'nplurals=2; plural=(n > 1);',
    ];

    $plural = isset($plural_forms[$locale]) ? $plural_forms[$locale] : 'nplurals=2; plural=(n != 1);';

    return <<<PO
# Copyright (C) 2026 Publications Manager
# This file is distributed under the GPL v2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Publications Manager 2.2.1\\n"
"Report-Msgid-Bugs-To: https://ntamadakis.gr\\n"
"POT-Creation-Date: {$date}\\n"
"PO-Revision-Date: {$date}\\n"
"Last-Translator: \\n"
"Language-Team: \\n"
"Language: {$locale}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: {$plural}\\n"

PO;
}

/**
 * Parse existing PO file to preserve translations
 */
function parse_po_file($file)
{
    $translations = [];

    if (!file_exists($file)) {
        return $translations;
    }

    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    $msgid = '';
    $msgid_plural = '';
    $msgstr = '';
    $msgstr_plural = [];
    $in_msgid = false;
    $in_msgid_plural = false;
    $in_msgstr = false;
    $in_msgstr_plural = false;
    $plural_index = 0;

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines for translation extraction
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        if (strpos($line, 'msgid "') === 0) {
            // Save previous translation
            if ($msgid && $msgstr) {
                if ($msgid_plural) {
                    $translations[$msgid . '|||' . $msgid_plural] = $msgstr_plural;
                } else {
                    $translations[$msgid] = $msgstr;
                }
            }

            // Start new msgid
            $msgid = substr($line, 7, -1);
            $msgid_plural = '';
            $msgstr = '';
            $msgstr_plural = [];
            $in_msgid = true;
            $in_msgstr = false;
            $in_msgid_plural = false;
            $in_msgstr_plural = false;
        } elseif (strpos($line, 'msgid_plural "') === 0) {
            $msgid_plural = substr($line, 14, -1);
            $in_msgid_plural = true;
            $in_msgid = false;
            $in_msgstr = false;
        } elseif (strpos($line, 'msgstr "') === 0) {
            $msgstr = substr($line, 8, -1);
            $in_msgstr = true;
            $in_msgid = false;
            $in_msgid_plural = false;
        } elseif (preg_match('/msgstr\[(\d+)\] "(.*)\"/', $line, $matches)) {
            $plural_index = (int)$matches[1];
            $msgstr_plural[$plural_index] = $matches[2];
            $in_msgstr_plural = true;
            $in_msgstr = false;
            $in_msgid = false;
            $in_msgid_plural = false;
        } elseif ($line[0] === '"' && $line[strlen($line) - 1] === '"') {
            // Continuation line
            $continuation = substr($line, 1, -1);

            if ($in_msgid) {
                $msgid .= $continuation;
            } elseif ($in_msgid_plural) {
                $msgid_plural .= $continuation;
            } elseif ($in_msgstr) {
                $msgstr .= $continuation;
            } elseif ($in_msgstr_plural) {
                $msgstr_plural[$plural_index] .= $continuation;
            }
        }
    }

    // Save last translation
    if ($msgid && ($msgstr || !empty($msgstr_plural))) {
        if ($msgid_plural) {
            $translations[$msgid . '|||' . $msgid_plural] = $msgstr_plural;
        } else {
            $translations[$msgid] = $msgstr;
        }
    }

    return $translations;
}
