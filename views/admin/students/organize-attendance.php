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
    <title>Organize School Days - Kindergarten Admin System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/admin-index.css">
    <link rel="stylesheet" href="../../../assets/css/organize-attendance.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Kindergarten Admin</h2>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="../index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                
               
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search for students, teachers...">
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=4e73df&color=fff" alt="Admin User">
                <span>Admin User</span>
            </div>
        </div>

        <!-- Page Content -->
        <div class="dashboard">
            <h1 class="page-title">Organize School Days</h1>
            <p class="page-description">Set up school days and holidays for each semester. Unchecked days = School days (default), Checked days = Holidays/Closed days.</p>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Semester Selection -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-graduation-cap"></i> Select Semester</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="academicYearFilter">Academic Year (Optional Filter)</label>
                        <select id="academicYearFilter" class="form-control">
                            <option value="">All Academic Years</option>
                            <!-- Academic years will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterSelect">Semester *</label>
                        <select id="semesterSelect" class="form-control" required>
                            <option value="">Choose a semester...</option>
                            <!-- Semesters will be populated via JavaScript -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Weekly Holiday Settings -->
            <div class="card" id="weeklyHolidayCard" style="display: none;">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-times"></i> Weekly Recurring Holidays</h2>
                </div>
                <div class="card-body">
                    <p class="form-help">Select days that are ALWAYS holidays (e.g., Friday, Saturday). These days will be automatically marked as closed in the calendar below.</p>
                    <div class="weekly-holiday-selector">
                        <div class="day-checkbox">
                            <input type="checkbox" id="sunday" value="0">
                            <label for="sunday">Sunday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="monday" value="1">
                            <label for="monday">Monday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="tuesday" value="2">
                            <label for="tuesday">Tuesday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="wednesday" value="3">
                            <label for="wednesday">Wednesday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="thursday" value="4">
                            <label for="thursday">Thursday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="friday" value="5">
                            <label for="friday">Friday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="saturday" value="6">
                            <label for="saturday">Saturday</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="card" id="calendarCard" style="display: none;">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-alt"></i> School Days Calendar</h2>
                    <div class="card-actions">
                        <button class="btn btn-refresh" onclick="generateCalendar()">
                            <i class="fas fa-sync-alt"></i> Refresh Calendar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-color school-day"></div>
                            <span>School Day (Unchecked)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color holiday-day"></div>
                            <span>Holiday/Closed (Checked)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color recurring-holiday"></div>
                            <span>Recurring Holiday (Auto-checked)</span>
                        </div>
                    </div>
                    
                    <div id="calendarContainer" class="calendar-container">
                        <!-- Calendar will be generated here via JavaScript -->
                    </div>
                    
                    <div class="calendar-actions">
                        <button type="button" class="btn btn-primary" id="saveSchoolDaysBtn" onclick="saveSchoolDays()">
                            <i class="fas fa-save"></i>
                            <span id="saveText">Save School Days</span>
                            <div class="loading-spinner" id="saveLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading data...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Semester Selected</h3>
                <p>Please select a semester to organize school days.</p>
            </div>
        </div>
    </div>

    <!-- Add Academic Year Modal -->
    <div id="addAcademicYearModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-alt"></i> Add Academic Year</h2>
                <span class="close" onclick="closeAddAcademicYearModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addAcademicYearForm">
                    <div class="form-group">
                        <label for="yearName">Academic Year Name *</label>
                        <input type="text" id="yearName" name="yearName" class="form-control" placeholder="e.g., 2024-2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicStartDate">Start Date *</label>
                        <input type="date" id="academicStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicEndDate">End Date *</label>
                        <input type="date" id="academicEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="academicIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            Set as current academic year
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddAcademicYearModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitAcademicYearBtn">
                            <span id="academicYearSubmitText">Add Academic Year</span>
                            <div class="loading" id="academicYearLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Semester Modal -->
    <div id="addSemesterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-graduation-cap"></i> Add Semester</h2>
                <span class="close" onclick="closeAddSemesterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addSemesterForm">
                    <div class="form-group">
                        <label for="academicYearSelect">Academic Year *</label>
                        <select id="academicYearSelect" name="academicYearId" class="form-control" required>
                            <option value="">Choose an academic year...</option>
                            <!-- Academic years will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="termName">Semester Name *</label>
                        <input type="text" id="termName" name="termName" class="form-control" placeholder="e.g., Fall 2024, Spring 2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterStartDate">Start Date *</label>
                        <input type="date" id="semesterStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterEndDate">End Date *</label>
                        <input type="date" id="semesterEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="semesterIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            Set as current semester
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddSemesterModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitSemesterBtn">
                            <span id="semesterSubmitText">Add Semester</span>
                            <div class="loading" id="semesterLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../../assets/js/organize-attendance.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
