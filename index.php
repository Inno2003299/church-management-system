<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Inventory Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#dashboard">
                <i class="bi bi-house-heart-fill me-2"></i>
                Church Management
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard" data-section="dashboard">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#members" data-section="members">
                            <i class="bi bi-people me-1"></i>Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#attendance" data-section="attendance">
                            <i class="bi bi-calendar-check me-1"></i>Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#offerings" data-section="offerings">
                            <i class="bi bi-cash-coin me-1"></i>Offerings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#instrumentalists" data-section="instrumentalists">
                            <i class="bi bi-music-note-beamed me-1"></i>Instrumentalists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#payments" data-section="payments">
                            <i class="bi bi-credit-card me-1"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#checkin" data-section="checkin">
                            <i class="bi bi-fingerprint me-1"></i>Check-in
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <span class="navbar-text me-3" id="current-date"></span>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" id="admin-dropdown">
                            <i class="bi bi-person-circle me-1"></i><span id="admin-name">Admin</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header" id="admin-email">admin@example.com</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="showAdminProfile()">
                                <i class="bi bi-person me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="setup_admin.php">
                                <i class="bi bi-gear me-2"></i>Admin Setup
                            </a></li>
                            <li><a class="dropdown-item" href="test_db_connection.php">
                                <i class="bi bi-database me-2"></i>Database Test
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid" style="margin-top: 76px;">
        <!-- Message Container for Enhanced Notifications -->
        <div id="message-container" class="position-fixed" style="top: 90px; right: 20px; z-index: 1060; max-width: 400px;"></div>

        <div class="row">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
                        <div class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i>
                            <span id="current-date"></span>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="total-members">0</h4>
                                            <p class="card-text">Total Members</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-people fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="today-attendance">0</h4>
                                            <p class="card-text">Today's Attendance</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-calendar-check fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="today-offerings">$0</h4>
                                            <p class="card-text">Today's Offerings</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-cash-coin fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="active-instrumentalists">0</h4>
                                            <p class="card-text">Active Instrumentalists</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-music-note-beamed fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-lightning-fill me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" onclick="showAddMemberModal()">
                                            <i class="bi bi-person-plus me-2"></i>Add New Member
                                        </button>
                                        <button class="btn btn-success" onclick="showRecordAttendanceModal()">
                                            <i class="bi bi-calendar-check me-2"></i>Record Attendance
                                        </button>
                                        <button class="btn btn-warning" onclick="openOfferingModal()">
                                            <i class="bi bi-cash-coin me-2"></i>Record Offerings
                                        </button>
                                        <button class="btn btn-info" onclick="showSection('payments')">
                                            <i class="bi bi-credit-card me-2"></i>Process Payments
                                        </button>
                                        <button class="btn btn-secondary" onclick="showSection('checkin')">
                                            <i class="bi bi-fingerprint me-2"></i>Fingerprint Check-in
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recent-activity">
                                        <p class="text-muted">Loading recent activity...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Other sections will be loaded dynamically -->
            <div id="members-section" class="content-section d-none"></div>
            <div id="attendance-section" class="content-section d-none"></div>
            <div id="offerings-section" class="content-section d-none"></div>
            <div id="instrumentalists-section" class="content-section d-none"></div>
            <div id="payments-section" class="content-section d-none"></div>
            <div id="checkin-section" class="content-section d-none"></div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Record Offering Modal -->
    <div class="modal fade" id="recordOfferingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin me-2"></i>Record Offering
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="offeringForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Offering Date</label>
                                    <input type="date" class="form-control" name="offering_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Service Type</label>
                                    <select class="form-select" name="service_type" required>
                                        <option value="">Select service type</option>
                                        <option value="Sunday Morning">Sunday Morning</option>
                                        <option value="Sunday Evening">Sunday Evening</option>
                                        <option value="Wednesday Prayer">Wednesday Prayer</option>
                                        <option value="Friday Service">Friday Service</option>
                                        <option value="Special Service">Special Service</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Offering Type</label>
                                    <select class="form-select" name="offering_type" required>
                                        <option value="">Select offering type</option>
                                        <option value="Tithe">Tithe</option>
                                        <option value="Thanksgiving">Thanksgiving</option>
                                        <option value="Seed Offering">Seed Offering</option>
                                        <option value="Building Fund">Building Fund</option>
                                        <option value="Mission">Mission</option>
                                        <option value="Special Collection">Special Collection</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Any additional notes about the offering..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitOffering()">
                        <i class="bi bi-check-circle me-2"></i>Record Offering
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/webauthn.js?v=<?php echo time(); ?>"></script>

    <!-- Error handling for CSS selector issues -->
    <script>
        // Suppress Bootstrap CSS selector warnings
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('not(:disabled)')) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    <!-- Fix for Record Attendance Button -->
    <script>
        // Ensure showRecordAttendanceModal function exists
        if (typeof window.showRecordAttendanceModal === 'undefined') {
            console.log('Creating showRecordAttendanceModal function...');

            window.showRecordAttendanceModal = function() {
                console.log('showRecordAttendanceModal called');

                // Simple working implementation - open the working attendance page
                window.open('fix_record_attendance.html', '_blank');
            };

            console.log('showRecordAttendanceModal function created successfully');
        }

        // Also ensure other modal functions exist
        if (typeof window.showAddMemberModal === 'undefined') {
            window.showAddMemberModal = function() {
                const name = prompt('Enter member name:');
                if (!name) return;

                const gender = prompt('Enter gender (Male/Female):');
                if (!gender) return;

                const phone = prompt('Enter phone number (optional):') || '';
                const email = prompt('Enter email (optional):') || '';

                // Submit member data
                fetch('api/save_members.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        full_name: name,
                        gender: gender,
                        phone: phone,
                        email: email
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success || result.id) {
                        alert('Member added successfully!');
                        location.reload(); // Refresh to update the display
                    } else {
                        alert('Error: ' + (result.error || 'Failed to add member'));
                    }
                })
                .catch(error => {
                    alert('Network error: ' + error.message);
                });
            };
        }

        // Force override the offering modal function
        window.showRecordOfferingModal = function() {
            console.log('Our showRecordOfferingModal called');

            // Set today's date
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('[name="offering_date"]').value = today;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('recordOfferingModal'));
            modal.show();
        };

        // Offering submission function
        window.submitOffering = async function() {
            console.log('Submitting offering...');

            const form = document.getElementById('offeringForm');
            const formData = new FormData(form);

            // Get form values using FormData
            const offeringDate = formData.get('offering_date');
            const serviceType = formData.get('service_type');
            const offeringType = formData.get('offering_type');
            const amount = formData.get('amount');
            const notes = formData.get('notes');

            // Validate required fields
            if (!offeringDate) {
                alert('Please select an offering date');
                return;
            }

            if (!serviceType) {
                alert('Please select a service type');
                return;
            }

            if (!offeringType) {
                alert('Please select an offering type');
                return;
            }

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            const offeringData = {
                offering_date: offeringDate,
                service_type: serviceType,
                offering_type: offeringType,
                amount: parseFloat(amount),
                notes: notes
            };

            try {
                console.log('Submitting offering data:', offeringData);
                console.log('JSON string being sent:', JSON.stringify(offeringData));

                const response = await fetch('api/save_offering.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(offeringData)
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                const responseText = await response.text();
                console.log('Raw response text:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}. Response: ${responseText}`);
                }

                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('Parsed API response:', result);
                } catch (parseError) {
                    throw new Error(`Invalid JSON response: ${parseError.message}. Response: ${responseText}`);
                }

                if (result.success) {
                    alert(`Offering recorded successfully!\n\nType: ${offeringType}\nAmount: $${amount}\nDate: ${offeringDate}`);

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('recordOfferingModal'));
                    modal.hide();

                    // Reset form
                    form.reset();
                } else {
                    throw new Error(result.error || 'Failed to record offering');
                }

            } catch (error) {
                console.error('Offering submission error:', error);
                alert('Error recording offering: ' + error.message);
            }
        };



        // Add fingerprint registration function
        window.registerFingerprint = function(memberId, memberName) {
            console.log('Registering fingerprint for:', memberName, 'ID:', memberId);

            // Show confirmation dialog
            const confirmed = confirm(`Register fingerprint for "${memberName}"?\n\nNote: This is a simulation. In a real system, this would connect to a fingerprint scanner.`);

            if (confirmed) {
                // Simulate fingerprint registration
                simulateFingerprintRegistration(memberId, memberName);
            }
        };

        // Simulate fingerprint registration
        async function simulateFingerprintRegistration(memberId, memberName) {
            try {
                // Update the member's has_fingerprint status
                const response = await fetch('api/update_member.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: memberId,
                        has_fingerprint: 1 // Set to true
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Fingerprint registered successfully for ${memberName}!`);

                    // Update the member list dynamically without page refresh
                    if (typeof app !== 'undefined' && app.currentSection === 'members') {
                        await app.loadMembers();
                        await app.renderMembersList();
                        app.updateMemberStats();
                    }
                } else {
                    alert(`❌ Failed to register fingerprint: ${result.error}`);
                }
            } catch (error) {
                console.error('Error registering fingerprint:', error);
                alert('Error registering fingerprint');
            }
        }

        console.log('All modal functions ensured');

        // Simple offering modal function
        window.openOfferingModal = function() {
            console.log('Opening our offering modal directly');

            // Set today's date
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('[name="offering_date"]').value = today;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('recordOfferingModal'));
            modal.show();
        };

        // Override the offering modal function AFTER everything loads
        setTimeout(() => {
            window.showRecordOfferingModal = function() {
                console.log('Our FINAL overridden showRecordOfferingModal called');

                // Set today's date
                const today = new Date().toISOString().split('T')[0];
                document.querySelector('[name="offering_date"]').value = today;

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('recordOfferingModal'));
                modal.show();
            };
            console.log('Offering modal function FINALLY overridden');
        }, 500);
    </script>
</body>
</html>
