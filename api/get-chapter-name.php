<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

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

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Initialize database
    $database = new Database();
    $db = $database->connect();
    
    // Get search query from query string
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Query must be at least 2 characters long'
        ]);
        exit();
    }
    
    // Normalize the query to handle Arabic character variations
    $normalizedQuery = normalizeArabicSearch($query);
    
    // Prepare search terms with variations
    $patterns = [
        '%' . $query . '%',  // Original query
        '%' . $normalizedQuery . '%'  // Normalized query
    ];
    
    // Create SQL with comprehensive normalization for better matching
    // This handles: ة/ه, ى/ي, أ/إ/آ/ا, and removes ال prefix
    $sql = "SELECT sura, name_ar, name_en,
            CASE 
                WHEN name_ar = :exact THEN 100
                WHEN name_en = :exact THEN 100
                WHEN name_ar LIKE :query1 THEN 90
                WHEN name_en LIKE :query1 THEN 85
                ELSE 70
            END as match_score
            FROM quran_suras 
            WHERE (
                name_ar LIKE :query1 
                OR name_en LIKE :query1
                OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    name_ar, 
                    'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه'), 'ى', 'ي'), 'ئ', 'ي'), 'ؤ', 'ا'
                ) LIKE :query2
                OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    CASE WHEN name_ar LIKE 'ال%' THEN SUBSTRING(name_ar, 3) ELSE name_ar END,
                    'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه'), 'ى', 'ي'), 'ئ', 'ي'), 'ؤ', 'ا'
                ) LIKE :query2
            )
            ORDER BY match_score DESC, sura ASC
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':exact', $query, PDO::PARAM_STR);
    $stmt->bindParam(':query1', $patterns[0], PDO::PARAM_STR);
    $stmt->bindParam(':query2', $patterns[1], PDO::PARAM_STR);
    $stmt->execute();
    
    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $results = [];
    foreach ($chapters as $chapter) {
        $results[] = [
            'id' => (int)$chapter['sura'],
            'name_ar' => $chapter['name_ar'],
            'name_en' => $chapter['name_en'],
            'display_name' => $chapter['name_ar'] . ' (' . $chapter['name_en'] . ')'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'chapters' => $results,
        'count' => count($results)
    ]);
    
} catch (PDOException $e) {
    error_log("Get chapter name API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Get chapter name API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?>

