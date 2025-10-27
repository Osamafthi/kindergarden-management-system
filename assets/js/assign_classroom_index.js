// Classroom Assignment Functionality
// This file contains additional functionality for classroom assignment

class ClassroomAssignmentManager {
    constructor() {
        this.classrooms = [];
        this.currentTeacherId = null;
    }
    
    // Initialize classroom assignment functionality
    init() {
        console.log('Classroom Assignment Manager initialized');
    }
    
    // Load classrooms for assignment
    async loadClassroomsForAssignment() {
        try {
            const response = await fetch('../../../api/get-classrooms.php');
            const data = await response.json();
            
            if (data.success) {
                this.classrooms = data.classrooms;
                return this.classrooms;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading classrooms:', error);
            throw error;
        }
    }
    
    // Assign classroom to teacher
    async assignClassroomToTeacher(teacherId, classroomId) {
        try {
            const response = await fetch('../../../api/assign-classroom.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    classroom_id: classroomId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return {
                    success: true,
                    message: data.message,
                    assignment_id: data.assignment_id
                };
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error assigning classroom:', error);
            throw error;
        }
    }
    
    // Get teacher's assigned classrooms
    async getTeacherClassrooms(teacherId) {
        try {
            const response = await fetch(`../../../api/get-teacher-classrooms.php?teacher_id=${teacherId}`);
            const data = await response.json();
            
            if (data.success) {
                return data.assignments;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error getting teacher classrooms:', error);
            throw error;
        }
    }
    
    // Remove classroom assignment
    async removeClassroomAssignment(teacherId, classroomId) {
        try {
            const response = await fetch('../../../api/remove-classroom-assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    classroom_id: classroomId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return {
                    success: true,
                    message: data.message
                };
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error removing classroom assignment:', error);
            throw error;
        }
    }
    
    // Utility function to format classroom data for display
    formatClassroomForDisplay(classroom) {
        return {
            id: classroom.id,
            name: classroom.name,
            gradeLevel: classroom.grade_level,
            roomNumber: classroom.room_number,
            capacity: classroom.capacity,
            displayText: `${classroom.name} (Grade ${classroom.grade_level}, Room ${classroom.room_number})`
        };
    }
    
    // Utility function to validate assignment data
    validateAssignmentData(teacherId, classroomId) {
        if (!teacherId || !classroomId) {
            return {
                valid: false,
                message: 'Teacher ID and Classroom ID are required'
            };
        }
        
        if (typeof teacherId !== 'number' || typeof classroomId !== 'number') {
            return {
                valid: false,
                message: 'Teacher ID and Classroom ID must be numbers'
            };
        }
        
        return {
            valid: true,
            message: 'Data is valid'
        };
    }
}

// Initialize the classroom assignment manager
const classroomAssignmentManager = new ClassroomAssignmentManager();

// Export for use in other files if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ClassroomAssignmentManager;
}
