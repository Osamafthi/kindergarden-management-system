<?php
class Admin extends User {
    // Database connection
    private $user_id;
    private $full_name;
    private $phone_number;
    private $date_of_hire;
    private $position_title;
    private $department;
    private $salary;
    private $permissions;
    private $is_super_admin;
    private $last_login;
    private $notes;

    // Constructor
    public function __construct($database) {
        parent::__construct($database); // Call parent constructor
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getFullName() {
        return $this->full_name;
    }

    public function getPhoneNumber() {
        return $this->phone_number;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getDateOfHire() {
        return $this->date_of_hire;
    }

    public function getPositionTitle() {
        return $this->position_title;
    }

    public function getDepartment() {
        return $this->department;
    }

    public function getSalary() {
        return $this->salary;
    }

    public function getPermissions() {
        return $this->permissions;
    }

    public function getIsSuperAdmin() {
        return $this->is_super_admin;
    }

    public function getIsActive() {
        return $this->is_active;
    }

    public function getLastLogin() {
        return $this->last_login;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setUserId($user_id) {
        $this->user_id = $user_id;
        return $this;
    }

    public function setFullName($full_name) {
        $this->full_name = $full_name;
        return $this;
    }

    public function setPhoneNumber($phone_number) {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function setDateOfHire($date_of_hire) {
        $this->date_of_hire = $date_of_hire;
        return $this;
    }

    public function setPositionTitle($position_title) {
        $this->position_title = $position_title;
        return $this;
    }

    public function setDepartment($department) {
        $this->department = $department;
        return $this;
    }

    public function setSalary($salary) {
        $this->salary = $salary;
        return $this;
    }

    public function setPermissions($permissions) {
        $this->permissions = $permissions;
        return $this;
    }

    public function setIsSuperAdmin($is_super_admin) {
        $this->is_super_admin = $is_super_admin;
        return $this;
    }

    public function setIsActive($is_active) {
        $this->is_active = $is_active;
        return $this;
    }

    public function setLastLogin($last_login) {
        $this->last_login = $last_login;
        return $this;
    }

    public function setNotes($notes) {
        $this->notes = $notes;
        return $this;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
        return $this;
    }

    // Optional: Method to load data from array (useful for database results)
    public function fromArray($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    // Optional: Method to convert object to array
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'date_of_hire' => $this->date_of_hire,
            'position_title' => $this->position_title,
            'department' => $this->department,
            'salary' => $this->salary,
            'permissions' => $this->permissions,
            'is_super_admin' => $this->is_super_admin,
            'is_active' => $this->is_active,
            'last_login' => $this->last_login,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    // Add academic year to database
    public function add_academic_year($data) {
        try {
            // Validate required fields
            if (empty($data['year_name'])) {
                return [
                    'success' => false,
                    'message' => 'Year name is required'
                ];
            }

            if (empty($data['start_date'])) {
                return [
                    'success' => false,
                    'message' => 'Start date is required'
                ];
            }

            if (empty($data['end_date'])) {
                return [
                    'success' => false,
                    'message' => 'End date is required'
                ];
            }

            // Validate date format
            $start_date = DateTime::createFromFormat('Y-m-d', $data['start_date']);
            $end_date = DateTime::createFromFormat('Y-m-d', $data['end_date']);

            if (!$start_date || !$end_date) {
                return [
                    'success' => false,
                    'message' => 'Invalid date format. Use YYYY-MM-DD'
                ];
            }

            // Validate that end date is after start date
            if ($end_date <= $start_date) {
                return [
                    'success' => false,
                    'message' => 'End date must be after start date'
                ];
            }

            // If marking as current, unmark other current academic years
            if (isset($data['is_current']) && $data['is_current']) {
                $unmark_sql = "UPDATE academic_years SET is_current = 0";
                $this->db->exec($unmark_sql);
            }

            // Prepare SQL statement
            $sql = "INSERT INTO academic_years (
                        year_name, 
                        start_date, 
                        end_date, 
                        is_current,
                        created_at,
                        updated_at
                    ) VALUES (
                        :year_name, 
                        :start_date, 
                        :end_date, 
                        :is_current,
                        NOW(),
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':year_name', $data['year_name'], PDO::PARAM_STR);
            $stmt->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
            $is_current = isset($data['is_current']) && $data['is_current'] ? 1 : 0;
            $stmt->bindParam(':is_current', $is_current, PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                $academic_year_id = $this->db->lastInsertId();

                return [
                    'success' => true,
                    'message' => 'Academic year added successfully',
                    'academic_year_id' => $academic_year_id,
                    'data' => [
                        'year_name' => $data['year_name'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'is_current' => $is_current
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add academic year to database',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
