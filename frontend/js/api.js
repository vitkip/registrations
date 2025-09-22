/**
 * API Utilities for Certificate Registration System
 * ຟັງຊັນຊ່ວຍເຫຼືອສຳລັບການເຊື່ອມຕໍ່ API
 */

class CertificateAPI {
    constructor() {
        this.baseURL = window.location.origin;
        this.token = localStorage.getItem('auth_token');
    }

    // Set authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    // Remove authentication token
    removeToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    // Get authentication headers
    getAuthHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        return headers;
    }

    // Generic API request method
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const config = {
            headers: this.getAuthHeaders(),
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Authentication methods
    async login(username, password) {
        return this.request('/backend/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
    }

    async logout() {
        const result = await this.request('/backend/auth/logout.php', {
            method: 'POST'
        });
        this.removeToken();
        return result;
    }

    async verifyToken() {
        return this.request('/backend/auth/verify.php', {
            method: 'POST'
        });
    }

    // Student registration methods
    async registerStudent(formData) {
        const url = `${this.baseURL}/backend/api/register.php`;
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData // FormData object for file uploads
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }

    async checkStatus(studentCode = '', email = '') {
        const params = new URLSearchParams();
        if (studentCode) params.append('student_code', studentCode);
        if (email) params.append('email', email);
        
        return this.request(`/backend/api/check_status.php?${params.toString()}`);
    }

    // Admin methods
    async getRegistrations(page = 1, limit = 20, filters = {}) {
        const params = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString(),
            ...filters
        });
        
        return this.request(`/backend/admin/registrations.php?${params.toString()}`);
    }

    async updateRegistration(id, status, notes = '') {
        return this.request('/backend/admin/registrations.php', {
            method: 'PUT',
            body: JSON.stringify({ id, status, notes })
        });
    }

    async deleteRegistration(id) {
        return this.request('/backend/admin/registrations.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // User management methods
    async getUsers(page = 1, limit = 20, search = '') {
        const params = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString(),
            search
        });
        
        return this.request(`/backend/admin/users.php?${params.toString()}`);
    }

    async createUser(userData) {
        return this.request('/backend/admin/users.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    async updateUser(id, userData) {
        return this.request('/backend/admin/users.php', {
            method: 'PUT',
            body: JSON.stringify({ id, ...userData })
        });
    }

    async deleteUser(id) {
        return this.request('/backend/admin/users.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    // Export methods
    async exportRegistrations(format = 'excel', filters = {}) {
        if (!this.token) {
            throw new Error('No authentication token');
        }
        
        try {
            const params = new URLSearchParams({
                format,
                ...filters
            });
            
            const response = await fetch(`${this.baseURL}/backend/admin/export.php?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}`);
            }
            
            // Get the blob content
            const blob = await response.blob();
            
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `registrations_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xls' : format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Clean up
            window.URL.revokeObjectURL(url);
            
            return { success: true };
            
        } catch (error) {
            console.error('Export error:', error);
            throw error;
        }
    }
}

// UI Utilities
class UIUtils {
    // Show alert message
    static showAlert(message, type = 'info', container = 'alerts-container') {
        const alertContainer = document.getElementById(container);
        if (!alertContainer) return;

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} fade-in`;
        alertDiv.innerHTML = `
            <span>${message}</span>
            <button type="button" class="close-alert" onclick="this.parentElement.remove()">×</button>
        `;

        alertContainer.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Show loading spinner
    static showLoading(button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner"></span> ກຳລັງໂຫລດ...';
        button.disabled = true;
        
        return () => {
            button.innerHTML = originalText;
            button.disabled = false;
        };
    }

    // Format date to Lao format
    static formatDateLao(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    // Get status badge HTML
    static getStatusBadge(status) {
        const statusMap = {
            'pending': { class: 'badge-pending', text: 'ກຳລັງລໍຖ້າການອະນຸມັດ' },
            'approved': { class: 'badge-approved', text: 'ອະນຸມັດແລ້ວ' },
            'rejected': { class: 'badge-rejected', text: 'ຖືກປະຕິເສດ' },
            'certificate_issued': { class: 'badge-issued', text: 'ອອກໃບປະກາດນິຍະບັດແລ້ວ' }
        };

        const statusInfo = statusMap[status] || { class: 'badge-pending', text: status };
        return `<span class="badge ${statusInfo.class}">${statusInfo.text}</span>`;
    }

    // Validate form
    static validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            const errorContainer = field.parentElement.querySelector('.form-error');
            
            if (!field.value.trim()) {
                field.classList.add('error');
                if (errorContainer) {
                    errorContainer.textContent = 'ກະລຸນາໃສ່ຂໍ້ມູນນີ້';
                }
                isValid = false;
            } else {
                field.classList.remove('error');
                if (errorContainer) {
                    errorContainer.textContent = '';
                }
            }
        });

        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                field.classList.add('error');
                const errorContainer = field.parentElement.querySelector('.form-error');
                if (errorContainer) {
                    errorContainer.textContent = 'ຮູບແບບອີເມລບໍ່ຖືກຕ້ອງ';
                }
                isValid = false;
            }
        });

        return isValid;
    }

    // Email validation
    static isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Phone validation for Lao numbers
    static isValidPhone(phone) {
        const phoneRegex = /^[0-9\-\+\(\)\s]{8,20}$/;
        return phoneRegex.test(phone);
    }

    // File size validation
    static validateFileSize(file, maxSizeMB = 5) {
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxSizeBytes;
    }

    // File type validation
    static validateFileType(file, allowedTypes) {
        const fileType = file.type.toLowerCase();
        const fileName = file.name.toLowerCase();
        const fileExt = fileName.split('.').pop();
        
        return allowedTypes.includes(fileType) || allowedTypes.includes(fileExt);
    }

    // Create pagination HTML
    static createPagination(currentPage, totalPages, onPageChange) {
        if (totalPages <= 1) return '';

        let html = '<div class="pagination">';
        
        // Previous button
        if (currentPage > 1) {
            html += `<button class="btn-pagination" onclick="${onPageChange}(${currentPage - 1})">‹ ກ່ອນໜ້າ</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            html += `<button class="btn-pagination ${activeClass}" onclick="${onPageChange}(${i})">${i}</button>`;
        }

        // Next button
        if (currentPage < totalPages) {
            html += `<button class="btn-pagination" onclick="${onPageChange}(${currentPage + 1})">ຕໍ່ໄປ ›</button>`;
        }

        html += '</div>';
        return html;
    }
}

// Initialize API instance
const api = new CertificateAPI();

// Check authentication on page load
document.addEventListener('DOMContentLoaded', async () => {
    if (api.token) {
        try {
            await api.verifyToken();
        } catch (error) {
            console.log('Token verification failed:', error);
            api.removeToken();
            // Redirect to login if on admin page
            if (window.location.pathname.includes('admin')) {
                window.location.href = '/frontend/login.html';
            }
        }
    }
});