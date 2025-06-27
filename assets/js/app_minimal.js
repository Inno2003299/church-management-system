// Minimal Church App for Testing
console.log('Loading minimal church app...');

class ChurchApp {
    constructor() {
        console.log('ChurchApp constructor called');
        this.currentSection = 'dashboard';
        this.members = [];
        this.init();
    }
    
    init() {
        console.log('ChurchApp initializing...');
        try {
            this.setupEventListeners();
            this.updateCurrentDate();
            console.log('ChurchApp initialized successfully');
        } catch (error) {
            console.error('Error initializing ChurchApp:', error);
        }
    }
    
    setupEventListeners() {
        console.log('Setting up event listeners...');
        // Navigation
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.currentTarget.getAttribute('data-section') || e.target.getAttribute('data-section');
                if (section) {
                    this.showSection(section);
                }
            });
        });
    }
    
    updateCurrentDate() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
            dateElement.textContent = now.toLocaleDateString('en-US', options);
        }
    }
    
    showSection(sectionName) {
        console.log('Showing section:', sectionName);
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('d-none');
        });
        
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Show selected section
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.classList.remove('d-none');
        }
        
        // Update active nav link
        const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        this.currentSection = sectionName;
    }
    
    showAddMemberModal() {
        console.log('showAddMemberModal called');
        alert('Add Member Modal - This is a test!\n\nThe function is working correctly.');
    }
    
    showRecordAttendanceModal() {
        console.log('showRecordAttendanceModal called');
        alert('Record Attendance Modal - This is a test!\n\nThe function is working correctly.');
    }
    
    showRecordOfferingModal() {
        console.log('showRecordOfferingModal called');
        alert('Record Offering Modal - This is a test!\n\nThe function is working correctly.');
    }
    
    showAddInstrumentalistModal() {
        console.log('showAddInstrumentalistModal called');
        alert('Add Instrumentalist Modal - This is a test!\n\nThe function is working correctly.');
    }
    
    showMessage(message, type = 'info') {
        console.log(`Message (${type}):`, message);
        alert(`${type.toUpperCase()}: ${message}`);
    }
}

// Initialize the application
console.log('Creating ChurchApp instance...');
try {
    const app = new ChurchApp();
    console.log('App instance created successfully');
    
    // Make app globally available
    window.app = app;
    
    // Bind global functions
    window.showSection = (section) => app.showSection(section);
    window.showAddMemberModal = () => app.showAddMemberModal();
    window.showRecordAttendanceModal = () => app.showRecordAttendanceModal();
    window.showRecordOfferingModal = () => app.showRecordOfferingModal();
    window.showAddInstrumentalistModal = () => app.showAddInstrumentalistModal();
    
    console.log('Global functions bound successfully');
    
} catch (error) {
    console.error('Error creating ChurchApp:', error);
    alert('Error loading Church App: ' + error.message);
}
