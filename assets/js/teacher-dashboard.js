// File: assets/js/teacher-dashboard.js
// Teacher Dashboard functionality

class TeacherDashboard {
    constructor() {
        this.classrooms = [];
        this.stats = {};
        this.teacherInfo = {};
        this.homeworkTypes = [];
        this.isLoading = false;
        this.classroomIdToStudents = {};
        
        this.initializeEventListeners();
        this.loadDashboard();
        this.initializeSessionForm();
    }
    
    // Arabic to Western numeral conversion utility
    convertArabicToWestern(text) {
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
    handleArabicNumeralInput(input) {
        const originalValue = input.value;
        const convertedValue = this.convertArabicToWestern(originalValue);
        
        if (originalValue !== convertedValue) {
            input.value = convertedValue;
            // Trigger change event to ensure validation runs
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    
    // Chapter autocomplete functionality
    async searchChapters(query, inputElement) {
        if (query.length < 2) {
            this.hideAutocomplete(inputElement);
            return;
        }
        
        try {
            const response = await fetch(`../../api/get-chapter-name.php?query=${encodeURIComponent(query)}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.chapters.length > 0) {
                this.showAutocomplete(inputElement, data.chapters);
            } else {
                this.hideAutocomplete(inputElement);
            }
        } catch (error) {
            console.error('Error searching chapters:', error);
            this.hideAutocomplete(inputElement);
        }
    }
    
    showAutocomplete(inputElement, chapters) {
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
            // Use mousedown instead of click to fire before blur event
            item.onmousedown = (e) => {
                e.preventDefault(); // Prevent blur from firing
                this.selectChapter(inputElement, chapter);
            };
            dropdown.appendChild(item);
        });
        
        inputElement.parentElement.appendChild(dropdown);
    }
    
    hideAutocomplete(inputElement) {
        const dropdown = inputElement.parentElement.querySelector('.chapter-autocomplete');
        if (dropdown) {
            dropdown.remove();
        }
    }
    
    selectChapter(inputElement, chapter) {
        inputElement.value = chapter.name_ar + ' (' + chapter.name_en + ')';
        inputElement.dataset.chapterId = chapter.id;
        inputElement.setAttribute('data-chapter-id', chapter.id); // Also set as attribute for reliability
        console.log('Chapter selected:', chapter.name_ar, 'ID:', chapter.id);
        console.log('Dataset chapterId set to:', inputElement.dataset.chapterId);
        this.hideAutocomplete(inputElement);
    }
    
    initializeEventListeners() {
        // Close modal when clicking outside
        document.getElementById('classroomModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('classroomModal')) {
                this.closeClassroomModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('classroomModal').style.display === 'block') {
                this.closeClassroomModal();
            }
        });
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            this.loadClassrooms();
        }, 300000); // 5 minutes
        
        // Session form submission
        const sessionForm = document.getElementById('sessionForm');
        if (sessionForm) {
            sessionForm.addEventListener('submit', (e) => this.handleSessionSubmit(e));
        }

        // Classroom select change to load homework types
        const classroomSelect = document.getElementById('classroomSelect');
        if (classroomSelect) {
            classroomSelect.addEventListener('change', (e) => this.handleClassroomChange(e));
        }
    }
    
    async loadDashboard() {
        try {
            // Load teacher info, classrooms, and homework types
            await Promise.all([
                this.loadTeacherInfo(),
                this.loadClassrooms(),
                this.loadHomeworkTypes()
            ]);
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showAlert('Error loading dashboard. Please refresh the page.', 'error');
        }
    }
    
    async loadTeacherInfo() {
        try {
            const response = await fetch('../../api/session-check.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.logged_in) {
                this.teacherInfo = data.session_info;
                this.updateTeacherInfo();
            } else {
                // Redirect to login if not logged in
                window.location.href = '../../views/auth/login.php';
            }
        } catch (error) {
            console.error('Error loading teacher info:', error);
        }
    }
    
    updateTeacherInfo() {
        const teacherName = document.getElementById('teacherName');
        const userName = document.getElementById('userName');
        
        if (teacherName) {
            teacherName.textContent = this.teacherInfo.user_name || 'Teacher';
        }
        
        if (userName) {
            userName.textContent = this.teacherInfo.user_name || 'Teacher';
        }
        
        // Update login time
        const loginTime = document.getElementById('loginTime');
        if (loginTime && this.teacherInfo.login_time) {
            const loginDate = new Date(this.teacherInfo.login_time * 1000);
            loginTime.textContent = this.formatTimeAgo(loginDate);
        }
    }
    
    async loadClassrooms() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch('../../api/get-teacher-classrooms.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            console.log('API Response:', data);
            
            if (data.success) {
                this.classrooms = data.classrooms || [];
                this.stats = data.stats || {};
                this.teacherInfo = data.teacher_info || {};
                
                console.log('Processed classrooms:', this.classrooms);
                
                this.renderClassrooms();
                this.updateStats();
                this.updateTeacherInfo();
                this.populateClassroomSelect();
            } else {
                this.showAlert('Error loading classrooms: ' + data.message, 'error');
                this.showEmptyState();
            }
        } catch (error) {
            console.error('Error loading classrooms:', error);
            this.showAlert('Network error loading classrooms', 'error');
            this.showEmptyState();
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    renderClassrooms() {
        const classroomsGrid = document.getElementById('classroomsGrid');
        
        if (this.classrooms.length === 0) {
            this.showEmptyState();
            return;
        }
        
        this.hideEmptyState();
        
        classroomsGrid.innerHTML = this.classrooms.map(classroom => {
            const assignedDate = new Date(classroom.assigned_date);
            const studentCount = classroom.student_count || 0;
            
            return `
                <div class="classroom-card" onclick="teacherDashboard.viewClassroomDetails(${classroom.classroom_id})">
                    <div class="classroom-header">
                        <div>
                            <div class="classroom-name">${classroom.classroom_name}</div>
                            <div class="classroom-grade">${classroom.grade_level}</div>
                        </div>
                        <div class="classroom-status">Active</div>
                    </div>
                    
                    <div class="classroom-details">
                        <div class="classroom-detail">
                            <i class="fas fa-door-open"></i>
                            <span>Room ${classroom.room_number}</span>
                        </div>
                        <div class="classroom-detail">
                            <i class="fas fa-users"></i>
                            <span>${studentCount} students</span>
                        </div>
                        <div class="classroom-detail">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Assigned ${this.formatDate(assignedDate)}</span>
                        </div>
                        <div class="classroom-detail">
                            <i class="fas fa-clock"></i>
                            <span>${this.formatTimeAgo(assignedDate)} ago</span>
                        </div>
                    </div>
                    
                    <div class="classroom-actions">
                        <button class="btn btn-view" onclick="event.stopPropagation(); teacherDashboard.viewClassroomDetails(${classroom.classroom_id})">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    updateStats() {
        // Update total classrooms
        const totalClassrooms = document.getElementById('totalClassrooms');
        if (totalClassrooms) {
            totalClassrooms.textContent = this.classrooms.length;
        }
        
        // Update total students
        const totalStudents = document.getElementById('totalStudents');
        if (totalStudents) {
            totalStudents.textContent = this.stats.total_students || 0;
        }
        
        // Update active assignments
        const activeAssignments = document.getElementById('activeAssignments');
        if (activeAssignments) {
            activeAssignments.textContent = this.stats.active_assignments || 0;
        }
        
        // Update years teaching
        const yearsTeaching = document.getElementById('yearsTeaching');
        if (yearsTeaching) {
            yearsTeaching.textContent = this.stats.years_teaching || 0;
        }
    }
    
    viewClassroomDetails(classroomId) {
        const classroom = this.classrooms.find(c => c.classroom_id == classroomId);
        if (!classroom) {
            this.showAlert('Classroom not found', 'error');
            return;
        }
        
        this.populateClassroomModal(classroom);
        this.loadStudentsForClassroom(classroom.classroom_id);
        this.showClassroomModal();
        
        // Show and configure the Take Attendance button
        const takeAttendanceBtn = document.getElementById('takeAttendanceBtn');
        if (takeAttendanceBtn) {
            takeAttendanceBtn.style.display = 'inline-block';
            takeAttendanceBtn.onclick = () => this.goToAttendance(classroom.classroom_id);
        }
    }
    
    populateClassroomModal(classroom) {
        const classroomDetails = document.getElementById('classroomDetails');
        const assignedDate = new Date(classroom.assigned_date);
        
        classroomDetails.innerHTML = `
            <div class="classroom-modal-info">
                <div class="classroom-modal-header">
                    <h4>${classroom.classroom_name}</h4>
                    <span class="classroom-grade-badge">${classroom.grade_level}</span>
                </div>
                
                <div class="classroom-modal-details">
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-door-open"></i>
                            Room Number
                        </div>
                        <div class="detail-value">${classroom.room_number}</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-users"></i>
                            Student Capacity
                        </div>
                        <div class="detail-value">${classroom.capacity || 'Not specified'}</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-calendar-plus"></i>
                            Assignment Date
                        </div>
                        <div class="detail-value">${this.formatDate(assignedDate)}</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-clock"></i>
                            Duration
                        </div>
                        <div class="detail-value">${this.formatTimeAgo(assignedDate)} ago</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-info-circle"></i>
                            Status
                        </div>
                        <div class="detail-value">
                            <span class="status-badge active">Active</span>
                        </div>
                    </div>
                </div>

                <div class="students-section">
                    <div class="students-header">
                        <h5><i class="fas fa-user-graduate"></i> Students</h5>
                        <div class="students-meta" id="studentsMeta">Loading...</div>
                    </div>
                    <div class="student-list" id="studentList">
                        <div class="student-list-loading"><span class="spinner"></span> Loading students...</div>
                    </div>
                </div>
            </div>
        `;
    }

    async loadStudentsForClassroom(classroomId) {
        try {
            if (this.classroomIdToStudents[classroomId]) {
                this.renderStudentList(classroomId, this.classroomIdToStudents[classroomId]);
                return;
            }

            const query = new URLSearchParams({ 
                classroom_id: classroomId,
                limit: 1000, 
                offset: 0 
            }).toString();
            
            const response = await fetch(`../../api/get-students-by-classroom.php?${query}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (!data.success) {
                this.renderStudentList(classroomId, [], 'Failed to load students: ' + data.message);
                return;
            }

            const students = Array.isArray(data.students) ? data.students : [];
            this.classroomIdToStudents[classroomId] = students;
            this.renderStudentList(classroomId, students);
        } catch (error) {
            console.error('Error loading students:', error);
            this.renderStudentList(classroomId, [], 'Network error loading students');
        }
    }

    renderStudentList(classroomId, students, errorMessage = null) {
        const list = document.getElementById('studentList');
        const meta = document.getElementById('studentsMeta');
        if (!list || !meta) return;

        if (errorMessage) {
            meta.textContent = '';
            list.innerHTML = `<div class="student-list-error">${errorMessage}</div>`;
            return;
        }

        meta.textContent = `${students.length} student${students.length !== 1 ? 's' : ''}`;

        if (students.length === 0) {
            list.innerHTML = `<div class="student-list-empty"><i class="fas fa-info-circle"></i> No students found for this classroom.</div>`;
            return;
        }

        list.innerHTML = students.map(student => `
            <button class="student-item" onclick="teacherDashboard.onStudentClick(${student.id}, ${classroomId})">
                <span class="student-avatar">${this.getInitials(student.full_name || student.name || '')}</span>
                <span class="student-name">${student.full_name || student.name || 'Unnamed Student'}</span>
                <span class="student-chevron"><i class="fas fa-chevron-right"></i></span>
            </button>
        `).join('');
    }

    getInitials(name) {
        if (!name || typeof name !== 'string') return '?';
        const parts = name.trim().split(/\s+/);
        const first = parts[0] ? parts[0][0] : '';
        const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (first + last).toUpperCase() || '?';
    }

    onStudentClick(studentId, classroomId) {
        // Navigate to classroom sessions page with both classroom ID and student ID
        window.location.href = `sessions.php?classroom_id=${classroomId}&student_id=${studentId}`;
    }
    
    // Navigate to attendance page
    goToAttendance(classroomId) {
        window.location.href = `student-attendance.php?classroom_id=${classroomId}`;
    }
    
    showClassroomModal() {
        document.getElementById('classroomModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    closeClassroomModal() {
        document.getElementById('classroomModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    showLoading() {
        document.getElementById('loadingContainer').style.display = 'block';
        document.getElementById('classroomsGrid').innerHTML = '';
    }
    
    hideLoading() {
        document.getElementById('loadingContainer').style.display = 'none';
    }
    
    showEmptyState() {
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('classroomsGrid').innerHTML = '';
    }
    
    hideEmptyState() {
        document.getElementById('emptyState').style.display = 'none';
    }
    
    showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
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
    
    formatDate(date) {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    formatTimeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 2592000) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 31536000) {
            const months = Math.floor(diffInSeconds / 2592000);
            return `${months} month${months > 1 ? 's' : ''} ago`;
        } else {
            const years = Math.floor(diffInSeconds / 31536000);
            return `${years} year${years > 1 ? 's' : ''} ago`;
        }
    }
    
    async logout() {
        if (confirm('Are you sure you want to logout?')) {
            try {
                const response = await fetch('../../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '../../views/auth/login.php';
                } else {
                    this.showAlert('Logout failed: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Logout error:', error);
                this.showAlert('An error occurred during logout', 'error');
            }
        }
    }
    
    // Initialize session form
    initializeSessionForm() {
        // This method is called after classrooms are loaded
        // The actual population happens in populateClassroomSelect()
    }
    
    // Populate classroom select dropdown
    populateClassroomSelect() {
        const classroomSelect = document.getElementById('classroomSelect');
        if (!classroomSelect) {
            console.log('Classroom select element not found');
            return;
        }
        
        console.log('Classrooms data:', this.classrooms);
        
        // Clear existing options except the first one
        classroomSelect.innerHTML = '<option value="">Choose a classroom...</option>';
        
        // Add classroom options
        if (this.classrooms && this.classrooms.length > 0) {
            this.classrooms.forEach(classroom => {
                const option = document.createElement('option');
                option.value = classroom.classroom_id;
                option.textContent = `${classroom.classroom_name} (${classroom.grade_level})`;
                classroomSelect.appendChild(option);
            });
            console.log(`Added ${this.classrooms.length} classroom options`);
        } else {
            console.log('No classrooms found to populate');
        }
    }
    
    // Handle session form submission
    async handleSessionSubmit(e) {
        e.preventDefault();
        
        const sessionName = document.getElementById('sessionName').value.trim();
        const classroomId = document.getElementById('classroomSelect').value;
        
        console.log('Session form submission:');
        console.log('Session Name:', sessionName);
        console.log('Selected Classroom ID:', classroomId);
        
        if (!sessionName) {
            this.showAlert('Please enter a session name', 'error');
            return;
        }
        
        if (!classroomId) {
            this.showAlert('Please select a classroom', 'error');
            return;
        }
        
        if (sessionName.length < 2) {
            this.showAlert('Session name must be at least 2 characters long', 'error');
            return;
        }
        
        this.setSessionLoadingState(true);
        
        try {
            // Ensure chapter was selected for any entered homework
            const clientCheck = this.validateHomeworkInputsClientSide();
            if (!clientCheck.valid) {
                this.showAlert(clientCheck.message, 'error');
                this.setSessionLoadingState(false);
                return;
            }

            // Gather strict homework entries (must include quran_suras_id)
            const homeworkData = this.collectHomeworkData();
            if (homeworkData.length > 0) {
                const validationResults = await Promise.all(
                    homeworkData.map(hw => fetch('../../api/add-homework-chapter.php', {
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
                            classroom_id: parseInt(classroomId),
                            quran_suras_id: hw.quran_suras_id
                        })
                    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error validating Quran homework' })))
                );

                const failed = validationResults.find(v => !v.success);
                if (failed) {
                    this.showAlert(failed.message || 'Invalid Quran homework data', 'error');
                    this.setSessionLoadingState(false);
                    return; // Abort without creating the session
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
                    session_name: sessionName,
                    teacher_id: this.teacherInfo.teacher_id || this.teacherInfo.user_id,
                    classroom_id: parseInt(classroomId)
                })
            });
            
            const sessionData = await sessionResponse.json();
            
            if (sessionData.success) {
                // If there's homework data, save it using the session IDs from the response
                if (homeworkData.length > 0 && sessionData.session_ids && sessionData.session_ids.length > 0) {
                    // Use the first session ID for all homework entries (they're all for the same classroom)
                    const sessionId = sessionData.session_ids[0];
                    
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
                                classroom_id: parseInt(classroomId),
                                quran_suras_id: homework.quran_suras_id
                            })
                        });
                        
                        const homeworkResult = await homeworkResponse.json();
                        
                        if (!homeworkResult.success) {
                            console.warn('Failed to save homework data:', homeworkResult.message);
                        }
                    }
                }
                
                this.showAlert('Session and homework data created successfully!', 'success');
                document.getElementById('sessionForm').reset();
                this.populateClassroomSelect(); // Reset the select
                this.hideHomeworkTypes(); // Hide homework types section
            } else {
                this.showAlert('Error: ' + sessionData.message, 'error');
            }
        } catch (error) {
            console.error('Error creating session:', error);
            this.showAlert('Network error. Please try again.', 'error');
        } finally {
            this.setSessionLoadingState(false);
        }
    }
    
    // Set loading state for session form
    setSessionLoadingState(isLoading) {
        const submitBtn = document.getElementById('createSessionBtn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        
        if (!submitBtn || !submitText || !loadingSpinner) return;
        
        submitBtn.disabled = isLoading;
        
        if (isLoading) {
            submitText.textContent = 'Creating...';
            loadingSpinner.style.display = 'inline-block';
        } else {
            submitText.textContent = 'Create Session';
            loadingSpinner.style.display = 'none';
        }
    }

    // Load homework types
    async loadHomeworkTypes() {
        try {
            const response = await fetch('../../api/get-homework-types.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.homeworkTypes = data.homework_types || [];
            } else {
                console.error('Error loading homework types:', data.message);
            }
        } catch (error) {
            console.error('Error loading homework types:', error);
        }
    }

    // Handle classroom change to show homework types
    handleClassroomChange(e) {
        const classroomId = e.target.value;
        if (classroomId) {
            this.showHomeworkTypes();
        } else {
            this.hideHomeworkTypes();
        }
    }

    // Show homework types section
    showHomeworkTypes() {
        const homeworkSection = document.getElementById('homeworkTypesSection');
        if (homeworkSection) {
            homeworkSection.style.display = 'block';
            this.renderHomeworkTypes();
        }
    }

    // Hide homework types section
    hideHomeworkTypes() {
        const homeworkSection = document.getElementById('homeworkTypesSection');
        if (homeworkSection) {
            homeworkSection.style.display = 'none';
        }
    }

    // Render homework types
    renderHomeworkTypes() {
        const container = document.getElementById('homeworkTypesContainer');
        if (!container || this.homeworkTypes.length === 0) return;

        container.innerHTML = this.homeworkTypes.map(homeworkType => {
            return `
                <div class="homework-type-item">
                    <div class="homework-type-name">
                        <strong>${homeworkType.name}</strong>
                        <small>${homeworkType.description}</small>
                    </div>
                    <div class="homework-inputs">
                        <div class="input-group chapter-input-group">
                            <label for="chapter_${homeworkType.id}">Chapter Name</label>
                            <input 
                                type="text"
                                id="chapter_${homeworkType.id}"
                                class="chapter-input" 
                                placeholder="Type chapter name..."
                                data-homework-id="${homeworkType.id}"
                                autocomplete="off"
                            />
                        </div>
                        <div class="input-row">
                            <div class="input-group">
                                <label for="from_${homeworkType.id}">From</label>
                                <input 
                                    type="text" 
                                    id="from_${homeworkType.id}"
                                    class="from-input" 
                                    placeholder="0"
                                    pattern="[0-9٠-٩]+"
                                    data-homework-id="${homeworkType.id}"
                                />
                            </div>
                            <div class="input-group">
                                <label for="to_${homeworkType.id}">To</label>
                                <input 
                                    type="text" 
                                    id="to_${homeworkType.id}"
                                    class="to-input" 
                                    placeholder="0"
                                    pattern="[0-9٠-٩]+"
                                    data-homework-id="${homeworkType.id}"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        // Add event listeners for Arabic numeral conversion
        setTimeout(() => {
            const fromInputs = container.querySelectorAll('.from-input');
            const toInputs = container.querySelectorAll('.to-input');
            const chapterInputs = container.querySelectorAll('.chapter-input');
            
            fromInputs.forEach(input => {
                input.addEventListener('input', () => this.handleArabicNumeralInput(input));
                input.addEventListener('blur', () => this.handleArabicNumeralInput(input));
            });
            
            toInputs.forEach(input => {
                input.addEventListener('input', () => this.handleArabicNumeralInput(input));
                input.addEventListener('blur', () => this.handleArabicNumeralInput(input));
            });
            
            const self = this; // Capture the TeacherDashboard instance
            chapterInputs.forEach(input => {
                let searchTimeout;
                input.addEventListener('input', function() {
                    const query = this.value.trim();
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => self.searchChapters(query, input), 300);
                });
                
                // Hide dropdown when clicking outside
                input.addEventListener('blur', function() {
                    setTimeout(() => self.hideAutocomplete(input), 200);
                });
                
                // Click outside to close
                document.addEventListener('click', (e) => {
                    if (!input.contains(e.target) && !input.parentElement.querySelector('.chapter-autocomplete')?.contains(e.target)) {
                        self.hideAutocomplete(input);
                    }
                });
            });
        }, 100);
    }

    // Collect homework data from form
    collectHomeworkData() {
        const homeworkData = [];
        const chapterInputs = document.querySelectorAll('.chapter-input');
        const fromInputs = document.querySelectorAll('.from-input');
        const toInputs = document.querySelectorAll('.to-input');

        // Create a map to collect data by homework ID
        const homeworkMap = {};

        chapterInputs.forEach(input => {
            const homeworkId = input.dataset.homeworkId;
            const chapterId = input.dataset.chapterId || input.getAttribute('data-chapter-id');
            const value = input.value.trim();
            console.log(`Collecting homework ${homeworkId}: value="${value}", chapterId="${chapterId}"`);
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
            const value = this.convertArabicToWestern(input.value.trim());
            const numericValue = parseInt(value);
            if (!isNaN(numericValue) && numericValue > 0) {
                if (!homeworkMap[homeworkId]) {
                    homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                }
                homeworkMap[homeworkId].quran_from = numericValue;
            }
        });

        toInputs.forEach(input => {
            const homeworkId = input.dataset.homeworkId;
            const value = this.convertArabicToWestern(input.value.trim());
            const numericValue = parseInt(value);
            if (!isNaN(numericValue) && numericValue > 0) {
                if (!homeworkMap[homeworkId]) {
                    homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                }
                homeworkMap[homeworkId].quran_to = numericValue;
            }
        });

        // Convert map to array and filter out incomplete entries
        Object.values(homeworkMap).forEach(homework => {
            if (homework.quran_chapter && homework.quran_from && homework.quran_to && homework.quran_suras_id) {
                homeworkData.push(homework);
            }
        });

        return homeworkData;
    }

    // Collect raw homework data without forcing chapter selection (lets API decide)
    collectHomeworkDataRaw() {
        const homeworkData = [];
        const chapterInputs = document.querySelectorAll('.chapter-input');
        const fromInputs = document.querySelectorAll('.from-input');
        const toInputs = document.querySelectorAll('.to-input');

        const homeworkMap = {};

        chapterInputs.forEach(input => {
            const homeworkId = input.dataset.homeworkId;
            const chapterId = input.dataset.chapterId || input.getAttribute('data-chapter-id'); // may be undefined if user typed only
            const value = input.value.trim();
            if (value) {
                if (!homeworkMap[homeworkId]) {
                    homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                }
                homeworkMap[homeworkId].quran_chapter = value;
                if (chapterId) homeworkMap[homeworkId].quran_suras_id = chapterId;
            }
        });

        fromInputs.forEach(input => {
            const homeworkId = input.dataset.homeworkId;
            const value = this.convertArabicToWestern(input.value.trim());
            const numericValue = parseInt(value);
            if (!isNaN(numericValue) && numericValue > 0) {
                if (!homeworkMap[homeworkId]) {
                    homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                }
                homeworkMap[homeworkId].quran_from = numericValue;
            }
        });

        toInputs.forEach(input => {
            const homeworkId = input.dataset.homeworkId;
            const value = this.convertArabicToWestern(input.value.trim());
            const numericValue = parseInt(value);
            if (!isNaN(numericValue) && numericValue > 0) {
                if (!homeworkMap[homeworkId]) {
                    homeworkMap[homeworkId] = { homework_type_id: homeworkId };
                }
                homeworkMap[homeworkId].quran_to = numericValue;
            }
        });

        Object.values(homeworkMap).forEach(homework => {
            // push any entry where user provided at least one field
            if (homework.quran_chapter || homework.quran_from || homework.quran_to) {
                homeworkData.push(homework);
            }
        });

        return homeworkData;
    }

    // Quick client-side guardrails to ensure sura id is captured from autocomplete
    validateHomeworkInputsClientSide() {
        const chapterInputs = document.querySelectorAll('.chapter-input');
        const fromInputs = document.querySelectorAll('.from-input');
        const toInputs = document.querySelectorAll('.to-input');

        // Map by homework ID
        const map = {};
        chapterInputs.forEach(input => {
            const id = input.dataset.homeworkId;
            const suraId = input.dataset.chapterId || input.getAttribute('data-chapter-id');
            const chapterText = input.value.trim();
            console.log(`Validating homework ${id}: chapterText="${chapterText}", suraId="${suraId}"`);
            map[id] = map[id] || {};
            map[id].suraId = suraId;
            map[id].chapterText = chapterText;
        });
        fromInputs.forEach(input => {
            const id = input.dataset.homeworkId;
            const num = parseInt(this.convertArabicToWestern(input.value.trim()));
            if (!isNaN(num)) {
                map[id] = map[id] || {};
                map[id].from = num;
            }
        });
        toInputs.forEach(input => {
            const id = input.dataset.homeworkId;
            const num = parseInt(this.convertArabicToWestern(input.value.trim()));
            if (!isNaN(num)) {
                map[id] = map[id] || {};
                map[id].to = num;
            }
        });

        // Validate for each entry that has any data
        for (const [id, entry] of Object.entries(map)) {
            const hasAny = entry.from || entry.to || entry.chapterText;
            if (!hasAny) continue;

            console.log(`Checking homework ${id}:`, entry);
            if (!entry.suraId && entry.chapterText) {
                return { valid: false, message: 'Please select a chapter from the suggestions to capture its ID.' };
            }
            if (entry.from && entry.to && entry.from > entry.to) {
                return { valid: false, message: 'Invalid verse range: From cannot be greater than To.' };
            }
        }
        return { valid: true };
    }
}

// Initialize the teacher dashboard when the page loads
let teacherDashboard;
document.addEventListener('DOMContentLoaded', function() {
    teacherDashboard = new TeacherDashboard();
});
