#!/usr/bin/env php
<?php
/**
 * Simple PO to MO compiler
 * 
 * Usage: php compile-translations.php
 * 
 * This script compiles all .po files in the languages directory to .mo files
 */

// Set the languages directory
$languages_dir = __DIR__;

// Find all .po files
$po_files = glob($languages_dir . '/*.po');

if (empty($po_files)) {
    echo "No .po files found in {$languages_dir}\n";
    exit(1);
}

echo "Found " . count($po_files) . " .po file(s) to compile\n\n";

foreach ($po_files as $po_file) {
    $mo_file = substr($po_file, 0, -3) . '.mo';

    echo "Compiling: " . basename($po_file) . "\n";

    if (compile_po_to_mo($po_file, $mo_file)) {
        echo "  ✓ Successfully created: " . basename($mo_file) . "\n";
    } else {
        echo "  ✗ Failed to create: " . basename($mo_file) . "\n";
    }
    echo "\n";
}

echo "Compilation complete!\n";

/**
 * Compile a PO file to MO format
 * 
 * @param string $po_file Path to .po file
 * @param string $mo_file Path to .mo file
 * @return bool Success status
 */
function compile_po_to_mo($po_file, $mo_file)
{
    if (!file_exists($po_file)) {
        return false;
    }

    $po_content = file_get_contents($po_file);
    if ($po_content === false) {
        return false;
    }

    // Parse PO file
    $translations = parse_po_file($po_content);

    if (empty($translations)) {
        return false;
    }

    // Generate MO file
    return generate_mo_file($translations, $mo_file);
}

/**
 * Parse PO file content
 * 
 * @param string $content PO file content
 * @return array Parsed translations
 */
function parse_po_file($content)
{
    $translations = array();
    $lines = explode("\n", $content);

    $msgid = '';
    $msgstr = '';
    $msgctxt = '';
    $in_msgid = false;
    $in_msgstr = false;
    $in_msgctxt = false;

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        // Handle msgctxt
        if (strpos($line, 'msgctxt ') === 0) {
            $msgctxt = substr($line, 8);
            $msgctxt = trim($msgctxt, '"');
            $in_msgctxt = true;
            $in_msgid = false;
            $in_msgstr = false;
            continue;
        }

        // Handle msgid
        if (strpos($line, 'msgid ') === 0) {
            // Save previous translation
            if (!empty($msgid) && !empty($msgstr)) {
                $key = $msgctxt ? $msgctxt . "\x04" . $msgid : $msgid;
                $translations[$key] = $msgstr;
            }

            $msgid = substr($line, 6);
            $msgid = trim($msgid, '"');
            $msgstr = '';
            $msgctxt = '';
            $in_msgid = true;
            $in_msgstr = false;
            $in_msgctxt = false;
            continue;
        }

        // Handle msgstr
        if (strpos($line, 'msgstr ') === 0) {
            $msgstr = substr($line, 7);
            $msgstr = trim($msgstr, '"');
            $in_msgstr = true;
            $in_msgid = false;
            $in_msgctxt = false;
            continue;
        }

        // Handle multiline strings
        if ($line[0] === '"') {
            $text = trim($line, '"');

            if ($in_msgid) {
                $msgid .= $text;
            } elseif ($in_msgstr) {
                $msgstr .= $text;
            } elseif ($in_msgctxt) {
                $msgctxt .= $text;
            }
        }
    }

    // Save last translation
    if (!empty($msgid) && !empty($msgstr)) {
        $key = $msgctxt ? $msgctxt . "\x04" . $msgid : $msgid;
        $translations[$key] = $msgstr;
    }

    // Process escape sequences
    foreach ($translations as $key => $value) {
        $translations[$key] = process_escape_sequences($value);
    }

    return $translations;
}

/**
 * Process escape sequences in strings
 * 
 * @param string $str String to process
 * @return string Processed string
 */
function process_escape_sequences($str)
{
    $replacements = array(
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\"' => '"',
        '\\\\' => '\\',
    );

    return str_replace(array_keys($replacements), array_values($replacements), $str);
}

/**
 * Generate MO file from translations
 * 
 * @param array $translations Translations array
 * @param string $mo_file Path to output MO file
 * @return bool Success status
 */
function generate_mo_file($translations, $mo_file)
{
    // MO file magic number (little endian)
    $magic = 0x950412de;

    $revision = 0;
    $count = count($translations);

    // Calculate offsets
    $originals_offset = 28; // Header size
    $translations_offset = $originals_offset + ($count * 8);

    // Build strings table
    $originals = array();
    $translated = array();
    $current_offset = $translations_offset + ($count * 8);

    foreach ($translations as $original => $translation) {
        $originals[] = array(
            'length' => strlen($original),
            'offset' => $current_offset,
            'string' => $original
        );
        $current_offset += strlen($original) + 1;
    }

    foreach ($translations as $original => $translation) {
        $translated[] = array(
            'length' => strlen($translation),
            'offset' => $current_offset,
            'string' => $translation
        );
        $current_offset += strlen($translation) + 1;
    }

    // Build MO file
    $mo_data = '';

    // Write header
    $mo_data .= pack('V', $magic);
    $mo_data .= pack('V', $revision);
    $mo_data .= pack('V', $count);
    $mo_data .= pack('V', $originals_offset);
    $mo_data .= pack('V', $translations_offset);
    $mo_data .= pack('V', 0); // Hash table size
    $mo_data .= pack('V', 0); // Hash table offset

    // Write original strings index
    foreach ($originals as $entry) {
        $mo_data .= pack('V', $entry['length']);
        $mo_data .= pack('V', $entry['offset']);
    }

    // Write translated strings index
    foreach ($translated as $entry) {
        $mo_data .= pack('V', $entry['length']);
        $mo_data .= pack('V', $entry['offset']);
    }

    // Write original strings
    foreach ($originals as $entry) {
        $mo_data .= $entry['string'] . "\0";
    }

    // Write translated strings
    foreach ($translated as $entry) {
        $mo_data .= $entry['string'] . "\0";
    }

    // Write to file
    return file_put_contents($mo_file, $mo_data) !== false;
}
