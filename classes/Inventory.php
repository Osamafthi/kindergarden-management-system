<?php
require_once 'Database.php';

class Inventory {
    private $db;
    
    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Add a new product
    public function add_product($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['description']) || !isset($data['quantity'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            
            // Validate quantity is numeric and positive
            if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity must be a positive number'
                ];
            }
            
            // Check if product name already exists
            $check_query = "SELECT id FROM products WHERE name = :name";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':name', $data['name']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Product name already exists'
                ];
            }
            
            // Prepare SQL query
            $query = "INSERT INTO products (name, description, current_quantity, original_quantity, created_at) 
                      VALUES (:name, :description, :quantity, :quantity, NOW())";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':quantity', $data['quantity']);
            
            // Execute query
            if ($stmt->execute()) {
                $product_id = $this->db->lastInsertId();
                
                // Log the initial IN movement
                $this->log_movement($product_id, 'IN', $data['quantity']);
                
                return [
                    'success' => true,
                    'message' => 'Product added successfully',
                    'product_id' => $product_id,
                    'data' => [
                        'id' => $product_id,
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'current_quantity' => $data['quantity'],
                        'original_quantity' => $data['quantity']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add product'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get all products
    public function get_products() {
        try {
            $query = "SELECT * FROM products ORDER BY name ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Update product quantity
    public function update_quantity($product_id, $quantity, $movement_type) {
        try {
            // Validate inputs
            if (!is_numeric($product_id) || $product_id <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid product ID'
                ];
            }
            
            if (!is_numeric($quantity) || $quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity must be a positive number'
                ];
            }
            
            if (!in_array($movement_type, ['IN', 'OUT'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid movement type'
                ];
            }
            
            // Check if product exists
            $check_query = "SELECT current_quantity FROM products WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $product_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
            
            $current_quantity = $check_stmt->fetch(PDO::FETCH_ASSOC)['current_quantity'];
            
            // Calculate new quantity
            if ($movement_type === 'IN') {
                $new_quantity = $current_quantity + $quantity;
            } else { // OUT
                $new_quantity = $current_quantity - $quantity;
                if ($new_quantity < 0) {
                    return [
                        'success' => false,
                        'message' => 'Cannot decrease quantity below zero'
                    ];
                }
            }
            
            // Update product quantity
            $update_query = "UPDATE products SET current_quantity = :new_quantity, updated_at = NOW() WHERE id = :id";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindParam(':new_quantity', $new_quantity);
            $update_stmt->bindParam(':id', $product_id);
            
            if ($update_stmt->execute()) {
                // Log the movement
                $this->log_movement($product_id, $movement_type, $quantity);
                
                return [
                    'success' => true,
                    'message' => 'Quantity updated successfully',
                    'new_quantity' => $new_quantity
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update quantity'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Reset original quantity (for Edit Product feature)
    public function reset_original_quantity($product_id, $new_quantity) {
        try {
            // Validate inputs
            if (!is_numeric($product_id) || $product_id <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid product ID'
                ];
            }
            
            if (!is_numeric($new_quantity) || $new_quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity must be a positive number'
                ];
            }
            
            // Check if product exists
            $check_query = "SELECT id FROM products WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $product_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
            
            // Update both current and original quantities
            $update_query = "UPDATE products SET current_quantity = :new_quantity, original_quantity = :new_quantity, updated_at = NOW() WHERE id = :id";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindParam(':new_quantity', $new_quantity);
            $update_stmt->bindParam(':id', $product_id);
            
            if ($update_stmt->execute()) {
                // Log the movement as IN
                $this->log_movement($product_id, 'IN', $new_quantity);
                
                return [
                    'success' => true,
                    'message' => 'Product quantity reset successfully',
                    'new_quantity' => $new_quantity
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reset quantity'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Log inventory movement
    private function log_movement($product_id, $movement_type, $quantity) {
        try {
            $query = "INSERT INTO inventory_movement (product_id, movement_type, quantity, created_at) 
                      VALUES (:product_id, :movement_type, :quantity, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':movement_type', $movement_type);
            $stmt->bindParam(':quantity', $quantity);
            
            $stmt->execute();
        } catch (PDOException $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log inventory movement: " . $e->getMessage());
        }
    }
    
    // Get product by ID
    public function get_product_by_id($id) {
        try {
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'success' => true,
                    'data' => $row
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Product not found'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}
?>
