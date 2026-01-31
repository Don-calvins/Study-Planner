<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Never show errors in production
ini_set('log_errors', 1);

// Security configurations
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ALLOWED_TYPES', ['txt', 'docx', 'pdf']);

function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Create uploads directory if missing
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJsonResponse(false, 'Invalid security token');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['study_file'])) {
    $file = $_FILES['study_file'];
    
    // Basic upload error check
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'Upload failed: ' . $file['error']);
    }
    
    // Security validations
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    
    // 1. Size validation
    if ($fileSize > MAX_FILE_SIZE) {
        sendJsonResponse(false, 'File too large. Max 5MB allowed.');
    }
    
    // 2. Extension whitelist
    if (!in_array($fileExt, ALLOWED_TYPES)) {
        sendJsonResponse(false, 'Unsupported format. Use TXT, DOCX, or PDF.');
    }
    
    // 3. MIME type + magic bytes validation
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    
    $validMimes = [
        'txt' => 'text/plain',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pdf' => 'application/pdf'
    ];
    
    if (!isset($validMimes[$fileExt]) || $mimeType !== $validMimes[$fileExt]) {
        sendJsonResponse(false, 'Invalid file type detected.');
    }
    
    // 4. Generate secure filename
    $uniqueName = bin2hex(random_bytes(16)) . '.' . $fileExt;
    $uploadPath = UPLOAD_DIR . $uniqueName;
    
    // Move file securely
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        sendJsonResponse(false, 'Failed to save file.');
    }
    
    // chmod for security
    chmod($uploadPath, 0644);
    
    try {
        // Parse content based on file type
        $content = '';
        switch ($fileExt) {
            case 'txt':
                $content = file_get_contents($uploadPath);
                break;
                
            case 'pdf':
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($uploadPath);
                $content = $pdf->getText();
                break;
                
            case 'docx':
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($uploadPath);
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $content .= $element->getText() . "\n\n";
                        }
                    }
                }
                break;
        }
        
        // Clean and split into topics (improved regex)
        $topics = array_filter(array_map('trim', preg_split('/(?:\n\s*\n|\r\n\s*\r\n){1,}/', $content)));
        
        if (empty($topics)) {
            sendJsonResponse(false, 'No valid content found in file.');
        }
        
        // Store in session with metadata
        $_SESSION['study_plan'] = [
            'topics' => array_slice($topics, 0, 50), // Limit to 50 topics
            'filename' => $fileName,
            'processed_at' => date('Y-m-d H:i:s'),
            'topic_count' => count($topics)
        ];
        
        // Clean up uploaded file (don't store permanently)
        unlink($uploadPath);
        
        sendJsonResponse(true, 'Study plan created successfully!', [
            'topic_count' => count($topics),
            'filename' => $fileName
        ]);
        
    } catch (Exception $e) {
        // Cleanup on error
        if (file_exists($uploadPath)) unlink($uploadPath);
        sendJsonResponse(false, 'Processing error: ' . $e->getMessage());
    }
}

sendJsonResponse(false, 'Invalid request');
?>
