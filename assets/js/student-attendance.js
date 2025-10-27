// Student Attendance JavaScript - Mobile-First Design

class StudentAttendance {
    constructor() {
        this.classroomId = null;
        this.currentSchoolDay = null;
        this.schoolDays = [];
        this.currentDayIndex = 0;
        this.students = [];
        this.attendanceData = {};
        this.classroomInfo = {};
        this.isLoading = false;
        this.currentStudentForNote = null;
        
        this.initializePage();
    }
    
    async initializePage() {
        try {
            // Get classroom ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            this.classroomId = parseInt(urlParams.get('classroom_id'));
            
            if (!this.classroomId) {
                this.showAlert('Invalid classroom ID', 'error');
                this.redirectToDashboard();
                return;
            }
            
            // Load classroom info and school days
            await this.loadClassroomInfo();
            await this.loadSchoolDays();
            await this.loadStudents();
            
            this.setupEventListeners();
            
        } catch (error) {
            console.error('Error initializing page:', error);
            this.showAlert('Failed to load attendance page', 'error');
        }
    }
    
    async loadClassroomInfo() {
        try {
            const response = await fetch(`../../api/get-teacher-classrooms.php`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.classrooms) {
                const classroom = data.classrooms.find(c => c.classroom_id == this.classroomId);
                if (classroom) {
                    this.classroomInfo = classroom;
                    this.updateClassroomHeader();
                } else {
                    throw new Error('Classroom not found');
                }
            } else {
                throw new Error(data.message || 'Failed to load classroom info');
            }
            
        } catch (error) {
            console.error('Error loading classroom info:', error);
            throw error;
        }
    }
    
    updateClassroomHeader() {
        const classroomName = document.getElementById('classroomName');
        if (classroomName && this.classroomInfo.classroom_name) {
            classroomName.textContent = this.classroomInfo.classroom_name;
        }
    }
    
    async loadSchoolDays() {
        try {
            this.showLoading();
            
            // Load all school days for this classroom
            const response = await fetch(`../../api/get-school-days.php?classroom_id=${this.classroomId}&limit=1000&offset=0`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.school_days && data.school_days.length > 0) {
                this.schoolDays = data.school_days;
                this.classroomInfo = { ...this.classroomInfo, ...data.classroom_info };
                
                // Find today's school day or the closest upcoming one
                const today = new Date().toISOString().split('T')[0];
                let targetIndex = 0;
                
                // Try to find today's date
                const todayIndex = this.schoolDays.findIndex(day => day.date === today);
                if (todayIndex !== -1) {
                    targetIndex = todayIndex;
                } else {
                    // Find the next upcoming school day
                    const upcomingIndex = this.schoolDays.findIndex(day => day.date >= today);
                    if (upcomingIndex !== -1) {
                        targetIndex = upcomingIndex;
                    }
                }
                
                this.currentDayIndex = targetIndex;
                this.currentSchoolDay = this.schoolDays[this.currentDayIndex];
                
                // Debug: Log the current school day data
                console.log('Current school day data:', {
                    has_attendance: this.currentSchoolDay.has_attendance,
                    attendance_status: this.currentSchoolDay.attendance_status,
                    attendance_record_id: this.currentSchoolDay.attendance_record_id,
                    date: this.currentSchoolDay.date
                });
                
                this.updateDateDisplay();
                this.updateAttendanceStatus();
                this.updateNavigationButtons();
                this.renderDatePagination();
                // Load existing attendance after setting the initial button state
                await this.loadExistingAttendance();
            } else {
                this.showEmptyState();
            }
            
        } catch (error) {
            console.error('Error loading school days:', error);
            this.showAlert('Failed to load school days', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    updateDateDisplay() {
        if (!this.currentSchoolDay) return;
        
        const dateDisplay = document.getElementById('dateDisplay');
        const dateSubtitle = document.getElementById('dateSubtitle');
        
        if (dateDisplay && dateSubtitle) {
            const date = new Date(this.currentSchoolDay.date);
            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            dateDisplay.textContent = formattedDate;
            dateSubtitle.textContent = this.currentSchoolDay.is_school_day ? 'School Day' : 'Non-School Day';
        }
    }
    
    updateAttendanceStatus() {
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        
        if (!statusIndicator || !statusText) return;
        
        console.log('updateAttendanceStatus called with:', {
            has_attendance: this.currentSchoolDay.has_attendance,
            attendance_status: this.currentSchoolDay.attendance_status
        });
        
        if (this.currentSchoolDay.has_attendance) {
            if (this.currentSchoolDay.attendance_status === 'closed') {
                statusIndicator.className = 'status-indicator completed';
                statusText.textContent = 'Completed';
                // Show reopen button for closed attendance
                console.log('Showing reopen button');
                this.showReopenButton();
            } else {
                statusIndicator.className = 'status-indicator open';
                statusText.textContent = 'In Progress';
                // Show submit button for open attendance
                console.log('Showing submit button');
                this.showSubmitButton();
            }
        } else {
            statusIndicator.className = 'status-indicator';
            statusText.textContent = 'Not Started';
            // Show submit button for new attendance
            console.log('Showing submit button (no attendance)');
            this.showSubmitButton();
        }
    }
    
    updateNavigationButtons() {
        const prevBtn = document.getElementById('prevDayBtn');
        const nextBtn = document.getElementById('nextDayBtn');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentDayIndex === 0;
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.currentDayIndex === this.schoolDays.length - 1;
        }
    }
    
    renderDatePagination() {
        const dateNavigation = document.querySelector('.date-navigation');
        if (!dateNavigation) return;
        
        // Create pagination container if it doesn't exist
        let paginationContainer = document.getElementById('datePagination');
        if (!paginationContainer) {
            paginationContainer = document.createElement('div');
            paginationContainer.id = 'datePagination';
            paginationContainer.className = 'date-pagination';
            dateNavigation.appendChild(paginationContainer);
        }
        
        // Clear existing content
        paginationContainer.innerHTML = '';
        
        // Show a subset of dates around current date
        const startIndex = Math.max(0, this.currentDayIndex - 2);
        const endIndex = Math.min(this.schoolDays.length - 1, this.currentDayIndex + 2);
        
        for (let i = startIndex; i <= endIndex; i++) {
            const day = this.schoolDays[i];
            const dateBtn = document.createElement('button');
            dateBtn.className = `date-btn ${i === this.currentDayIndex ? 'active' : ''}`;
            dateBtn.innerHTML = `
                <div class="date-day">${new Date(day.date).getDate()}</div>
                <div class="date-month">${new Date(day.date).toLocaleDateString('en-US', { month: 'short' })}</div>
            `;
            dateBtn.onclick = () => this.goToDate(i);
            paginationContainer.appendChild(dateBtn);
        }
    }
    
    goToDate(index) {
        if (index < 0 || index >= this.schoolDays.length) return;
        
        this.currentDayIndex = index;
        this.currentSchoolDay = this.schoolDays[this.currentDayIndex];
        this.attendanceData = {}; // Clear current attendance data
        
        this.updateDateDisplay();
        this.updateAttendanceStatus();
        this.updateNavigationButtons();
        this.renderDatePagination();
        this.renderStudents();
        this.loadExistingAttendance();
    }
    
    async loadStudents() {
        try {
            const response = await fetch(`../../api/get-students-by-classroom.php?classroom_id=${this.classroomId}&limit=1000&offset=0`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.students) {
                this.students = data.students;
                this.updateStudentsCount();
                this.renderStudents();
            } else {
                throw new Error(data.message || 'Failed to load students');
            }
            
        } catch (error) {
            console.error('Error loading students:', error);
            this.showAlert('Failed to load students', 'error');
        }
    }
    
    updateStudentsCount() {
        const studentsCount = document.getElementById('studentsCount');
        if (studentsCount) {
            studentsCount.textContent = `${this.students.length} student${this.students.length !== 1 ? 's' : ''}`;
        }
    }
    
    renderStudents() {
        const studentsList = document.getElementById('studentsList');
        if (!studentsList) return;
        
        studentsList.innerHTML = this.students.map(student => {
            const attendance = this.attendanceData[student.id] || { status: '', note: '' };
            
            return `
                <div class="student-card" data-student-id="${student.id}">
                    <div class="student-header">
                        <div class="student-avatar">
                            ${this.getInitials(student.full_name || student.name || '')}
                        </div>
                        <div class="student-info">
                            <div class="student-name">${student.full_name || student.name || 'Unnamed Student'}</div>
                            <div class="student-id">ID: ${student.id}</div>
                        </div>
                    </div>
                    
                    <div class="status-buttons">
                        <button class="status-btn present ${attendance.status === 'present' ? 'active' : ''}" 
                                data-status="present" 
                                data-student-id="${student.id}">
                            <i class="fas fa-check"></i>
                            Present
                        </button>
                        <button class="status-btn absent ${attendance.status === 'absent' ? 'active' : ''}" 
                                data-status="absent" 
                                data-student-id="${student.id}">
                            <i class="fas fa-times"></i>
                            Absent
                        </button>
                        <button class="status-btn late ${attendance.status === 'late' ? 'active' : ''}" 
                                data-status="late" 
                                data-student-id="${student.id}">
                            <i class="fas fa-clock"></i>
                            Late
                        </button>
                        <button class="status-btn excused ${attendance.status === 'excused' ? 'active' : ''}" 
                                data-status="excused" 
                                data-student-id="${student.id}">
                            <i class="fas fa-user-clock"></i>
                            Excused
                        </button>
                    </div>
                    
                    <div class="note-section">
                        <button class="note-btn" onclick="studentAttendance.openNoteModal(${student.id})">
                            <i class="fas fa-sticky-note"></i>
                            ${attendance.note ? 'Edit Note' : 'Add Note'}
                        </button>
                        ${attendance.note ? `<div class="note-display">${attendance.note}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        this.showStudentsContainer();
    }
    
    getInitials(name) {
        if (!name || typeof name !== 'string') return '?';
        const parts = name.trim().split(/\s+/);
        const first = parts[0] ? parts[0][0] : '';
        const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (first + last).toUpperCase() || '?';
    }
    
    setupEventListeners() {
        // Status button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.status-btn')) {
                const btn = e.target.closest('.status-btn');
                const studentId = parseInt(btn.dataset.studentId);
                const status = btn.dataset.status;
                
                this.selectStatus(studentId, status);
            }
        });
        
        // Navigation buttons
        const prevBtn = document.getElementById('prevDayBtn');
        const nextBtn = document.getElementById('nextDayBtn');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.navigateDay(-1));
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.navigateDay(1));
        }
        
        // Submit button
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitAttendance());
        }
        
        // Reopen button
        const reopenBtn = document.getElementById('reopenBtn');
        if (reopenBtn) {
            reopenBtn.addEventListener('click', () => this.reopenAttendance());
        }
        
        // Save note button
        const saveNoteBtn = document.getElementById('saveNoteBtn');
        if (saveNoteBtn) {
            saveNoteBtn.addEventListener('click', () => this.saveNote());
        }
        
        // Close modal when clicking outside
        const noteModal = document.getElementById('noteModal');
        if (noteModal) {
            noteModal.addEventListener('click', (e) => {
                if (e.target === noteModal) {
                    this.closeNoteModal();
                }
            });
        }
    }
    
    selectStatus(studentId, status) {
        // Update attendance data
        if (!this.attendanceData[studentId]) {
            this.attendanceData[studentId] = { status: '', note: '' };
        }
        
        this.attendanceData[studentId].status = status;
        
        // Update UI
        const studentCard = document.querySelector(`[data-student-id="${studentId}"]`);
        if (studentCard) {
            // Remove active class from all status buttons
            const statusBtns = studentCard.querySelectorAll('.status-btn');
            statusBtns.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to selected button
            const selectedBtn = studentCard.querySelector(`[data-status="${status}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
            }
        }
        
        // Update submit button state
        this.updateSubmitButtonState();
    }
    
    updateSubmitButtonState() {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;
        
        const totalStudents = this.students.length;
        const markedStudents = Object.values(this.attendanceData).filter(att => att.status).length;
        
        submitBtn.disabled = markedStudents < totalStudents;
        
        const submitText = document.getElementById('submitText');
        if (submitText) {
            submitText.textContent = `Submit Attendance (${markedStudents}/${totalStudents})`;
        }
    }
    
    async loadExistingAttendance() {
        // If we have an attendance_record_id, we definitely have attendance
        if (this.currentSchoolDay.attendance_record_id) {
            this.currentSchoolDay.has_attendance = true;
        }
        
        if (!this.currentSchoolDay.has_attendance) {
            this.showActionButtons();
            this.showSubmitButton();
            return;
        }
        
        try {
            const response = await fetch(`../../api/get-attendance.php?classroom_id=${this.classroomId}&school_day_id=${this.currentSchoolDay.id}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.attendance_record) {
                // Load existing attendance data
                this.attendanceData = {};
                data.attendance_record.students.forEach(student => {
                    this.attendanceData[student.student_id] = {
                        status: student.status,
                        note: student.note || ''
                    };
                });
                
                // Set the attendance record ID
                this.currentSchoolDay.attendance_record_id = data.attendance_record.id;
                
                console.log('Loaded existing attendance record:', {
                    record_id: data.attendance_record.id,
                    school_day_id: this.currentSchoolDay.id,
                    classroom_id: this.classroomId,
                    status: data.attendance_record.status
                });
                
                // Re-render students with existing data
                this.renderStudents();
                
                // Show appropriate action buttons (only if different from current state)
                if (data.attendance_record.status === 'closed') {
                    this.showReopenButton();
                } else {
                    this.showSubmitButton();
                }
            } else {
                // If API call failed but we know there's attendance, show appropriate button based on status
                if (this.currentSchoolDay.has_attendance) {
                    if (this.currentSchoolDay.attendance_status === 'closed') {
                    this.showReopenButton();
                } else {
                    this.showSubmitButton();
                }
            } else {
                this.showActionButtons();
                    this.showSubmitButton();
                }
            }
            
        } catch (error) {
            console.error('Error loading existing attendance:', error);
            // If API call failed but we know there's attendance, show appropriate button based on status
            if (this.currentSchoolDay.has_attendance) {
                if (this.currentSchoolDay.attendance_status === 'closed') {
                    this.showReopenButton();
                } else {
                    this.showSubmitButton();
                }
            } else {
            this.showActionButtons();
                this.showSubmitButton();
            }
        }
    }
    
    showActionButtons() {
        const actionButtons = document.getElementById('actionButtons');
        console.log('showActionButtons called:', {
            actionButtons: !!actionButtons,
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
        
        if (actionButtons) {
            actionButtons.style.display = 'flex';
        }
        // Don't automatically show submit button - let the caller decide which button to show
        
        console.log('After showActionButtons:', {
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
    }
    
    showSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        const reopenBtn = document.getElementById('reopenBtn');
        const actionButtons = document.getElementById('actionButtons');
        
        console.log('showSubmitButton called:', {
            submitBtn: !!submitBtn,
            reopenBtn: !!reopenBtn,
            actionButtons: !!actionButtons,
            submitBtnDisplay: submitBtn ? submitBtn.style.display : 'not found',
            reopenBtnDisplay: reopenBtn ? reopenBtn.style.display : 'not found',
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
        
        // Make sure action buttons container is visible
        if (actionButtons) {
            actionButtons.style.display = 'flex';
        }
        
        if (submitBtn) submitBtn.style.display = 'flex';
        if (reopenBtn) reopenBtn.style.display = 'none';
        
        this.updateSubmitButtonState();
        
        console.log('After showSubmitButton:', {
            submitBtnDisplay: submitBtn ? submitBtn.style.display : 'not found',
            reopenBtnDisplay: reopenBtn ? reopenBtn.style.display : 'not found',
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
    }
    
    showReopenButton() {
        const submitBtn = document.getElementById('submitBtn');
        const reopenBtn = document.getElementById('reopenBtn');
        const actionButtons = document.getElementById('actionButtons');
        
        console.log('showReopenButton called:', {
            submitBtn: !!submitBtn,
            reopenBtn: !!reopenBtn,
            actionButtons: !!actionButtons,
            submitBtnDisplay: submitBtn ? submitBtn.style.display : 'not found',
            reopenBtnDisplay: reopenBtn ? reopenBtn.style.display : 'not found',
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
        
        // Make sure action buttons container is visible
        if (actionButtons) {
            actionButtons.style.display = 'flex';
        }
        
        if (submitBtn) submitBtn.style.display = 'none';
        if (reopenBtn) reopenBtn.style.display = 'flex';
        
        console.log('After showReopenButton:', {
            submitBtnDisplay: submitBtn ? submitBtn.style.display : 'not found',
            reopenBtnDisplay: reopenBtn ? reopenBtn.style.display : 'not found',
            actionButtonsDisplay: actionButtons ? actionButtons.style.display : 'not found'
        });
    }
    
    async submitAttendance() {
        if (this.isLoading) return;
        
        // Validate all students are marked
        const totalStudents = this.students.length;
        const markedStudents = Object.values(this.attendanceData).filter(att => att.status).length;
        
        if (markedStudents < totalStudents) {
            this.showAlert(`Please mark attendance for all ${totalStudents} students`, 'warning');
            return;
        }
        
        this.setSubmitLoadingState(true);
        
        try {
            // Prepare data
            const students = this.students.map(student => ({
                student_id: student.id,
                status: this.attendanceData[student.id]?.status || 'present',
                note: this.attendanceData[student.id]?.note || ''
            }));
            
            const data = {
                classroom_id: this.classroomId,
                school_day_id: this.currentSchoolDay.id,
                academic_year_id: this.classroomInfo.academic_year_id,
                term_id: this.classroomInfo.term_id,
                students: students
            };
            
            // If we have an existing attendance record, include it in the data
            if (this.currentSchoolDay.attendance_record_id) {
                data.attendance_record_id = this.currentSchoolDay.attendance_record_id;
                console.log('Updating existing attendance record:', this.currentSchoolDay.attendance_record_id);
                console.log('Current school day data:', {
                    id: this.currentSchoolDay.id,
                    attendance_record_id: this.currentSchoolDay.attendance_record_id,
                    classroom_id: this.classroomId
                });
            } else {
                console.log('Creating new attendance record');
            }
            
            const response = await fetch('../../api/save-attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Attendance saved successfully!', 'success');
                this.currentSchoolDay.has_attendance = true;
                this.currentSchoolDay.attendance_status = 'closed';
                this.currentSchoolDay.attendance_record_id = result.attendance_record_id;
                this.updateAttendanceStatus();
                this.showReopenButton();
            } else {
                throw new Error(result.message || 'Failed to save attendance');
            }
            
        } catch (error) {
            console.error('Error submitting attendance:', error);
            this.showAlert('Failed to save attendance: ' + error.message, 'error');
        } finally {
            this.setSubmitLoadingState(false);
        }
    }
    
    async reopenAttendance() {
        if (this.isLoading) return;
        
        if (!this.currentSchoolDay.attendance_record_id) {
            this.showAlert('No attendance record to reopen', 'error');
            return;
        }
        
        this.setSubmitLoadingState(true);
        
        try {
            const response = await fetch('../../api/reopen-attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    attendance_record_id: this.currentSchoolDay.attendance_record_id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Attendance reopened for editing', 'success');
                this.currentSchoolDay.attendance_status = 'open';
                this.updateAttendanceStatus();
                
                // Load existing attendance data after reopening
                await this.loadExistingAttendance();
                
                this.showSubmitButton();
            } else {
                throw new Error(result.message || 'Failed to reopen attendance');
            }
            
        } catch (error) {
            console.error('Error reopening attendance:', error);
            this.showAlert('Failed to reopen attendance: ' + error.message, 'error');
        } finally {
            this.setSubmitLoadingState(false);
        }
    }
    
    openNoteModal(studentId) {
        const student = this.students.find(s => s.id === studentId);
        if (!student) return;
        
        this.currentStudentForNote = studentId;
        
        const noteStudentInfo = document.getElementById('noteStudentInfo');
        const studentNote = document.getElementById('studentNote');
        
        if (noteStudentInfo) {
            noteStudentInfo.innerHTML = `
                <div class="student-avatar">
                    ${this.getInitials(student.full_name || student.name || '')}
                </div>
                <div class="student-info">
                    <div class="student-name">${student.full_name || student.name || 'Unnamed Student'}</div>
                    <div class="student-id">ID: ${student.id}</div>
                </div>
            `;
        }
        
        if (studentNote) {
            studentNote.value = this.attendanceData[studentId]?.note || '';
        }
        
        const noteModal = document.getElementById('noteModal');
        if (noteModal) {
            noteModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeNoteModal() {
        const noteModal = document.getElementById('noteModal');
        if (noteModal) {
            noteModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        this.currentStudentForNote = null;
    }
    
    saveNote() {
        if (!this.currentStudentForNote) return;
        
        const studentNote = document.getElementById('studentNote');
        if (!studentNote) return;
        
        const note = studentNote.value.trim();
        
        // Update attendance data
        if (!this.attendanceData[this.currentStudentForNote]) {
            this.attendanceData[this.currentStudentForNote] = { status: '', note: '' };
        }
        
        this.attendanceData[this.currentStudentForNote].note = note;
        
        // Update UI
        this.renderStudents();
        
        this.closeNoteModal();
        this.showAlert('Note saved', 'success');
    }
    
    async navigateDay(direction) {
        if (this.isLoading) return;
        
        const newIndex = this.currentDayIndex + direction;
        if (newIndex >= 0 && newIndex < this.schoolDays.length) {
            this.goToDate(newIndex);
        }
    }
    
    setSubmitLoadingState(isLoading) {
        this.isLoading = isLoading;
        
        const submitBtn = document.getElementById('submitBtn');
        const submitLoading = document.getElementById('submitLoading');
        
        if (submitBtn) {
            submitBtn.disabled = isLoading;
        }
        
        if (submitLoading) {
            submitLoading.style.display = isLoading ? 'inline-block' : 'none';
        }
    }
    
    showLoading() {
        const loadingContainer = document.getElementById('loadingContainer');
        const studentsContainer = document.getElementById('studentsContainer');
        const emptyState = document.getElementById('emptyState');
        
        if (loadingContainer) loadingContainer.style.display = 'flex';
        if (studentsContainer) studentsContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
    }
    
    hideLoading() {
        const loadingContainer = document.getElementById('loadingContainer');
        if (loadingContainer) {
            loadingContainer.style.display = 'none';
        }
    }
    
    showStudentsContainer() {
        const studentsContainer = document.getElementById('studentsContainer');
        const emptyState = document.getElementById('emptyState');
        
        if (studentsContainer) studentsContainer.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';
    }
    
    showEmptyState() {
        const studentsContainer = document.getElementById('studentsContainer');
        const emptyState = document.getElementById('emptyState');
        
        if (studentsContainer) studentsContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'flex';
    }
    
    showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
        `;
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alert);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }
    
    redirectToDashboard() {
        window.location.href = 'index.php';
    }
}

// Initialize the student attendance when the page loads
let studentAttendance;
document.addEventListener('DOMContentLoaded', function() {
    studentAttendance = new StudentAttendance();
});
