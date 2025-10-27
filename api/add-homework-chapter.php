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
    
    // Validate required fields (support validate-only mode where session_homework_id is not required)
    $validateOnly = isset($input['validate_only']) && ($input['validate_only'] === true || $input['validate_only'] === 1 || $input['validate_only'] === '1');
    $required_fields = $validateOnly
        ? ['homework_type_id', 'quran_from', 'quran_to', 'quran_chapter', 'classroom_id', 'quran_suras_id']
        : ['session_homework_id', 'homework_type_id', 'quran_from', 'quran_to', 'quran_chapter', 'classroom_id', 'quran_suras_id'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
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
    // Get PDO connection
    $pdo = $database->connect();
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
