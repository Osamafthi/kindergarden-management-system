<?php
// Start session and include authentication
session_start();
require_once '../../../includes/autoload.php';
require_once '../../../includes/SessionManager.php';

// Initialize database and session manager
$database = new Database();
$sessionManager = new SessionManager($database);

// Check if user is logged in as admin
if (!User::isLoggedIn() || !User::isAdmin()) {
    // Redirect to login page
    header('Location: ../../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View & Edit Students - Kindergarten Admin System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/view_edit_student.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> View & Edit Students</h1>
            <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Alert Messages -->
        <div id="alertContainer"></div>
        
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search students by name...">
                <button id="searchBtn" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
                <button id="clearBtn" class="clear-btn">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
        
        <!-- Status Toggle Buttons -->
        <div class="status-toggle-container">
            <button class="status-toggle-btn active" id="activeStudentsBtn" onclick="studentsManager.toggleStudentsView('active')">
                <i class="fas fa-check-circle"></i> Active Students
            </button>
            <button class="status-toggle-btn" id="inactiveStudentsBtn" onclick="studentsManager.toggleStudentsView('inactive')">
                <i class="fas fa-times-circle"></i> Inactive Students
            </button>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-child"></i>
                </div>
                <div class="stat-number" id="activeStudents">0</div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="stat-number" id="recentStudents">0</div>
                <div class="stat-label">Enrolled This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                <div class="stat-number" id="avgAge">0</div>
                <div class="stat-label">Average Age</div>
            </div>
        </div>
        
        <!-- Students Table -->
        <div class="students-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Students List</h2>
            </div>
            
            <div class="table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Age</th>
                            <th>Level</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <!-- Students will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading students...</p>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-user-slash"></i>
                <h3>No Students Found</h3>
                <p>No students match your search criteria.</p>
            </div>
            
            <!-- Pagination -->
            <div id="paginationContainer" class="pagination-container" style="display: none;">
                <div class="pagination-info" id="paginationInfo">
                    Showing 0 to 0 of 0 entries
                </div>
                <div class="pagination-wrapper">
                    <button class="pagination-nav-btn" id="firstPageBtn" onclick="studentsManager.goToPage(1)" disabled>
                        <i class="fas fa-angle-double-left"></i> First
                    </button>
                    <button class="pagination-nav-btn" id="prevPageBtn" onclick="studentsManager.goToPreviousPage()" disabled>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <div class="pagination-buttons" id="paginationButtons">
                        <!-- Pagination buttons will be generated here -->
                    </div>
                    <button class="pagination-nav-btn" id="nextPageBtn" onclick="studentsManager.goToNextPage()" disabled>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="pagination-nav-btn" id="lastPageBtn" onclick="studentsManager.goToLastPage()" disabled>
                        Last <i class="fas fa-angle-double-right"></i>
                    </button>
                    <div class="pagination-jump">
                        <span>Go to:</span>
                        <input type="number" id="jumpToPageInput" min="1" placeholder="Page">
                        <button onclick="studentsManager.jumpToPage()">Go</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Student</h3>
                <button class="modal-close" onclick="studentsManager.closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm">
                    <input type="hidden" id="editStudentId" name="studentId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editFirstName">First Name *</label>
                            <input type="text" id="editFirstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="editLastName">Last Name *</label>
                            <input type="text" id="editLastName" name="lastName" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editDateOfBirth">Date of Birth *</label>
                            <input type="date" id="editDateOfBirth" name="dateOfBirth" required>
                        </div>
                        <div class="form-group">
                            <label for="editGender">Gender *</label>
                            <select id="editGender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editStudentLevel">Student Level *</label>
                        <select id="editStudentLevel" name="studentLevel" required>
                            <option value="">Select Level</option>
                            <option value="pre-k">Pre-K</option>
                            <option value="kindergarten">Kindergarten</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPhoto">Student Photo</label>
                        <div class="photo-upload" id="editPhotoUploadContainer">
                            <input type="file" id="editPhoto" name="photo" accept="image/*">
                            <div class="photo-icon" id="editPhotoIcon">📷</div>
                            <p id="editUploadText">Click to upload a photo</p>
                            <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                                Supported formats: JPG, PNG, GIF (Max 5MB)
                            </p>
                            <div class="upload-status" id="editUploadStatus"></div>
                            <img class="photo-preview" id="editPhotoPreview" alt="Preview">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-secondary" onclick="studentsManager.closeEditModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-modal btn-modal-primary" id="updateStudentBtn" onclick="studentsManager.updateStudent()">
                    <i class="fas fa-save"></i> Update Student
                </button>
            </div>
        </div>
    </div>

    <!-- Assign to Classroom Modal -->
    <div id="assignClassroomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-door-open"></i> Assign Student to Classroom</h3>
                <button class="modal-close" onclick="studentsManager.closeAssignClassroomModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="studentInfo" class="student-info-display">
                    <!-- Student info will be populated here -->
                </div>
                
                <div class="form-group">
                    <label for="classroomSelect">Select Classroom *</label>
                    <select id="classroomSelect" name="classroomId" required>
                        <option value="">Loading classrooms...</option>
                    </select>
                </div>
                
                <div id="classroomInfo" class="classroom-info-display" style="display: none;">
                    <!-- Classroom info will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-secondary" onclick="studentsManager.closeAssignClassroomModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-modal btn-modal-primary" id="assignStudentBtn" onclick="studentsManager.assignStudentToClassroom()">
                    <i class="fas fa-check"></i> Assign Student
                </button>
            </div>
        </div>
    </div>

    <script>
        class StudentsManager {
            constructor() {
                this.students = [];
                this.currentPage = 1;
                this.itemsPerPage = 10;
                this.totalCount = 0;
                this.searchTerm = '';
                this.isLoading = false;
                this.currentStatusFilter = 'active'; // 'active' or 'inactive'
                
                this.initializeEventListeners();
                this.loadStudents();
            }
            
            initializeEventListeners() {
                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.searchTerm = document.getElementById('searchInput').value.trim();
                    this.currentPage = 1;
                    this.loadStudents();
                });
                
                // Clear search
                document.getElementById('clearBtn').addEventListener('click', () => {
                    document.getElementById('searchInput').value = '';
                    this.searchTerm = '';
                    this.currentPage = 1;
                    this.loadStudents();
                });
                
                // Search on Enter key
                document.getElementById('searchInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.searchTerm = document.getElementById('searchInput').value.trim();
                        this.currentPage = 1;
                        this.loadStudents();
                    }
                });
                
                // Close modal when clicking outside
                document.getElementById('editStudentModal').addEventListener('click', (e) => {
                    if (e.target === document.getElementById('editStudentModal')) {
                        this.closeEditModal();
                    }
                });
                
                // Close modal on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && document.getElementById('editStudentModal').style.display === 'block') {
                        this.closeEditModal();
                    }
                });
                
                // Photo upload functionality
                document.getElementById('editPhoto').addEventListener('change', (e) => {
                    this.handleEditPhotoChange(e);
                });
                
                // Jump to page input keyboard support
                document.getElementById('jumpToPageInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.jumpToPage();
                    }
                });
            }
            
            async loadStudents() {
                if (this.isLoading) return;
                
                this.isLoading = true;
                this.showLoading();
                
                try {
                    const params = new URLSearchParams({
                        limit: this.itemsPerPage,
                        offset: (this.currentPage - 1) * this.itemsPerPage,
                        status: this.currentStatusFilter
                    });
                    
                    if (this.searchTerm) {
                        params.append('search', this.searchTerm);
                    }
                    
                    const response = await fetch(`../../../api/get-students.php?${params}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.students = data.students;
                        this.totalCount = data.total_count;
                        this.renderStudents();
                        this.updateStats();
                        this.renderPagination();
                    } else {
                        this.showAlert('Error loading students: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showAlert('Network error. Please try again.', 'error');
                } finally {
                    this.isLoading = false;
                    this.hideLoading();
                }
            }
            
            // Toggle between active and inactive students
            toggleStudentsView(status) {
                this.currentStatusFilter = status;
                this.currentPage = 1;
                this.searchTerm = '';
                document.getElementById('searchInput').value = '';
                
                // Update button states
                const activeBtn = document.getElementById('activeStudentsBtn');
                const inactiveBtn = document.getElementById('inactiveStudentsBtn');
                
                if (status === 'active') {
                    activeBtn.classList.add('active');
                    inactiveBtn.classList.remove('active');
                } else {
                    activeBtn.classList.remove('active');
                    inactiveBtn.classList.add('active');
                }
                
                this.loadStudents();
            }
            
            renderStudents() {
                const tbody = document.getElementById('studentsTableBody');
                
                if (this.students.length === 0) {
                    this.showEmptyState();
                    return;
                }
                
                this.hideEmptyState();
                
                tbody.innerHTML = this.students.map(student => {
                    const fullName = `${student.first_name} ${student.last_name}`;
                    const age = this.calculateAge(student.date_of_birth);
                    const enrollmentDate = new Date(student.enrollment_date).toLocaleDateString();
                    const photoUrl = this.normalizePhotoUrl(student.photo);
                    
                    return `
                        <tr class="fade-in">
                            <td>
                                <div class="student-info">
                                    ${photoUrl ? `<img class="student-avatar-img" src="${photoUrl}" alt="${fullName}">` : `
                                    <div class=\"student-avatar\">${student.first_name.charAt(0).toUpperCase()}${student.last_name.charAt(0).toUpperCase()}</div>`}
                                    <div class="student-details">
                                        <h4>${fullName}</h4>
                                        <p>ID: ${student.id}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="student-details">
                                    <p><strong>${age} years old</strong></p>
                                    <p>Born: ${new Date(student.date_of_birth).toLocaleDateString()}</p>
                                </div>
                            </td>
                            <td>
                                <div class="student-details">
                                    <p><strong>${student.current_level_id}</strong></p>
                                    <p>${student.gender}</p>
                                  
                                </div>
                            </td>
                            <td>
                                <div class="student-details">
                                    <p><strong>${enrollmentDate}</strong></p>
                                    <p>${this.getTimeSinceEnrollment(student.enrollment_date)}</p>
                                </div>
                            </td>
                             
                            <td>
                                <span class="status-badge ${student.status === 'active' ? 'active' : 'inactive'}">
                                    ${student.status === 'active' ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    ${this.currentStatusFilter === 'active' ? `
                                    <button class="btn btn-view" onclick="studentsManager.assignToClassroom(${student.id})">
                                        <i class="fas fa-door-open"></i> Assign to Classroom
                                    </button>
                                    <button class="btn btn-deactivate" onclick="studentsManager.deactivateStudent(${student.id})">
                                        <i class="fas fa-user-slash"></i> De-activate
                                    </button>
                                    ` : `
                                    <button class="btn btn-activate" onclick="studentsManager.activateStudent(${student.id})">
                                        <i class="fas fa-user-check"></i> Activate
                                    </button>
                                    `}
                                    <button class="btn btn-reports" onclick="studentsManager.viewReport(${student.id})">
                                        <i class="fas fa-chart-line"></i> Reports
                                    </button>
                                    <button class="btn btn-edit" onclick="studentsManager.editStudent(${student.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-delete" onclick="studentsManager.deleteStudent(${student.id})">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Build absolute photo URL from stored relative path
            normalizePhotoUrl(photoPath) {
                if (!photoPath) return '';
                // If already absolute (http/https), return as-is
                if (/^https?:\/\//i.test(photoPath)) return photoPath;
                // Ensure leading slash and prefix with app base
                let normalized = photoPath.replace(/^\.+\/?/, '');
                if (!normalized.startsWith('/')) normalized = '/' + normalized;
                // Prepend app base path
                normalized = '/kindergarden' + normalized.replace(/^\/kindergarden\//, '/');
                return normalized;
            }
            
            calculateAge(dateOfBirth) {
                const today = new Date();
                const birthDate = new Date(dateOfBirth);
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                return age;
            }
            
            getTimeSinceEnrollment(enrollmentDate) {
                const today = new Date();
                const enrollment = new Date(enrollmentDate);
                const diffTime = Math.abs(today - enrollment);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays === 0) return 'Today';
                if (diffDays === 1) return 'Yesterday';
                if (diffDays < 30) return `${diffDays} days ago`;
                if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
                return `${Math.floor(diffDays / 365)} years ago`;
            }
            
            updateStats() {
                document.getElementById('totalStudents').textContent = this.totalCount;
                document.getElementById('activeStudents').textContent = this.students.length;
                
                // Calculate recent students (enrolled this month)
                const thisMonth = new Date().getMonth();
                const thisYear = new Date().getFullYear();
                const recentCount = this.students.filter(student => {
                    const enrollmentDate = new Date(student.enrollment_date);
                    return enrollmentDate.getMonth() === thisMonth && enrollmentDate.getFullYear() === thisYear;
                }).length;
                document.getElementById('recentStudents').textContent = recentCount;
                
                // Calculate average age
                if (this.students.length > 0) {
                    const totalAge = this.students.reduce((sum, student) => {
                        return sum + this.calculateAge(student.date_of_birth);
                    }, 0);
                    const avgAge = Math.round(totalAge / this.students.length);
                    document.getElementById('avgAge').textContent = avgAge;
                }
            }
            
            renderPagination() {
                const container = document.getElementById('paginationContainer');
                const info = document.getElementById('paginationInfo');
                const buttons = document.getElementById('paginationButtons');
                
                if (this.totalCount <= this.itemsPerPage) {
                    container.style.display = 'none';
                    return;
                }
                
                container.style.display = 'flex';
                
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
                const endItem = Math.min(this.currentPage * this.itemsPerPage, this.totalCount);
                
                info.textContent = `Showing ${startItem} to ${endItem} of ${this.totalCount} entries`;
                
                // Update navigation buttons
                document.getElementById('firstPageBtn').disabled = this.currentPage === 1;
                document.getElementById('prevPageBtn').disabled = this.currentPage === 1;
                document.getElementById('nextPageBtn').disabled = this.currentPage === totalPages;
                document.getElementById('lastPageBtn').disabled = this.currentPage === totalPages;
                
                // Generate smart pagination buttons with ellipsis
                buttons.innerHTML = this.generatePaginationButtons(this.currentPage, totalPages);
                
                // Update jump to page input
                document.getElementById('jumpToPageInput').max = totalPages;
                document.getElementById('jumpToPageInput').value = '';
            }
            
            generatePaginationButtons(currentPage, totalPages) {
                const buttons = [];
                const maxVisiblePages = 7; // Show max 7 page buttons
                
                if (totalPages <= maxVisiblePages) {
                    // Show all pages if total is small
                    for (let i = 1; i <= totalPages; i++) {
                        buttons.push(this.createPageButton(i, i === currentPage));
                    }
                } else {
                    // Smart pagination with ellipsis
                    if (currentPage <= 4) {
                        // Show first 5 pages, ellipsis, last page
                        for (let i = 1; i <= 5; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                        buttons.push(this.createEllipsisButton());
                        buttons.push(this.createPageButton(totalPages, false));
                    } else if (currentPage >= totalPages - 3) {
                        // Show first page, ellipsis, last 5 pages
                        buttons.push(this.createPageButton(1, false));
                        buttons.push(this.createEllipsisButton());
                        for (let i = totalPages - 4; i <= totalPages; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                    } else {
                        // Show first page, ellipsis, current-1, current, current+1, ellipsis, last page
                        buttons.push(this.createPageButton(1, false));
                        buttons.push(this.createEllipsisButton());
                        for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                        buttons.push(this.createEllipsisButton());
                        buttons.push(this.createPageButton(totalPages, false));
                    }
                }
                
                return buttons.join('');
            }
            
            createPageButton(pageNumber, isActive) {
                return `<button class="pagination-btn ${isActive ? 'active' : ''}" onclick="studentsManager.goToPage(${pageNumber})">${pageNumber}</button>`;
            }
            
            createEllipsisButton() {
                return `<span class="pagination-btn ellipsis">...</span>`;
            }
            
            goToPage(page) {
                if (page < 1 || page > Math.ceil(this.totalCount / this.itemsPerPage)) return;
                this.currentPage = page;
                this.loadStudents();
            }
            
            goToPreviousPage() {
                if (this.currentPage > 1) {
                    this.goToPage(this.currentPage - 1);
                }
            }
            
            goToNextPage() {
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                if (this.currentPage < totalPages) {
                    this.goToPage(this.currentPage + 1);
                }
            }
            
            goToFirstPage() {
                this.goToPage(1);
            }
            
            goToLastPage() {
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                this.goToPage(totalPages);
            }
            
            jumpToPage() {
                const input = document.getElementById('jumpToPageInput');
                const page = parseInt(input.value);
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                
                if (page && page >= 1 && page <= totalPages) {
                    this.goToPage(page);
                } else {
                    this.showAlert(`Please enter a valid page number between 1 and ${totalPages}`, 'error');
                }
            }
            
            showLoading() {
                document.getElementById('loadingContainer').style.display = 'block';
                document.getElementById('studentsTableBody').innerHTML = '';
                document.getElementById('paginationContainer').style.display = 'none';
            }
            
            hideLoading() {
                document.getElementById('loadingContainer').style.display = 'none';
            }
            
            showEmptyState() {
                document.getElementById('emptyState').style.display = 'block';
                document.getElementById('paginationContainer').style.display = 'none';
            }
            
            hideEmptyState() {
                document.getElementById('emptyState').style.display = 'none';
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                `;
                alert.style.display = 'block';
                
                alertContainer.innerHTML = '';
                alertContainer.appendChild(alert);
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
            
            // Action methods
            viewStudent(id) {
                this.showAlert(`View student with ID: ${id}`, 'info');
                // Implement view functionality
            }
            
            editStudent(id) {
                // Find the student data
                const student = this.students.find(s => s.id == id);
                if (!student) {
                    this.showAlert('Student not found', 'error');
                    return;
                }
                
                // Populate the modal with student data
                this.populateEditModal(student);
                this.showEditModal();
            }
            
            populateEditModal(student) {
                document.getElementById('editStudentId').value = student.id;
                document.getElementById('editFirstName').value = student.first_name;
                document.getElementById('editLastName').value = student.last_name;
                document.getElementById('editDateOfBirth').value = student.date_of_birth;
                document.getElementById('editGender').value = student.gender;
                document.getElementById('editStudentLevel').value = student.student_level_at_enrollment || student.student_level;
                
                // Handle photo display
               
                this.resetEditPhotoUpload();
                if (student.photo) {
                    this.showExistingPhoto(student.photo);
                }
            }
            
            showEditModal() {
                document.getElementById('editStudentModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            closeEditModal() {
                document.getElementById('editStudentModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                document.getElementById('editStudentForm').reset();
                this.resetEditPhotoUpload();
            }
            
            async updateStudent() {
                // Validate required fields
                const requiredFields = ['firstName', 'lastName', 'dateOfBirth', 'gender', 'studentLevel'];
                for (const field of requiredFields) {
                    const element = document.getElementById('edit' + field.charAt(0).toUpperCase() + field.slice(1));
                    if (!element || !element.value || element.value.trim() === '') {
                        this.showAlert(`Please fill in the ${field} field`, 'error');
                        return;
                    }
                }
                
                // Disable the update button
                const updateBtn = document.getElementById('updateStudentBtn');
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                
                try {
                    // Create FormData for file upload
                    const formData = new FormData();
                    formData.append('studentId', document.getElementById('editStudentId').value);
                    formData.append('firstName', document.getElementById('editFirstName').value.trim());
                    formData.append('lastName', document.getElementById('editLastName').value.trim());
                    formData.append('dateOfBirth', document.getElementById('editDateOfBirth').value);
                    formData.append('gender', document.getElementById('editGender').value);
                    formData.append('studentLevel', document.getElementById('editStudentLevel').value);
                    
                    // Add photo if selected
                    const photoFile = document.getElementById('editPhoto').files[0];
                    if (photoFile) {
                        formData.append('photo', photoFile);
                    }
                    
                    const response = await fetch('../../../api/update-student.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('Student updated successfully!', 'success');
                        this.closeEditModal();
                        this.loadStudents(); // Refresh the students list
                    } else {
                        this.showAlert('Error updating student: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showAlert('Network error. Please try again.', 'error');
                } finally {
                    // Re-enable the update button
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<i class="fas fa-save"></i> Update Student';
                }
            }
            
            deleteStudent(id) {
                if (confirm('Are you sure you want to delete this student?')) {
                    this.showAlert(`Delete student with ID: ${id}`, 'warning');
                    // Implement delete functionality
                }
            }
            
            viewReport(student_id) {
                window.location.href = `../reports/student-reports.php?student_id=${student_id}`;
            }
            
            // De-activate student
            async deactivateStudent(student_id) {
                if (confirm('Are you sure you want to de-activate this student?')) {
                    try {
                        const response = await fetch('../../../api/update-student-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                student_id: student_id,
                                status: 'inactive'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert('Student de-activated successfully!', 'success');
                            this.loadStudents(); // Reload the list
                        } else {
                            this.showAlert('Error de-activating student: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showAlert('Network error. Please try again.', 'error');
                    }
                }
            }
            
            // Activate student
            async activateStudent(student_id) {
                if (confirm('Are you sure you want to activate this student?')) {
                    try {
                        const response = await fetch('../../../api/update-student-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                student_id: student_id,
                                status: 'active'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert('Student activated successfully!', 'success');
                            this.loadStudents(); // Reload the list
                        } else {
                            this.showAlert('Error activating student: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showAlert('Network error. Please try again.', 'error');
                    }
                }
            }
            
            // Photo upload methods
            handleEditPhotoChange(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('editPhotoPreview');
                const photoUpload = document.getElementById('editPhotoUploadContainer');
                const photoIcon = document.getElementById('editPhotoIcon');
                const uploadText = document.getElementById('editUploadText');
                const uploadStatus = document.getElementById('editUploadStatus');
                
                // Reset status
                uploadStatus.className = 'upload-status';
                uploadStatus.textContent = '';
                
                if (file) {
                    // Validate file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        this.showEditUploadStatus('Photo size must be less than 5MB', 'error');
                        this.resetEditPhotoUpload();
                        return;
                    }
                    
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        this.showEditUploadStatus('Please select a valid image file', 'error');
                        this.resetEditPhotoUpload();
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        photoUpload.classList.add('has-image');
                        photoIcon.textContent = '✅';
                        uploadText.textContent = 'Photo selected: ' + file.name;
                        this.showEditUploadStatus('Photo ready for upload!', 'success');
                        photoUpload.style.padding = '15px';
                    }.bind(this);
                    reader.readAsDataURL(file);
                } else {
                    this.resetEditPhotoUpload();
                }
            }
            
            showEditUploadStatus(message, type) {
                const uploadStatus = document.getElementById('editUploadStatus');
                uploadStatus.textContent = message;
                uploadStatus.className = 'upload-status status-' + type;
            }
            
            resetEditPhotoUpload() {
                const preview = document.getElementById('editPhotoPreview');
                const photoUpload = document.getElementById('editPhotoUploadContainer');
                const photoIcon = document.getElementById('editPhotoIcon');
                const uploadText = document.getElementById('editUploadText');
                const uploadStatus = document.getElementById('editUploadStatus');
                
                preview.style.display = 'none';
                photoUpload.classList.remove('has-image');
                photoIcon.textContent = '📷';
                uploadText.textContent = 'Click to upload a photo';
                uploadStatus.className = 'upload-status';
                uploadStatus.textContent = '';
                photoUpload.style.padding = '30px';
                document.getElementById('editPhoto').value = '';
            }
            
            showExistingPhoto(photoUrl) {
                const preview = document.getElementById('editPhotoPreview');
                const photoUpload = document.getElementById('editPhotoUploadContainer');
                const photoIcon = document.getElementById('editPhotoIcon');
                const uploadText = document.getElementById('editUploadText');
                
                // Normalize to absolute path rooted at /kindergarden
                let normalizedUrl = photoUrl || '';
                if (normalizedUrl && !/^https?:\/\//i.test(normalizedUrl)) {
                    // Ensure it starts with a leading slash
                    if (normalizedUrl.startsWith('../')) {
                        // Stored paths like "assets/uploads/..." are fine; just ensure leading slash
                        normalizedUrl = normalizedUrl.replace(/^\.+\/?/, '');
                    }
                    if (!normalizedUrl.startsWith('/')) {
                        normalizedUrl = '/' + normalizedUrl;
                    }
                    normalizedUrl = '/kindergarden' + normalizedUrl.replace(/^\/kindergarden\//, '/');
                }
                preview.src = normalizedUrl || photoUrl;
                preview.style.display = 'block';
                photoUpload.classList.add('has-image');
                photoIcon.textContent = '✅';
                uploadText.textContent = 'Current photo (click to change)';
                photoUpload.style.padding = '15px';
               
            }
        }
        
        // Initialize the students manager when the page loads
        let studentsManager;
        document.addEventListener('DOMContentLoaded', function() {
            studentsManager = new StudentsManager();
        });
    </script>
    <script src="../../../assets/js/assign_student_to_classroom.js"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
