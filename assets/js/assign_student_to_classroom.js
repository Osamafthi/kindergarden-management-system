// File: assets/js/assign_student_to_classroom.js
// Classroom Assignment functionality for StudentsManager

// Extend the StudentsManager class with classroom assignment methods
if (typeof StudentsManager !== 'undefined') {
    
    // Add classroom assignment methods to StudentsManager prototype
    Object.assign(StudentsManager.prototype, {
        
        // Current student being assigned
        currentStudentId: null,
        classrooms: [],
        
        // Assign student to classroom - main entry point
        assignToClassroom(studentId) {
            this.currentStudentId = studentId;
            
            // Find the student data
            const student = this.students.find(s => s.id == studentId);
            if (!student) {
                this.showAlert('Student not found', 'error');
                return;
            }
            
            // Populate student info in modal
            this.populateStudentInfo(student);
            
            // Load classrooms
            this.loadClassrooms();
            
            // Show modal
            this.showAssignClassroomModal();
        },
        
        // Populate student information in the modal
        populateStudentInfo(student) {
            const studentInfoDiv = document.getElementById('studentInfo');
            const fullName = `${student.first_name} ${student.last_name}`;
            const age = this.calculateAge(student.date_of_birth);
            
            studentInfoDiv.innerHTML = `
                <div class="student-assignment-info">
                    <div class="student-avatar-section">
                        ${student.photo ? 
                            `<img class="student-avatar-img" src="${this.normalizePhotoUrl(student.photo)}" alt="${fullName}">` : 
                            `<div class="student-avatar">${student.first_name.charAt(0).toUpperCase()}${student.last_name.charAt(0).toUpperCase()}</div>`
                        }
                    </div>
                    <div class="student-details-section">
                        <h4>${fullName}</h4>
                        <p><strong>Student ID:</strong> ${student.id}</p>
                        <p><strong>Age:</strong> ${age} years old</p>
                        <p><strong>Level:</strong> ${student.current_level_id || student.student_level_at_enrollment || 'Not specified'}</p>
                        <p><strong>Gender:</strong> ${student.gender}</p>
                        ${student.classroom_id ? `<p><strong>Current Classroom:</strong> ${student.classroom_name || 'Assigned'}</p>` : '<p><strong>Current Classroom:</strong> Not assigned</p>'}
                    </div>
                </div>
            `;
        },
        
        // Load classrooms from API
        async loadClassrooms() {
            const classroomSelect = document.getElementById('classroomSelect');
            const classroomInfo = document.getElementById('classroomInfo');
            
            // Show loading state
            classroomSelect.innerHTML = '<option value="">Loading classrooms...</option>';
            classroomInfo.style.display = 'none';
            
            try {
                const response = await fetch('../../../api/get-classrooms.php');
                const data = await response.json();
                
                if (data.success) {
                    this.classrooms = data.classrooms;
                    this.populateClassroomSelect();
                } else {
                    classroomSelect.innerHTML = '<option value="">Error loading classrooms</option>';
                    this.showAlert('Error loading classrooms: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading classrooms:', error);
                classroomSelect.innerHTML = '<option value="">Error loading classrooms</option>';
                this.showAlert('Network error loading classrooms', 'error');
            }
        },
        
        // Populate classroom select dropdown
        populateClassroomSelect() {
            const classroomSelect = document.getElementById('classroomSelect');
            
            if (this.classrooms.length === 0) {
                classroomSelect.innerHTML = '<option value="">No classrooms available</option>';
                return;
            }
            
            classroomSelect.innerHTML = '<option value="">Select a classroom...</option>';
            
            this.classrooms.forEach(classroom => {
                const option = document.createElement('option');
                option.value = classroom.id;
                option.textContent = `${classroom.name} (${classroom.grade_level}) - Room ${classroom.room_number}`;
                option.dataset.capacity = classroom.capacity;
                option.dataset.gradeLevel = classroom.grade_level;
                option.dataset.roomNumber = classroom.room_number;
                classroomSelect.appendChild(option);
            });
            
            // Add event listener for classroom selection
            classroomSelect.addEventListener('change', (e) => {
                this.handleClassroomSelection(e.target.value);
            });
        },
        
        // Handle classroom selection change
        handleClassroomSelection(classroomId) {
            const classroomInfo = document.getElementById('classroomInfo');
            
            if (!classroomId) {
                classroomInfo.style.display = 'none';
                return;
            }
            
            const classroom = this.classrooms.find(c => c.id == classroomId);
            if (!classroom) {
                classroomInfo.style.display = 'none';
                return;
            }
            
            // Show classroom information
            classroomInfo.innerHTML = `
                <div class="classroom-selection-info">
                    <h5><i class="fas fa-door-open"></i> Selected Classroom</h5>
                    <div class="classroom-details">
                        <p><strong>Name:</strong> ${classroom.name}</p>
                        <p><strong>Grade Level:</strong> ${classroom.grade_level}</p>
                        <p><strong>Room Number:</strong> ${classroom.room_number}</p>
                        <p><strong>Capacity:</strong> ${classroom.capacity} students</p>
                    </div>
                </div>
            `;
            classroomInfo.style.display = 'block';
        },
        
        // Show assign classroom modal
        showAssignClassroomModal() {
            document.getElementById('assignClassroomModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        },
        
        // Close assign classroom modal
        closeAssignClassroomModal() {
            document.getElementById('assignClassroomModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset form
            document.getElementById('classroomSelect').value = '';
            document.getElementById('classroomInfo').style.display = 'none';
            this.currentStudentId = null;
        },
        
        // Assign student to selected classroom
        async assignStudentToClassroom() {
            const classroomSelect = document.getElementById('classroomSelect');
            const assignBtn = document.getElementById('assignStudentBtn');
            
            // Validate selection
            if (!classroomSelect.value) {
                this.showAlert('Please select a classroom', 'error');
                return;
            }
            
            if (!this.currentStudentId) {
                this.showAlert('Student ID not found', 'error');
                return;
            }
            
            // Disable button and show loading
            assignBtn.disabled = true;
            assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            try {
                const response = await fetch('../../../api/assign-student-to-classroom.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: this.currentStudentId,
                        classroom_id: classroomSelect.value
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showAlert(`Student assigned to ${data.classroom_name} successfully!`, 'success');
                    this.closeAssignClassroomModal();
                    this.loadStudents(); // Refresh the students list
                } else {
                    this.showAlert('Error assigning student: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showAlert('Network error. Please try again.', 'error');
            } finally {
                // Re-enable button
                assignBtn.disabled = false;
                assignBtn.innerHTML = '<i class="fas fa-check"></i> Assign Student';
            }
        }
    });
    
    // Add event listeners for modal interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal when clicking outside
        const assignModal = document.getElementById('assignClassroomModal');
        if (assignModal) {
            assignModal.addEventListener('click', function(e) {
                if (e.target === assignModal) {
                    if (typeof studentsManager !== 'undefined') {
                        studentsManager.closeAssignClassroomModal();
                    }
                }
            });
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && assignModal && assignModal.style.display === 'block') {
                if (typeof studentsManager !== 'undefined') {
                    studentsManager.closeAssignClassroomModal();
                }
            }
        });
    });
    
} else {
    console.error('StudentsManager class not found. Make sure the main script is loaded first.');
}
