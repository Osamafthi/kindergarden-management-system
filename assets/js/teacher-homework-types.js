// Teacher Homework Types - External JavaScript

// Global variables
let sessionType = 'quran'; // Will be determined dynamically
let homeworkData = [];
let gradesData = {};
let studentsData = [];
let currentStudentId = null;

// Initialize page
document.addEventListener('DOMContentLoaded', async function() {
    await loadStudents();
    await detectSessionType();
    await loadHomeworkData();
});

// Detect session type based on URL parameters or API response
async function detectSessionType() {
    try {
        console.log('جاري تحديد نوع الجلسة...');
        
        // First try to get Quran homework data
        const quranResponse = await fetch(`../../api/get-homework-data.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${studentId}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const quranData = await quranResponse.json();
        console.log('استجابة بيانات القرآن:', quranData);
        
        if (quranData.success && quranData.homework_data && quranData.homework_data.length > 0) {
            sessionType = 'quran';
            console.log('تم تعيين نوع الجلسة إلى: قرآن');
        } else {
            // Try modules data
            const modulesResponse = await fetch(`../../api/get-homework-data-module.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${studentId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const modulesData = await modulesResponse.json();
            console.log('استجابة بيانات الوحدات:', modulesData);
            
            if (modulesData.success && modulesData.homework_data && modulesData.homework_data.length > 0) {
                sessionType = 'modules';
                console.log('تم تعيين نوع الجلسة إلى: وحدات');
            } else {
                console.log('لم يتم العثور على بيانات للقرآن أو الوحدات، الافتراضي هو القرآن');
            }
        }
        
        // Update UI based on session type
        updateUIForSessionType();
        
    } catch (error) {
        console.error('خطأ في تحديد نوع الجلسة:', error);
        // Default to Quran if detection fails
        sessionType = 'quran';
        updateUIForSessionType();
    }
}

// Update UI based on session type
function updateUIForSessionType() {
    const tableContainer = document.querySelector('.table-container');
    const modulesContainer = document.querySelector('.modules-container');
    
    if (sessionType === 'quran') {
        if (tableContainer) tableContainer.style.display = 'block';
        if (modulesContainer) modulesContainer.style.display = 'none';
    } else if (sessionType === 'modules') {
        if (tableContainer) tableContainer.style.display = 'none';
        if (modulesContainer) modulesContainer.style.display = 'block';
    }
}

// Load homework data based on session type
async function loadHomeworkData() {
    console.log('جاري تحميل بيانات الواجبات لنوع الجلسة:', sessionType);
    showLoading();
    
    try {
        let response;
        
        if (sessionType === 'quran') {
            console.log('جاري جلب بيانات واجبات القرآن...');
            response = await fetch(`../../api/get-homework-data.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${currentStudentId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
        } else {
            console.log('جاري جلب بيانات واجبات الوحدات...');
            response = await fetch(`../../api/get-homework-data-module.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${currentStudentId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
        }
        
        const data = await response.json();
        console.log('استجابة بيانات الواجبات:', data);
        
        if (data.success) {
            homeworkData = data.homework_data || [];
            console.log('تم تحميل بيانات الواجبات:', homeworkData);
            renderHomeworkData(homeworkData);
        } else {
            console.log('خطأ في تحميل بيانات الواجبات:', data.message);
            showAlert('خطأ في تحميل بيانات الواجبات: ' + data.message, 'error');
            showEmptyState();
        }
    } catch (error) {
        console.error('خطأ في تحميل بيانات الواجبات:', error);
        showAlert('خطأ في الشبكة أثناء تحميل بيانات الواجبات', 'error');
        showEmptyState();
    } finally {
        hideLoading();
    }
}

// Render homework data based on session type
function renderHomeworkData(data) {
    if (data.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    if (sessionType === 'quran') {
        renderQuranTable(data);
    } else {
        renderModulesList(data);
    }
}

// Render Quran homework data in table format
function renderQuranTable(data) {
    const tableBody = document.getElementById('homeworkTableBody');
    
    if (!tableBody) return;
    
    tableBody.innerHTML = data.map(homework => {
        const createdDate = new Date(homework.created_at);
        const gradeValue = homework.grade || '';
        
        return `
            <tr data-homework-id="${homework.homework_type_id}">
                <td class="homework-info">
                    <div class="homework-type-name">${homework.homework_type_name}</div>
                    <div class="homework-description">${homework.description || ''}</div>
                </td>
                <td class="chapter-name">${homework.quran_chapter || ''}</td>
                <td class="verse-range">${homework.quran_from || ''} - ${homework.quran_to || ''}</td>
                <td class="grade-cell">
                    <input type="number" 
                           class="grade-input" 
                           value="${gradeValue}" 
                           min="0" 
                           max="${homework.max_grade}" 
                           placeholder="الدرجة"
                           data-homework-id="${homework.homework_type_id}"
                           onchange="updateGrade(${homework.homework_type_id}, this.value)">
                    <div class="max-grade-info">الحد الأقصى: ${homework.max_grade}</div>
                </td>
                <td class="created-date">${formatDate(createdDate)}</td>
            </tr>
        `;
    }).join('');
}

// Render modules homework data in list format
function renderModulesList(data) {
    const modulesContainer = document.querySelector('.modules-container');
    
    if (!modulesContainer) return;
    
    // Create modules list if it doesn't exist
    let modulesList = modulesContainer.querySelector('.modules-list');
    if (!modulesList) {
        modulesList = document.createElement('div');
        modulesList.className = 'modules-list';
        modulesContainer.appendChild(modulesList);
    }
    
    modulesList.innerHTML = data.map(module => {
        const createdDate = new Date(module.created_at);
        const gradeValue = module.grade || '';
        const isPDF = module.photo && module.photo.toLowerCase().endsWith('.pdf');
        
        return `
            <div class="module-item" data-module-id="${module.module_id}">
                <div class="module-header">
                    <div class="module-title">${module.lesson_title}</div>
                    <div class="module-type">${module.homework_type_name}</div>
                </div>
                
                <div class="module-content">
                    ${module.photo ? `
                        ${isPDF ? `
                            <div class="pdf-viewer-container">
                                <div class="pdf-viewer-header">
                                    <div class="pdf-info">
                                        <i class="fas fa-file-pdf"></i>
                                        <span class="pdf-filename">${module.photo.split('/').pop()}</span>
                                    </div>
                                    <div class="pdf-actions">
                                        <a href="../../${module.photo}" target="_blank" class="pdf-action-btn" title="فتح في نافذة جديدة">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <a href="../../${module.photo}" download class="pdf-action-btn" title="تحميل PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="pdf-viewer-wrapper" id="pdf-${module.module_id}">
                                    <div class="pdfjs-container" data-pdf-url="../../${module.photo}" style="display: none;"></div>
                                    <iframe 
                                        src="../../${module.photo}#view=Fit&toolbar=0&navpanes=0&scrollbar=1&page=1&zoom=85" 
                                        class="pdf-iframe" 
                                        type="application/pdf"
                                        title="${module.lesson_title} PDF"
                                        frameborder="0"
                                        scrolling="yes"
                                        allowfullscreen
                                        onload="handlePDFLoad(this)"
                                        onerror="handlePDFError(this)">
                                    </iframe>
                                    <div class="pdf-loading">
                                        <div class="loading-spinner-large"></div>
                                        <p>جاري تحميل PDF...</p>
                                    </div>
                                    <div class="pdf-error" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <p>تعذر عرض PDF</p>
                                        <a href="../../${module.photo}" target="_blank" class="btn btn-primary">
                                            <i class="fas fa-external-link-alt"></i> فتح في نافذة جديدة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        ` : `
                            <img src="../../${module.photo}" alt="${module.lesson_title}" class="module-image" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div style="display: none; text-align: center; color: #6c757d; padding: 20px;">
                                <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p>الصورة غير متاحة</p>
                            </div>
                        `}
                    ` : `
                        <div style="text-align: center; color: #6c757d; padding: 20px;">
                            <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>لم يتم تحميل ملف</p>
                        </div>
                    `}
                </div>
                
                <div class="module-grade-section">
                    <span class="grade-label">الدرجة:</span>
                    <input type="number" 
                           class="module-grade-input" 
                           value="${gradeValue}" 
                           min="0" 
                           max="${module.max_grade}" 
                           placeholder="أدخل الدرجة"
                           data-module-id="${module.module_id}"
                           data-homework-type-id="${module.homework_type_name}"
                           onchange="updateModuleGrade(${module.module_id}, this.value)">
                    <span class="max-grade-info">الحد الأقصى: ${module.max_grade}</span>
                </div>
                
                <div class="module-date">تاريخ الإنشاء: ${formatDate(createdDate)}</div>
            </div>
        `;
    }).join('');
    initializePDFViewers(data);
}

// Update grade for Quran homework
function updateGrade(homeworkTypeId, grade) {
    const gradeInput = document.querySelector(`input[data-homework-id="${homeworkTypeId}"]`);
    
    if (!gradeInput) return;
    
    // Validate grade
    const maxGrade = parseInt(gradeInput.getAttribute('max'));
    const gradeValue = parseInt(grade);
    
    if (gradeValue < 0 || gradeValue > maxGrade) {
        gradeInput.classList.add('error');
        showAlert(`Grade must be between 0 and ${maxGrade}`, 'error');
        return;
    }
    
    gradeInput.classList.remove('error');
    gradesData[homeworkTypeId] = gradeValue;
}

// Update grade for module homework
function updateModuleGrade(moduleId, grade) {
    const gradeInput = document.querySelector(`input[data-module-id="${moduleId}"]`);
    
    if (!gradeInput) return;
    
    // Validate grade
    const maxGrade = parseInt(gradeInput.getAttribute('max'));
    const gradeValue = parseInt(grade);
    
    if (gradeValue < 0 || gradeValue > maxGrade) {
        gradeInput.classList.add('error');
        showAlert(`Grade must be between 0 and ${maxGrade}`, 'error');
        return;
    }
    
    gradeInput.classList.remove('error');
    gradesData[moduleId] = gradeValue;
}

// Submit grades
async function submitGrades() {
    const submitBtn = document.getElementById('submitGradesBtn');
    const submitText = document.getElementById('submitGradesText');
    const loadingSpinner = document.getElementById('submitLoadingSpinner');
    
    // Set loading state
    submitBtn.disabled = true;
    submitText.textContent = 'Submitting...';
    loadingSpinner.style.display = 'inline-block';
    
    try {
        // Collect all grades
        const grades = [];
        
        if (sessionType === 'quran') {
            // Collect Quran grades
            homeworkData.forEach(homework => {
                const gradeInput = document.querySelector(`input[data-homework-id="${homework.homework_type_id}"]`);
                if (gradeInput && gradeInput.value !== '') {
                    grades.push({
                        homework_type_id: homework.homework_type_id,
                        homework_grades_id: homework.id, // This is the homework_grades table ID
                        grade: parseInt(gradeInput.value)
                    });
                }
            });
        } else {
            // Collect module grades
            homeworkData.forEach(module => {
                const gradeInput = document.querySelector(`input[data-module-id="${module.module_id}"]`);
                if (gradeInput && gradeInput.value !== '') {
                    grades.push({
                        homework_type_id: module.homework_types_id,
                        session_module_id: module.module_id, // This is the session_module table ID
                        grade: parseInt(gradeInput.value)
                    });
                }
            });
        }
        
        if (grades.length === 0) {
            showAlert('يرجى إدخال درجة واحدة على الأقل قبل الحفظ', 'error');
            return;
        }
        
        // Determine which API to call based on session type
        const apiEndpoint = sessionType === 'quran' 
            ? '../../api/add-grade-homework.php' 
            : '../../api/add-grade-homework-module.php';
        
        console.log('جاري إرسال الدرجات إلى:', apiEndpoint);
        console.log('بيانات الدرجات:', grades);
        
        // Get session_id from homework data
        if (homeworkData.length === 0) {
            showAlert('لا توجد بيانات واجبات متاحة', 'error');
            return;
        }
        
        const sessionId = homeworkData[0].session_id;
        
        if (!sessionId) {
            console.error('session_id مفقود في بيانات الواجبات:', homeworkData[0]);
            showAlert('خطأ: لم يتم العثور على معرف الجلسة. يرجى المحاولة مرة أخرى.', 'error');
            return;
        }
        
        // Submit grades
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                session_id: sessionId,
                student_id: currentStudentId,
                grades: grades
            })
        });
        
        const result = await response.json();
        console.log('استجابة الحفظ:', result);
        
        if (result.success) {
            showAlert('تم حفظ الدرجات بنجاح!', 'success');
            // Reload data to show updated grades
            setTimeout(() => {
                loadHomeworkData();
            }, 1500);
        } else {
            showAlert('خطأ في حفظ الدرجات: ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('خطأ في حفظ الدرجات:', error);
        showAlert('خطأ في الشبكة أثناء حفظ الدرجات', 'error');
    } finally {
        // Reset loading state
        submitBtn.disabled = false;
        submitText.textContent = 'حفظ الدرجات';
        loadingSpinner.style.display = 'none';
    }
}

// Show loading state
function showLoading() {
    const loadingContainer = document.getElementById('loadingContainer');
    const homeworkTable = document.getElementById('homeworkTable');
    const modulesContainer = document.querySelector('.modules-container');
    
    if (loadingContainer) loadingContainer.style.display = 'block';
    if (homeworkTable) homeworkTable.style.display = 'none';
    if (modulesContainer) modulesContainer.style.display = 'none';
}

// Hide loading state
function hideLoading() {
    const loadingContainer = document.getElementById('loadingContainer');
    const homeworkTable = document.getElementById('homeworkTable');
    const modulesContainer = document.querySelector('.modules-container');
    
    if (loadingContainer) loadingContainer.style.display = 'none';
    if (homeworkTable) homeworkTable.style.display = 'table';
    if (modulesContainer) modulesContainer.style.display = 'block';
}

// Show empty state
function showEmptyState() {
    const emptyState = document.getElementById('emptyState');
    const homeworkTable = document.getElementById('homeworkTable');
    const modulesContainer = document.querySelector('.modules-container');
    
    if (emptyState) emptyState.style.display = 'block';
    if (homeworkTable) homeworkTable.style.display = 'none';
    if (modulesContainer) modulesContainer.style.display = 'none';
}

// Hide empty state
function hideEmptyState() {
    const emptyState = document.getElementById('emptyState');
    
    if (emptyState) emptyState.style.display = 'none';
}

// Show alert message
function showAlert(message, type) {
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

// Format date
function formatDate(date) {
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Go back to sessions page
function goBack() {
    window.location.href = `sessions.php?classroom_id=${classroomId}`;
}

// Logout function
async function logout() {
    if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
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
                showAlert('فشل تسجيل الخروج: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('خطأ في تسجيل الخروج:', error);
            showAlert('حدث خطأ أثناء تسجيل الخروج', 'error');
        }
    }
}

// Student Management Functions
async function loadStudents() {
    try {
        console.log('جاري تحميل الطلاب للفصل:', classroomId);
        
        const response = await fetch(`../../api/get-students-by-classroom.php?classroom_id=${classroomId}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        console.log('استجابة الطلاب:', result);
        
        if (result.success) {
            studentsData = result.students;
            currentStudentId = studentId; // Set current student from URL
            
            renderStudentSelector();
            updateCurrentStudentInfo();
        } else {
            showAlert('خطأ في تحميل الطلاب: ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('خطأ في تحميل الطلاب:', error);
        showAlert('خطأ في الشبكة أثناء تحميل الطلاب', 'error');
    }
}

function renderStudentSelector() {
    const studentSelector = document.getElementById('studentSelector');
    const studentCardsContainer = document.getElementById('studentCardsContainer');
    const studentCount = document.getElementById('studentCount');
    
    // Update student count
    studentCount.textContent = `${studentsData.length} طالب`;
    
    // Clear existing options
    studentSelector.innerHTML = '';
    
    // Add options to dropdown
    studentsData.forEach(student => {
        const option = document.createElement('option');
        option.value = student.id;
        option.textContent = `${student.first_name} ${student.last_name}`;
        if (student.id == currentStudentId) {
            option.selected = true;
        }
        studentSelector.appendChild(option);
    });
    
    // Render mobile cards
    studentCardsContainer.innerHTML = '';
    studentsData.forEach(student => {
        const card = document.createElement('div');
        card.className = `student-card ${student.id == currentStudentId ? 'active' : ''}`;
        card.onclick = () => switchToStudent(student.id);
        
        const initials = `${student.first_name.charAt(0)}${student.last_name.charAt(0)}`.toUpperCase();
        
        card.innerHTML = `
            <div class="student-card-info">
                <div class="student-avatar">${initials}</div>
                <div class="student-details">
                    <h4>${student.first_name} ${student.last_name}</h4>
                    <p>Student ID: ${student.id}</p>
                </div>
                <div class="student-status pending">Pending</div>
            </div>
        `;
        
        studentCardsContainer.appendChild(card);
    });
}

function switchToStudent(newStudentId) {
    if (newStudentId == currentStudentId) return;
    
    // Update current student
    currentStudentId = newStudentId;
    
    // Update dropdown
    const studentSelector = document.getElementById('studentSelector');
    studentSelector.value = newStudentId;
    
    // Update mobile cards
    document.querySelectorAll('.student-card').forEach(card => {
        card.classList.remove('active');
        if (card.onclick.toString().includes(newStudentId)) {
            card.classList.add('active');
        }
    });
    
    // Update current student info
    updateCurrentStudentInfo();
    
    // Reload homework data for new student
    loadHomeworkData();
}

function switchStudent() {
    const studentSelector = document.getElementById('studentSelector');
    const selectedStudentId = parseInt(studentSelector.value);
    
    if (selectedStudentId && selectedStudentId !== currentStudentId) {
        switchToStudent(selectedStudentId);
    }
}

function updateCurrentStudentInfo() {
    const currentStudentInfo = document.getElementById('currentStudentInfo');
    const student = studentsData.find(s => s.id == currentStudentId);
    
    if (student) {
        currentStudentInfo.innerHTML = `
            <h4><i class="fas fa-user"></i> Currently Grading: ${student.first_name} ${student.last_name}</h4>
            <p>Student ID: ${student.id} • Session: ${sessionName} • Date: ${sessionDate}</p>
        `;
    }
}

// PDF Viewer Functions
function getOptimalPDFZoom() {
    const width = window.innerWidth;
    
    // Return optimal zoom percentage based on device width
    if (width >= 1400) return 90;
    if (width >= 1024) return 85;
    if (width >= 768) return 80;
    if (width >= 414) return 75;
    if (width >= 375) return 70;
    return 65;
}

function handlePDFLoad(iframe) {
    const wrapper = iframe.parentElement;
    const loadingDiv = wrapper.querySelector('.pdf-loading');
    
    // Adjust zoom based on device
    const optimalZoom = getOptimalPDFZoom();
    const currentSrc = iframe.src;
    
    // Update zoom in URL if needed
    if (currentSrc && !currentSrc.includes(`zoom=${optimalZoom}`)) {
        const baseSrc = currentSrc.split('#')[0];
        iframe.src = `${baseSrc}#view=Fit&toolbar=0&navpanes=0&scrollbar=1&page=1&zoom=${optimalZoom}`;
    }
    
    // Hide loading indicator after PDF loads
    setTimeout(() => {
        if (loadingDiv) loadingDiv.style.display = 'none';
        wrapper.classList.add('loaded');
    }, 500);
}

function handlePDFError(iframe) {
    const wrapper = iframe.parentElement;
    const loadingDiv = wrapper.querySelector('.pdf-loading');
    const errorDiv = wrapper.querySelector('.pdf-error');
    
    if (loadingDiv) loadingDiv.style.display = 'none';
    if (iframe) iframe.style.display = 'none';
    // Try PDF.js fallback
    tryInitPDFJS(wrapper);
}

// Adjust PDF zoom on window resize
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        const pdfIframes = document.querySelectorAll('.pdf-iframe');
        const optimalZoom = getOptimalPDFZoom();
        
        pdfIframes.forEach(iframe => {
            const currentSrc = iframe.src;
            if (currentSrc) {
                const baseSrc = currentSrc.split('#')[0];
                iframe.src = `${baseSrc}#view=Fit&toolbar=0&navpanes=0&scrollbar=1&page=1&zoom=${optimalZoom}`;
            }
        });
    }, 500);
});

// Detect mobile devices (iOS/Android)
function isMobileDevice() {
    const ua = navigator.userAgent || navigator.vendor || window.opera;
    return /android/i.test(ua) || /iPad|iPhone|iPod/.test(ua);
}

// Load PDF.js script once
let pdfjsLoadingPromise = null;
function loadPDFJSScripts() {
    if (pdfjsLoadingPromise) return pdfjsLoadingPromise;
    pdfjsLoadingPromise = new Promise((resolve, reject) => {
        const scriptCore = document.createElement('script');
        scriptCore.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
        scriptCore.crossOrigin = 'anonymous';
        scriptCore.onload = () => {
            const scriptWorker = document.createElement('script');
            scriptWorker.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            scriptWorker.crossOrigin = 'anonymous';
            scriptWorker.onload = () => resolve();
            scriptWorker.onerror = reject;
            document.head.appendChild(scriptWorker);
        };
        scriptCore.onerror = reject;
        document.head.appendChild(scriptCore);
    });
    return pdfjsLoadingPromise;
}

async function renderPDFWithPDFJS(container, pdfUrl) {
    try {
        await loadPDFJSScripts();
        if (!window['pdfjsLib']) throw new Error('PDF.js not available');
        const pdfjsLib = window['pdfjsLib'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        const pdf = await loadingTask.promise;

        // Clear container and show
        container.innerHTML = '';
        container.style.display = 'block';

        const scale = getPDFJSScale();

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum);
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            canvas.className = 'pdfjs-page-canvas';
            const context = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            await page.render(renderContext).promise;
            container.appendChild(canvas);
        }
    } catch (e) {
        const fallbackMsg = document.createElement('div');
        fallbackMsg.style.display = 'flex';
        fallbackMsg.style.flexDirection = 'column';
        fallbackMsg.style.alignItems = 'center';
        fallbackMsg.style.justifyContent = 'center';
        fallbackMsg.style.color = '#dc3545';
        fallbackMsg.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:48px;margin-bottom:12px;"></i><p>Unable to render PDF inline.</p>';
        container.innerHTML = '';
        container.appendChild(fallbackMsg);
        container.style.display = 'block';
    }
}

function getPDFJSScale() {
    const width = window.innerWidth;
    if (width >= 1400) return 1.25;
    if (width >= 1024) return 1.1;
    if (width >= 768) return 1.0;
    if (width >= 414) return 0.9;
    if (width >= 375) return 0.85;
    return 0.8;
}

function tryInitPDFJS(wrapper) {
    const iframe = wrapper.querySelector('.pdf-iframe');
    const pdfjsContainer = wrapper.querySelector('.pdfjs-container');
    const pdfUrl = pdfjsContainer ? pdfjsContainer.getAttribute('data-pdf-url') : null;
    if (!pdfjsContainer || !pdfUrl) return;
    if (iframe) iframe.style.display = 'none';
    renderPDFWithPDFJS(pdfjsContainer, pdfUrl);
}

function initializePDFViewers(data) {
    const isMobile = isMobileDevice();
    if (!isMobile) return;
    // For mobile, prefer PDF.js rendering immediately
    document.querySelectorAll('.pdf-viewer-wrapper').forEach(wrapper => {
        const pdfjsContainer = wrapper.querySelector('.pdfjs-container');
        const pdfUrl = pdfjsContainer ? pdfjsContainer.getAttribute('data-pdf-url') : null;
        const iframe = wrapper.querySelector('.pdf-iframe');
        if (pdfUrl && pdfjsContainer) {
            if (iframe) iframe.style.display = 'none';
            renderPDFWithPDFJS(pdfjsContainer, pdfUrl);
        }
    });
}