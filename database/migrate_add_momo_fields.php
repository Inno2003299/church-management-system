<?php
/**
 * Database Migration: Add MoMo and Paystack fields to instrumentalists table
 * Run this script once to update the existing database structure
 */

require_once '../config/db.php';

echo "<h2>Database Migration: Adding MoMo and Paystack Fields</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px 0;'>";

try {
    // Check if we're connected
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    echo "✓ Database connection established<br>";

    // Check if instrumentalists table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'instrumentalists'");
    if ($table_check->num_rows == 0) {
        throw new Exception('Instrumentalists table does not exist. Please run setup.php first.');
    }

    echo "✓ Instrumentalists table found<br>";

    // Get current table structure
    $columns_result = $conn->query("SHOW COLUMNS FROM instrumentalists");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    echo "✓ Current table has " . count($existing_columns) . " columns<br>";

    // Define new columns to add
    $new_columns = [
        'momo_provider' => "ENUM('MTN', 'Vodafone', 'AirtelTigo', 'Other') NULL COMMENT 'Mobile Money Provider'",
        'momo_number' => "VARCHAR(20) NULL COMMENT 'Mobile Money Number'",
        'momo_name' => "VARCHAR(255) NULL COMMENT 'Name registered with MoMo account'",
        'bank_account_number' => "VARCHAR(50) NULL COMMENT 'Bank Account Number'",
        'bank_name' => "VARCHAR(100) NULL COMMENT 'Bank Name'",
        'bank_account_name' => "VARCHAR(255) NULL COMMENT 'Bank Account Name'",
        'preferred_payment_method' => "ENUM('Mobile Money', 'Bank Transfer', 'Cash') DEFAULT 'Mobile Money' COMMENT 'Preferred payment method'",
        'paystack_recipient_code' => "VARCHAR(100) NULL COMMENT 'Paystack transfer recipient code'"
    ];

    $added_columns = 0;
    $skipped_columns = 0;

    foreach ($new_columns as $column_name => $column_definition) {
        if (!in_array($column_name, $existing_columns)) {
            $sql = "ALTER TABLE instrumentalists ADD COLUMN $column_name $column_definition";
            
            if ($conn->query($sql)) {
                echo "✓ Added column: <strong>$column_name</strong><br>";
                $added_columns++;
            } else {
                echo "✗ Failed to add column $column_name: " . $conn->error . "<br>";
            }
        } else {
            echo "- Column <strong>$column_name</strong> already exists (skipped)<br>";
            $skipped_columns++;
        }
    }

    // Update instrumentalist_payments table for Paystack fields
    echo "<br><strong>Updating instrumentalist_payments table...</strong><br>";
    
    $payment_table_check = $conn->query("SHOW TABLES LIKE 'instrumentalist_payments'");
    if ($payment_table_check->num_rows > 0) {
        // Get current payment table structure
        $payment_columns_result = $conn->query("SHOW COLUMNS FROM instrumentalist_payments");
        $existing_payment_columns = [];
        while ($row = $payment_columns_result->fetch_assoc()) {
            $existing_payment_columns[] = $row['Field'];
        }

        // Define new payment columns
        $new_payment_columns = [
            'paystack_transfer_code' => "VARCHAR(100) NULL COMMENT 'Paystack transfer code'",
            'paystack_transfer_id' => "VARCHAR(100) NULL COMMENT 'Paystack transfer ID'",
            'paystack_status' => "VARCHAR(50) NULL COMMENT 'Paystack transfer status'",
            'paystack_failure_reason' => "TEXT NULL COMMENT 'Reason for failed Paystack transfer'",
            'paystack_recipient_code' => "VARCHAR(100) NULL COMMENT 'Paystack recipient code used'"
        ];

        foreach ($new_payment_columns as $column_name => $column_definition) {
            if (!in_array($column_name, $existing_payment_columns)) {
                $sql = "ALTER TABLE instrumentalist_payments ADD COLUMN $column_name $column_definition";
                
                if ($conn->query($sql)) {
                    echo "✓ Added payment column: <strong>$column_name</strong><br>";
                    $added_columns++;
                } else {
                    echo "✗ Failed to add payment column $column_name: " . $conn->error . "<br>";
                }
            } else {
                echo "- Payment column <strong>$column_name</strong> already exists (skipped)<br>";
                $skipped_columns++;
            }
        }

        // Update payment_method enum to include Paystack Transfer
        $update_enum_sql = "ALTER TABLE instrumentalist_payments 
                           MODIFY COLUMN payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Mobile Money', 'Paystack Transfer', 'Other') DEFAULT 'Mobile Money'";
        
        if ($conn->query($update_enum_sql)) {
            echo "✓ Updated payment_method enum to include 'Paystack Transfer'<br>";
        } else {
            echo "- Payment method enum update failed or already updated: " . $conn->error . "<br>";
        }

        // Update payment_status enum to include Failed
        $update_status_sql = "ALTER TABLE instrumentalist_payments 
                             MODIFY COLUMN payment_status ENUM('Pending', 'Approved', 'Paid', 'Failed', 'Cancelled') DEFAULT 'Pending'";
        
        if ($conn->query($update_status_sql)) {
            echo "✓ Updated payment_status enum to include 'Failed'<br>";
        } else {
            echo "- Payment status enum update failed or already updated: " . $conn->error . "<br>";
        }

    } else {
        echo "⚠ instrumentalist_payments table not found<br>";
    }

    echo "<br><strong>Migration Summary:</strong><br>";
    echo "✓ Added $added_columns new columns<br>";
    echo "- Skipped $skipped_columns existing columns<br>";
    echo "<br>✅ <strong>Migration completed successfully!</strong><br>";
    echo "<br>You can now:<br>";
    echo "1. Register instrumentalists with MoMo details<br>";
    echo "2. Process payments via Paystack<br>";
    echo "3. Track payment status and references<br>";

} catch (Exception $e) {
    echo "<br>❌ <strong>Migration failed:</strong> " . $e->getMessage() . "<br>";
}

echo "</div>";
echo "<p><a href='../index.php'>← Back to Church Management System</a></p>";
?>
