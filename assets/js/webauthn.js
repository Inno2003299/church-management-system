// WebAuthn Fingerprint Authentication
class WebAuthnManager {
    constructor() {
        this.isSupported = this.checkSupport();
        this.registeredCredentials = new Map();
    }
    
    checkSupport() {
        return !!(navigator.credentials && navigator.credentials.create && navigator.credentials.get && window.PublicKeyCredential);
    }
    
    // Convert ArrayBuffer to Base64
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
    
    // Convert Base64 to ArrayBuffer
    base64ToArrayBuffer(base64) {
        const binary = window.atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }
    
    // Generate random challenge
    generateChallenge() {
        const array = new Uint8Array(32);
        window.crypto.getRandomValues(array);
        return array;
    }
    
    // Register fingerprint for a member
    async registerFingerprint(memberId, memberName) {
        if (!this.isSupported) {
            throw new Error('WebAuthn is not supported in this browser');
        }
        
        try {
            const challenge = this.generateChallenge();
            const userId = new TextEncoder().encode(memberId.toString());
            
            const publicKeyCredentialCreationOptions = {
                challenge: challenge,
                rp: {
                    name: "Church Management System",
                    id: window.location.hostname,
                },
                user: {
                    id: userId,
                    name: memberName,
                    displayName: memberName,
                },
                pubKeyCredParams: [
                    {
                        alg: -7, // ES256
                        type: "public-key"
                    },
                    {
                        alg: -257, // RS256
                        type: "public-key"
                    }
                ],
                authenticatorSelection: {
                    authenticatorAttachment: "platform", // Use platform authenticator (built-in)
                    userVerification: "required",
                    requireResidentKey: false
                },
                timeout: 60000,
                attestation: "direct"
            };
            
            const credential = await navigator.credentials.create({
                publicKey: publicKeyCredentialCreationOptions
            });
            
            if (!credential) {
                throw new Error('Failed to create credential');
            }
            
            // Prepare data for server
            const credentialData = {
                member_id: memberId,
                credential_id: this.arrayBufferToBase64(credential.rawId),
                public_key: this.arrayBufferToBase64(credential.response.publicKey),
                device_name: await this.getDeviceName()
            };
            
            // Send to server
            const response = await fetch('api/webauthn_register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(credentialData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.registeredCredentials.set(credentialData.credential_id, {
                    memberId: memberId,
                    memberName: memberName
                });
                return result;
            } else {
                throw new Error(result.error || 'Registration failed');
            }
            
        } catch (error) {
            console.error('Fingerprint registration error:', error);
            throw error;
        }
    }
    
    // Authenticate using fingerprint
    async authenticateFingerprint() {
        if (!this.isSupported) {
            throw new Error('WebAuthn is not supported in this browser');
        }
        
        try {
            const challenge = this.generateChallenge();
            
            const publicKeyCredentialRequestOptions = {
                challenge: challenge,
                timeout: 60000,
                userVerification: "required",
                rpId: window.location.hostname
            };
            
            const assertion = await navigator.credentials.get({
                publicKey: publicKeyCredentialRequestOptions
            });
            
            if (!assertion) {
                throw new Error('Authentication failed');
            }
            
            // Prepare data for server
            const authData = {
                credential_id: this.arrayBufferToBase64(assertion.rawId),
                signature: this.arrayBufferToBase64(assertion.response.signature),
                authenticator_data: this.arrayBufferToBase64(assertion.response.authenticatorData),
                client_data: this.arrayBufferToBase64(assertion.response.clientDataJSON)
            };
            
            // Send to server for verification
            const response = await fetch('api/webauthn_authenticate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(authData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result;
            } else {
                throw new Error(result.error || 'Authentication failed');
            }
            
        } catch (error) {
            console.error('Fingerprint authentication error:', error);
            throw error;
        }
    }
    
    // Check in using fingerprint
    async fingerprintCheckin(serviceDate, serviceType) {
        try {
            // First authenticate
            const authResult = await this.authenticateFingerprint();
            
            if (!authResult.success) {
                throw new Error('Authentication failed');
            }
            
            // Then record check-in
            const checkinData = {
                credential_id: authResult.credential_id || this.getLastUsedCredential(),
                service_date: serviceDate,
                service_type: serviceType
            };
            
            const response = await fetch('api/fingerprint_checkin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(checkinData)
            });
            
            const result = await response.json();
            return result;
            
        } catch (error) {
            console.error('Fingerprint check-in error:', error);
            throw error;
        }
    }
    
    // Get device name for registration
    async getDeviceName() {
        const userAgent = navigator.userAgent;
        let deviceName = 'Unknown Device';
        
        if (userAgent.includes('Windows')) {
            deviceName = 'Windows Device';
        } else if (userAgent.includes('Mac')) {
            deviceName = 'Mac Device';
        } else if (userAgent.includes('Linux')) {
            deviceName = 'Linux Device';
        } else if (userAgent.includes('Android')) {
            deviceName = 'Android Device';
        } else if (userAgent.includes('iPhone') || userAgent.includes('iPad')) {
            deviceName = 'iOS Device';
        }
        
        return deviceName;
    }
    
    // Get last used credential (for demo purposes)
    getLastUsedCredential() {
        const credentials = Array.from(this.registeredCredentials.keys());
        return credentials[credentials.length - 1] || '';
    }
    
    // Show fingerprint registration modal
    showRegistrationModal(memberId, memberName) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-fingerprint me-2"></i>Register Fingerprint
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <h6>Register fingerprint for: <strong>${memberName}</strong></h6>
                        <div class="fingerprint-scanner mt-4 mb-4" id="fingerprint-register">
                            <i class="bi bi-fingerprint fingerprint-icon"></i>
                        </div>
                        <p class="text-muted">
                            Click the fingerprint icon above and follow your browser's instructions to register your fingerprint.
                        </p>
                        <div id="registration-status"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="start-registration">
                            <i class="bi bi-fingerprint me-2"></i>Start Registration
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Handle registration
        const startBtn = modal.querySelector('#start-registration');
        const scanner = modal.querySelector('#fingerprint-register');
        const status = modal.querySelector('#registration-status');
        
        const handleRegistration = async () => {
            try {
                startBtn.disabled = true;
                scanner.classList.add('scanning');
                status.innerHTML = '<div class="status-message status-warning">Please use your fingerprint sensor...</div>';
                
                const result = await this.registerFingerprint(memberId, memberName);
                
                status.innerHTML = '<div class="status-message status-success">Fingerprint registered successfully!</div>';
                
                setTimeout(() => {
                    modalInstance.hide();
                    app.showMessage('Fingerprint registered successfully!', 'success');
                }, 2000);
                
            } catch (error) {
                status.innerHTML = `<div class="status-message status-error">Error: ${error.message}</div>`;
                startBtn.disabled = false;
                scanner.classList.remove('scanning');
            }
        };
        
        startBtn.addEventListener('click', handleRegistration);
        scanner.addEventListener('click', handleRegistration);
        
        // Clean up when modal is hidden
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
}

// Initialize WebAuthn manager
const webAuthnManager = new WebAuthnManager();

// Global functions for fingerprint operations
window.registerFingerprint = (memberId, memberName) => {
    if (!webAuthnManager.isSupported) {
        app.showMessage('Fingerprint authentication is not supported in this browser. Please use Chrome or Edge with a fingerprint sensor.', 'warning');
        return;
    }
    
    webAuthnManager.showRegistrationModal(memberId, memberName);
};

window.authenticateFingerprint = async () => {
    try {
        const result = await webAuthnManager.authenticateFingerprint();
        return result;
    } catch (error) {
        app.showMessage(`Authentication failed: ${error.message}`, 'error');
        throw error;
    }
};

window.fingerprintCheckin = async (serviceDate, serviceType) => {
    try {
        const result = await webAuthnManager.fingerprintCheckin(serviceDate, serviceType);
        return result;
    } catch (error) {
        app.showMessage(`Check-in failed: ${error.message}`, 'error');
        throw error;
    }
};
