<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Arabic to Western numeral conversion utility
    function convertArabicToWestern($text) {
        if (!$text) return $text;
        
        $arabicToWestern = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'
        ];
        
        return str_replace(array_keys($arabicToWestern), array_values($arabicToWestern), $text);
    }

    // Enhanced function to normalize Arabic characters for flexible searching
    function normalizeArabicSearch($text) {
        // Convert to lowercase (for English names)
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remove diacritics (tashkeel)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
        
        // Replace all variations of 'alif' with a simple 'ا'
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        
        // Replace variations of 'hamza'
        $text = str_replace(['ء', 'ئ', 'ؤ'], 'ا', $text);
        
        // Replace taa marbouta ة with haa ه (very common variation)
        $text = str_replace('ة', 'ه', $text);
        
        // Replace alif maqsura ى with yaa ي
        $text = str_replace('ى', 'ي', $text);
        
        // Normalize different forms of yaa
        $text = str_replace(['ئ', 'ى'], 'ي', $text);
        
        // Remove definite article "ال" from the beginning
        $text = preg_replace('/^ال/', '', $text);
        
        // Remove extra spaces and trim
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    // Calculate similarity score between two strings
    function calculateSimilarity($str1, $str2) {
        $str1 = normalizeArabicSearch($str1);
        $str2 = normalizeArabicSearch($str2);
        
        // If exact match after normalization
        if ($str1 === $str2) {
            return 100;
        }
        
        // If one string is contained in the other
        if (strpos($str2, $str1) !== false || strpos($str1, $str2) !== false) {
            return 90;
        }
        
        // Use similar_text for fuzzy matching
        similar_text($str1, $str2, $percent);
        
        return $percent;
    }

    // Enhanced function to find chapter ID by fuzzy matching chapter name
    function findChapterByName($pdo, $chapterText) {
        $normalizedInput = normalizeArabicSearch($chapterText);
        
        // Get all suras from database
        $sql = "SELECT sura, name_ar, name_en FROM quran_suras ORDER BY sura ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $allSuras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($allSuras as $sura) {
            // Calculate similarity with Arabic name
            $scoreArabic = calculateSimilarity($chapterText, $sura['name_ar']);
            
            // Calculate similarity with English name
            $scoreEnglish = calculateSimilarity($chapterText, $sura['name_en']);
            
            // Take the best score
            $score = max($scoreArabic, $scoreEnglish);
            
            // Also check if input is contained in the name (partial match)
            $normalizedNameAr = normalizeArabicSearch($sura['name_ar']);
            $normalizedNameEn = normalizeArabicSearch($sura['name_en']);
            
            // Boost score if there's a substring match
            if (strpos($normalizedNameAr, $normalizedInput) !== false || 
                strpos($normalizedInput, $normalizedNameAr) !== false) {
                $score = max($score, 85);
            }
            
            if (strpos($normalizedNameEn, $normalizedInput) !== false || 
                strpos($normalizedInput, $normalizedNameEn) !== false) {
                $score = max($score, 80);
            }
            
            // Update best match if this score is better
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $sura;
            }
        }
        
        // Accept match if similarity is at least 60%
        if ($bestMatch && $bestScore >= 60) {
            return [
                'success' => true,
                'sura_id' => (int)$bestMatch['sura'],
                'name_ar' => $bestMatch['name_ar'],
                'name_en' => $bestMatch['name_en'],
                'match_score' => $bestScore
            ];
        }
        
        return [
            'success' => false,
            'message' => 'اسم السورة غير موجود في قاعدة البيانات. يرجى التحقق من الاسم والمحاولة مرة أخرى.'
        ];
    }

    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in and is a teacher or admin
    if (!User::isLoggedIn() || (!User::isTeacher() && !User::isAdmin())) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher or Admin access required'
        ]);
        exit();
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit();
    }
    
    // Convert Arabic numerals to Western numerals
    if (isset($input['quran_from'])) {
        $input['quran_from'] = convertArabicToWestern($input['quran_from']);
    }
    if (isset($input['quran_to'])) {
        $input['quran_to'] = convertArabicToWestern($input['quran_to']);
    }
    
    // Get PDO connection early as we might need it for fuzzy matching
    $pdo = $database->connect();
    
    // Check if we need to resolve chapter name to ID via fuzzy matching
    if ((!isset($input['quran_suras_id']) || $input['quran_suras_id'] === null || $input['quran_suras_id'] === '') 
        && isset($input['quran_chapter_text']) && !empty($input['quran_chapter_text'])) {
        
        // Try to find chapter by name using fuzzy matching
        $chapterMatch = findChapterByName($pdo, $input['quran_chapter_text']);
        
        if (!$chapterMatch['success']) {
            echo json_encode([
                'success' => false,
                'message' => $chapterMatch['message']
            ]);
            exit();
        }
        
        // Use the matched chapter ID
        $input['quran_suras_id'] = $chapterMatch['sura_id'];
        $input['quran_chapter'] = $chapterMatch['name_ar'] . ' (' . $chapterMatch['name_en'] . ')';
    }
    
    // Validate required fields (support validate-only mode where session_homework_id is not required)
    // Note: quran_suras_id is not strictly required if quran_chapter_text is provided (will be resolved by fuzzy matching)
    $validateOnly = isset($input['validate_only']) && ($input['validate_only'] === true || $input['validate_only'] === 1 || $input['validate_only'] === '1');
    
    // Basic required fields (without quran_suras_id initially)
    $required_fields = $validateOnly
        ? ['homework_type_id', 'quran_from', 'quran_to', 'quran_chapter', 'classroom_id']
        : ['session_homework_id', 'homework_type_id', 'quran_from', 'quran_to', 'quran_chapter', 'classroom_id'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Now check if we have either quran_suras_id OR quran_chapter_text
    $hasChapterId = isset($input['quran_suras_id']) && $input['quran_suras_id'] !== '' && $input['quran_suras_id'] !== null;
    $hasChapterText = isset($input['quran_chapter_text']) && $input['quran_chapter_text'] !== '' && trim($input['quran_chapter_text']) !== '';
    
    if (!$hasChapterId && !$hasChapterText) {
        echo json_encode([
            'success' => false,
            'message' => 'يجب توفير معرف السورة أو اسم السورة'
        ]);
        exit();
    }
    
    // Additional validations before DB insert
    $fromVerse = (int)$input['quran_from'];
    $toVerse = (int)$input['quran_to'];
    $suraId = (int)$input['quran_suras_id'];

    // 1) Validate verses are ordered correctly
    if ($fromVerse > $toVerse) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid verse range: quran_from cannot be greater than quran_to'
        ]);
        exit();
    }

    // 2) Validate verses do not exceed the chapter's total_verses
    $stmt = $pdo->prepare('SELECT total_verses FROM quran_suras WHERE sura = :sura LIMIT 1');
    $stmt->execute([':sura' => $suraId]);
    $sura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sura) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid chapter selected'
        ]);
        exit();
    }

    $totalVerses = (int)$sura['total_verses'];

    if ($fromVerse < 1 || $toVerse < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Verse numbers must be 1 or greater'
        ]);
        exit();
    }

    if ($fromVerse > $totalVerses || $toVerse > $totalVerses) {
        echo json_encode([
            'success' => false,
            'message' => 'The selected verses exceed the total number of verses for this chapter (' . $totalVerses . ')'
        ]);
        exit();
    }

    // If validate-only, return success after validations without DB insert
    if ($validateOnly) {
        echo json_encode([
            'success' => true,
            'message' => 'Validation passed'
        ]);
        exit();
    }

    // Create Homework instance
    $homework = new Homework($pdo);
    
    // Prepare homework chapter data
    $homework_data = [
        'session_homework_id' => (int)$input['session_homework_id'],
        'homework_type_id' => (int)$input['homework_type_id'],
        'quran_from' => (int)$input['quran_from'],
        'quran_to' => (int)$input['quran_to'],
        'quran_chapter' => trim($input['quran_chapter']),
        'classroom_id' => (int)$input['classroom_id'],
        'quran_suras_id' => (int)$input['quran_suras_id']
    ];
    
    // Add homework chapter
    $result = $homework->addHomeworkChapter($homework_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'homework_grade_id' => $result['homework_grade_id'],
            'data' => $result['data']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add homework chapter API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding homework chapter data: ' . $e->getMessage()
    ]);
}
?>
