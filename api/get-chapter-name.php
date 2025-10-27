<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

// Function to normalize Arabic characters for flexible searching
function normalizeArabicSearch($text) {
    // Replace all variations of 'alif' with a simple 'ا'
    $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
    // Replace variations of 'hamza' with simple form
    $text = str_replace(['ء', 'ئ', 'ؤ'], 'ا', $text);
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
    // Replace 'ا' in query to match different forms: أ, إ, آ, ا
    $patterns = [
        '%' . $query . '%',  // Original query
        '%' . $normalizedQuery . '%'  // Normalized query
    ];
    
    // Create SQL that searches with normalized database names
    // Replace different forms of alif in database to match normalized query
    $sql = "SELECT sura, name_ar, name_en 
            FROM quran_suras 
            WHERE (
                name_ar LIKE :query1 
                OR name_en LIKE :query1
                OR REPLACE(REPLACE(REPLACE(name_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا') LIKE :query2
            )
            ORDER BY sura ASC
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
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

