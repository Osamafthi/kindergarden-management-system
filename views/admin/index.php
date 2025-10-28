<?php
// Start session and include authentication
session_start();
require_once '../../includes/autoload.php';
require_once '../../includes/SessionManager.php';

// Initialize database and session manager
$database = new Database();
$sessionManager = new SessionManager($database);

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    User::logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Check if user is logged in as admin
if (!User::isLoggedIn() || !User::isAdmin()) {
    // Redirect to the existing login page
    header('Location: ../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kindergarten Admin System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin-index.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Kindergarten Admin</h2>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>الصفحة الرئيسية</span></a></li>
                <li><a href="teachers/add-teacher.php"><i class="fas fa-user-plus"></i> <span>اضافة مدرس</span></a></li>
                <li><a href="students/add-student.php"><i class="fas fa-child"></i> <span>اضافة طالب</span></a></li>
                <li><a href="classrooms/add-classroom.php"><i class="fas fa-school"></i> <span>اضافة فصل</span></a></li>
                <li><a href="#" onclick="openHomeworkModal()"><i class="fas fa-book"></i> <span>اضافة مادة جديدة قران، عقيدة</span></a></li>
                <li><a href="teachers/view-edit-teacher.php"><i class="fas fa-users"></i> <span>عرض وتعديل المعلمين</span></a></li>
                <li><a href="students/view-edit-student.php"><i class="fas fa-user-graduate"></i> <span>عرض وتعديل الطلاب</span></a></li>
                <li><a href="#" onclick="openCreateSessionModal()" ><i class="fas fa-tasks"></i> <span>اضافة حصة لمدرس </span></a></li>
                <li><a href="#" onclick="openAddAcademicYearModal()"><i class="fas fa-calendar-alt"></i> <span>اضافة سنة دراسية جديدة</span></a></li>
                <li><a href="#" onclick="openAddSemesterModal()"><i class="fas fa-graduation-cap"></i> <span>اضافة ترم جديد</span></a></li>
                <li><a href="students/organize-attendance.php"><i class="fas fa-calendar-check"></i> <span>اضافة ايام الدراسة وايام الاجازة</span></a></li>
               
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
                <span>الادمن</span>
                <a href="?logout=1" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard">
            <h1 class="page-title">عرض الصفحة الرئيسية</h1>

            <!-- Stats Cards -->
            <div class="cards-row">
                <div class="card stat-card teachers">
                    <div class="card-body">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3>24</h3>
                        <p>عدد المعلمين</p>
                    </div>
                </div>

                <div class="card stat-card students">
                    <div class="card-body">
                        <i class="fas fa-child"></i>
                        <h3>186</h3>
                        <p>عدد الطلاب</p>
                    </div>
                </div>

                <div class="card stat-card classrooms">
                    <div class="card-body">
                        <i class="fas fa-school"></i>
                        <h3>8</h3>
                        <p>عدد الفصول</p>
                    </div>
                </div>

                <div class="card stat-card homework">
                    <div class="card-body">
                        <i class="fas fa-book"></i>
                        <h3>12</h3>
                        <p>عدد مواد القران والعقيدة</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <div class="action-btn" onclick="location.href='teachers/add-teacher.php'">
                            <i class="fas fa-user-plus"></i>
                            <span>اضافة معلمr</span>
                        </div>
                        <div class="action-btn" onclick="location.href='students/add-student.php'">
                            <i class="fas fa-child"></i>
                            <span>اضافة طالب</span>
                        </div>
                        <div class="action-btn" onclick="location.href='classrooms/add-classroom.php'">
                            <i class="fas fa-school"></i>
                            <span>اضافة فصل</span>
                        </div>
                        <div class="action-btn" onclick="openHomeworkModal()">
                            <i class="fas fa-book"></i>
                            <span>اضافة مادة عقيدة او قران</span>
                        </div>
                        <div class="action-btn" onclick="location.href='inventory/manage_inventory.php'">
                            <i class="fas fa-boxes"></i>
                            <span>ادارة المخزن</span>
                        </div>
                      
                        <div class="action-btn" onclick="openCreateSessionModal()">
                            <i class="fas fa-calendar-plus"></i>
                            <span>اضافة حصة للمدرس</span>
                        </div>
                        <div class="action-btn" onclick="openAddAcademicYearModal()">
                            <i class="fas fa-calendar-alt"></i>
                            <span>اضافة سنه دراسية جديدة</span>
                        </div>
                        <div class="action-btn" onclick="openAddSemesterModal()">
                            <i class="fas fa-graduation-cap"></i>
                            <span>اضافة ترم جديد</span>
                        </div>
                        <div class="action-btn" onclick="location.href='students/organize-attendance.php'">
                            <i class="fas fa-calendar-check"></i>
                            <span>اضافة ايام الدراسة والاجازة</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer>
                <p>Kindergarten Admin System © 2023. All rights reserved.</p>
            </footer>
        </div>
    </div>



    <script>
        // Toggle sidebar on mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                // Load teachers, students, classrooms, and homework types in parallel
                const [teachersRes, studentsRes, classroomsRes, quranHomeworkRes, modulesHomeworkRes] = await Promise.all([
                    fetch('../../api/get-teachers.php?limit=1'),
                    fetch('../../api/get-students.php?limit=1'),
                    fetch('../../api/get-classrooms.php'),
                    fetch('../../api/get-homework-types.php'),
                    fetch('../../api/get-homework-types-modules.php')
                ]);
                
                const teachersData = await teachersRes.json();
                const studentsData = await studentsRes.json();
                const classroomsData = await classroomsRes.json();
                const quranHomeworkData = await quranHomeworkRes.json();
                const modulesHomeworkData = await modulesHomeworkRes.json();
                
                // Update stat cards with actual data
                if (teachersData.success && teachersData.total_count !== undefined) {
                    document.querySelector('.stat-card.teachers h3').textContent = teachersData.total_count;
                }
                
                if (studentsData.success && studentsData.total_count !== undefined) {
                    document.querySelector('.stat-card.students h3').textContent = studentsData.total_count;
                }
                
                if (classroomsData.success && classroomsData.count !== undefined) {
                    document.querySelector('.stat-card.classrooms h3').textContent = classroomsData.count;
                }
                
                // Calculate total homework types count (quran + modules)
                let homeworkCount = 0;
                if (quranHomeworkData.success && quranHomeworkData.count !== undefined) {
                    homeworkCount += quranHomeworkData.count;
                }
                if (modulesHomeworkData.success && modulesHomeworkData.count !== undefined) {
                    homeworkCount += modulesHomeworkData.count;
                }
                
                if (homeworkCount > 0) {
                    document.querySelector('.stat-card.homework h3').textContent = homeworkCount;
                }
                
            } catch (error) {
                console.error('خطا في تحميل الصفحة الرئيسية:', error);
            }
        }

        // Simulate loading
        document.addEventListener('DOMContentLoaded', function() {
            console.log('تم تحميل لوحة التحكم بنجاح');
            
            // Load dashboard statistics
            loadDashboardStats();
            
            // You can add more interactive features here
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Homework form submission
            const homeworkForm = document.getElementById('homeworkForm');
            if (homeworkForm) {
                homeworkForm.addEventListener('submit', handleHomeworkSubmit);
            }
        });

        // Modal Functions
        function openHomeworkModal() {
            document.getElementById('homeworkModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeHomeworkModal() {
            document.getElementById('homeworkModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
            // Reset form
            document.getElementById('homeworkForm').reset();
            // Hide loading state
            setHomeworkLoadingState(false);
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('homeworkModal');
            if (event.target === modal) {
                closeHomeworkModal();
            }
        }

        // Handle homework form submission
        async function handleHomeworkSubmit(e) {
            e.preventDefault();
            
            const formData = {
                homework_type_name: document.getElementById('homeworkName').value.trim(),
                description: document.getElementById('homeworkDescription').value.trim(),
                max_grade: parseInt(document.getElementById('maxGrade').value),
                different_types: document.getElementById('homeworkCategory').value
            };

            // Validate form data
            if (!validateHomeworkForm(formData)) {
                return;
            }

            // Show loading state
            setHomeworkLoadingState(true);

            try {
                const response = await fetch('../../api/add-homework-type.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Success
                    showHomeworkAlert('تمت إضافة نوع الواجب بنجاح!', 'success');
                    document.getElementById('homeworkForm').reset();
                    // Close modal after a short delay
                    setTimeout(() => {
                        closeHomeworkModal();
                    }, 1500);
                } else {
                    // API error
                    throw new Error(result.message || 'فشل في إضافة نوع الواجب');
                }

            } catch (error) {
                console.error('خطأ:', error);
                showHomeworkAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                setHomeworkLoadingState(false);
            }
        }

        // Validate homework form
        function validateHomeworkForm(data) {
            if (!data.homework_type_name || data.homework_type_name.length < 2) {
                showHomeworkAlert('يجب أن يكون اسم نوع الواجب على الأقل حرفين.', 'error');
                return false;
            }

            if (!data.description || data.description.length < 10) {
                showHomeworkAlert('يجب أن يكون الوصف على الأقل 10 أحرف.', 'error');
                return false;
            }

            if (!data.max_grade || data.max_grade < 1 || data.max_grade > 100) {
                showHomeworkAlert('يجب أن تكون الدرجة القصوى بين 1 و 100.', 'error');
                return false;
            }

            if (!data.different_types || (data.different_types !== 'quran' && data.different_types !== 'modules')) {
                showHomeworkAlert('يرجى اختيار فئة واجب صالحة.', 'error');
                return false;
            }

            return true;
        }

        // Set loading state for homework form
        function setHomeworkLoadingState(isLoading) {
            const submitBtn = document.getElementById('submitHomeworkBtn');
            const submitText = document.getElementById('submitText');
            const loading = document.getElementById('homeworkLoading');
            
            submitBtn.disabled = isLoading;
            
            if (isLoading) {
                submitText.textContent = 'جاري الإضافة...';
                loading.style.display = 'inline-block';
            } else {
                submitText.textContent = 'إضافة نوع الواجب';
                loading.style.display = 'none';
            }
        }

        // Arabic to Western numeral conversion utility
        function convertArabicToWestern(text) {
            if (!text) return text;
            
            const arabicToWestern = {
                '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
            };
            
            return text.toString().replace(/[٠-٩]/g, function(match) {
                return arabicToWestern[match] || match;
            });
        }

        // Enhanced input handler for Arabic numerals
        function handleArabicNumeralInput(input) {
            const originalValue = input.value;
            const convertedValue = convertArabicToWestern(originalValue);
            
            if (originalValue !== convertedValue) {
                input.value = convertedValue;
                // Trigger change event to ensure validation runs
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Show alert message (you can customize this to show in a better location)
        function showHomeworkAlert(message, type) {
            // For now, using alert - you can replace this with a better notification system
            if (type === 'success') {
                alert('✅ ' + message);
            } else {
                alert('❌ ' + message);
            }
        }

        // Create Session Modal Functions
        function openCreateSessionModal() {
            document.getElementById('createSessionModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            // Clear classroom dropdown initially
            const classroomSelect = document.getElementById('classroomSelect');
            classroomSelect.innerHTML = '<option value="">اختر معلماً أولاً...</option>';
            // Hide both homework sections initially
            document.getElementById('quranHomeworkTypesSection').style.display = 'none';
            document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
        }

        function closeCreateSessionModal() {
            document.getElementById('createSessionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('createSessionForm').reset();
            document.getElementById('selectedTeacherId').value = '';
            document.getElementById('teacherDropdown').style.display = 'none';
            document.getElementById('quranHomeworkTypesSection').style.display = 'none';
            document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
            // Reset classroom dropdown
            const classroomSelect = document.getElementById('classroomSelect');
            classroomSelect.innerHTML = '<option value="">اختر معلماً أولاً...</option>';
            // Remove any existing event listeners to prevent duplicates
            classroomSelect.removeEventListener('change', handleClassroomChange);
            // Hide loading state
            setSessionLoadingState(false);
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const homeworkModal = document.getElementById('homeworkModal');
            const createSessionModal = document.getElementById('createSessionModal');
            const addAcademicYearModal = document.getElementById('addAcademicYearModal');
            const addSemesterModal = document.getElementById('addSemesterModal');
            
            if (event.target === homeworkModal) {
                closeHomeworkModal();
            }
            if (event.target === createSessionModal) {
                closeCreateSessionModal();
            }
            if (event.target === addAcademicYearModal) {
                closeAddAcademicYearModal();
            }
            if (event.target === addSemesterModal) {
                closeAddSemesterModal();
            }
        }

        // Teacher search functionality
        let teacherSearchTimeout;
        document.addEventListener('DOMContentLoaded', function() {
            const teacherSearch = document.getElementById('teacherSearch');
            if (teacherSearch) {
                teacherSearch.addEventListener('input', function() {
                    clearTimeout(teacherSearchTimeout);
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        document.getElementById('teacherDropdown').style.display = 'none';
                        document.getElementById('selectedTeacherId').value = '';
                        // Clear classroom dropdown when teacher search is cleared
                        const classroomSelect = document.getElementById('classroomSelect');
                        classroomSelect.innerHTML = '<option value="">اختر معلماً أولاً...</option>';
                        return;
                    }
                    
                    teacherSearchTimeout = setTimeout(() => {
                        searchTeachers(query);
                    }, 300);
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.teacher-search-container')) {
                        document.getElementById('teacherDropdown').style.display = 'none';
                    }
                });
            }

            // Handle create session form submission
            const createSessionForm = document.getElementById('createSessionForm');
            if (createSessionForm) {
                createSessionForm.addEventListener('submit', handleCreateSessionSubmit);
            }
        });

        // Search teachers function
        async function searchTeachers(query) {
            try {
                const response = await fetch(`../../api/get-teachers.php?search=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success && data.teachers.length > 0) {
                    displayTeacherSuggestions(data.teachers);
                } else {
                    document.getElementById('teacherDropdown').style.display = 'none';
                }
            } catch (error) {
                console.error('خطأ في البحث عن المعلمين:', error);
                document.getElementById('teacherDropdown').style.display = 'none';
            }
        }

        // Display teacher suggestions
        function displayTeacherSuggestions(teachers) {
            const dropdown = document.getElementById('teacherDropdown');
            dropdown.innerHTML = '';
            
            teachers.forEach(teacher => {
                const item = document.createElement('div');
                item.className = 'teacher-suggestion';
                item.textContent = teacher.full_name;
                item.onclick = () => selectTeacher(teacher);
                dropdown.appendChild(item);
            });
            
            dropdown.style.display = 'block';
        }

        // Select teacher function
        function selectTeacher(teacher) {
            document.getElementById('teacherSearch').value = teacher.full_name;
            document.getElementById('selectedTeacherId').value = teacher.id;
            document.getElementById('teacherDropdown').style.display = 'none';
            
            // Load classrooms for the selected teacher
            loadTeacherClassrooms(teacher.id);
        }

        // Load classrooms for specific teacher function
        async function loadTeacherClassrooms(teacherId) {
            try {
                const response = await fetch(`../../api/get-teacher-classrooms.php?teacher_id=${teacherId}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('classroomSelect');
                    select.innerHTML = '<option value="">اختر فصلاً دراسياً...</option>';
                    
                    if (data.classrooms && data.classrooms.length > 0) {
                        data.classrooms.forEach(classroom => {
                            const option = document.createElement('option');
                            option.value = classroom.classroom_id;
                            option.textContent = classroom.classroom_name;
                            select.appendChild(option);
                        });
                        
                        // Add event listener for classroom change to show homework types
                        select.addEventListener('change', handleClassroomChange);
                        
                        // Add event listener for homework category change
                        const homeworkCategorySelect = document.getElementById('homeworkCategorySession');
                        homeworkCategorySelect.addEventListener('change', handleHomeworkCategoryChange);
                    } else {
                        select.innerHTML = '<option value="">لا توجد فصول مسندة لهذا المعلم</option>';
                    }
                } else {
                    const select = document.getElementById('classroomSelect');
                    select.innerHTML = '<option value="">خطأ في تحميل الفصول</option>';
                    console.error('خطأ في تحميل فصول المعلم:', data.message);
                }
            } catch (error) {
                console.error('خطأ في تحميل فصول المعلم:', error);
                const select = document.getElementById('classroomSelect');
                select.innerHTML = '<option value="">خطأ في تحميل الفصول</option>';
            }
        }

        // Handle classroom change to show/hide homework types
        function handleClassroomChange(event) {
            const classroomId = event.target.value;
            const homeworkCategory = document.getElementById('homeworkCategorySession').value;
            
            if (classroomId && homeworkCategory) {
                // Load appropriate homework types based on category
                loadHomeworkTypes(homeworkCategory);
                
                if (homeworkCategory === 'quran') {
                    document.getElementById('quranHomeworkTypesSection').style.display = 'block';
                    document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
                } else if (homeworkCategory === 'modules') {
                    document.getElementById('modulesHomeworkTypesSection').style.display = 'block';
                    document.getElementById('quranHomeworkTypesSection').style.display = 'none';
                }
            } else {
                document.getElementById('quranHomeworkTypesSection').style.display = 'none';
                document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
            }
        }

        // Handle homework category change
        function handleHomeworkCategoryChange(event) {
            const homeworkCategory = event.target.value;
            const classroomId = document.getElementById('classroomSelect').value;
            
            if (homeworkCategory && classroomId) {
                // Load appropriate homework types based on category
                loadHomeworkTypes(homeworkCategory);
                
                if (homeworkCategory === 'quran') {
                    document.getElementById('quranHomeworkTypesSection').style.display = 'block';
                    document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
                } else if (homeworkCategory === 'modules') {
                    document.getElementById('modulesHomeworkTypesSection').style.display = 'block';
                    document.getElementById('quranHomeworkTypesSection').style.display = 'none';
                }
            } else {
                document.getElementById('quranHomeworkTypesSection').style.display = 'none';
                document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
            }
        }

        // Chapter autocomplete functionality
        async function searchChapters(query, inputElement) {
            if (query.length < 2) {
                hideAutocomplete(inputElement);
                return;
            }
            
            try {
                const response = await fetch(`../../api/get-chapter-name.php?query=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success && data.chapters.length > 0) {
                    showAutocomplete(inputElement, data.chapters);
                } else {
                    hideAutocomplete(inputElement);
                }
            } catch (error) {
                console.error('خطأ في البحث عن السور:', error);
                hideAutocomplete(inputElement);
            }
        }
        
        function showAutocomplete(inputElement, chapters) {
            // Remove existing dropdown if any
            const existingDropdown = inputElement.parentElement.querySelector('.chapter-autocomplete');
            if (existingDropdown) {
                existingDropdown.remove();
            }
            
            const dropdown = document.createElement('div');
            dropdown.className = 'chapter-autocomplete';
            
            chapters.forEach(chapter => {
                const item = document.createElement('div');
                item.className = 'chapter-suggestion';
                
                // Create a structured display with Arabic and English names
                const content = document.createElement('div');
                content.style.display = 'flex';
                content.style.flexDirection = 'column';
                content.style.gap = '4px';
                
                const arabicName = document.createElement('span');
                arabicName.textContent = chapter.name_ar;
                arabicName.style.fontSize = '15px';
                arabicName.style.fontWeight = '500';
                arabicName.style.color = '#2c3e50';
                
                const englishName = document.createElement('span');
                englishName.textContent = chapter.name_en;
                englishName.style.fontSize = '12px';
                englishName.style.color = '#7f8c8d';
                englishName.style.fontStyle = 'italic';
                
                content.appendChild(arabicName);
                content.appendChild(englishName);
                
                item.appendChild(content);
                item.onclick = () => selectChapter(inputElement, chapter);
                dropdown.appendChild(item);
            });
            
            inputElement.parentElement.appendChild(dropdown);
        }
        
        function hideAutocomplete(inputElement) {
            const dropdown = inputElement.parentElement.querySelector('.chapter-autocomplete');
            if (dropdown) {
                dropdown.remove();
            }
        }
        
        function selectChapter(inputElement, chapter) {
            inputElement.value = chapter.name_ar + ' (' + chapter.name_en + ')';
            inputElement.dataset.chapterId = chapter.id;
            hideAutocomplete(inputElement);
        }

        // Load homework types function
        async function loadHomeworkTypes(type = 'quran') {
            try {
                console.log('جاري تحميل أنواع الواجبات للنوع:', type);
                const response = await fetch(`../../api/get-homework-types${type === 'modules' ? '-modules' : ''}.php`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                console.log('استجابة أنواع الواجبات:', data);
                
                if (data.success) {
                    const container = document.getElementById(`${type}HomeworkTypesContainer`);
                    container.innerHTML = '';
                    
                    data.homework_types.forEach(homeworkType => {
                        const homeworkDiv = document.createElement('div');
                        homeworkDiv.className = 'homework-type-item';
                        
                        if (type === 'quran') {
                            // Quran homework type UI
                            homeworkDiv.innerHTML = `
                                <div class="homework-type-header">
                                    <h4>${homeworkType.name}</h4>
                                    <span class="max-grade">Max: ${homeworkType.max_grade}</span>
                                </div>
                                <p class="homework-description">${homeworkType.description}</p>
                                <div class="homework-inputs">
                                    <div class="input-group chapter-input-group">
                                        <label>Chapter Name:</label>
                                        <input type="text" class="chapter-input" data-homework-id="${homeworkType.id}" placeholder="Type chapter name..." autocomplete="off">
                                    </div>
                                    <div class="input-group">
                                        <label>From Verse:</label>
                                        <input type="text" class="from-input" data-homework-id="${homeworkType.id}" min="1" placeholder="1" pattern="[0-9٠-٩]+" autocomplete="off">
                                    </div>
                                    <div class="input-group">
                                        <label>To Verse:</label>
                                        <input type="text" class="to-input" data-homework-id="${homeworkType.id}" min="1" placeholder="7" pattern="[0-9٠-٩]+" autocomplete="off">
                                    </div>
                                </div>
                            `;
                        } else {
                            // Modules homework type UI
                            homeworkDiv.innerHTML = `
                                <div class="homework-type-header">
                                    <h4>${homeworkType.name}</h4>
                                    <span class="max-grade">Max: ${homeworkType.max_grade}</span>
                                </div>
                                <p class="homework-description">${homeworkType.description}</p>
                                <div class="homework-inputs">
                                    <div class="input-group">
                                        <label>Lesson Title:</label>
                                        <input type="text" class="lesson-title-input" data-homework-id="${homeworkType.id}" placeholder="e.g., Introduction to Arabic Letters">
                                    </div>
                                    <div class="input-group">
                                        <label>Upload File:</label>
                                        <input type="file" class="file-input" data-homework-id="${homeworkType.id}" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="form-help">Accepted formats: PDF, JPG, JPEG, PNG (Max: 90MB)</small>
                                    </div>
                                </div>
                            `;
                        }
                        container.appendChild(homeworkDiv);
                    });
                    
                    // Add event listeners for Arabic numeral conversion and chapter autocomplete (only for Quran)
                    if (type === 'quran') {
                        setTimeout(() => {
                            const fromInputs = container.querySelectorAll('.from-input');
                            const toInputs = container.querySelectorAll('.to-input');
                            const chapterInputs = container.querySelectorAll('.chapter-input');
                            
                            fromInputs.forEach(input => {
                                input.addEventListener('input', () => handleArabicNumeralInput(input));
                                input.addEventListener('blur', () => handleArabicNumeralInput(input));
                            });
                            
                            toInputs.forEach(input => {
                                input.addEventListener('input', () => handleArabicNumeralInput(input));
                                input.addEventListener('blur', () => handleArabicNumeralInput(input));
                            });
                            
                            chapterInputs.forEach(input => {
                                let searchTimeout;
                                input.addEventListener('input', function() {
                                    const query = this.value.trim();
                                    clearTimeout(searchTimeout);
                                    searchTimeout = setTimeout(() => searchChapters(query, input), 300);
                                });
                                
                                // Hide dropdown when clicking outside
                                input.addEventListener('blur', function() {
                                    setTimeout(() => hideAutocomplete(input), 200);
                                });
                            });
                        }, 100);
                    }
                } else {
                    console.error('فشل في تحميل أنواع الواجبات:', data.message);
                    showHomeworkAlert('فشل في تحميل أنواع الواجبات: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('خطأ في تحميل أنواع الواجبات:', error);
                showHomeworkAlert('خطأ في الشبكة أثناء تحميل أنواع الواجبات', 'error');
            }
        }

        // Collect homework data from form
        function collectHomeworkData() {
            const homeworkData = [];
            const chapterInputs = document.querySelectorAll('.chapter-input');
            const fromInputs = document.querySelectorAll('.from-input');
            const toInputs = document.querySelectorAll('.to-input');

            // Create a map to collect data by homework ID
            const homeworkMap = {};

            chapterInputs.forEach(input => {
                const homeworkId = input.dataset.homeworkId;
                const chapterId = input.dataset.chapterId;
                const value = input.value.trim();
                if (value && chapterId) {
                    if (!homeworkMap[homeworkId]) {
                        homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                    }
                    homeworkMap[homeworkId].quran_chapter = value;
                    homeworkMap[homeworkId].quran_suras_id = chapterId;
                }
            });

            fromInputs.forEach(input => {
                const homeworkId = input.dataset.homeworkId;
                const value = parseInt(input.value);
                if (value && !isNaN(value)) {
                    if (!homeworkMap[homeworkId]) {
                        homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                    }
                    homeworkMap[homeworkId].quran_from = value;
                }
            });

            toInputs.forEach(input => {
                const homeworkId = input.dataset.homeworkId;
                const value = parseInt(input.value);
                if (value && !isNaN(value)) {
                    if (!homeworkMap[homeworkId]) {
                        homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                    }
                    homeworkMap[homeworkId].quran_to = value;
                }
            });

            // Convert map to array and filter out incomplete entries
            Object.values(homeworkMap).forEach(homework => {
                if (homework.quran_chapter && homework.quran_from !== undefined && homework.quran_to !== undefined && homework.quran_suras_id) {
                    homeworkData.push(homework);
                }
            });

            return homeworkData;
        }

        // Handle create session form submission
        async function handleCreateSessionSubmit(e) {
            e.preventDefault();
            
            const formData = {
                session_name: document.getElementById('sessionName').value.trim(),
                teacher_id: document.getElementById('selectedTeacherId').value,
                classroom_id: parseInt(document.getElementById('classroomSelect').value),
                homework_category: document.getElementById('homeworkCategorySession').value
            };

            // Validate form data
            if (!validateCreateSessionForm(formData)) {
                return;
            }

            // Show loading state
            setSessionLoadingState(true);

            try {
                // If Quran category and there is homework data, validate first via API
                if (formData.homework_category === 'quran') {
                    const quranHomeworkData = collectHomeworkData();
                    if (quranHomeworkData.length > 0) {
                        const validationResults = await Promise.all(
                            quranHomeworkData.map(hw => fetch('../../api/add-homework-chapter.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    validate_only: true,
                                    homework_type_id: hw.homework_type_id,
                                    quran_from: hw.quran_from,
                                    quran_to: hw.quran_to,
                                    quran_chapter: hw.quran_chapter,
                                    classroom_id: formData.classroom_id,
                                    quran_suras_id: hw.quran_suras_id
                                })
                            }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error validating Quran homework' })))
                        );

                        const failed = validationResults.find(v => !v.success);
                        if (failed) {
                            showHomeworkAlert(failed.message || 'بيانات واجب القرآن غير صحيحة', 'error');
                            setSessionLoadingState(false);
                            return; // Stop here, do not create session
                        }
                    }
                }

                // First, create the session
                const sessionResponse = await fetch('../../api/add-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        session_name: formData.session_name,
                        teacher_id: parseInt(formData.teacher_id),
                        classroom_id: formData.classroom_id
                    })
                });
                
                const sessionData = await sessionResponse.json();
                
                if (sessionData.success) {
                    // Use the first session ID for all homework entries (they're all for the same classroom)
                    const sessionId = sessionData.session_ids && sessionData.session_ids.length > 0 ? sessionData.session_ids[0] : null;
                    
                    if (!sessionId) {
                        throw new Error('فشل في الحصول على معرف الحصة من استجابة الخادم');
                    }
                    
                    console.log('تم إنشاء الحصة بنجاح برقم:', sessionId);
                    
                    if (formData.homework_category === 'quran') {
                        // Collect homework chapter data
                        const homeworkData = collectHomeworkData();
                        
                        // If there's homework data, save it using the session IDs from the response
                        if (homeworkData.length > 0) {
                            for (const homework of homeworkData) {
                                const homeworkResponse = await fetch('../../api/add-homework-chapter.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        session_homework_id: sessionId,
                                        homework_type_id: homework.homework_type_id,
                                        quran_from: homework.quran_from,
                                        quran_to: homework.quran_to,
                                        quran_chapter: homework.quran_chapter,
                                        classroom_id: parseInt(formData.classroom_id),
                                        quran_suras_id: homework.quran_suras_id
                                    })
                                });
                                
                                const homeworkResult = await homeworkResponse.json();
                                
                                if (!homeworkResult.success) {
                                    console.warn('فشل في حفظ بيانات الواجب:', homeworkResult.message);
                                    showHomeworkAlert(`تحذير: فشل في حفظ واجب "${homework.quran_chapter}": ${homeworkResult.message}`, 'error');
                                }
                            }
                        }
                    } else if (formData.homework_category === 'modules') {
                        // Collect modules data
                        const modulesData = collectModulesData();
                        console.log('بيانات الوحدات المجمعة:', modulesData);
                        console.log('معرف الحصة للوحدات:', sessionId);
                        console.log('نوع معرف الحصة:', typeof sessionId);
                        console.log('قيمة معرف الحصة:', String(sessionId));
                        
                        // If there's modules data, save it using FormData for file uploads
                        if (modulesData.length > 0) {
                            for (const module of modulesData) {
                                console.log('حفظ الوحدة:', module);
                                console.log('معرف نوع واجب الوحدة:', module.homework_type_id);
                                console.log('عنوان الدرس:', module.lesson_title);
                                console.log('ملف الوحدة:', module.file);
                                
                                const formDataToSend = new FormData();
                                
                                // Validate and append each field
                                const sessionHomeworkId = String(sessionId || '');
                                const homeworkTypeId = String(module.homework_type_id || '');
                                const lessonTitle = module.lesson_title || '';
                                const fileToUpload = module.file;
                                const classroomId = String(parseInt(formData.classroom_id) || '');
                                
                                console.log('على وشك الإضافة إلى FormData:');
                                console.log('  معرف واجب الحصة:', sessionHomeworkId);
                                console.log('  معرف نوع الواجب:', homeworkTypeId);
                                console.log('  عنوان الدرس:', lessonTitle);
                                console.log('  الملف:', fileToUpload ? fileToUpload.name : 'لا يوجد');
                                console.log('  معرف الفصل:', classroomId);
                                
                                formDataToSend.append('session_homework_id', sessionHomeworkId);
                                formDataToSend.append('homework_type_id', homeworkTypeId);
                                formDataToSend.append('lesson_title', lessonTitle);
                                formDataToSend.append('file', fileToUpload);
                                formDataToSend.append('classroom_id', classroomId);
                                
                                console.log('محتويات FormData بعد الإضافة:');
                                for (let [key, value] of formDataToSend.entries()) {
                                    console.log(key, ':', value instanceof File ? value.name : value);
                                }
                                
                                const moduleResponse = await fetch('../../api/add-homework-module.php', {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: formDataToSend
                                });
                                
                                const moduleResult = await moduleResponse.json();
                                console.log('استجابة الوحدة:', moduleResult);
                                
                                if (!moduleResult.success) {
                                    console.warn('فشل في حفظ بيانات الوحدة:', moduleResult.message);
                                    showHomeworkAlert(`تحذير: فشل في حفظ الوحدة "${module.lesson_title}": ${moduleResult.message}`, 'error');
                                }
                            }
                        } else {
                            console.warn('لم يتم جمع بيانات الوحدات');
                        }
                    }
                    
                    // Success
                    showHomeworkAlert('تم إنشاء الحصة بنجاح!', 'success');
                    document.getElementById('createSessionForm').reset();
                    document.getElementById('selectedTeacherId').value = '';
                    document.getElementById('quranHomeworkTypesSection').style.display = 'none';
                    document.getElementById('modulesHomeworkTypesSection').style.display = 'none';
                    
                    // Close modal after a short delay
                    setTimeout(() => {
                        closeCreateSessionModal();
                    }, 1500);
                } else {
                    throw new Error(sessionData.message || 'فشل في إنشاء الحصة');
                }

            } catch (error) {
                console.error('خطأ:', error);
                showHomeworkAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                setSessionLoadingState(false);
            }
        }

        // Validate create session form
        function validateCreateSessionForm(data) {
            if (!data.session_name || data.session_name.length < 2) {
                showHomeworkAlert('يجب أن يكون اسم الحصة على الأقل حرفين.', 'error');
                return false;
            }

            if (!data.teacher_id) {
                showHomeworkAlert('يرجى اختيار معلم من القائمة المنسدلة.', 'error');
                return false;
            }

            if (!data.classroom_id) {
                showHomeworkAlert('يرجى اختيار فصل دراسي.', 'error');
                return false;
            }

            if (!data.homework_category || (data.homework_category !== 'quran' && data.homework_category !== 'modules')) {
                showHomeworkAlert('يرجى اختيار فئة الواجب.', 'error');
                return false;
            }

            return true;
        }

        // Set loading state for session form
        function setSessionLoadingState(isLoading) {
            const submitBtn = document.getElementById('submitSessionBtn');
            const submitText = document.getElementById('sessionSubmitText');
            const loading = document.getElementById('sessionLoading');
            
            submitBtn.disabled = isLoading;
            
            if (isLoading) {
                submitText.textContent = 'جاري الإنشاء...';
                loading.style.display = 'inline-block';
            } else {
                submitText.textContent = 'إنشاء الحصة';
                loading.style.display = 'none';
            }
        }

        // Collect modules data from form
        function collectModulesData() {
            const modulesData = [];
            const lessonTitleInputs = document.querySelectorAll('.lesson-title-input');
            const fileInputs = document.querySelectorAll('.file-input');

            // Create a map to collect data by homework ID
            const modulesMap = {};

            lessonTitleInputs.forEach(input => {
                const homeworkId = input.dataset.homeworkId;
                const value = input.value.trim();
                if (value) {
                    if (!modulesMap[homeworkId]) {
                        modulesMap[homeworkId] = { homework_type_id: homeworkId };
                    }
                    modulesMap[homeworkId].lesson_title = value;
                }
            });

            fileInputs.forEach(input => {
                const homeworkId = input.dataset.homeworkId;
                const file = input.files[0];
                console.log('إدخال الملف لمعرف الواجب:', homeworkId, 'الملف:', file);
                if (file) {
                    if (!modulesMap[homeworkId]) {
                        modulesMap[homeworkId] = { homework_type_id: homeworkId };
                    }
                    modulesMap[homeworkId].file = file;
                    console.log('تم إضافة الملف إلى modulesMap:', modulesMap[homeworkId]);
                } else {
                    console.log('لم يتم اختيار ملف لمعرف الواجب:', homeworkId);
                }
            });

            // Convert map to array and filter out incomplete entries
            Object.values(modulesMap).forEach(module => {
                if (module.lesson_title && module.file) {
                    modulesData.push(module);
                }
            });

            return modulesData;
        }

        // Academic Year Modal Functions
        function openAddAcademicYearModal() {
            document.getElementById('addAcademicYearModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeAddAcademicYearModal() {
            document.getElementById('addAcademicYearModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('addAcademicYearForm').reset();
            setAcademicYearLoadingState(false);
        }

        // Semester Modal Functions
        function openAddSemesterModal() {
            document.getElementById('addSemesterModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            loadAcademicYears();
        }

        function closeAddSemesterModal() {
            document.getElementById('addSemesterModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('addSemesterForm').reset();
            setSemesterLoadingState(false);
        }

        // Load academic years for semester modal
        async function loadAcademicYears() {
            try {
                const response = await fetch('../../api/get-academic-years.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('academicYearSelect');
                    select.innerHTML = '<option value="">Choose an academic year...</option>';
                    
                    data.academic_years.forEach(year => {
                        const option = document.createElement('option');
                        option.value = year.id;
                        option.textContent = year.year_name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('خطأ في تحميل السنوات الدراسية:', error);
            }
        }

        // Handle academic year form submission
        async function handleAcademicYearSubmit(e) {
            e.preventDefault();
            
            const formData = {
                year_name: document.getElementById('yearName').value.trim(),
                start_date: document.getElementById('academicStartDate').value,
                end_date: document.getElementById('academicEndDate').value,
                is_current: document.getElementById('academicIsCurrent').checked
            };

            // Validate form data
            if (!validateAcademicYearForm(formData)) {
                return;
            }

            setAcademicYearLoadingState(true);

            try {
                const response = await fetch('../../api/add-academic-year.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showHomeworkAlert('تمت إضافة السنة الدراسية بنجاح!', 'success');
                    document.getElementById('addAcademicYearForm').reset();
                    setTimeout(() => {
                        closeAddAcademicYearModal();
                    }, 1500);
                } else {
                    throw new Error(result.message || 'فشل في إضافة السنة الدراسية');
                }

            } catch (error) {
                console.error('خطأ:', error);
                showHomeworkAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                setAcademicYearLoadingState(false);
            }
        }

        // Handle semester form submission
        async function handleSemesterSubmit(e) {
            e.preventDefault();
            
            const formData = {
                academic_year_id: parseInt(document.getElementById('academicYearSelect').value),
                term_name: document.getElementById('termName').value.trim(),
                start_date: document.getElementById('semesterStartDate').value,
                end_date: document.getElementById('semesterEndDate').value,
                is_current: document.getElementById('semesterIsCurrent').checked
            };

            // Validate form data
            if (!validateSemesterForm(formData)) {
                return;
            }

            setSemesterLoadingState(true);

            try {
                const response = await fetch('../../api/add-semester.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showHomeworkAlert('تمت إضافة الترم بنجاح!', 'success');
                    document.getElementById('addSemesterForm').reset();
                    setTimeout(() => {
                        closeAddSemesterModal();
                    }, 1500);
                } else {
                    throw new Error(result.message || 'فشل في إضافة الترم');
                }

            } catch (error) {
                console.error('خطأ:', error);
                showHomeworkAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
            } finally {
                setSemesterLoadingState(false);
            }
        }

        // Validate academic year form
        function validateAcademicYearForm(data) {
            if (!data.year_name || data.year_name.length < 2) {
                showHomeworkAlert('يجب أن يكون اسم السنة الدراسية على الأقل حرفين.', 'error');
                return false;
            }

            if (!data.start_date) {
                showHomeworkAlert('تاريخ البداية مطلوب.', 'error');
                return false;
            }

            if (!data.end_date) {
                showHomeworkAlert('تاريخ النهاية مطلوب.', 'error');
                return false;
            }

            if (new Date(data.end_date) <= new Date(data.start_date)) {
                showHomeworkAlert('يجب أن يكون تاريخ النهاية بعد تاريخ البداية.', 'error');
                return false;
            }

            return true;
        }

        // Validate semester form
        function validateSemesterForm(data) {
            if (!data.academic_year_id) {
                showHomeworkAlert('يرجى اختيار سنة دراسية.', 'error');
                return false;
            }

            if (!data.term_name || data.term_name.length < 2) {
                showHomeworkAlert('يجب أن يكون اسم الترم على الأقل حرفين.', 'error');
                return false;
            }

            if (!data.start_date) {
                showHomeworkAlert('تاريخ البداية مطلوب.', 'error');
                return false;
            }

            if (!data.end_date) {
                showHomeworkAlert('تاريخ النهاية مطلوب.', 'error');
                return false;
            }

            if (new Date(data.end_date) <= new Date(data.start_date)) {
                showHomeworkAlert('يجب أن يكون تاريخ النهاية بعد تاريخ البداية.', 'error');
                return false;
            }

            return true;
        }

        // Set loading state for academic year form
        function setAcademicYearLoadingState(isLoading) {
            const submitBtn = document.getElementById('submitAcademicYearBtn');
            const submitText = document.getElementById('academicYearSubmitText');
            const loading = document.getElementById('academicYearLoading');
            
            submitBtn.disabled = isLoading;
            
            if (isLoading) {
                submitText.textContent = 'جاري الإضافة...';
                loading.style.display = 'inline-block';
            } else {
                submitText.textContent = 'إضافة السنة الدراسية';
                loading.style.display = 'none';
            }
        }

        // Set loading state for semester form
        function setSemesterLoadingState(isLoading) {
            const submitBtn = document.getElementById('submitSemesterBtn');
            const submitText = document.getElementById('semesterSubmitText');
            const loading = document.getElementById('semesterLoading');
            
            submitBtn.disabled = isLoading;
            
            if (isLoading) {
                submitText.textContent = 'جاري الإضافة...';
                loading.style.display = 'inline-block';
            } else {
                submitText.textContent = 'إضافة الترم';
                loading.style.display = 'none';
            }
        }

        // Add event listeners for new forms
        document.addEventListener('DOMContentLoaded', function() {
            const academicYearForm = document.getElementById('addAcademicYearForm');
            if (academicYearForm) {
                academicYearForm.addEventListener('submit', handleAcademicYearSubmit);
            }

            const semesterForm = document.getElementById('addSemesterForm');
            if (semesterForm) {
                semesterForm.addEventListener('submit', handleSemesterSubmit);
            }
        });
    </script>

    <!-- Homework Modal -->
    <div id="homeworkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-book"></i> إضافة مادة جديدة قران، عقيدة</h2>
                <span class="close" onclick="closeHomeworkModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="homeworkForm">
                    <div class="form-group">
                        <label for="homeworkName">اسم المادة *</label>
                        <input type="text" id="homeworkName" name="homeworkName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="homeworkDescription">وصف المادة *</label>
                        <textarea id="homeworkDescription" name="homeworkDescription" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="maxGrade"> اعلي درجة ممكنة للمادة *</label>
                        <input type="number" id="maxGrade" name="maxGrade" class="form-control" min="1" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="homeworkCategory">نوع المادة *</label>
                        <select id="homeworkCategory" name="homeworkCategory" class="form-control" required>
                            <option value="">اختر فئة...</option>
                            <option value="quran">قرآن</option>
                            <option value="modules">عقيدة او ماشابه ذلك</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeHomeworkModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitHomeworkBtn">
                            <span id="submitText">إضافة المادة</span>
                            <div class="loading" id="homeworkLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Session Modal -->
    <div id="createSessionModal" class="modal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-plus"></i> إنشاء حصة للمعلم</h2>
                <span class="close" onclick="closeCreateSessionModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createSessionForm">
                    <div class="form-group">
                        <label for="teacherSearch">
                            <i class="fas fa-user"></i> اسم المعلم *
                        </label>
                        <div class="teacher-search-container">
                            <input 
                                type="text" 
                                id="teacherSearch" 
                                name="teacherSearch" 
                                class="form-control" 
                                placeholder="ابدأ بكتابة اسم المعلم..."
                                autocomplete="off"
                                required
                            >
                            <div id="teacherDropdown" class="teacher-dropdown" style="display: none;">
                                <!-- Teacher suggestions will appear here -->
                            </div>
                        </div>
                        <small class="form-help">اكتب الأحرف الأولى من اسم المعلم</small>
                        <input type="hidden" id="selectedTeacherId" name="teacher_id">
                    </div>
                    
                    <div class="form-group">
                        <label for="sessionName">
                            <i class="fas fa-tag"></i> اسم الحصة *
                        </label>
                        <input 
                            type="text" 
                            id="sessionName" 
                            name="session_name" 
                            class="form-control" 
                            placeholder="أدخل اسم الحصة (مثال: درس الرياضيات، وقت القراءة، نشاط فني)"
                            required
                            maxlength="100"
                        >
                        <small class="form-help">اختر اسماً وصفياً لهذه الحصة</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="classroomSelect">
                            <i class="fas fa-chalkboard"></i> اختر الفصل *
                        </label>
                        <select id="classroomSelect" name="classroom_id" class="form-control" required>
                            <option value="">اختر الفصل الخاص بالحصة ...</option>
                            <!-- Classrooms will be populated via JavaScript -->
                        </select>
                        <small class="form-help">اختر الفصل  الذي سيتم اضافة الحصة اليه</small>
                    </div>

                    <div class="form-group">
                        <label for="homeworkCategorySession">
                            <i class="fas fa-tags"></i> نوع المادة قران ،،عقيدة *
                        </label>
                        <select id="homeworkCategorySession" name="homeworkCategory" class="form-control" required>
                            <option value="">اختر فئة...</option>
                            <option value="quran">قرآن</option>
                            <option value="modules">عقيدة او ماشابه ذلك</option>
                        </select>
                        <small class="form-help">اختر نوع الواجب لهذه الحصة</small>
                    </div>

                    <!-- Quran Homework Types Section -->
                    <div class="homework-types-section" id="quranHomeworkTypesSection" style="display: none;">
                        <h3><i class="fas fa-book"></i> أنواع واجبات القرآن</h3>
                        <div class="homework-types-container" id="quranHomeworkTypesContainer">
                            <!-- Quran homework types will be loaded here via JavaScript -->
                        </div>
                    </div>

                    <!-- Modules Homework Types Section -->
                    <div class="homework-types-section" id="modulesHomeworkTypesSection" style="display: none;">
                        <h3><i class="fas fa-file-alt"></i> أنواع واجبات الوحدات</h3>
                        <div class="homework-types-container" id="modulesHomeworkTypesContainer">
                            <!-- Module homework types will be loaded here via JavaScript -->
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeCreateSessionModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitSessionBtn">
                            <span id="sessionSubmitText">إنشاء الحصة</span>
                            <div class="loading" id="sessionLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Academic Year Modal -->
    <div id="addAcademicYearModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-alt"></i> إضافة سنة دراسية</h2>
                <span class="close" onclick="closeAddAcademicYearModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addAcademicYearForm">
                    <div class="form-group">
                        <label for="yearName">اسم السنة الدراسية *</label>
                        <input type="text" id="yearName" name="yearName" class="form-control" placeholder="مثال: 2024-2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicStartDate">تاريخ البداية *</label>
                        <input type="date" id="academicStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicEndDate">تاريخ النهاية *</label>
                        <input type="date" id="academicEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="academicIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            تعيين كسنة دراسية حالية
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddAcademicYearModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitAcademicYearBtn">
                            <span id="academicYearSubmitText">إضافة السنة الدراسية</span>
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
                <h2><i class="fas fa-graduation-cap"></i> إضافة ترم دراسي</h2>
                <span class="close" onclick="closeAddSemesterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addSemesterForm">
                    <div class="form-group">
                        <label for="academicYearSelect">السنة الدراسية *</label>
                        <select id="academicYearSelect" name="academicYearId" class="form-control" required>
                            <option value="">اختر سنة دراسية...</option>
                            <!-- Academic years will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="termName">اسم الترم *</label>
                        <input type="text" id="termName" name="termName" class="form-control" placeholder="مثال: خريف 2024، ربيع 2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterStartDate">تاريخ البداية *</label>
                        <input type="date" id="semesterStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterEndDate">تاريخ النهاية *</label>
                        <input type="date" id="semesterEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="semesterIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            تعيين كترم حالي
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddSemesterModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitSemesterBtn">
                            <span id="semesterSubmitText">إضافة الترم</span>
                            <div class="loading" id="semesterLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script src="../../assets/js/arabic-converter.js"></script>
</html>