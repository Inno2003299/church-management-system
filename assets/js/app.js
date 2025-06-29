// Church Inventory Management System - Main Application
class ChurchApp {
    constructor() {
        this.currentSection = 'dashboard';
        this.members = [];
        this.services = [];
        this.offerings = [];
        this.instrumentalists = [];
        
        this.init();
    }
    
    async init() {
        console.log('ChurchApp initializing...');

        // Check authentication first
        await this.checkAuthentication();

        this.setupEventListeners();
        this.updateCurrentDate();
        this.loadDashboardData();
        this.showSection('dashboard');
        console.log('ChurchApp initialized successfully');
    }

    async checkAuthentication() {
        try {
            const response = await fetch('api/auth.php?action=check');
            const result = await response.json();

            if (result.authenticated) {
                // Update admin info in UI
                this.updateAdminInfo(result.admin);
                console.log('Admin authenticated:', result.admin.name);
            } else {
                // Redirect to login
                console.log('Not authenticated, redirecting to login');
                window.location.href = 'login.php';
                return;
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            // On error, redirect to login for security
            window.location.href = 'login.php';
        }
    }

    updateAdminInfo(admin) {
        const adminNameElement = document.getElementById('admin-name');
        const adminEmailElement = document.getElementById('admin-email');

        if (adminNameElement) {
            adminNameElement.textContent = admin.name;
        }
        if (adminEmailElement) {
            adminEmailElement.textContent = admin.email;
        }

        // Store admin info for later use
        this.currentAdmin = admin;
    }
    
    setupEventListeners() {
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

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleFormSubmission(e.target);
            }
        });

        // Dashboard quick actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('[onclick]')) {
                // Let onclick handlers work
                return;
            }
        });
    }

    async handleFormSubmission(form) {
        // This function handles generic form submissions
        // For now, we'll just log it since specific forms have their own handlers
        console.log('Form submission handled:', form.id);
    }

    updateCurrentDate() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
    }
    
    async loadDashboardData() {
        try {
            this.showLoading(true);

            // Load stats
            await Promise.all([
                this.loadMembers(),
                this.loadTodayAttendance(),
                this.loadTodayOfferings(),
                this.loadInstrumentalists()
            ]);

            this.updateDashboardStats();
            this.loadRecentActivity();

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showMessage('Error loading dashboard data', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadMembers() {
        try {
            const response = await fetch('api/get_members.php');
            const data = await response.json();
            this.members = data;
            return data;
        } catch (error) {
            console.error('Error loading members:', error);
            return [];
        }
    }
    
    async loadTodayAttendance() {
        const today = new Date().toISOString().split('T')[0];
        console.log(`Loading attendance for date: ${today}`);
        try {
            const response = await fetch(`api/attendance.php?date=${today}`);
            console.log('Attendance API response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Attendance API data:', data);

            this.todayAttendance = data;
            console.log(`Loaded ${data.length} attendance records for today (${today})`);
            return data;
        } catch (error) {
            console.error('Error loading attendance:', error);
            this.todayAttendance = [];
            return [];
        }
    }
    
    async loadTodayOfferings() {
        const today = new Date().toISOString().split('T')[0];
        try {
            const response = await fetch(`api/get_offerings.php?date=${today}`);
            const data = await response.json();
            if (data.success) {
                this.todayOfferings = data.offerings;
                this.todayOfferingsTotal = data.totals.total;
                console.log(`Loaded ${data.offerings.length} offerings for today, total: $${data.totals.total}`);
                return data;
            } else {
                throw new Error(data.error || 'Failed to load offerings');
            }
        } catch (error) {
            console.error('Error loading offerings:', error);
            this.todayOfferings = [];
            this.todayOfferingsTotal = 0;
            return { offerings: [], totals: { total: 0 } };
        }
    }
    
    async loadInstrumentalists() {
        try {
            console.log('Fetching instrumentalists from API...');
            const response = await fetch('api/instrumentalists.php');
            console.log('API response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Raw API data:', data);

            this.instrumentalists = data;
            console.log('Instrumentalists stored in app:', this.instrumentalists);

            return data;
        } catch (error) {
            console.error('Error loading instrumentalists:', error);
            this.instrumentalists = [];
            return [];
        }
    }
    
    updateDashboardStats() {
        // Update member count
        const memberElement = document.getElementById('total-members');
        if (memberElement && this.members) {
            memberElement.textContent = this.members.length;
        }

        // Update instrumentalist count
        const instrumentalistElement = document.getElementById('active-instrumentalists');
        if (instrumentalistElement && this.instrumentalists) {
            instrumentalistElement.textContent = this.instrumentalists.length;
        }

        // Update today's attendance count
        const attendanceCount = this.todayAttendance ? this.todayAttendance.length : 0;
        const attendanceElement = document.getElementById('today-attendance');
        if (attendanceElement) {
            attendanceElement.textContent = attendanceCount;
        }

        // Update today's offerings total
        const offeringsTotal = this.todayOfferingsTotal || 0;
        const offeringsElement = document.getElementById('today-offerings');
        if (offeringsElement) {
            offeringsElement.textContent = `$${offeringsTotal.toFixed(2)}`;
        }
    }
    
    async loadRecentActivity() {
        const activityContainer = document.getElementById('recent-activity');
        activityContainer.innerHTML = `
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-person-plus text-primary me-3"></i>
                    <div>
                        <div class="fw-bold">New member registered</div>
                        <small class="text-muted">2 hours ago</small>
                    </div>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-calendar-check text-success me-3"></i>
                    <div>
                        <div class="fw-bold">Attendance recorded</div>
                        <small class="text-muted">3 hours ago</small>
                    </div>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-cash-coin text-warning me-3"></i>
                    <div>
                        <div class="fw-bold">Offering collected</div>
                        <small class="text-muted">5 hours ago</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    showSection(sectionName) {
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
            targetSection.classList.add('fade-in');
        }
        
        // Update active nav link
        const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        this.currentSection = sectionName;
        
        // Load section content
        this.loadSectionContent(sectionName);
    }
    
    async loadSectionContent(sectionName) {
        const sectionContainer = document.getElementById(`${sectionName}-section`);
        
        switch (sectionName) {
            case 'dashboard':
                // Dashboard is already loaded
                break;
                
            case 'members':
                await this.loadMembersSection(sectionContainer);
                break;
                
            case 'attendance':
                await this.loadAttendanceSection(sectionContainer);
                break;
                
            case 'offerings':
                await this.loadOfferingsSection(sectionContainer);
                break;
                
            case 'instrumentalists':
                await this.loadInstrumentalistsSection(sectionContainer);
                break;

            case 'payments':
                await this.loadPaymentsSection(sectionContainer);
                break;

            case 'checkin':
                await this.loadCheckinSection(sectionContainer);
                break;
        }
    }
    
    async loadMembersSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>Members Management</h2>
                        <p class="text-muted mb-0">Manage church members, edit details, and track membership</p>
                    </div>
                    <button class="btn btn-primary btn-lg shadow-sm" onclick="app.showAddMemberModal()">
                        <i class="bi bi-person-plus me-2"></i>Add New Member
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-people-fill fs-1 mb-2"></i>
                                <h3 class="mb-0" id="total-members-count">0</h3>
                                <p class="mb-0">Total Members</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-person-check-fill fs-1 mb-2"></i>
                                <h3 class="mb-0" id="active-members-count">0</h3>
                                <p class="mb-0">Active Members</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-fingerprint fs-1 mb-2"></i>
                                <h3 class="mb-0" id="fingerprint-members-count">0</h3>
                                <p class="mb-0">With Fingerprint</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0 fw-bold">
                                    <i class="bi bi-list-ul me-2 text-primary"></i>All Members
                                    <span class="badge bg-primary ms-2" id="members-count">0</span>
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 bg-light"
                                           placeholder="Search members by name, phone, or email..."
                                           id="member-search">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="members-list">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-3">Loading members...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await this.loadMembers();
        await this.renderMembersList();
        this.updateMemberStats();
        this.setupMembersSearch();
    }

    async loadMembers() {
        try {
            const response = await fetch('api/get_members.php');

            if (response.ok) {
                this.members = await response.json();
            } else {
                console.error('Failed to load members:', response.status);
                this.members = [];
            }
        } catch (error) {
            console.error('Error loading members:', error);
            this.members = [];
        }
    }

    setupMembersSearch() {
        const searchInput = document.getElementById('member-search');
        const genderFilter = document.getElementById('gender-filter');

        if (searchInput) {
            searchInput.addEventListener('input', () => this.filterMembers());
        }
        if (genderFilter) {
            genderFilter.addEventListener('change', () => this.filterMembers());
        }
    }

    filterMembers() {
        const searchTerm = document.getElementById('member-search')?.value.toLowerCase() || '';
        const genderFilter = document.getElementById('gender-filter')?.value || '';

        const filteredMembers = this.members.filter(member => {
            const matchesSearch = member.full_name.toLowerCase().includes(searchTerm) ||
                                (member.phone && member.phone.includes(searchTerm));
            const matchesGender = !genderFilter || member.gender === genderFilter;

            return matchesSearch && matchesGender;
        });

        this.renderFilteredMembers(filteredMembers);
    }

    renderFilteredMembers(members) {
        const container = document.getElementById('members-list');
        const countBadge = document.getElementById('members-count');

        if (!container) {
            console.error('members-list container not found');
            return;
        }

        if (countBadge) {
            countBadge.textContent = `${members.length} member${members.length !== 1 ? 's' : ''}`;
        }

        if (members.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No members found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            `;
            return;
        }

        const membersHtml = members.map(member => `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card member-card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <h6 class="card-title mb-0 fw-bold">${member.full_name}</h6>
                                    <small class="text-muted">Member ID: ${member.id}</small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="#" onclick="app.editMember(${member.id})">
                                        <i class="bi bi-pencil me-2 text-primary"></i>Edit Member
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="registerFingerprint(${member.id}, '${member.full_name}')">
                                        <i class="bi bi-fingerprint me-2 text-info"></i>Register Fingerprint
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="app.deleteMember(${member.id})">
                                        <i class="bi bi-trash me-2"></i>Delete Member
                                    </a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="member-details">
                            <div class="detail-item mb-2">
                                <i class="bi bi-telephone-fill me-2 text-success"></i>
                                <span class="detail-text">${member.phone || 'No phone number'}</span>
                            </div>
                            <div class="detail-item mb-2">
                                <i class="bi bi-person-badge-fill me-2 text-info"></i>
                                <span class="detail-text">${member.gender}</span>
                            </div>
                            ${member.email ? `
                                <div class="detail-item mb-2">
                                    <i class="bi bi-envelope-fill me-2 text-warning"></i>
                                    <span class="detail-text">${member.email}</span>
                                </div>
                            ` : ''}
                            ${member.occupation ? `
                                <div class="detail-item mb-2">
                                    <i class="bi bi-briefcase-fill me-2 text-secondary"></i>
                                    <span class="detail-text">${member.occupation}</span>
                                </div>
                            ` : ''}
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                                ${member.created_at ? `
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-plus me-1"></i>
                                        ${new Date(member.created_at).toLocaleDateString()}
                                    </small>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = `<div class="row">${membersHtml}</div>`;
    }

    async renderMembersList() {
        if (!this.members) {
            console.error('No members data available for rendering');
            return;
        }
        this.renderFilteredMembers(this.members);
    }

    updateMemberStats() {
        if (!this.members) return;

        const totalMembers = this.members.length;
        const activeMembers = this.members.filter(m => m.status !== 'inactive').length;
        const fingerprintMembers = this.members.filter(m => m.has_fingerprint == 1 || m.has_fingerprint === true).length;

        // Update stat cards in members section
        const totalElement = document.getElementById('total-members-count');
        const activeElement = document.getElementById('active-members-count');
        const fingerprintElement = document.getElementById('fingerprint-members-count');

        if (totalElement) {
            totalElement.textContent = totalMembers;
        }
        if (activeElement) {
            activeElement.textContent = activeMembers;
        }
        if (fingerprintElement) {
            fingerprintElement.textContent = fingerprintMembers;
        }

        // Update members count badge in the list
        const membersCountBadge = document.getElementById('members-count');
        if (membersCountBadge) {
            membersCountBadge.textContent = totalMembers;
        }
    }



    async loadAttendanceSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-calendar-check me-2"></i>Attendance Management</h2>
                    <button class="btn btn-primary" onclick="app.showRecordAttendanceModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record Attendance
                    </button>
                </div>

                <!-- Date Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Select Date:</label>
                                <input type="date" class="form-control" id="attendance-date" value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Service Type:</label>
                                <select class="form-select" id="attendance-service-type">
                                    <option value="">All Services</option>
                                    <option value="Sunday Morning">Sunday Morning</option>
                                    <option value="Sunday Evening">Sunday Evening</option>
                                    <option value="Wednesday">Wednesday Service</option>
                                    <option value="Friday">Friday Service</option>
                                    <option value="Special Event">Special Event</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="app.loadAttendanceData()">
                                    <i class="bi bi-search me-2"></i>Load Attendance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 id="total-attendance">0</h3>
                                <p class="mb-0">Total Present</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 id="fingerprint-checkins">0</h3>
                                <p class="mb-0">Fingerprint Check-ins</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 id="manual-checkins">0</h3>
                                <p class="mb-0">Manual Check-ins</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3 id="attendance-percentage">0%</h3>
                                <p class="mb-0">Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attendance Records</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="app.exportAttendance()">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="attendance-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading attendance...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        await this.loadAttendanceData();
    }

    async loadAttendanceData() {
        const dateInput = document.getElementById('attendance-date');
        const serviceTypeInput = document.getElementById('attendance-service-type');
        const container = document.getElementById('attendance-list');

        if (!dateInput || !container) return;

        const date = dateInput.value;
        const serviceType = serviceTypeInput?.value || '';

        try {
            let url = `api/attendance.php?date=${date}`;
            if (serviceType) {
                url += `&service_type=${encodeURIComponent(serviceType)}`;
            }

            const response = await fetch(url);
            if (response.ok) {
                const attendance = await response.json();
                this.renderAttendanceList(attendance);
                this.updateAttendanceStats(attendance);
            } else {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-circle fs-1"></i>
                        <p class="mt-2">Failed to load attendance data</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading attendance:', error);
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-wifi-off fs-1"></i>
                    <p class="mt-2">No attendance records found</p>
                    <button class="btn btn-primary" onclick="app.showRecordAttendanceModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record First Attendance
                    </button>
                </div>
            `;
        }
    }

    renderAttendanceList(attendance) {
        const container = document.getElementById('attendance-list');

        if (!attendance || attendance.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-calendar-x fs-1"></i>
                    <p class="mt-2">No attendance records for this date</p>
                    <button class="btn btn-primary" onclick="app.showRecordAttendanceModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record Attendance
                    </button>
                </div>
            `;
            return;
        }

        const attendanceHtml = attendance.map(record => `
            <div class="row align-items-center border-bottom py-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check text-success me-3 fs-4"></i>
                        <div>
                            <div class="fw-bold">${record.member_name}</div>
                            <small class="text-muted">${record.service_type}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Check-in Time</div>
                    <div>${new Date(record.check_in_time).toLocaleTimeString()}</div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Method</div>
                    <span class="badge ${record.check_in_method === 'Fingerprint' ? 'bg-success' : 'bg-primary'}">
                        <i class="bi bi-${record.check_in_method === 'Fingerprint' ? 'fingerprint' : 'pencil'} me-1"></i>
                        ${record.check_in_method}
                    </span>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Status</div>
                    <span class="badge bg-success">Present</span>
                </div>
                <div class="col-md-1">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="app.editAttendance(${record.id})">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="app.removeAttendance(${record.id})">
                                <i class="bi bi-trash me-2"></i>Remove
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = attendanceHtml;
    }

    updateAttendanceStats(attendance) {
        const totalElement = document.getElementById('total-attendance');
        const fingerprintElement = document.getElementById('fingerprint-checkins');
        const manualElement = document.getElementById('manual-checkins');
        const percentageElement = document.getElementById('attendance-percentage');

        if (!attendance || attendance.length === 0) {
            if (totalElement) totalElement.textContent = '0';
            if (fingerprintElement) fingerprintElement.textContent = '0';
            if (manualElement) manualElement.textContent = '0';
            if (percentageElement) percentageElement.textContent = '0%';
            return;
        }

        const total = attendance.length;
        const fingerprintCount = attendance.filter(a => a.check_in_method === 'Fingerprint').length;
        const manualCount = total - fingerprintCount;

        // Calculate percentage based on total members (you might want to adjust this)
        const totalMembers = this.members.length || 1;
        const percentage = Math.round((total / totalMembers) * 100);

        if (totalElement) totalElement.textContent = total;
        if (fingerprintElement) fingerprintElement.textContent = fingerprintCount;
        if (manualElement) manualElement.textContent = manualCount;
        if (percentageElement) percentageElement.textContent = `${percentage}%`;
    }

    async loadOfferingsSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-cash-coin me-2"></i>Offerings Management</h2>
                    <button class="btn btn-primary" onclick="openOfferingModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record Offering
                    </button>
                </div>

                <!-- Offerings Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 id="total-offerings">$0</h3>
                                <p class="mb-0">Total Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 id="tithe-total">$0</h3>
                                <p class="mb-0">Tithe</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 id="thanksgiving-total">$0</h3>
                                <p class="mb-0">Thanksgiving</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3 id="other-total">$0</h3>
                                <p class="mb-0">Other</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Today's Offerings</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="app.exportOfferings()">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="offerings-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading offerings...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        await this.loadOfferingsData();
    }

    async loadOfferingsData() {
        const container = document.getElementById('offerings-list');
        const today = new Date().toISOString().split('T')[0];

        try {
            const response = await fetch(`api/get_offerings.php?date=${today}`);
            const data = await response.json();

            if (data.success) {
                // Update the summary cards
                this.updateOfferingCards(data.totals);

                // Display the offerings list
                if (data.offerings.length > 0) {
                    this.renderOfferingsList(data.offerings);
                } else {
                    container.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cash-coin fs-1"></i>
                            <p class="mt-2">No offerings recorded today</p>
                            <button class="btn btn-primary" onclick="openOfferingModal()">
                                <i class="bi bi-plus-circle me-2"></i>Record First Offering
                            </button>
                        </div>
                    `;
                }
            } else {
                throw new Error(data.error || 'Failed to load offerings');
            }
        } catch (error) {
            console.error('Error loading offerings:', error);
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cash-coin fs-1"></i>
                    <p class="mt-2">Error loading offerings</p>
                    <button class="btn btn-primary" onclick="openOfferingModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record Offering
                    </button>
                </div>
            `;
        }
    }

    updateOfferingCards(totals) {
        // Update the summary cards with actual data
        const totalElement = document.getElementById('total-offerings');
        const titheElement = document.getElementById('tithe-total');
        const thanksgivingElement = document.getElementById('thanksgiving-total');
        const otherElement = document.getElementById('other-total');

        if (totalElement) totalElement.textContent = `$${totals.total.toFixed(2)}`;
        if (titheElement) titheElement.textContent = `$${totals.tithe.toFixed(2)}`;
        if (thanksgivingElement) thanksgivingElement.textContent = `$${totals.thanksgiving.toFixed(2)}`;
        if (otherElement) otherElement.textContent = `$${totals.other.toFixed(2)}`;
    }

    renderOfferingsList(offerings) {
        const container = document.getElementById('offerings-list');

        if (!offerings || offerings.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cash-coin fs-1"></i>
                    <p class="mt-2">No offerings recorded</p>
                    <button class="btn btn-primary" onclick="openOfferingModal()">
                        <i class="bi bi-plus-circle me-2"></i>Record First Offering
                    </button>
                </div>
            `;
            return;
        }

        // Display offerings in a table
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        offerings.forEach(offering => {
            const time = new Date(offering.created_at).toLocaleTimeString();
            const amount = `$${offering.amount.toFixed(2)}`;
            const notes = offering.notes || '-';

            html += `
                <tr>
                    <td>${time}</td>
                    <td>${offering.service_type}</td>
                    <td><span class="badge bg-primary">${offering.offering_type}</span></td>
                    <td><strong>${amount}</strong></td>
                    <td>${notes}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    async loadInstrumentalistsSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-music-note-beamed me-2"></i>Instrumentalists</h2>
                    <button class="btn btn-primary" onclick="app.showAddInstrumentalistModal()">
                        <i class="bi bi-person-plus me-2"></i>Add Instrumentalist
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Instrumentalists</h5>
                    </div>
                    <div class="card-body">
                        <div id="instrumentalists-list">Loading instrumentalists...</div>
                    </div>
                </div>
            </div>
        `;

        // Load and display instrumentalists
        console.log('Loading instrumentalists section...');
        await this.loadInstrumentalists();
        console.log('Instrumentalists loaded, count:', this.instrumentalists.length);
        await this.renderInstrumentalistsList();
        console.log('Instrumentalists rendered');
    }

    async renderInstrumentalistsList() {
        console.log('renderInstrumentalistsList called');
        console.log('Instrumentalists data:', this.instrumentalists);

        const container = document.getElementById('instrumentalists-list');

        if (!container) {
            console.error('Instrumentalists list container not found');
            return;
        }

        console.log('Container found, instrumentalists count:', this.instrumentalists.length);

        if (this.instrumentalists.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-music-note-beamed fs-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No instrumentalists found</h4>
                    <p class="text-muted">Add your first instrumentalist to get started</p>
                    <button class="btn btn-primary" onclick="app.showAddInstrumentalistModal()">
                        <i class="bi bi-person-plus me-2"></i>Add First Instrumentalist
                    </button>
                </div>
            `;
            return;
        }

        const instrumentalistsHtml = this.instrumentalists.map(instrumentalist => `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">${instrumentalist.full_name}</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="app.editInstrumentalist(${instrumentalist.id})">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="app.deleteInstrumentalist(${instrumentalist.id})">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <p class="card-text text-muted small mb-1">
                            <i class="bi bi-music-note me-1"></i>${instrumentalist.instrument || 'No instrument specified'}
                        </p>
                        ${instrumentalist.phone ? `<p class="card-text text-muted small mb-1">
                            <i class="bi bi-telephone me-1"></i>${instrumentalist.phone}
                        </p>` : ''}
                        ${instrumentalist.email ? `<p class="card-text text-muted small mb-1">
                            <i class="bi bi-envelope me-1"></i>${instrumentalist.email}
                        </p>` : ''}
                        ${instrumentalist.skill_level ? `<p class="card-text text-muted small mb-1">
                            <i class="bi bi-star me-1"></i>Skill: ${instrumentalist.skill_level}
                        </p>` : ''}
                        ${instrumentalist.hourly_rate ? `<p class="card-text text-muted small mb-1">
                            <i class="bi bi-cash me-1"></i>Hourly: $${parseFloat(instrumentalist.hourly_rate).toFixed(2)}
                        </p>` : ''}
                        ${instrumentalist.per_service_rate ? `<p class="card-text text-muted small mb-1">
                            <i class="bi bi-cash-coin me-1"></i>Per Service: $${parseFloat(instrumentalist.per_service_rate).toFixed(2)}
                        </p>` : ''}
                        <div class="mt-2">
                            <span class="badge bg-success">Active</span>
                            ${instrumentalist.created_at ? `<small class="text-muted ms-2">Added ${new Date(instrumentalist.created_at).toLocaleDateString()}</small>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = `<div class="row">${instrumentalistsHtml}</div>`;
    }

    async loadPaymentsSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <!-- Simple Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-credit-card me-2"></i>Make Payment</h2>
                    <button class="btn btn-success btn-lg" onclick="app.showMakePaymentModal()">
                        <i class="bi bi-plus-circle me-2"></i>Make Payment
                    </button>
                </div>

                <!-- Account Balance -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="card-title" id="paystack-balance">Loading...</h3>
                                <p class="card-text">Account Balance</p>
                                <button class="btn btn-light btn-sm" onclick="app.refreshPaystackBalance()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh Balance
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="card-title" id="total-paid">GH₵0.00</h3>
                                <p class="card-text">Total Paid Today</p>
                                <button class="btn btn-light btn-sm" onclick="app.viewPaymentHistory()">
                                    <i class="bi bi-clock-history me-1"></i>View History
                                </button>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Simple Payment List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list me-2"></i>Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <div id="simple-payments-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading payments...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Load simple payment data
        await this.loadSimplePaymentData();
    }

    showMakePaymentModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-credit-card me-2"></i>Make Payment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="make-payment-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Select Instrumentalist *</label>
                                        <select class="form-select" name="instrumentalist_id" required id="instrumentalist-select">
                                            <option value="">Loading instrumentalists...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Select Service *</label>
                                        <select class="form-select" name="service_id" required id="service-select">
                                            <option value="">Loading services...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Amount (GH₵) *</label>
                                        <input type="number" class="form-control" name="amount" step="0.01" min="0" required placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Type</label>
                                        <select class="form-select" name="payment_type">
                                            <option value="Per Service">Per Service</option>
                                            <option value="Hourly">Hourly</option>
                                            <option value="Fixed Amount">Fixed Amount</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Payment notes..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="app.submitMakePayment()">
                            <i class="bi bi-credit-card me-2"></i>Create Payment
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Load instrumentalists and services
        this.loadInstrumentalistsForPayment();
        this.loadServicesForPayment();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async loadSimplePaymentData() {
        try {
            // Load Paystack balance
            await this.loadPaystackBalance();

            // Load recent payments
            const response = await fetch('api/payment_processing_simple.php?action=pending_payments');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log('Raw payment response:', text);

            let payments;
            try {
                payments = JSON.parse(text);
                // Check if the response is an error object
                if (payments && payments.error) {
                    console.error('API error:', payments.error);
                    payments = [];
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                payments = [];
            }

            this.displaySimplePaymentList(payments);

        } catch (error) {
            console.error('Error loading payment data:', error);
            this.showMessage('Error loading payment data: ' + error.message, 'error');
            // Display empty state
            this.displaySimplePaymentList([]);
        }
    }

    displaySimplePaymentList(payments) {
        const container = document.getElementById('simple-payments-list');

        // Handle case where payments is not an array or is an error object
        if (!payments || !Array.isArray(payments) || payments.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-credit-card fs-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No payments yet</h4>
                    <p class="text-muted">Click "Make Payment" to create your first payment</p>
                </div>
            `;
            return;
        }

        const paymentsHtml = payments.slice(0, 10).map(payment => `
            <div class="row align-items-center border-bottom py-3">
                <div class="col-md-3">
                    <div class="fw-bold">${payment.full_name || 'Unknown'}</div>
                    <small class="text-muted">${payment.instrument || 'No instrument'}</small>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Service</div>
                    <div>${payment.service_type || 'Unknown'}</div>
                    <small class="text-muted">${payment.service_date ? new Date(payment.service_date).toLocaleDateString() : ''}</small>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Amount</div>
                    <div class="fw-bold text-success">GH₵${parseFloat(payment.amount || 0).toFixed(2)}</div>
                </div>
                <div class="col-md-2">
                    <span class="badge ${this.getStatusBadgeClass(payment.payment_status)}">${payment.payment_status || 'Unknown'}</span>
                </div>
                <div class="col-md-3 text-end">
                    ${this.getPaymentActionButtons(payment)}
                </div>
            </div>
        `).join('');

        container.innerHTML = paymentsHtml;
    }

    getStatusBadgeClass(status) {
        switch (status) {
            case 'Pending': return 'bg-warning';
            case 'Approved': return 'bg-info';
            case 'Paid': return 'bg-success';
            case 'Failed': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    getPaymentActionButtons(payment) {
        if (payment.payment_status === 'Pending') {
            return `
                <button class="btn btn-success btn-sm" onclick="app.quickApproveAndPay(${payment.id})" title="Approve & Pay">
                    <i class="bi bi-lightning-fill me-1"></i>Pay Now
                </button>
            `;
        } else if (payment.payment_status === 'Approved') {
            return `
                <button class="btn btn-primary btn-sm" onclick="app.quickPaystackTransfer(${payment.id})" title="Pay via Paystack">
                    <i class="bi bi-credit-card me-1"></i>Pay
                </button>
            `;
        } else {
            return `
                <button class="btn btn-outline-info btn-sm" onclick="app.viewPaymentDetails(${payment.id})" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
            `;
        }
    }

    async loadInstrumentalistsForPayment() {
        try {
            const select = document.getElementById('instrumentalist-select');
            select.innerHTML = '<option value="">Loading instrumentalists...</option>';

            const response = await fetch('api/instrumentalists.php');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const instrumentalists = await response.json();
            console.log('Loaded instrumentalists:', instrumentalists);

            select.innerHTML = '<option value="">Select instrumentalist...</option>';

            if (instrumentalists && instrumentalists.length > 0) {
                instrumentalists.forEach(inst => {
                    select.innerHTML += `<option value="${inst.id}">${inst.full_name} - ${inst.instrument}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">No instrumentalists found</option>';
            }
        } catch (error) {
            console.error('Error loading instrumentalists:', error);
            const select = document.getElementById('instrumentalist-select');
            select.innerHTML = '<option value="">Error loading instrumentalists</option>';
            this.showMessage('Error loading instrumentalists', 'error');
        }
    }

    async loadServicesForPayment() {
        try {
            const select = document.getElementById('service-select');
            select.innerHTML = '<option value="">Loading services...</option>';

            const response = await fetch('api/services.php');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log('Raw services response:', text);

            let services;
            try {
                services = JSON.parse(text);
                // Check if the response is an error object
                if (services && services.error) {
                    console.error('API error:', services.error);
                    services = [];
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                services = [];
            }

            console.log('Loaded services:', services);

            select.innerHTML = '<option value="">Select service...</option>';

            if (services && services.length > 0) {
                services.forEach(service => {
                    const displayDate = new Date(service.service_date).toLocaleDateString();
                    select.innerHTML += `<option value="${service.id}">${service.service_type} - ${displayDate}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">No services found</option>';
                // Offer to create a service for today
                const today = new Date().toISOString().split('T')[0];
                select.innerHTML += `<option value="create_today">Create service for today</option>`;
            }
        } catch (error) {
            console.error('Error loading services:', error);
            const select = document.getElementById('service-select');
            select.innerHTML = '<option value="">Error loading services</option>';
            // Add fallback option to create today's service
            const today = new Date().toISOString().split('T')[0];
            select.innerHTML += `<option value="create_today">Create service for today</option>`;
            this.showMessage('Error loading services: ' + error.message, 'error');
        }
    }

    async submitMakePayment() {
        const form = document.getElementById('make-payment-form');
        const formData = new FormData(form);

        // Check if user selected "create_today" for service
        let serviceId = formData.get('service_id');
        if (serviceId === 'create_today') {
            // Create a service for today first
            try {
                const today = new Date().toISOString().split('T')[0];
                const createServiceResponse = await fetch('api/services.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        service_date: today,
                        service_type: 'Sunday Morning'
                    })
                });

                const serviceResult = await createServiceResponse.json();
                if (serviceResult.success) {
                    serviceId = serviceResult.service_id;
                    this.showMessage('Service created for today', 'info');
                } else {
                    this.showMessage('Failed to create service', 'error');
                    return;
                }
            } catch (error) {
                this.showMessage('Error creating service', 'error');
                return;
            }
        }

        const data = {
            instrumentalist_id: formData.get('instrumentalist_id'),
            service_id: serviceId,
            amount: formData.get('amount'),
            payment_type: formData.get('payment_type'),
            notes: formData.get('notes')
        };

        // Validate required fields
        if (!data.instrumentalist_id || !data.service_id || !data.amount) {
            this.showMessage('Please fill in all required fields', 'warning');
            return;
        }

        try {
            const response = await fetch('api/payment_processing.php?action=create_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.showMessage('Payment created successfully!', 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();

                // Update payment data dynamically
                await this.loadSimplePaymentData();

                // Update dashboard if we're there
                if (this.currentSection === 'dashboard') {
                    this.updateDashboardStats();
                }
            } else {
                this.showMessage(result.error || 'Failed to create payment', 'error');
            }

        } catch (error) {
            console.error('Error creating payment:', error);
            this.showMessage('Error creating payment: ' + error.message, 'error');
        }
    }

    async quickApproveAndPay(paymentId) {
        if (confirm('Approve and pay this instrumentalist via Paystack?')) {
            try {
                // First approve
                const approved = await this.approvePayment(paymentId, false, false);
                if (approved) {
                    // Then pay via Paystack
                    await this.processPaystackPayment(paymentId);
                    await this.loadSimplePaymentData();
                }
            } catch (error) {
                this.showMessage('Error processing payment', 'error');
            }
        }
    }

    async quickPaystackTransfer(paymentId) {
        if (confirm('Send payment via Paystack MoMo transfer?')) {
            try {
                await this.processPaystackPayment(paymentId);
                await this.loadSimplePaymentData();
            } catch (error) {
                this.showMessage('Error processing Paystack transfer', 'error');
            }
        }
    }

    viewPaymentDetails(paymentId) {
        this.showMessage('Payment details view coming soon', 'info');
    }

    viewPaymentHistory() {
        this.showMessage('Payment history view coming soon', 'info');
    }

    async loadCheckinSection(container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-fingerprint me-2"></i>Fingerprint Check-in</h2>
                    <div class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i>
                        <span>${new Date().toLocaleDateString()}</span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card shadow-custom">
                            <div class="card-header text-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-fingerprint me-2"></i>
                                    Touch Fingerprint Sensor to Check In
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-4">
                                    <label for="service-type-select" class="form-label">Select Service Type:</label>
                                    <select class="form-select w-50 mx-auto" id="service-type-select">
                                        <option value="Sunday Morning">Sunday Morning</option>
                                        <option value="Sunday Evening">Sunday Evening</option>
                                        <option value="Wednesday">Wednesday Service</option>
                                        <option value="Friday">Friday Service</option>
                                        <option value="Special Event">Special Event</option>
                                    </select>
                                </div>

                                <div class="fingerprint-scanner mb-4" id="checkin-scanner">
                                    <i class="bi bi-fingerprint fingerprint-icon"></i>
                                </div>

                                <div id="checkin-status" class="mb-3"></div>

                                <button class="btn btn-primary btn-lg" id="start-checkin">
                                    <i class="bi bi-fingerprint me-2"></i>Start Check-in
                                </button>

                                <div class="mt-4">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Make sure your fingerprint is registered before attempting to check in.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Recent Check-ins Today
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="recent-checkins">Loading recent check-ins...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.setupCheckinInterface();
        await this.loadRecentCheckins();
    }

    showLoading(show) {
        const spinner = document.getElementById('loading-spinner');
        if (show) {
            spinner.classList.remove('d-none');
        } else {
            spinner.classList.add('d-none');
        }
    }
    
    setupCheckinInterface() {
        const startBtn = document.getElementById('start-checkin');
        const scanner = document.getElementById('checkin-scanner');
        const status = document.getElementById('checkin-status');
        const serviceSelect = document.getElementById('service-type-select');

        if (!startBtn || !scanner || !status || !serviceSelect) return;

        const handleCheckin = async () => {
            if (!webAuthnManager.isSupported) {
                status.innerHTML = '<div class="status-message status-error">Fingerprint authentication is not supported in this browser.</div>';
                return;
            }

            try {
                startBtn.disabled = true;
                scanner.classList.add('scanning');
                status.innerHTML = '<div class="status-message status-warning">Please use your fingerprint sensor...</div>';

                const serviceDate = new Date().toISOString().split('T')[0];
                const serviceType = serviceSelect.value;

                const result = await fingerprintCheckin(serviceDate, serviceType);

                if (result.success) {
                    status.innerHTML = `
                        <div class="status-message status-success">
                            <i class="bi bi-check-circle me-2"></i>
                            ${result.message}
                        </div>
                    `;

                    // Refresh recent check-ins
                    await this.loadRecentCheckins();

                    // Reset after 3 seconds
                    setTimeout(() => {
                        status.innerHTML = '';
                        startBtn.disabled = false;
                        scanner.classList.remove('scanning');
                    }, 3000);
                } else {
                    throw new Error(result.error || 'Check-in failed');
                }

            } catch (error) {
                status.innerHTML = `<div class="status-message status-error">Error: ${error.message}</div>`;
                startBtn.disabled = false;
                scanner.classList.remove('scanning');
            }
        };

        startBtn.addEventListener('click', handleCheckin);
        scanner.addEventListener('click', handleCheckin);
    }

    async loadRecentCheckins() {
        const container = document.getElementById('recent-checkins');
        if (!container) return;

        const today = new Date().toISOString().split('T')[0];

        try {
            // For now, show sample data since we don't have the attendance API endpoint yet
            const sampleCheckins = [
                {
                    member_name: 'John Doe',
                    service_type: 'Sunday Morning',
                    check_in_time: new Date().toISOString(),
                    check_in_method: 'Fingerprint'
                }
            ];

            if (sampleCheckins.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-clock-history fs-3"></i>
                        <p class="mt-2">No fingerprint check-ins today</p>
                    </div>
                `;
                return;
            }

            const checkinsHtml = sampleCheckins.map(checkin => `
                <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check text-success me-3"></i>
                        <div>
                            <div class="fw-bold">${checkin.member_name}</div>
                            <small class="text-muted">${checkin.service_type}</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small">${new Date(checkin.check_in_time).toLocaleTimeString()}</div>
                        <span class="badge bg-success">Fingerprint</span>
                    </div>
                </div>
            `).join('');

            container.innerHTML = checkinsHtml;

        } catch (error) {
            container.innerHTML = '<div class="text-muted">Error loading recent check-ins</div>';
        }
    }

    async loadPaymentData() {
        try {
            // Load Paystack balance
            await this.loadPaystackBalance();

            // Load payment summary
            const summaryResponse = await fetch('api/payment_processing.php?action=payment_summary');
            const summaryData = await summaryResponse.json();
            this.updatePaymentSummary(summaryData);

            // Load pending payments
            await this.loadPendingPayments();

            // Load approved payments
            await this.loadApprovedPayments();

            // Load paid payments
            await this.loadPaidPayments();

        } catch (error) {
            console.error('Error loading payment data:', error);
            this.showMessage('Error loading payment data', 'error');
        }
    }

    async loadPaystackBalance() {
        try {
            console.log('Loading Paystack balance...');
            const response = await fetch('api/paystack_payments.php?action=balance');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Paystack balance result:', result);

            const balanceElement = document.getElementById('paystack-balance');
            if (balanceElement) {
                if (result.success) {
                    const balance = result.formatted_balance || 'GH₵0.00';
                    balanceElement.textContent = balance;
                    balanceElement.style.color = '#fff';
                    balanceElement.title = `Last updated: ${new Date().toLocaleTimeString()}`;

                    // Update last updated time
                    const lastUpdatedElement = document.getElementById('balance-last-updated');
                    if (lastUpdatedElement) {
                        lastUpdatedElement.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
                    }

                    // Show success notification on first load
                    if (!this.paystackNotificationShown) {
                        this.showMessage(`✅ Paystack connected! Balance: ${balance}`, 'success');
                        this.paystackNotificationShown = true;
                    }
                } else {
                    balanceElement.textContent = 'Error loading';
                    balanceElement.style.color = '#ffcccc';
                    balanceElement.title = result.error || 'Failed to load balance';
                    console.error('Paystack balance error:', result.error);
                    this.showMessage('⚠️ Paystack balance error: ' + (result.error || 'Failed to load balance'), 'warning');
                }
            }
        } catch (error) {
            console.error('Error loading Paystack balance:', error);
            const balanceElement = document.getElementById('paystack-balance');
            if (balanceElement) {
                balanceElement.textContent = 'Connection Error';
                balanceElement.style.color = '#ffcccc';
                balanceElement.title = 'Failed to connect to Paystack API';
                this.showMessage('❌ Paystack connection failed. Check configuration.', 'error');
            }
        }
    }

    async refreshPaystackBalance() {
        this.showMessage('Refreshing Paystack balance...', 'info');
        await this.loadPaystackBalance();
        this.showMessage('Balance refreshed', 'success');
    }

    async viewPaymentHistory() {
        try {
            // Load payment history
            const response = await fetch('api/payment_processing.php?action=payment_history');
            const result = await response.json();

            if (result.success) {
                this.showPaymentHistoryModal(result.payments);
            } else {
                this.showMessage('Failed to load payment history', 'error');
            }
        } catch (error) {
            console.error('Error loading payment history:', error);
            this.showMessage('Error loading payment history', 'error');
        }
    }

    showPaymentHistoryModal(payments) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-clock-history me-2"></i>Payment History
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${payments.length === 0 ?
                            '<div class="text-center text-muted"><p>No payment history found</p></div>' :
                            `<div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Instrumentalist</th>
                                            <th>Service</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${payments.map(payment => `
                                            <tr>
                                                <td>${new Date(payment.payment_date || payment.created_at).toLocaleDateString()}</td>
                                                <td>${payment.full_name}</td>
                                                <td>${payment.service_type} - ${payment.service_date}</td>
                                                <td>GH₵${payment.amount}</td>
                                                <td>
                                                    <span class="badge ${payment.payment_method === 'Paystack Transfer' ? 'bg-primary' : 'bg-secondary'}">
                                                        ${payment.payment_method}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge ${
                                                        payment.payment_status === 'Paid' ? 'bg-success' :
                                                        payment.payment_status === 'Failed' ? 'bg-danger' :
                                                        payment.payment_status === 'Approved' ? 'bg-info' : 'bg-warning'
                                                    }">
                                                        ${payment.payment_status}
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        ${payment.paystack_transfer_code || payment.reference_number || 'N/A'}
                                                    </small>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>`
                        }
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="app.exportPaymentHistory()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async exportPaymentHistory() {
        try {
            const response = await fetch('api/payment_processing.php?action=payment_history&limit=1000');
            const result = await response.json();

            if (result.success && result.payments.length > 0) {
                // Create CSV content
                const headers = ['Date', 'Instrumentalist', 'Service', 'Amount', 'Method', 'Status', 'Reference'];
                const csvContent = [
                    headers.join(','),
                    ...result.payments.map(payment => [
                        new Date(payment.payment_date || payment.created_at).toLocaleDateString(),
                        payment.full_name,
                        `${payment.service_type} - ${payment.service_date}`,
                        payment.amount,
                        payment.payment_method,
                        payment.payment_status,
                        payment.paystack_transfer_code || payment.reference_number || 'N/A'
                    ].join(','))
                ].join('\\n');

                // Download CSV
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `payment_history_${new Date().toISOString().split('T')[0]}.csv`;
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.showMessage('Payment history exported successfully', 'success');
            } else {
                this.showMessage('No payment history to export', 'warning');
            }
        } catch (error) {
            console.error('Error exporting payment history:', error);
            this.showMessage('Error exporting payment history', 'error');
        }
    }

    async refreshPaymentData() {
        this.showMessage('Refreshing payment data...', 'info');
        await this.loadPaymentData();
        this.showMessage('Payment data refreshed', 'success');
    }

    updatePaymentSummary(data) {
        const summary = data.summary || [];

        let pendingCount = 0, approvedCount = 0, paidCount = 0, totalAmount = 0;

        summary.forEach(item => {
            switch (item.payment_status) {
                case 'Pending':
                    pendingCount = item.count;
                    break;
                case 'Approved':
                    approvedCount = item.count;
                    break;
                case 'Paid':
                    paidCount = item.count;
                    totalAmount = item.total_amount;
                    break;
            }
        });

        // Update summary cards
        const pendingElement = document.getElementById('pending-payments-count');
        const approvedElement = document.getElementById('approved-payments-count');
        const totalElement = document.getElementById('total-amount');

        if (pendingElement) pendingElement.textContent = pendingCount;
        if (approvedElement) approvedElement.textContent = approvedCount;
        if (totalElement) totalElement.textContent = `GH₵${totalAmount.toFixed(2)}`;
    }

    async loadPendingPayments() {
        const container = document.getElementById('pending-payments-list');

        try {
            const response = await fetch('api/payment_processing.php?action=pending_payments');
            const payments = await response.json();

            if (payments.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-credit-card fs-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No pending payments</h4>
                        <p class="text-muted">All payments are up to date</p>
                    </div>
                `;
                return;
            }

            const paymentsHtml = payments.map(payment => `
                <div class="row align-items-center border-bottom py-3">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle text-primary me-2 fs-4"></i>
                            <div>
                                <div class="fw-bold">${payment.full_name}</div>
                                <small class="text-muted">${payment.instrument}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Service</div>
                        <div>${payment.service_type}</div>
                        <small class="text-muted">${new Date(payment.service_date).toLocaleDateString()}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Amount</div>
                        <div class="fw-bold text-success">$${parseFloat(payment.amount).toFixed(2)}</div>
                    </div>
                    <div class="col-md-2">
                        <span class="badge bg-${payment.payment_status === 'Pending' ? 'warning' : 'info'}">
                            ${payment.payment_status}
                        </span>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="btn-group btn-group-sm">
                            ${payment.payment_status === 'Pending' ? `
                                <button class="btn btn-outline-success" onclick="app.approvePayment(${payment.id})">
                                    <i class="bi bi-check-circle me-1"></i>Approve
                                </button>
                            ` : ''}
                            ${payment.payment_status === 'Approved' ? `
                                <button class="btn btn-outline-primary" onclick="app.processPayment(${payment.id})">
                                    <i class="bi bi-credit-card me-1"></i>Process
                                </button>
                            ` : ''}
                            <button class="btn btn-outline-secondary" onclick="app.editPayment(${payment.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${payment.payment_status === 'Paid' ? `
                                <button class="btn btn-outline-info" onclick="app.printReceipt(${payment.id})">
                                    <i class="bi bi-printer"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            container.innerHTML = paymentsHtml;

        } catch (error) {
            container.innerHTML = '<div class="text-muted">Error loading pending payments</div>';
        }
    }

    async loadApprovedPayments() {
        const container = document.getElementById('approved-payments-list');

        try {
            const response = await fetch('api/payment_processing.php?action=pending_payments');
            const allPayments = await response.json();
            const approvedPayments = allPayments.filter(payment => payment.payment_status === 'Approved');

            if (approvedPayments.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle fs-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No approved payments</h4>
                        <p class="text-muted">All approved payments have been processed</p>
                    </div>
                `;
                return;
            }

            const paymentsHtml = approvedPayments.map(payment => `
                <div class="row align-items-center border-bottom py-3">
                    <div class="col-md-1">
                        <input type="checkbox" class="form-check-input" value="${payment.id}" name="selected_payments">
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold">${payment.full_name}</div>
                        <small class="text-muted">${payment.instrument}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Service</div>
                        <div>${payment.service_type}</div>
                        <small class="text-muted">${new Date(payment.service_date).toLocaleDateString()}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Amount</div>
                        <div class="fw-bold text-success">GH₵${parseFloat(payment.amount).toFixed(2)}</div>
                    </div>
                    <div class="col-md-2">
                        <span class="badge bg-info">Ready to Pay</span>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="app.processPayment(${payment.id})" title="Process Payment">
                                <i class="bi bi-credit-card"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="app.printReceipt(${payment.id})" title="Print Receipt">
                                <i class="bi bi-printer"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            container.innerHTML = paymentsHtml;

        } catch (error) {
            container.innerHTML = '<div class="text-muted">Error loading approved payments</div>';
        }
    }

    async loadPaidPayments() {
        const container = document.getElementById('paid-payments-list');

        try {
            const response = await fetch('api/payment_processing.php?action=pending_payments');
            const allPayments = await response.json();
            const paidPayments = allPayments.filter(payment => payment.payment_status === 'Paid');

            if (paidPayments.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-credit-card fs-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No paid payments</h4>
                        <p class="text-muted">No payments have been processed yet</p>
                    </div>
                `;
                return;
            }

            const paymentsHtml = paidPayments.map(payment => `
                <div class="row align-items-center border-bottom py-3">
                    <div class="col-md-3">
                        <div class="fw-bold">${payment.full_name}</div>
                        <small class="text-muted">${payment.instrument}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Service</div>
                        <div>${payment.service_type}</div>
                        <small class="text-muted">${new Date(payment.service_date).toLocaleDateString()}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Amount</div>
                        <div class="fw-bold text-success">GH₵${parseFloat(payment.amount).toFixed(2)}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Payment Method</div>
                        <div>${payment.payment_method || 'N/A'}</div>
                    </div>
                    <div class="col-md-2">
                        <span class="badge bg-success">Paid</span>
                        ${payment.payment_date ? `<br><small class="text-muted">${new Date(payment.payment_date).toLocaleDateString()}</small>` : ''}
                    </div>
                    <div class="col-md-1 text-end">
                        <button class="btn btn-outline-info btn-sm" onclick="app.printReceipt(${payment.id})" title="Print Receipt">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            container.innerHTML = paymentsHtml;

        } catch (error) {
            container.innerHTML = '<div class="text-muted">Error loading paid payments</div>';
        }
    }

    async approvePayment(paymentId, showConfirm = true, showMessage = true) {
        if (showConfirm && !confirm('Are you sure you want to approve this payment?')) return;

        try {
            const response = await fetch('api/payment_processing_simple.php?action=approve_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    payment_id: paymentId,
                    approved_by: 'Admin'
                })
            });

            const result = await response.json();

            if (result.success) {
                if (showMessage) {
                    this.showMessage('Payment approved successfully', 'success');
                    await this.loadPaymentData();
                }
                return true;
            } else {
                if (showMessage) {
                    this.showMessage(result.error || 'Failed to approve payment', 'error');
                }
                return false;
            }

        } catch (error) {
            if (showMessage) {
                this.showMessage('Error approving payment', 'error');
            }
            return false;
        }
    }

    async processPayment(paymentId) {
        this.showProcessPaymentModal(paymentId);
    }

    async showProcessPaymentModal(paymentId) {
        // First check if instrumentalist has MoMo details for Paystack
        const recipientStatus = await this.checkRecipientStatus(paymentId);

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-credit-card me-2"></i>Process Payment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${recipientStatus.canUsePaystack ? `
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Paystack MoMo Available:</strong> This instrumentalist can receive payments via Mobile Money through Paystack.
                            </div>
                        ` : `
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>MoMo Details Missing:</strong> Complete MoMo details required for Paystack payments.
                            </div>
                        `}

                        <form id="process-payment-form">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" onchange="app.togglePaymentMethodFields(this.value)" required>
                                    <option value="">Select payment method</option>
                                    ${recipientStatus.canUsePaystack ? '<option value="Paystack Transfer">🚀 Paystack MoMo Transfer (Recommended)</option>' : ''}
                                    <option value="Mobile Money">Manual Mobile Money</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div id="paystack-info" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="bi bi-lightning-fill me-2"></i>
                                    <strong>Automatic Transfer:</strong> Payment will be sent directly to the instrumentalist's MoMo account via Paystack.
                                </div>
                            </div>
                            <div id="manual-fields">
                                <div class="mb-3">
                                    <label class="form-label">Reference Number (Optional)</label>
                                    <input type="text" class="form-control" name="reference_number" placeholder="Transaction reference">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Paid By</label>
                                    <input type="text" class="form-control" name="paid_by" value="Admin" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="process-payment-btn" onclick="app.submitProcessPayment(${paymentId})">
                            <i class="bi bi-check-circle me-2"></i>Process Payment
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async checkRecipientStatus(paymentId) {
        try {
            // Get payment details to find instrumentalist
            const response = await fetch(`api/payments.php?payment_id=${paymentId}`);
            const paymentData = await response.json();

            if (paymentData && paymentData.length > 0) {
                const instrumentalistId = paymentData[0].instrumentalist_id;

                const statusResponse = await fetch(`api/paystack_payments.php?action=recipient_status&instrumentalist_id=${instrumentalistId}`);
                const statusData = await statusResponse.json();

                return {
                    canUsePaystack: statusData.success && statusData.ready_for_payment,
                    hasRecipient: statusData.has_recipient,
                    hasMomoDetails: statusData.has_momo_details
                };
            }
        } catch (error) {
            console.error('Error checking recipient status:', error);
        }

        return { canUsePaystack: false, hasRecipient: false, hasMomoDetails: false };
    }

    togglePaymentMethodFields(paymentMethod) {
        const paystackInfo = document.getElementById('paystack-info');
        const manualFields = document.getElementById('manual-fields');
        const processBtn = document.getElementById('process-payment-btn');

        if (paymentMethod === 'Paystack Transfer') {
            paystackInfo.style.display = 'block';
            manualFields.style.display = 'none';
            processBtn.innerHTML = '<i class="bi bi-lightning-fill me-2"></i>Send via Paystack';
            processBtn.className = 'btn btn-primary';
        } else {
            paystackInfo.style.display = 'none';
            manualFields.style.display = 'block';
            processBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Process Payment';
            processBtn.className = 'btn btn-success';
        }
    }

    async submitProcessPayment(paymentId) {
        const form = document.getElementById('process-payment-form');
        const formData = new FormData(form);
        const paymentMethod = formData.get('payment_method');

        if (paymentMethod === 'Paystack Transfer') {
            // Handle Paystack payment
            await this.processPaystackPayment(paymentId);
        } else {
            // Handle manual payment
            const data = {
                payment_id: paymentId,
                payment_method: paymentMethod,
                reference_number: formData.get('reference_number'),
                payment_date: formData.get('payment_date'),
                paid_by: formData.get('paid_by')
            };

            try {
                const response = await fetch('api/payment_processing.php?action=process_payment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    this.showMessage('Payment processed successfully', 'success');
                    bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
                    await this.loadPaymentData();
                } else {
                    this.showMessage(result.error || 'Failed to process payment', 'error');
                }

            } catch (error) {
                this.showMessage('Error processing payment', 'error');
            }
        }
    }

    async processPaystackPayment(paymentId) {
        try {
            this.showMessage('Processing Paystack transfer...', 'info');

            const response = await fetch('api/paystack_payments.php?action=process_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_id: paymentId })
            });

            const result = await response.json();

            if (result.success) {
                // Show detailed success message
                const successMessage = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">🎉 Payment Successful!</h5>
                        <p><strong>Amount:</strong> ${result.amount}</p>
                        <p><strong>Transfer Code:</strong> ${result.transfer_code || 'N/A'}</p>
                        <p><strong>Status:</strong> Payment completed successfully via Paystack MoMo transfer</p>
                        <hr>
                        <p class="mb-0">The instrumentalist will receive the payment in their mobile money account.</p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                // Show the detailed message
                const messageContainer = document.getElementById('message-container') || document.body;
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = successMessage;
                messageContainer.appendChild(tempDiv.firstElementChild);

                // Also show simple message
                this.showMessage(`✅ Payment successful! ${result.amount} sent via Paystack`, 'success');

                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
                await this.loadPaymentData();
                await this.loadPaystackBalance(); // Refresh balance
            } else {
                this.showMessage(`❌ Paystack transfer failed: ${result.error}`, 'error');
            }

        } catch (error) {
            this.showMessage('Error processing Paystack payment', 'error');
        }
    }

    showMessage(message, type = 'info') {
        // Clear ALL existing messages of the same type to prevent duplicates
        const existingToasts = document.querySelectorAll(`.alert-${type}.position-fixed`);
        existingToasts.forEach(toast => toast.remove());

        // For success messages, also clear any error messages to avoid confusion
        if (type === 'success') {
            const errorToasts = document.querySelectorAll(`.alert-error.position-fixed, .alert-danger.position-fixed`);
            errorToasts.forEach(toast => toast.remove());
        }

        // Create and show toast message
        const toast = document.createElement('div');

        // Make success messages more prominent
        const isSuccess = type === 'success';
        const extraClasses = isSuccess ? ' border-success shadow-lg' : '';
        const fontSize = isSuccess ? 'font-size: 1.1rem; font-weight: 600;' : '';

        // Stack messages vertically
        const existingMessages = document.querySelectorAll('.alert.position-fixed');
        const topOffset = 100 + (existingMessages.length * 80);

        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed${extraClasses}`;
        toast.style.cssText = `top: ${topOffset}px; right: 20px; z-index: 9999; min-width: 350px; ${fontSize}`;

        // Add icon based on type
        let icon = '';
        switch(type) {
            case 'success': icon = '✅ '; break;
            case 'error': icon = '❌ '; break;
            case 'warning': icon = '⚠️ '; break;
            case 'info': icon = 'ℹ️ '; break;
        }

        toast.innerHTML = `
            <div style="display: flex; align-items: center;">
                <span style="margin-right: 8px;">${icon}</span>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        // Auto remove after longer time for success messages
        const autoRemoveTime = isSuccess ? 7000 : 5000;
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, autoRemoveTime);
    }
    showCalculatePaymentsModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-calculator me-2"></i>Calculate Service Payments
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="calculate-payments-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Service Date</label>
                                    <input type="date" class="form-control" name="service_date" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Type</label>
                                    <select class="form-select" name="service_type" required>
                                        <option value="Sunday Morning">Sunday Morning</option>
                                        <option value="Sunday Evening">Sunday Evening</option>
                                        <option value="Wednesday">Wednesday Service</option>
                                        <option value="Friday">Friday Service</option>
                                        <option value="Special Event">Special Event</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-center mb-3">
                                <button type="button" class="btn btn-primary" onclick="app.calculateServicePayments()">
                                    <i class="bi bi-calculator me-2"></i>Calculate Payments
                                </button>
                            </div>
                        </form>

                        <div id="payment-calculations" class="d-none">
                            <hr>
                            <h6>Payment Calculations</h6>
                            <div id="calculations-list"></div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="app.createCalculatedPayments()">
                                    <i class="bi bi-check-circle me-2"></i>Create All Payments
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async calculateServicePayments() {
        const form = document.getElementById('calculate-payments-form');
        const formData = new FormData(form);

        const serviceDate = formData.get('service_date');
        const serviceType = formData.get('service_type');

        try {
            const response = await fetch(`api/payment_processing.php?action=calculate_payments&service_date=${serviceDate}&service_type=${serviceType}`);
            const data = await response.json();

            const calculationsDiv = document.getElementById('payment-calculations');
            const listDiv = document.getElementById('calculations-list');

            if (data.calculations.length === 0) {
                listDiv.innerHTML = '<div class="text-muted text-center py-3">No active instrumentalists found</div>';
                calculationsDiv.classList.remove('d-none');
                return;
            }

            const calculationsHtml = data.calculations.map(calc => `
                <div class="row align-items-center border-bottom py-2">
                    <div class="col-md-4">
                        <div class="fw-bold">${calc.full_name}</div>
                        <small class="text-muted">${calc.instrument}</small>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Rate</div>
                        <div>$${parseFloat(calc.per_service_rate || 0).toFixed(2)}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Amount</div>
                        <div class="fw-bold text-success">$${parseFloat(calc.calculated_amount).toFixed(2)}</div>
                    </div>
                    <div class="col-md-2">
                        ${calc.existing_payment ?
                            '<span class="badge bg-warning">Exists</span>' :
                            '<span class="badge bg-success">New</span>'
                        }
                    </div>
                </div>
            `).join('');

            listDiv.innerHTML = calculationsHtml;
            calculationsDiv.classList.remove('d-none');

            // Store calculation data for later use
            this.currentCalculations = data;

        } catch (error) {
            this.showMessage('Error calculating payments', 'error');
        }
    }

    async createCalculatedPayments() {
        if (!this.currentCalculations) return;

        const instrumentalists = this.currentCalculations.calculations
            .filter(calc => calc.can_create && calc.calculated_amount > 0)
            .map(calc => ({
                instrumentalist_id: calc.instrumentalist_id,
                amount: calc.calculated_amount,
                payment_type: 'Per Service',
                notes: `Auto-calculated for ${this.currentCalculations.service_type} service`
            }));

        if (instrumentalists.length === 0) {
            this.showMessage('No new payments to create', 'warning');
            return;
        }

        try {
            const response = await fetch('api/payment_processing.php?action=bulk_calculate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_date: this.currentCalculations.service_date,
                    service_type: this.currentCalculations.service_type,
                    instrumentalists: instrumentalists
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Created ${result.created_count} payments successfully`, 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
                await this.loadPaymentData();
            } else {
                this.showMessage(result.errors?.join(', ') || 'Failed to create payments', 'error');
            }

        } catch (error) {
            this.showMessage('Error creating payments', 'error');
        }
    }

    printReceipt(paymentId) {
        const receiptWindow = window.open(`api/payment_reports.php?action=receipt&payment_id=${paymentId}`, '_blank');
        receiptWindow.focus();
    }

    exportPayments() {
        const month = prompt('Enter month (YYYY-MM):', new Date().toISOString().substr(0, 7));
        if (month) {
            window.open(`api/payment_reports.php?action=export_payments&month=${month}`, '_blank');
        }
    }

    async refreshPayments() {
        await this.loadPaymentData();
        this.showMessage('Payments refreshed', 'success');
    }

    editPayment(id) {
        this.showMessage('Edit payment functionality coming soon', 'info');
    }

    async bulkApprovePayments() {
        const checkboxes = document.querySelectorAll('input[name="selected_payments"]:checked');
        if (checkboxes.length === 0) {
            this.showMessage('Please select payments to approve', 'warning');
            return;
        }

        const paymentIds = Array.from(checkboxes).map(cb => cb.value);

        if (confirm(`Approve ${paymentIds.length} selected payments?`)) {
            try {
                for (const paymentId of paymentIds) {
                    await this.approvePayment(paymentId, false); // Don't show individual messages
                }
                this.showMessage(`Successfully approved ${paymentIds.length} payments`, 'success');
                await this.loadPaymentData();
            } catch (error) {
                this.showMessage('Error in bulk approval', 'error');
            }
        }
    }

    async bulkProcessPaystack() {
        const checkboxes = document.querySelectorAll('#approved-payments input[name="selected_payments"]:checked');
        if (checkboxes.length === 0) {
            this.showMessage('Please select approved payments to process via Paystack', 'warning');
            return;
        }

        const paymentIds = Array.from(checkboxes).map(cb => cb.value);

        if (confirm(`Process ${paymentIds.length} payments via Paystack MoMo transfer?\n\nThis will send money directly to instrumentalists' mobile money accounts.`)) {
            try {
                this.showMessage(`Processing ${paymentIds.length} Paystack transfers...`, 'info');

                let successCount = 0;
                let failCount = 0;

                for (const paymentId of paymentIds) {
                    try {
                        await this.processPaystackPayment(paymentId);
                        successCount++;
                    } catch (error) {
                        failCount++;
                    }
                }

                this.showMessage(`Bulk Paystack processing completed: ${successCount} successful, ${failCount} failed`,
                    failCount === 0 ? 'success' : 'warning');
                await this.loadPaymentData();
            } catch (error) {
                this.showMessage('Error in bulk Paystack processing', 'error');
            }
        }
    }

    async bulkProcessManual() {
        const checkboxes = document.querySelectorAll('#approved-payments input[name="selected_payments"]:checked');
        if (checkboxes.length === 0) {
            this.showMessage('Please select approved payments to process manually', 'warning');
            return;
        }

        this.showMessage('Manual bulk processing - please process each payment individually for now', 'info');
    }

    async exportPaidPayments() {
        const month = prompt('Enter month to export (YYYY-MM):', new Date().toISOString().substr(0, 7));
        if (month) {
            window.open(`api/payment_reports.php?action=export_payments&month=${month}`, '_blank');
        }
    }

    showPaymentReports() {
        this.showMessage('Payment reports functionality coming soon', 'info');
    }

    showPaymentSettings() {
        // Open Paystack setup page
        window.open('paystack_setup.html', '_blank');
    }

    processBulkPayments() {
        this.showMessage('Use the new bulk processing buttons in the approved payments tab', 'info');
    }

    showCreateBatchModal() {
        // Get all approved payments first
        fetch('api/payment_processing.php?action=pending_payments')
            .then(response => response.json())
            .then(payments => {
                const approvedPayments = payments.filter(p => p.payment_status === 'Approved');

                if (approvedPayments.length === 0) {
                    this.showMessage('No approved payments available for batch processing', 'warning');
                    return;
                }

                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-collection me-2"></i>Create Payment Batch
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="create-batch-form">
                                    <div class="mb-3">
                                        <label class="form-label">Batch Name</label>
                                        <input type="text" class="form-control" name="batch_name"
                                               value="Payment Batch ${new Date().toLocaleDateString()}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="2"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Select Payments</label>
                                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            ${approvedPayments.map(payment => `
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="${payment.id}"
                                                           id="payment-${payment.id}" name="payment_ids">
                                                    <label class="form-check-label" for="payment-${payment.id}">
                                                        <strong>${payment.full_name}</strong> (${payment.instrument}) -
                                                        $${parseFloat(payment.amount).toFixed(2)} -
                                                        ${payment.service_type} ${new Date(payment.service_date).toLocaleDateString()}
                                                    </label>
                                                </div>
                                            `).join('')}
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="app.selectAllPayments(true)">
                                                Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="app.selectAllPayments(false)">
                                                Clear All
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="app.submitCreateBatch()">
                                    <i class="bi bi-collection me-2"></i>Create Batch
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();

                modal.addEventListener('hidden.bs.modal', () => {
                    document.body.removeChild(modal);
                });
            });
    }

    selectAllPayments(select) {
        const checkboxes = document.querySelectorAll('input[name="payment_ids"]');
        checkboxes.forEach(cb => cb.checked = select);
    }

    async submitCreateBatch() {
        const form = document.getElementById('create-batch-form');
        const formData = new FormData(form);

        const selectedPayments = Array.from(document.querySelectorAll('input[name="payment_ids"]:checked'))
            .map(cb => parseInt(cb.value));

        if (selectedPayments.length === 0) {
            this.showMessage('Please select at least one payment', 'warning');
            return;
        }

        const data = {
            batch_name: formData.get('batch_name'),
            notes: formData.get('notes'),
            payment_ids: selectedPayments,
            created_by: 'Admin'
        };

        try {
            const response = await fetch('api/payment_processing.php?action=create_batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Batch created with ${result.payment_count} payments totaling $${result.total_amount}`, 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
                await this.loadPaymentData();
            } else {
                this.showMessage(result.error || 'Failed to create batch', 'error');
            }

        } catch (error) {
            this.showMessage('Error creating batch', 'error');
        }
    }

    // Modal functions
    showAddMemberModal() {
        console.log('showAddMemberModal called');
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>Add New Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="add-member-form">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="app.submitAddMember()">
                            <i class="bi bi-person-plus me-2"></i>Add Member
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async submitAddMember() {
        // Prevent multiple rapid clicks
        if (this.isSubmittingMember) {
            return;
        }
        this.isSubmittingMember = true;

        const form = document.getElementById('add-member-form');
        const formData = new FormData(form);

        const data = {
            full_name: formData.get('full_name'),
            phone: formData.get('phone'),
            email: formData.get('email'),
            gender: formData.get('gender'),
            date_of_birth: formData.get('date_of_birth'),
            address: formData.get('address')
        };

        // Validate required fields
        if (!data.full_name || !data.full_name.trim()) {
            this.showMessage('Please enter the member\'s full name', 'error');
            return;
        }

        if (!data.gender) {
            this.showMessage('Please select the member\'s gender', 'error');
            return;
        }

        try {
            const response = await fetch('api/save_members.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                this.showMessage(`API Error: ${response.status} ${response.statusText}`, 'error');
                return;
            }

            const result = await response.json();

            if (result.success) {
                // Show success message using API message or fallback
                const successMessage = result.message || `Member "${data.full_name}" added successfully`;
                this.showMessage(`✅ ${successMessage}`, 'success');

                // Hide modal after showing success message
                const modal = document.querySelector('.modal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                // Small delay to ensure modal is properly hidden
                setTimeout(async () => {
                    // Update members data and UI dynamically
                    await this.loadMembers();

                    // Always update UI if we're on the members section
                    if (this.currentSection === 'members') {
                        await this.renderMembersList();
                        this.updateMemberStats();
                    }

                    // Always update dashboard stats
                    this.updateDashboardStats();

                    // Reset the submission flag
                    this.isSubmittingMember = false;
                }, 100);
            } else {
                this.showMessage(result.error || 'Failed to add member', 'error');
                this.isSubmittingMember = false;
            }

        } catch (error) {
            console.error('Error adding member:', error);
            this.showMessage('Error adding member: ' + error.message, 'error');
        } finally {
            // Reset the submission flag
            this.isSubmittingMember = false;
        }
    }

    async editMember(id) {
        try {
            // Find the member data
            const member = this.members.find(m => m.id == id);
            if (!member) {
                this.showMessage('Member not found', 'error');
                return;
            }

            // Show edit modal
            this.showEditMemberModal(member);
        } catch (error) {
            console.error('Error editing member:', error);
            this.showMessage('Error loading member data', 'error');
        }
    }

    async deleteMember(id) {
        try {
            // Find the member data
            const member = this.members.find(m => m.id == id);
            if (!member) {
                this.showMessage('Member not found', 'error');
                return;
            }

            // Show confirmation dialog
            const confirmed = confirm(`Are you sure you want to delete "${member.full_name}"?\n\nThis action cannot be undone.`);
            if (!confirmed) return;

            // Delete the member
            const response = await fetch('api/delete_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Member "${member.full_name}" deleted successfully`, 'success');

                // Update members data and UI dynamically
                await this.loadMembers();
                if (this.currentSection === 'members') {
                    await this.renderMembersList();
                    this.updateMemberStats();
                }

                // Also update dashboard stats if we're on dashboard
                if (this.currentSection === 'dashboard') {
                    this.updateDashboardStats();
                }
            } else {
                this.showMessage(result.error || 'Failed to delete member', 'error');
            }
        } catch (error) {
            console.error('Error deleting member:', error);
            this.showMessage('Error deleting member', 'error');
        }
    }

    showEditMemberModal(member) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square me-2"></i>Edit Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="edit-member-form">
                            <input type="hidden" id="edit-member-id" value="${member.id}">

                            <div class="mb-3">
                                <label for="edit-full-name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit-full-name" value="${member.full_name}" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit-phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="edit-phone" value="${member.phone || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit-gender" class="form-label">Gender *</label>
                                    <select class="form-select" id="edit-gender" required>
                                        <option value="Male" ${member.gender === 'Male' ? 'selected' : ''}>Male</option>
                                        <option value="Female" ${member.gender === 'Female' ? 'selected' : ''}>Female</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="edit-email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="edit-email" value="${member.email || ''}">
                            </div>

                            <div class="mb-3">
                                <label for="edit-address" class="form-label">Address</label>
                                <textarea class="form-control" id="edit-address" rows="2">${member.address || ''}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit-date-of-birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="edit-date-of-birth" value="${member.date_of_birth || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit-occupation" class="form-label">Occupation</label>
                                    <input type="text" class="form-control" id="edit-occupation" value="${member.occupation || ''}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="app.submitEditMember()">
                            <i class="bi bi-check-lg me-1"></i>Update Member
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async submitEditMember() {
        try {
            const id = document.getElementById('edit-member-id').value;
            const fullName = document.getElementById('edit-full-name').value.trim();
            const phone = document.getElementById('edit-phone').value.trim();
            const gender = document.getElementById('edit-gender').value;
            const email = document.getElementById('edit-email').value.trim();
            const address = document.getElementById('edit-address').value.trim();
            const dateOfBirth = document.getElementById('edit-date-of-birth').value;
            const occupation = document.getElementById('edit-occupation').value.trim();

            if (!fullName) {
                this.showMessage('Please enter the member\'s full name', 'error');
                return;
            }

            const memberData = {
                id: parseInt(id),
                full_name: fullName,
                phone: phone || null,
                gender: gender,
                email: email || null,
                address: address || null,
                date_of_birth: dateOfBirth || null,
                occupation: occupation || null
            };

            const response = await fetch('api/update_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(memberData)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Member updated successfully', 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();

                // Update members data and UI dynamically
                await this.loadMembers();
                if (this.currentSection === 'members') {
                    await this.renderMembersList();
                    this.updateMemberStats();
                }

                // Also update dashboard if we're there
                if (this.currentSection === 'dashboard') {
                    this.updateDashboardStats();
                }
            } else {
                this.showMessage(result.error || 'Failed to update member', 'error');
            }
        } catch (error) {
            console.error('Error updating member:', error);
            this.showMessage('Error updating member', 'error');
        }
    }

    // Add global registerFingerprint function
    registerFingerprint(memberId, memberName) {
        // For now, simulate fingerprint registration
        const confirmed = confirm(`Register fingerprint for "${memberName}"?\n\nNote: This is a simulation. In a real system, this would connect to a fingerprint scanner.`);

        if (confirmed) {
            // Simulate successful fingerprint registration
            this.simulateFingerprintRegistration(memberId, memberName);
        }
    }

    async simulateFingerprintRegistration(memberId, memberName) {
        try {
            // Update the member's has_fingerprint status
            const response = await fetch('api/update_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: memberId,
                    has_fingerprint: true
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`✅ Fingerprint registered successfully for ${memberName}`, 'success');

                // Reload members if we're on the members page
                if (this.currentSection === 'members') {
                    await this.loadMembers();
                    await this.renderMembersList();
                    this.updateMemberStats();
                }
            } else {
                this.showMessage(`❌ Failed to register fingerprint: ${result.error}`, 'error');
            }
        } catch (error) {
            console.error('Error registering fingerprint:', error);
            this.showMessage('Error registering fingerprint', 'error');
        }
    }

    exportMembers() {
        this.showMessage('Export members functionality coming soon', 'info');
    }

    editAttendance(id) {
        this.showMessage('Edit attendance functionality coming soon', 'info');
    }

    removeAttendance(id) {
        if (confirm('Are you sure you want to remove this attendance record?')) {
            this.showMessage('Remove attendance functionality coming soon', 'info');
        }
    }

    exportAttendance() {
        const dateInput = document.getElementById('attendance-date');
        const serviceTypeInput = document.getElementById('attendance-service-type');

        if (!dateInput || !dateInput.value) {
            this.showMessage('Please select a date first', 'error');
            return;
        }

        const date = dateInput.value;
        const serviceType = serviceTypeInput?.value || '';

        // Get attendance data from the current div structure (not table)
        const attendanceContainer = document.getElementById('attendance-list');
        const attendanceRows = attendanceContainer.querySelectorAll('.row.align-items-center.border-bottom');

        if (!attendanceRows || attendanceRows.length === 0) {
            this.showMessage('No attendance data to export', 'warning');
            return;
        }

        // Extract data from div structure
        const attendanceData = [];
        attendanceRows.forEach(row => {
            const memberNameElement = row.querySelector('.fw-bold');
            const serviceTypeElement = row.querySelector('.text-muted');
            const checkInTimeElement = row.querySelector('.col-md-3 div:last-child');

            if (memberNameElement && serviceTypeElement && checkInTimeElement) {
                attendanceData.push({
                    member_name: memberNameElement.textContent.trim(),
                    service_type: serviceTypeElement.textContent.trim(),
                    service_date: date,
                    check_in_time: checkInTimeElement.textContent.trim()
                });
            }
        });

        if (attendanceData.length === 0) {
            this.showMessage('No attendance data to export', 'warning');
            return;
        }

        // Export to CSV
        this.exportAttendanceToCSV(attendanceData, date, serviceType);

        // Ask user if they want to clear the attendance after export
        if (confirm(`Attendance exported successfully!\n\nWould you like to clear the attendance records for ${date}?\n\nThis action cannot be undone.`)) {
            this.clearAttendanceData(date, serviceType);
        }
    }

    exportAttendanceToCSV(attendanceData, date, serviceType) {
        // Create CSV content
        const headers = ['Date', 'Service Type', 'Member Name', 'Check-in Time'];
        const csvContent = [
            headers.join(','),
            ...attendanceData.map(record => [
                record.service_date || date,
                record.service_type || serviceType || 'All Services',
                `"${record.member_name}"`,
                `"${record.check_in_time || 'N/A'}"`
            ].join(','))
        ].join('\n');

        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const filename = `attendance_${date}${serviceType ? '_' + serviceType.replace(/\s+/g, '_') : ''}.csv`;

        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.showMessage(`Attendance exported successfully as ${filename}`, 'success');
        } else {
            this.showMessage('Export not supported in this browser', 'error');
        }
    }

    showClearAttendanceConfirmation(date, serviceType) {
        const serviceText = serviceType ? ` for ${serviceType}` : '';
        const message = `Attendance data has been exported successfully.\n\nWould you like to clear the attendance records for ${date}${serviceText}?\n\nThis action cannot be undone.`;

        if (confirm(message)) {
            this.clearAttendanceData(date, serviceType);
        }
    }

    async clearAttendanceData(date, serviceType) {
        try {
            this.showMessage('Clearing attendance data...', 'info');

            const clearData = {
                date: date,
                service_type: serviceType || null,
                action: 'clear'
            };

            const response = await fetch('api/clear_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(clearData)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Successfully cleared ${result.cleared_count} attendance records`, 'success');
                // Reload attendance data
                this.loadAttendanceData();
            } else {
                this.showMessage('Failed to clear attendance data', 'error');
            }

        } catch (error) {
            this.showMessage('Error clearing attendance data', 'error');
        }
    }

    async clearOfferingsData(date) {
        try {
            this.showMessage('Clearing offerings data...', 'info');

            const clearData = {
                date: date,
                action: 'clear'
            };

            const response = await fetch('api/clear_offerings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(clearData)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Successfully cleared ${result.cleared_count} offering records`, 'success');
                // Reload offerings data
                this.loadOfferingsData();
            } else {
                this.showMessage('Failed to clear offerings data', 'error');
            }

        } catch (error) {
            this.showMessage('Error clearing offerings data', 'error');
        }
    }

    exportOfferings() {
        // Get today's date
        const today = new Date().toISOString().split('T')[0];

        // Get offerings data from the current table
        const offeringsTable = document.querySelector('#offerings-list table tbody');
        if (!offeringsTable || offeringsTable.children.length === 0) {
            this.showMessage('No offerings data to export', 'warning');
            return;
        }

        // Extract data from table
        const offeringsData = [];
        Array.from(offeringsTable.children).forEach(row => {
            const cells = row.children;
            if (cells.length >= 5) {
                offeringsData.push({
                    time: cells[0].textContent.trim(),
                    service_type: cells[1].textContent.trim(),
                    offering_type: cells[2].textContent.trim(),
                    amount: cells[3].textContent.trim(),
                    notes: cells[4].textContent.trim()
                });
            }
        });

        if (offeringsData.length === 0) {
            this.showMessage('No offerings data to export', 'warning');
            return;
        }

        // Export to CSV
        this.exportOfferingsToCSV(offeringsData, today);

        // Ask user if they want to clear the offerings after export
        if (confirm(`Offerings exported successfully!\n\nWould you like to clear the offering records for ${today}?\n\nThis action cannot be undone.`)) {
            this.clearOfferingsData(today);
        }
    }

    exportOfferingsToCSV(offeringsData, date) {
        // Create CSV content
        const headers = ['Date', 'Time', 'Service Type', 'Offering Type', 'Amount', 'Notes'];
        const csvContent = [
            headers.join(','),
            ...offeringsData.map(record => [
                date,
                `"${record.time}"`,
                `"${record.service_type}"`,
                `"${record.offering_type}"`,
                record.amount,
                `"${record.notes}"`
            ].join(','))
        ].join('\n');

        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const filename = `offerings_${date}.csv`;

        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.showMessage(`Offerings exported successfully as ${filename}`, 'success');
        } else {
            this.showMessage('Export not supported in this browser', 'error');
        }
    }

    showAdminProfile() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-circle me-2"></i>Admin Profile
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="admin-profile-content">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading profile...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });

        // Load profile data
        this.loadAdminProfile();
    }

    async loadAdminProfile() {
        try {
            const response = await fetch('api/auth.php?action=profile');
            const profile = await response.json();

            if (response.ok) {
                const content = document.getElementById('admin-profile-content');
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>${profile.full_name}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${profile.email}</td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><span class="badge bg-primary">${profile.role}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Login:</strong></td>
                                    <td>${profile.last_login ? new Date(profile.last_login).toLocaleString() : 'Never'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Member Since:</strong></td>
                                    <td>${new Date(profile.created_at).toLocaleDateString()}</td>
                                </tr>
                                <tr>
                                    <td><strong>Active Sessions:</strong></td>
                                    <td>${profile.active_sessions}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('admin-profile-content').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error loading profile: ${profile.error}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('admin-profile-content').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-wifi-off me-2"></i>
                    Failed to load profile data
                </div>
            `;
        }
    }

    showRecordAttendanceModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-calendar-check me-2"></i>Record Attendance
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="record-attendance-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Service Date</label>
                                    <input type="date" class="form-control" name="service_date" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Type</label>
                                    <select class="form-select" name="service_type" required>
                                        <option value="Sunday Morning">Sunday Morning</option>
                                        <option value="Sunday Evening">Sunday Evening</option>
                                        <option value="Wednesday">Wednesday Service</option>
                                        <option value="Friday">Friday Service</option>
                                        <option value="Special Event">Special Event</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Members Present</label>
                                <div id="members-checklist" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    Loading members...
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="app.submitRecordAttendance()">
                            <i class="bi bi-check-circle me-2"></i>Record Attendance
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Load members checklist
        this.loadMembersChecklist();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async loadMembersChecklist() {
        const container = document.getElementById('members-checklist');

        if (this.members.length === 0) {
            await this.loadMembers();
        }

        const checklistHtml = this.members.map(member => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="${member.id}" id="member-${member.id}">
                <label class="form-check-label" for="member-${member.id}">
                    ${member.full_name}
                </label>
            </div>
        `).join('');

        container.innerHTML = checklistHtml;
    }

    async submitRecordAttendance() {
        const form = document.getElementById('record-attendance-form');
        const formData = new FormData(form);

        const selectedMembers = Array.from(document.querySelectorAll('#members-checklist input:checked'))
            .map(cb => parseInt(cb.value));

        if (selectedMembers.length === 0) {
            this.showMessage('Please select at least one member', 'warning');
            return;
        }

        const data = {
            service_date: formData.get('service_date'),
            service_type: formData.get('service_type'),
            attendees: selectedMembers,
            check_in_method: 'Manual'
        };

        try {
            const response = await fetch('api/save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(`Attendance recorded for ${selectedMembers.length} members`, 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();

                // Update attendance data dynamically
                if (this.currentSection === 'attendance') {
                    this.loadAttendanceData();
                }

                // Update dashboard stats if we're on dashboard
                if (this.currentSection === 'dashboard') {
                    this.updateDashboardStats();
                }
            } else {
                this.showMessage(result.error || 'Failed to record attendance', 'error');
            }

        } catch (error) {
            this.showMessage('Error recording attendance', 'error');
        }
    }

    showRecordOfferingModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-cash-coin me-2"></i>Record Offering
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="record-offering-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Service Date</label>
                                    <input type="date" class="form-control" name="service_date" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Type</label>
                                    <select class="form-select" name="service_type" required>
                                        <option value="Sunday Morning">Sunday Morning</option>
                                        <option value="Sunday Evening">Sunday Evening</option>
                                        <option value="Wednesday">Wednesday Service</option>
                                        <option value="Friday">Friday Service</option>
                                        <option value="Special Event">Special Event</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Offering Type</label>
                                <select class="form-select" name="offering_type_id" required>
                                    <option value="">Select offering type</option>
                                    <option value="1">Tithe</option>
                                    <option value="2">Thanksgiving</option>
                                    <option value="3">Seed Offering</option>
                                    <option value="4">Building Fund</option>
                                    <option value="5">Mission</option>
                                    <option value="6">Special Collection</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="app.submitRecordOffering()">
                            <i class="bi bi-cash-coin me-2"></i>Record Offering
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async submitRecordOffering() {
        const form = document.getElementById('offeringForm');
        const formData = new FormData(form);

        const data = {
            offering_date: formData.get('service_date'),
            service_type: formData.get('service_type'),
            offering_type: formData.get('offering_type_id'),
            amount: parseFloat(formData.get('amount')),
            notes: formData.get('notes')
        };

        try {
            const response = await fetch('api/save_offering.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Offering recorded successfully', 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();

                // Update offerings data dynamically
                if (this.currentSection === 'offerings') {
                    this.loadOfferingsData();
                }

                // Update dashboard stats if we're on dashboard
                if (this.currentSection === 'dashboard') {
                    this.updateDashboardStats();
                }
            } else {
                this.showMessage(result.error || 'Failed to record offering', 'error');
            }

        } catch (error) {
            this.showMessage('Error recording offering', 'error');
        }
    }

    showAddInstrumentalistModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>Add Instrumentalist
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="add-instrumentalist-form">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instrument *</label>
                                <input type="text" class="form-control" name="instrument" required placeholder="e.g., Piano, Guitar, Drums">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Skill Level</label>
                                <select class="form-select" name="skill_level">
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate" selected>Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                    <option value="Professional">Professional</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Hourly Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">GH₵</span>
                                        <input type="number" class="form-control" name="hourly_rate" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Per Service Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">GH₵</span>
                                        <input type="number" class="form-control" name="per_service_rate" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information Section -->
                            <div class="mb-3 mt-4">
                                <h6 class="text-primary"><i class="bi bi-credit-card me-2"></i>Payment Information</h6>
                                <hr class="mt-1 mb-3">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Preferred Payment Method</label>
                                <select class="form-select" name="preferred_payment_method" onchange="app.togglePaymentFields(this.value)">
                                    <option value="Mobile Money" selected>Mobile Money (MoMo)</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash">Cash</option>
                                </select>
                            </div>

                            <!-- Mobile Money Fields -->
                            <div id="momo-fields">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">MoMo Provider *</label>
                                        <select class="form-select" name="momo_provider" required>
                                            <option value="">Select Provider</option>
                                            <option value="MTN">MTN Mobile Money</option>
                                            <option value="Vodafone">Vodafone Cash</option>
                                            <option value="AirtelTigo">AirtelTigo Money</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">MoMo Number *</label>
                                        <input type="tel" class="form-control" name="momo_number" placeholder="e.g., 0241234567" required>
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label class="form-label">Name on MoMo Account *</label>
                                    <input type="text" class="form-control" name="momo_name" placeholder="Full name as registered with MoMo" required>
                                    <div class="form-text">This must match exactly with your MoMo account name</div>
                                </div>
                            </div>

                            <!-- Bank Transfer Fields -->
                            <div id="bank-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Bank Name</label>
                                        <input type="text" class="form-control" name="bank_name" placeholder="e.g., GCB Bank">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Account Number</label>
                                        <input type="text" class="form-control" name="bank_account_number" placeholder="Account number">
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label class="form-label">Account Name</label>
                                    <input type="text" class="form-control" name="bank_account_name" placeholder="Full name on bank account">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="app.submitAddInstrumentalist()">
                            <i class="bi bi-person-plus me-2"></i>Add Instrumentalist
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    togglePaymentFields(paymentMethod) {
        const momoFields = document.getElementById('momo-fields');
        const bankFields = document.getElementById('bank-fields');

        if (paymentMethod === 'Mobile Money') {
            momoFields.style.display = 'block';
            bankFields.style.display = 'none';
            // Make MoMo fields required
            momoFields.querySelectorAll('input[required], select[required]').forEach(field => {
                field.required = true;
            });
            // Make bank fields optional
            bankFields.querySelectorAll('input, select').forEach(field => {
                field.required = false;
            });
        } else if (paymentMethod === 'Bank Transfer') {
            momoFields.style.display = 'none';
            bankFields.style.display = 'block';
            // Make MoMo fields optional
            momoFields.querySelectorAll('input, select').forEach(field => {
                field.required = false;
            });
            // Bank fields are optional by default
        } else {
            // Cash - hide both
            momoFields.style.display = 'none';
            bankFields.style.display = 'none';
            // Make all payment fields optional
            momoFields.querySelectorAll('input, select').forEach(field => {
                field.required = false;
            });
            bankFields.querySelectorAll('input, select').forEach(field => {
                field.required = false;
            });
        }
    }

    async submitAddInstrumentalist() {
        const form = document.getElementById('add-instrumentalist-form');
        const formData = new FormData(form);

        const data = {
            full_name: formData.get('full_name'),
            phone: formData.get('phone'),
            email: formData.get('email'),
            instrument: formData.get('instrument'),
            skill_level: formData.get('skill_level'),
            hourly_rate: formData.get('hourly_rate'),
            per_service_rate: formData.get('per_service_rate'),
            preferred_payment_method: formData.get('preferred_payment_method'),
            momo_provider: formData.get('momo_provider'),
            momo_number: formData.get('momo_number'),
            momo_name: formData.get('momo_name'),
            bank_name: formData.get('bank_name'),
            bank_account_number: formData.get('bank_account_number'),
            bank_account_name: formData.get('bank_account_name'),
            notes: formData.get('notes')
        };

        try {
            const response = await fetch('api/instrumentalists.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Instrumentalist added successfully', 'success');
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();

                // Update instrumentalists data dynamically
                await this.loadInstrumentalists();

                // Update payment section if we're there
                if (this.currentSection === 'payments') {
                    await this.loadSimplePaymentData();
                }
            } else {
                this.showMessage(result.error || 'Failed to add instrumentalist', 'error');
            }

        } catch (error) {
            this.showMessage('Error adding instrumentalist', 'error');
        }
    }
}

// Initialize the application
const app = new ChurchApp();

// Global utility functions
window.showSection = (section) => app.showSection(section);
window.showAdminProfile = () => app.showAdminProfile();

// Wait for app to be initialized before binding global functions
document.addEventListener('DOMContentLoaded', function() {
    // Give app time to initialize
    setTimeout(() => {
        // Member management functions
        window.showAddMemberModal = () => {
            console.log('showAddMemberModal called');
            if (typeof app !== 'undefined' && app.showAddMemberModal) {
                app.showAddMemberModal();
            } else {
                console.error('app.showAddMemberModal not available');
                alert('Member modal function not available. Please refresh the page.');
            }
        };

        window.editMember = (id) => {
            if (typeof app !== 'undefined' && app.editMember) {
                app.editMember(id);
            } else {
                console.error('app.editMember not available');
            }
        };

        window.registerFingerprint = (id) => {
            // Get member name first
            if (typeof app !== 'undefined' && app.members) {
                const member = app.members.find(m => m.id == id);
                if (member) {
                    registerFingerprint(id, member.full_name);
                } else {
                    app.showMessage('Member not found', 'error');
                }
            } else {
                console.error('app not available for fingerprint registration');
            }
        };

        // Attendance functions
        window.showRecordAttendanceModal = () => {
            console.log('showRecordAttendanceModal called');
            if (typeof app !== 'undefined' && app.showRecordAttendanceModal) {
                app.showRecordAttendanceModal();
            } else {
                console.error('app.showRecordAttendanceModal not available');
                alert('Attendance modal function not available. Please refresh the page.');
            }
        };

        // Offerings functions
        window.showRecordOfferingModal = () => {
            console.log('showRecordOfferingModal called');
            if (typeof app !== 'undefined' && app.showRecordOfferingModal) {
                app.showRecordOfferingModal();
            } else {
                console.error('app.showRecordOfferingModal not available');
                alert('Offering modal function not available. Please refresh the page.');
            }
        };

        // Instrumentalist functions
        window.showAddInstrumentalistModal = () => {
            console.log('showAddInstrumentalistModal called');
            if (typeof app !== 'undefined' && app.showAddInstrumentalistModal) {
                app.showAddInstrumentalistModal();
            } else {
                console.error('app.showAddInstrumentalistModal not available');
                alert('Instrumentalist modal function not available. Please refresh the page.');
            }
        };

        // Payment functions
        window.showCalculatePaymentsModal = () => {
            console.log('showCalculatePaymentsModal called');
            if (typeof app !== 'undefined' && app.showCalculatePaymentsModal) {
                app.showCalculatePaymentsModal();
            } else {
                console.error('app.showCalculatePaymentsModal not available');
            }
        };

        window.showCreateBatchModal = () => {
            console.log('showCreateBatchModal called');
            if (typeof app !== 'undefined' && app.showCreateBatchModal) {
                app.showCreateBatchModal();
            } else {
                console.error('app.showCreateBatchModal not available');
            }
        };

        console.log('All global functions bound successfully');
    }, 500); // Wait 500ms for app to initialize
});
