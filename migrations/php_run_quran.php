<?php
/**
 * Quran Data Importer
 * Run this script ONCE to import all Quran data from Tanzil XML files
 */

// Database configuration
require_once '../classes/Database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    // ========================================
    // STEP 1: Load Metadata XML (suras info)
    // ========================================
    echo "Loading metadata XML...\n";
    $metadataFile = 'quran-data.xml'; // First XML file with metadata
    $metadata = simplexml_load_file($metadataFile);
    
    if (!$metadata) {
        die("Error: Cannot load metadata XML file.\n");
    }
    
    // Clear existing data
    echo "Clearing existing data...\n";
    $conn->exec("TRUNCATE TABLE quran_verses");
    $conn->exec("TRUNCATE TABLE quran_suras");
    
    // ========================================
    // STEP 2: Import Suras Metadata
    // ========================================
    echo "Importing suras metadata...\n";
    $stmtSura = $conn->prepare("
        INSERT INTO quran_suras 
        (sura, name_ar, name_en, type, total_verses, rukus, start_global_index)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($metadata->suras->sura as $sura) {
        $stmtSura->execute([
            (int)$sura['index'],
            (string)$sura['name'],
            (string)$sura['ename'],
            (string)$sura['type'],
            (int)$sura['ayas'],
            (int)$sura['rukus'],
            (int)$sura['start']
        ]);
    }
    
    echo "Imported " . count($metadata->suras->sura) . " suras metadata.\n\n";
    
    // ========================================
    // STEP 3: Load Text XML (verses text)
    // ========================================
    echo "Loading text XML...\n";
    $textFile = 'quran-simple.xml'; // Second XML file with verse text
    $quranText = simplexml_load_file($textFile);
    
    if (!$quranText) {
        die("Error: Cannot load text XML file.\n");
    }
    
    // ========================================
    // STEP 4: Import Verses with Text
    // ========================================
    echo "Importing verses...\n";
    $stmtVerse = $conn->prepare("
        INSERT INTO quran_verses 
        (global_index, sura, ayah, sura_name_ar, sura_name_en, text, bismillah, words)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $globalIndex = 0;
    $totalVerses = 0;
    
    foreach ($quranText->sura as $sura) {
        $suraIndex = (int)$sura['index'];
        $suraNameAr = (string)$sura['name'];
        
        // Get sura metadata for English name
        $suraMetadata = null;
        foreach ($metadata->suras->sura as $sm) {
            if ((int)$sm['index'] == $suraIndex) {
                $suraMetadata = $sm;
                break;
            }
        }
        $suraNameEn = $suraMetadata ? (string)$suraMetadata['ename'] : '';
        
        echo "Processing Sura $suraIndex: $suraNameAr ($suraNameEn)...\n";
        
        foreach ($sura->aya as $aya) {
            $ayahIndex = (int)$aya['index'];
            $text = (string)$aya['text'];
            $bismillah = isset($aya['bismillah']) ? (string)$aya['bismillah'] : null;
            
            // Count words (Arabic words separated by spaces)
            $words = count(preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY));
            
            $stmtVerse->execute([
                $globalIndex,
                $suraIndex,
                $ayahIndex,
                $suraNameAr,
                $suraNameEn,
                $text,
                $bismillah,
                $words
            ]);
            
            $globalIndex++;
            $totalVerses++;
        }
    }
    
    echo "\n========================================\n";
    echo "Import completed successfully!\n";
    echo "Total Suras: " . count($metadata->suras->sura) . "\n";
    echo "Total Verses: $totalVerses\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>