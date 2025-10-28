// Organize Attendance JavaScript

// Global variables
let currentSemester = null;
let recurringHolidays = [];
let schoolDaysData = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('تم تحميل صفحة تنظيم الحضور');
    
    // Test if elements exist
    const academicYearFilter = document.getElementById('academicYearFilter');
    const semesterSelect = document.getElementById('semesterSelect');
    
    if (!academicYearFilter || !semesterSelect) {
        console.error('لم يتم العثور على العناصر المطلوبة!');
        return;
    }
    
    // Load initial data
    loadAcademicYears();
    loadSemesters();
    
    // Add event listeners
    setupEventListeners();
    
    // Setup modal functionality
    setupModals();
});

// Setup event listeners
function setupEventListeners() {
    // Academic year filter change
    document.getElementById('academicYearFilter').addEventListener('change', function() {
        loadSemesters(this.value);
    });
    
    // Semester selection change
    document.getElementById('semesterSelect').addEventListener('change', function() {
        if (this.value) {
            selectSemester(this.value);
        } else {
            hideCalendar();
        }
    });
    
    // Weekly holiday checkboxes
    const weeklyCheckboxes = document.querySelectorAll('.weekly-holiday-selector input[type="checkbox"]');
    weeklyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRecurringHolidays();
            if (currentSemester) {
                generateCalendar();
            }
        });
    });
}

// Setup modal functionality
function setupModals() {
    // Academic Year Modal Functions
    window.openAddAcademicYearModal = function() {
        document.getElementById('addAcademicYearModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    };

    window.closeAddAcademicYearModal = function() {
        document.getElementById('addAcademicYearModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addAcademicYearForm').reset();
        setAcademicYearLoadingState(false);
    };

    // Semester Modal Functions
    window.openAddSemesterModal = function() {
        document.getElementById('addSemesterModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        loadAcademicYearsForModal();
    };

    window.closeAddSemesterModal = function() {
        document.getElementById('addSemesterModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addSemesterForm').reset();
        setSemesterLoadingState(false);
    };

    // Close modals when clicking outside
    window.onclick = function(event) {
        const addAcademicYearModal = document.getElementById('addAcademicYearModal');
        const addSemesterModal = document.getElementById('addSemesterModal');
        
        if (event.target === addAcademicYearModal) {
            closeAddAcademicYearModal();
        }
        if (event.target === addSemesterModal) {
            closeAddSemesterModal();
        }
    };

    // Form submissions
    document.getElementById('addAcademicYearForm').addEventListener('submit', handleAcademicYearSubmit);
    document.getElementById('addSemesterForm').addEventListener('submit', handleSemesterSubmit);
}

// Load academic years
async function loadAcademicYears() {
    try {
        showLoading(true);
        const response = await fetch('../../../api/get-academic-years.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('academicYearFilter');
            select.innerHTML = '<option value="">جميع السنوات الدراسية</option>';
            
            if (data.academic_years && data.academic_years.length > 0) {
                data.academic_years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year.id;
                    option.textContent = year.year_name;
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'لا توجد سنوات دراسية متاحة';
                select.appendChild(option);
            }
        } else {
            console.error('خطأ في واجهة برمجة التطبيقات:', data.message);
            showAlert('فشل في تحميل السنوات الدراسية: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('خطأ في تحميل السنوات الدراسية:', error);
        showAlert('خطأ في الشبكة عند تحميل السنوات الدراسية: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Load academic years for modal
async function loadAcademicYearsForModal() {
    try {
        const response = await fetch('../../../api/get-academic-years.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('academicYearSelect');
            select.innerHTML = '<option value="">اختر سنة دراسية...</option>';
            
            data.academic_years.forEach(year => {
                const option = document.createElement('option');
                option.value = year.id;
                option.textContent = year.year_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('خطأ في تحميل السنوات الدراسية للنافذة المنبثقة:', error);
    }
}

// Load semesters
async function loadSemesters(academicYearId = null) {
    try {
        showLoading(true);
        let url = '../../../api/get-semesters.php';
        if (academicYearId) {
            url += `?academic_year_id=${academicYearId}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('خطأ في تحليل JSON:', parseError);
            console.error('نص الاستجابة الذي فشل في التحليل:', responseText);
            throw new Error('استجابة JSON غير صالحة من الخادم');
        }
        
        if (data.success) {
            const select = document.getElementById('semesterSelect');
            select.innerHTML = '<option value="">اختر فصلاً دراسياً...</option>';
            
            if (data.semesters && data.semesters.length > 0) {
                data.semesters.forEach(semester => {
                    const option = document.createElement('option');
                    option.value = semester.id;
                    option.textContent = `${semester.term_name} (${semester.year_name})`;
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'لا توجد فصول دراسية متاحة';
                select.appendChild(option);
            }
        } else {
            console.error('خطأ في واجهة برمجة التطبيقات:', data.message);
            showAlert('فشل في تحميل الفصول الدراسية: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('خطأ في تحميل الفصول الدراسية:', error);
        showAlert('خطأ في الشبكة عند تحميل الفصول الدراسية: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Select semester and show calendar
async function selectSemester(semesterId) {
    try {
        showLoading(true);
        
        // Get semester data from API
        const response = await fetch(`../../../api/get-semesters.php`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success && data.semesters) {
            // Find the selected semester
            const selectedSemester = data.semesters.find(semester => semester.id == semesterId);
            
            if (selectedSemester) {
                currentSemester = {
                    id: selectedSemester.id,
                    term_name: selectedSemester.term_name,
                    start_date: selectedSemester.start_date,
                    end_date: selectedSemester.end_date,
                    academic_year_id: selectedSemester.academic_year_id,
                    year_name: selectedSemester.year_name
                };
                
                console.log('الفصل الدراسي المحدد:', currentSemester);
                
                // Load existing school days for this semester
                await loadExistingSchoolDays(semesterId);
                
                // Show weekly holiday selector and calendar
                document.getElementById('weeklyHolidayCard').style.display = 'block';
                document.getElementById('calendarCard').style.display = 'block';
                document.getElementById('emptyState').style.display = 'none';
                
                // Generate calendar
                generateCalendar();
            } else {
                throw new Error('لم يتم العثور على الفصل الدراسي');
            }
        } else {
            throw new Error('فشل في تحميل بيانات الفصل الدراسي');
        }
        
    } catch (error) {
        console.error('خطأ في اختيار الفصل الدراسي:', error);
        showAlert('خطأ في تحميل بيانات الفصل الدراسي: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Load existing school days
async function loadExistingSchoolDays(termId) {
    try {
        const response = await fetch(`../../../api/get-school-days.php?term_id=${termId}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            schoolDaysData = data.school_days || [];
        } else {
            schoolDaysData = [];
        }
    } catch (error) {
        console.error('خطأ في تحميل أيام الدراسة الحالية:', error);
        schoolDaysData = [];
    }
}

// Update recurring holidays array
function updateRecurringHolidays() {
    recurringHolidays = [];
    const checkboxes = document.querySelectorAll('.weekly-holiday-selector input[type="checkbox"]:checked');
    checkboxes.forEach(checkbox => {
        recurringHolidays.push(parseInt(checkbox.value));
    });
}

// Generate calendar
function generateCalendar() {
    if (!currentSemester) {
        console.error('لم يتم اختيار فصل دراسي');
        return;
    }
    
    console.log('إنشاء التقويم للفصل الدراسي:', currentSemester);
    
    // Use actual semester dates from the currentSemester object
    const startDate = new Date(currentSemester.start_date);
    const endDate = new Date(currentSemester.end_date);
    
    console.log('نطاق تاريخ التقويم:', startDate, 'إلى', endDate);
    
    const container = document.getElementById('calendarContainer');
    container.innerHTML = '';
    
    // Generate months
    const currentDate = new Date(startDate);
    while (currentDate <= endDate) {
        const monthCalendar = generateMonthCalendar(currentDate, startDate, endDate);
        container.appendChild(monthCalendar);
        
        // Move to next month
        currentDate.setMonth(currentDate.getMonth() + 1);
        currentDate.setDate(1);
    }
    
    console.log('تم إنشاء التقويم بنجاح');
    
    // Debug: Check what was actually created
    const allElements = document.querySelectorAll('.calendar-day');
    const emptyElements = document.querySelectorAll('.calendar-day.empty');
    const nonEmptyElements = document.querySelectorAll('.calendar-day:not(.empty)');
    
        console.log(`تم إنشاء التقويم: ${nonEmptyElements.length} يوم دراسي`);
}

// Generate calendar for a single month
function generateMonthCalendar(date, semesterStart, semesterEnd) {
    const monthDiv = document.createElement('div');
    monthDiv.className = 'calendar-month';
    
    // Month header
    const monthHeader = document.createElement('div');
    monthHeader.className = 'calendar-month-header';
    monthHeader.textContent = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    monthDiv.appendChild(monthHeader);
    
    // Calendar grid
    const grid = document.createElement('div');
    grid.className = 'calendar-grid';
    
    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const headerCell = document.createElement('div');
        headerCell.className = 'calendar-header-cell';
        headerCell.textContent = day;
        grid.appendChild(headerCell);
    });
    
    // Get first day of month and number of days
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDay = firstDay.getDay();
    
    // Add empty cells for days before month starts
    for (let i = 0; i < startDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        grid.appendChild(emptyDay);
    }
    
    // Add days of the month
    let validDaysInMonth = 0;
    let emptyDaysInMonth = 0;
    
    for (let day = 1; day <= daysInMonth; day++) {
        const dayDate = new Date(date.getFullYear(), date.getMonth(), day);
        
        // Check if this day is within the semester range
        // Normalize dates to avoid timezone issues
        const dayDateNormalized = new Date(dayDate.getFullYear(), dayDate.getMonth(), dayDate.getDate());
        const semesterStartNormalized = new Date(semesterStart.getFullYear(), semesterStart.getMonth(), semesterStart.getDate());
        const semesterEndNormalized = new Date(semesterEnd.getFullYear(), semesterEnd.getMonth(), semesterEnd.getDate());
        
        if (dayDateNormalized < semesterStartNormalized || dayDateNormalized > semesterEndNormalized) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day empty';
            grid.appendChild(emptyDay);
            emptyDaysInMonth++;
            continue;
        }
        
        validDaysInMonth++;
        
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        
        const dayOfWeek = dayDate.getDay();
        
        // Check if this is a recurring holiday
        const isRecurringHoliday = recurringHolidays.includes(dayOfWeek);
        
        // Check if this day has existing data
        const existingDay = schoolDaysData.find(sd => {
            const sdDate = new Date(sd.date);
            return sdDate.getFullYear() === dayDate.getFullYear() &&
                   sdDate.getMonth() === dayDate.getMonth() &&
                   sdDate.getDate() === dayDate.getDate();
        });
        
        // Determine initial state
        let isHoliday = false;
        if (isRecurringHoliday) {
            isHoliday = true;
            dayDiv.classList.add('recurring-holiday');
        } else if (existingDay) {
            isHoliday = existingDay.is_school_day === 0;
            dayDiv.classList.add(isHoliday ? 'holiday-day' : 'school-day');
        } else {
            // Default: school day (unchecked)
            dayDiv.classList.add('school-day');
        }
        
        // Day number
        const dayNumber = document.createElement('div');
        dayNumber.className = 'calendar-day-number';
        dayNumber.textContent = day;
        dayDiv.appendChild(dayNumber);
        
        // Checkbox
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'calendar-day-checkbox';
        checkbox.checked = isHoliday;
        checkbox.disabled = isRecurringHoliday;
        
        // Add change event listener
        checkbox.addEventListener('change', function() {
            if (isRecurringHoliday) return; // Don't allow changes to recurring holidays
            
            if (this.checked) {
                dayDiv.classList.remove('school-day');
                dayDiv.classList.add('holiday-day');
            } else {
                dayDiv.classList.remove('holiday-day');
                dayDiv.classList.add('school-day');
            }
        });
        
        dayDiv.appendChild(checkbox);
        grid.appendChild(dayDiv);
    }
    
    monthDiv.appendChild(grid);
    
    
    return monthDiv;
}

// Hide calendar
function hideCalendar() {
    document.getElementById('weeklyHolidayCard').style.display = 'none';
    document.getElementById('calendarCard').style.display = 'none';
    document.getElementById('emptyState').style.display = 'block';
    currentSemester = null;
}

// Save school days
async function saveSchoolDays() {
    if (!currentSemester) {
        showAlert('يرجى اختيار فصل دراسي أولاً', 'error');
        return;
    }
    
    try {
        setSaveLoadingState(true);
        
        // Collect all school day data
        const schoolDays = [];
        const dayElements = document.querySelectorAll('.calendar-day:not(.empty)');
        
        console.log('تم العثور على', dayElements.length, 'عناصر أيام التقويم');
        
        // Debug: Check all calendar elements
        const allCalendarElements = document.querySelectorAll('.calendar-day');
        console.log('إجمالي عناصر التقويم:', allCalendarElements.length);
        
        const emptyElements = document.querySelectorAll('.calendar-day.empty');
        console.log('عناصر التقويم الفارغة:', emptyElements.length);
        
        const nonEmptyElements = document.querySelectorAll('.calendar-day:not(.empty)');
        console.log('عناصر التقويم غير الفارغة:', nonEmptyElements.length);
        
        dayElements.forEach((dayElement, index) => {
            console.log(`معالجة العنصر ${index + 1}/${dayElements.length}`);
            
            const checkbox = dayElement.querySelector('.calendar-day-checkbox');
            const dayNumber = dayElement.querySelector('.calendar-day-number').textContent;
            
            if (!checkbox || !dayNumber) {
                console.warn('مربع الاختيار أو رقم اليوم مفقود للعنصر:', dayElement);
                return;
            }
            
            // Find the month for this day
            const monthElement = dayElement.closest('.calendar-month');
            const monthHeader = monthElement.querySelector('.calendar-month-header').textContent;
            
            
            // Parse date properly
            // monthHeader format: "September 2024" or "October 2024"
            // Extract month and year from monthHeader
            const monthYearMatch = monthHeader.match(/(\w+)\s+(\d{4})/);
            if (!monthYearMatch) {
                console.warn('فشل في تحليل رأس الشهر:', monthHeader);
                return;
            }
            
            const monthName = monthYearMatch[1];
            const year = parseInt(monthYearMatch[2]);
            
            // Convert month name to number (0-based)
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const monthIndex = monthNames.indexOf(monthName);
            
            if (monthIndex === -1) {
                console.warn('اسم شهر غير صالح:', monthName);
                return;
            }
            
            // Create date object properly
            const date = new Date(year, monthIndex, parseInt(dayNumber));
            
            if (!isNaN(date.getTime())) {
                const dateFormatted = date.toISOString().split('T')[0]; // YYYY-MM-DD format
                const isSchoolDay = checkbox.checked ? 0 : 1; // Inverted logic: checked = holiday (0), unchecked = school day (1)
                
                schoolDays.push({
                    date: dateFormatted,
                    is_school_day: isSchoolDay,
                    note: ''
                });
                
            } else {
                console.warn('فشل في إنشاء التاريخ لـ:', monthName, year, dayNumber);
            }
        });
        
        console.log(`تم جمع ${schoolDays.length} يوم دراسي`);
        
        // Validate that we have data
        if (schoolDays.length === 0) {
            throw new Error('لم يتم العثور على أيام في التقويم. يرجى اختيار فصل دراسي وإنشاء التقويم أولاً.');
        }
        
        // Prepare data for API
        const data = {
            term_id: parseInt(currentSemester.id),
            academic_year_id: parseInt(currentSemester.academic_year_id),
            school_days: schoolDays,
            recurring_weekly_holidays: recurringHolidays
        };
        
        
        const response = await fetch('../../../api/set-school-days.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('استجابة خطأ HTTP:', errorText);
            throw new Error(`خطأ HTTP! الحالة: ${response.status}`);
        }
        
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('خطأ في تحليل JSON:', parseError);
            console.error('نص الاستجابة الذي فشل في التحليل:', responseText);
            throw new Error('استجابة JSON غير صالحة من الخادم');
        }
        
        if (result.success) {
            showAlert('تم حفظ أيام الدراسة بنجاح!', 'success');
            // Reload existing school days
            await loadExistingSchoolDays(currentSemester.id);
        } else {
            throw new Error(result.message || 'فشل في حفظ أيام الدراسة');
        }
        
    } catch (error) {
        console.error('خطأ في حفظ أيام الدراسة:', error);
        showAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
    } finally {
        setSaveLoadingState(false);
    }
}

// Show/hide loading state
function showLoading(show) {
    const loadingContainer = document.getElementById('loadingContainer');
    loadingContainer.style.display = show ? 'block' : 'none';
}

// Set save button loading state
function setSaveLoadingState(isLoading) {
    const saveBtn = document.getElementById('saveSchoolDaysBtn');
    const saveText = document.getElementById('saveText');
    const loading = document.getElementById('saveLoading');
    
    saveBtn.disabled = isLoading;
    
    if (isLoading) {
        saveText.textContent = 'جاري الحفظ...';
        loading.style.display = 'inline-block';
    } else {
        saveText.textContent = 'حفظ أيام الدراسة';
        loading.style.display = 'none';
    }
}

// Show alert message
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    alertDiv.innerHTML = `<i class="${icon}"></i> ${message}`;
    
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Academic Year Modal Functions
async function handleAcademicYearSubmit(e) {
    e.preventDefault();
    
    const formData = {
        year_name: document.getElementById('yearName').value.trim(),
        start_date: document.getElementById('academicStartDate').value,
        end_date: document.getElementById('academicEndDate').value,
        is_current: document.getElementById('academicIsCurrent').checked
    };

    if (!validateAcademicYearForm(formData)) {
        return;
    }

    setAcademicYearLoadingState(true);

    try {
        const response = await fetch('../../../api/add-academic-year.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showAlert('تمت إضافة السنة الدراسية بنجاح!', 'success');
            document.getElementById('addAcademicYearForm').reset();
            setTimeout(() => {
                closeAddAcademicYearModal();
                loadAcademicYears(); // Refresh the list
            }, 1500);
        } else {
            throw new Error(result.message || 'فشل في إضافة السنة الدراسية');
        }

    } catch (error) {
        console.error('خطأ:', error);
        showAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
    } finally {
        setAcademicYearLoadingState(false);
    }
}

// Semester Modal Functions
async function handleSemesterSubmit(e) {
    e.preventDefault();
    
    const formData = {
        academic_year_id: parseInt(document.getElementById('academicYearSelect').value),
        term_name: document.getElementById('termName').value.trim(),
        start_date: document.getElementById('semesterStartDate').value,
        end_date: document.getElementById('semesterEndDate').value,
        is_current: document.getElementById('semesterIsCurrent').checked
    };

    if (!validateSemesterForm(formData)) {
        return;
    }

    setSemesterLoadingState(true);

    try {
        const response = await fetch('../../../api/add-semester.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showAlert('تمت إضافة الفصل الدراسي بنجاح!', 'success');
            document.getElementById('addSemesterForm').reset();
            setTimeout(() => {
                closeAddSemesterModal();
                loadSemesters(); // Refresh the list
            }, 1500);
        } else {
            throw new Error(result.message || 'فشل في إضافة الفصل الدراسي');
        }

    } catch (error) {
        console.error('خطأ:', error);
        showAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
    } finally {
        setSemesterLoadingState(false);
    }
}

// Validation functions
function validateAcademicYearForm(data) {
    if (!data.year_name || data.year_name.length < 2) {
        showAlert('يجب أن يكون اسم السنة الدراسية على الأقل حرفين.', 'error');
        return false;
    }

    if (!data.start_date) {
        showAlert('تاريخ البداية مطلوب.', 'error');
        return false;
    }

    if (!data.end_date) {
        showAlert('تاريخ النهاية مطلوب.', 'error');
        return false;
    }

    if (new Date(data.end_date) <= new Date(data.start_date)) {
        showAlert('يجب أن يكون تاريخ النهاية بعد تاريخ البداية.', 'error');
        return false;
    }

    return true;
}

function validateSemesterForm(data) {
    if (!data.academic_year_id) {
        showAlert('يرجى اختيار سنة دراسية.', 'error');
        return false;
    }

    if (!data.term_name || data.term_name.length < 2) {
        showAlert('يجب أن يكون اسم الفصل الدراسي على الأقل حرفين.', 'error');
        return false;
    }

    if (!data.start_date) {
        showAlert('تاريخ البداية مطلوب.', 'error');
        return false;
    }

    if (!data.end_date) {
        showAlert('تاريخ النهاية مطلوب.', 'error');
        return false;
    }

    if (new Date(data.end_date) <= new Date(data.start_date)) {
        showAlert('يجب أن يكون تاريخ النهاية بعد تاريخ البداية.', 'error');
        return false;
    }

    return true;
}

// Set loading states for modals
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

function setSemesterLoadingState(isLoading) {
    const submitBtn = document.getElementById('submitSemesterBtn');
    const submitText = document.getElementById('semesterSubmitText');
    const loading = document.getElementById('semesterLoading');
    
    submitBtn.disabled = isLoading;
    
    if (isLoading) {
        submitText.textContent = 'جاري الإضافة...';
        loading.style.display = 'inline-block';
    } else {
        submitText.textContent = 'إضافة الفصل الدراسي';
        loading.style.display = 'none';
    }
}
