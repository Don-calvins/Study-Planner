<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['study_file'])) {
    $file = $_FILES['study_file'];
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if ($file_ext == 'txt') {
        $content = file_get_contents($file['tmp_name']);
    } elseif ($file_ext == 'docx') {
        require 'vendor/autoload.php'; // Load PHPWord for DOCX parsing
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file['tmp_name']);
        $content = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . "\n";
                }
            }
        }
    } elseif ($file_ext == 'pdf') {
        require 'vendor/autoload.php'; // Load Smalot/PdfParser
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($file['tmp_name']);
        $content = $pdf->getText();
    } else {
        die("Unsupported file format. Please upload TXT, DOCX, or PDF.");
    }
    
    // Split content into topics
    $topics = preg_split('/\n\n+/', trim($content));
    $_SESSION['study_plan'] = $topics;
    header("Location: study_plan.php");
    exit;
}
?>
