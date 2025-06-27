<?php
// Check PHP syntax
header('Content-Type: text/plain');

echo "Checking payment_processing.php syntax...\n";

// Check if file can be included without errors
ob_start();
$error = false;

try {
    // Don't execute, just check syntax
    $content = file_get_contents('api/payment_processing.php');
    
    // Check for basic syntax issues
    if (substr_count($content, '{') !== substr_count($content, '}')) {
        echo "ERROR: Mismatched braces\n";
        $error = true;
    }
    
    if (substr_count($content, '(') !== substr_count($content, ')')) {
        echo "ERROR: Mismatched parentheses\n";
        $error = true;
    }
    
    // Try to parse the file
    $tokens = token_get_all($content);
    echo "File parsed successfully - " . count($tokens) . " tokens\n";
    
    if (!$error) {
        echo "No obvious syntax errors found\n";
        
        // Try to actually include it with error suppression
        error_reporting(0);
        
        // Backup current GET values
        $backup_get = $_GET;
        $backup_method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // Set up for testing
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['action'] = 'pending_payments';
        
        ob_start();
        include 'api/payment_processing.php';
        $output = ob_get_clean();
        
        // Restore values
        $_GET = $backup_get;
        $_SERVER['REQUEST_METHOD'] = $backup_method;
        
        echo "Include test completed\n";
        echo "Output length: " . strlen($output) . " characters\n";
        
        if (strlen($output) > 0) {
            echo "First 200 chars of output:\n";
            echo substr($output, 0, 200) . "\n";
        }
    }
    
} catch (ParseError $e) {
    echo "PARSE ERROR: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "RUNTIME ERROR: " . $e->getMessage() . "\n";
}

ob_end_clean();
?>
