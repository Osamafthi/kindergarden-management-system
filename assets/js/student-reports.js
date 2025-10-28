/* Student Reports JavaScript */
/* File: assets/js/student-reports.js */

class ReportManager {
    constructor() {
        this.studentId = null;
        this.currentPeriod = 'monthly';
        this.reportData = null;
        this.isLoading = false;
        
        this.initializePage();
    }
    
    initializePage() {
        // Get student ID from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        this.studentId = urlParams.get('student_id');
        
        if (!this.studentId) {
            this.showAlert('معرف الطالب مطلوب', 'error');
            return;
        }
        
        // Load initial report data
        this.loadReportData();
    }
    
    async loadReportData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch(`../../../api/get-student-report.php?student_id=${this.studentId}&period=${this.currentPeriod}`);
            const data = await response.json();
            
            if (data.success) {
                this.reportData = data;
                this.renderReport();
            } else {
                this.showAlert('خطأ في تحميل التقرير: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('خطأ:', error);
            this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    renderReport() {
        if (!this.reportData) return;
        
        // Render student info
        this.renderStudentInfo();
        
        // Render period info
        this.renderPeriodInfo();
        
        // Render overall performance
        this.renderOverallPerformance();
        
        // Render attendance
        this.renderAttendance();
        
        // Render recitation
        this.renderRecitation();
        
        // Render recitation per type
        this.renderRecitationPerType();
        
        // Render modules
        this.renderModules();
    }
    
    renderStudentInfo() {
        const student = this.reportData.student;
        
        // Student name and level
        document.getElementById('studentName').textContent = student.full_name;
        document.getElementById('studentLevel').textContent = `المستوى: ${student.student_level_at_enrollment || 'غير متاح'}`;
        
        // Student photo
        const photoContainer = document.getElementById('studentPhoto');
        if (student.photo) {
            const photoUrl = this.normalizePhotoUrl(student.photo);
            photoContainer.innerHTML = `<img src="${photoUrl}" alt="${student.full_name}" class="student-photo-img">`;
        } else {
            photoContainer.innerHTML = `
                <div class="photo-placeholder">
                    <span>${student.first_name.charAt(0)}${student.last_name.charAt(0)}</span>
                </div>
            `;
        }
    }
    
    renderPeriodInfo() {
        const periodNames = {
            'monthly': 'الشهر الحالي',
            'weekly': 'آخر 7 أيام'
        };
        
        document.getElementById('currentPeriod').textContent = periodNames[this.currentPeriod];
        
        const dateRange = this.reportData.date_range;
        const startDate = new Date(dateRange.start).toLocaleDateString();
        const endDate = new Date(dateRange.end).toLocaleDateString();
        document.getElementById('dateRange').textContent = `${startDate} - ${endDate}`;
        
        // Update print version
        const printPeriodEl = document.getElementById('printCurrentPeriod');
        const printDateRangeEl = document.getElementById('printDateRange');
        if (printPeriodEl) printPeriodEl.textContent = periodNames[this.currentPeriod];
        if (printDateRangeEl) printDateRangeEl.textContent = `${startDate} - ${endDate}`;
    }
    
    renderOverallPerformance() {
        const overall = this.reportData.overall_performance;
        const card = document.getElementById('overallPerformanceCard');
        
        if (overall.total_submissions > 0) {
            document.getElementById('overallGrade').textContent = overall.overall_avg_grade;
            document.getElementById('overallLevel').textContent = overall.overall_performance_level;
            document.getElementById('overallDetails').textContent = `${overall.total_submissions} تسليم`;
            
            // Add performance level class
            card.className = `overview-card overall-performance level-${overall.overall_performance_level.toLowerCase()}`;
        } else {
            document.getElementById('overallGrade').textContent = '--';
            document.getElementById('overallLevel').textContent = 'غير متاح';
            document.getElementById('overallDetails').textContent = 'لا توجد بيانات متاحة';
            card.className = 'overview-card overall-performance no-data';
        }
    }
    
    renderAttendance() {
        const attendance = this.reportData.attendance_details;
        const card = document.getElementById('attendancePerformanceCard');
        
        if (attendance.total_days > 0) {
            document.getElementById('attendancePercentage').textContent = `${attendance.attendance_percentage}%`;
            document.getElementById('attendanceDetails').textContent = `${attendance.present_days}/${attendance.total_days} يوم حضور`;
            
            // Add attendance level class
            const percentage = attendance.attendance_percentage;
            let level = 'poor';
            if (percentage >= 90) level = 'excellent';
            else if (percentage >= 80) level = 'good';
            else if (percentage >= 70) level = 'fair';
            
            card.className = `overview-card attendance-performance level-${level}`;
        } else {
            document.getElementById('attendancePercentage').textContent = '--%';
            document.getElementById('attendanceDetails').textContent = 'لا توجد سجلات حضور';
            card.className = 'overview-card attendance-performance no-data';
        }
    }
    
    renderRecitation() {
        if (!this.reportData) return;
        
        const recitation = this.reportData.recitation_quantity;
        const card = document.getElementById('recitationPerformanceCard');
        
        if (!card) {
            console.error('لم يتم العثور على عنصر بطاقة التلاوة');
            return;
        }
        
        if (!recitation) {
            const pagesEl = document.getElementById('recitationPages');
            const detailsEl = document.getElementById('recitationDetails');
            if (pagesEl) pagesEl.textContent = '--';
            if (detailsEl) detailsEl.textContent = 'لا توجد بيانات متاحة';
            card.className = 'overview-card recitation-performance no-data';
            return;
        }
        
        if (recitation.total_homework_items > 0) {
            const pages = parseFloat(recitation.equivalent_pages) || 0;
            const pagesEl = document.getElementById('recitationPages');
            const detailsEl = document.getElementById('recitationDetails');
            
            if (pagesEl) pagesEl.textContent = `${pages.toFixed(2)} صفحة`;
            if (detailsEl) detailsEl.textContent = `${recitation.total_verses || 0} آية، ${recitation.total_words || 0} كلمة`;
            
            // Add performance level class based on pages
            let level = 'poor';
            if (pages >= 10) level = 'excellent';
            else if (pages >= 5) level = 'good';
            else if (pages >= 2) level = 'fair';
            
            card.className = `overview-card recitation-performance level-${level}`;
        } else {
            const pagesEl = document.getElementById('recitationPages');
            const detailsEl = document.getElementById('recitationDetails');
            if (pagesEl) pagesEl.textContent = '0 صفحة';
            if (detailsEl) detailsEl.textContent = 'لم يتم تسجيل واجب قرآني';
            card.className = 'overview-card recitation-performance no-data';
        }
    }
    
    renderRecitationPerType() {
        if (!this.reportData) return;
        
        const recitationTypes = this.reportData.recitation_quantity_per_type || [];
        const grid = document.getElementById('recitationTypesGrid');
        const emptyState = document.getElementById('recitationTypesEmptyState');
        const loading = document.getElementById('recitationTypesLoading');
        
        if (!grid || !emptyState || !loading) {
            console.error('لم يتم العثور على عناصر التلاوة حسب النوع');
            return;
        }
        
        // Hide loading
        loading.style.display = 'none';
        
        if (!recitationTypes || recitationTypes.length === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        emptyState.style.display = 'none';
        grid.style.display = 'grid';
        
        // Create type cards
        grid.innerHTML = recitationTypes.map(type => {
            const pages = parseFloat(type.equivalent_pages) || 0;
            
            // Determine performance level
            let level = 'poor';
            if (pages >= 10) level = 'excellent';
            else if (pages >= 5) level = 'good';
            else if (pages >= 2) level = 'fair';
            
            return `
                <div class="recitation-type-card ${level} fade-in">
                    <div class="type-header">
                        <h4><i class="fas fa-book-quran"></i> ${this.escapeHtml(type.homework_type_name)}</h4>
                    </div>
                    <div class="type-content">
                        <div class="type-pages">
                            <span class="pages-value">${pages.toFixed(2)}</span>
                            <span class="pages-label">صفحة</span>
                        </div>
                        <div class="type-details">
                            <span><i class="fas fa-list"></i> ${type.total_verses || 0} آية</span>
                            <span><i class="fas fa-font"></i> ${type.total_words || 0} كلمة</span>
                            <span><i class="fas fa-tasks"></i> ${type.total_homework_items || 0} واجب</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Utility method to escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    renderModules() {
        const modules = this.reportData.module_performance;
        const activeModules = this.reportData.active_modules;
        const grid = document.getElementById('modulesGrid');
        const emptyState = document.getElementById('modulesEmptyState');
        
        if (modules.length === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = 'لم يتم العثور على درجات واجبات لهذه الفترة.';
            return;
        }
        
        emptyState.style.display = 'none';
        grid.style.display = 'grid';
        
        // Create module cards
        grid.innerHTML = modules.map(module => {
            const performanceClass = `level-${module.performance_level.toLowerCase()}`;
            
            return `
                <div class="module-card ${performanceClass} fade-in">
                    <div class="module-header">
                        <h4>${module.module_name}</h4>
                        <span class="module-level level-${module.performance_level.toLowerCase()}">${module.performance_level}</span>
                    </div>
                    <div class="module-content">
                        <div class="module-grade">${module.avg_grade}</div>
                        <div class="module-details">
                            <span>متوسط الدرجة</span>
                            <span>${module.total_submissions} تسليم</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    switchPeriod(period) {
        if (this.currentPeriod === period || this.isLoading) return;
        
        this.currentPeriod = period;
        
        // Update toggle buttons
        document.getElementById('monthlyBtn').classList.toggle('active', period === 'monthly');
        document.getElementById('weeklyBtn').classList.toggle('active', period === 'weekly');
        
        // Reload data
        this.loadReportData();
    }
    
    showLoading() {
        document.getElementById('modulesLoading').style.display = 'block';
        document.getElementById('modulesGrid').style.display = 'none';
        document.getElementById('modulesEmptyState').style.display = 'none';
    }
    
    hideLoading() {
        document.getElementById('modulesLoading').style.display = 'none';
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
    
    // Utility method to format dates
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Utility method to calculate time ago
    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'اليوم';
        if (diffDays === 1) return 'أمس';
        if (diffDays < 7) return `منذ ${diffDays} أيام`;
        if (diffDays < 30) return `منذ ${Math.floor(diffDays / 7)} أسابيع`;
        if (diffDays < 365) return `منذ ${Math.floor(diffDays / 30)} أشهر`;
        return `منذ ${Math.floor(diffDays / 365)} سنوات`;
    }
    
    // Method to refresh data
    refresh() {
        this.loadReportData();
    }
    
    // Method to export report (future enhancement)
    exportReport() {
        // This could be implemented to export the report as PDF or CSV
        console.log('وظيفة التصدير لم يتم تطبيقها بعد');
    }
}

// Initialize the report manager when the page loads
let reportManager;
document.addEventListener('DOMContentLoaded', function() {
    reportManager = new ReportManager();
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + R to refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            reportManager.refresh();
        }
        
        // Ctrl/Cmd + M for monthly view
        if ((e.ctrlKey || e.metaKey) && e.key === 'm') {
            e.preventDefault();
            reportManager.switchPeriod('monthly');
        }
        
        // Ctrl/Cmd + W for weekly view
        if ((e.ctrlKey || e.metaKey) && e.key === 'w') {
            e.preventDefault();
            reportManager.switchPeriod('weekly');
        }
    });
});

// Add some utility functions for debugging
window.reportDebug = {
    getReportData: () => reportManager?.reportData,
    getCurrentPeriod: () => reportManager?.currentPeriod,
    getStudentId: () => reportManager?.studentId,
    refresh: () => reportManager?.refresh(),
    switchToMonthly: () => reportManager?.switchPeriod('monthly'),
    switchToWeekly: () => reportManager?.switchPeriod('weekly')
};
