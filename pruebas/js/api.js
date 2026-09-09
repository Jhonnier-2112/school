// Cliente HTTP para consumir los endpoints de https://pruebas.femtribe.com.co/api/v1
const API = {
    async request(endpoint, options = {}) {
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        const headers = options.headers || {};

        const token = Storage.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        if (!(options.body instanceof FormData) && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        try {
            const res = await fetch(url, {
                ...options,
                headers
            });

            const json = await res.json().catch(() => null);

            if (!res.ok) {
                const errorMsg = (json && json.message) || `Error ${res.status}: ${res.statusText}`;
                throw new Error(errorMsg);
            }

            return json;
        } catch (err) {
            console.warn(`[API Request Error] ${endpoint}:`, err);
            throw err;
        }
    },

    // 1. Auth Endpoints
    async register(data) {
        return this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async login(email, password) {
        return this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    },

    async getMe() {
        return this.request('/auth/me');
    },

    async checkActivationToken(token) {
        return this.request(`/auth/activate?token=${encodeURIComponent(token)}`);
    },

    async setPassword(data) {
        return this.request('/auth/set-password', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    // 2. Student Endpoints
    async getContract() {
        return this.request('/student/contract');
    },

    async acceptContract(contractId) {
        return this.request('/student/contract/accept', {
            method: 'POST',
            body: JSON.stringify({ contract_id: contractId, accept_terms: true })
        });
    },

    async getCourseSummary() {
        return this.request('/student/course-summary');
    },

    async getPayments() {
        return this.request('/student/payments');
    },

    async uploadDocument(formData) {
        return this.request('/student/document', {
            method: 'POST',
            body: formData
        });
    },

    async getDocumentStatus() {
        return this.request('/student/document');
    },

    async listExams() {
        return this.request('/student/exams');
    },

    async startExam(examId) {
        return this.request(`/student/exams/${examId}/start`, {
            method: 'POST'
        });
    },

    async submitExam(sessionId, answers) {
        return this.request(`/student/exams/sessions/${sessionId}/submit`, {
            method: 'POST',
            body: JSON.stringify({ answers })
        });
    },

    async getExamResults(sessionId) {
        return this.request(`/student/exams/sessions/${sessionId}/results`);
    },

    async getExamHistory() {
        return this.request('/student/exams/history');
    },

    // 3. Admin Endpoints
    async getDashboardStats() {
        return this.request('/admin/dashboard');
    },

    async listStudents(search = '', limit = 20, offset = 0) {
        const q = new URLSearchParams({ search, limit, offset });
        return this.request(`/admin/students?${q.toString()}`);
    },

    async getStudentDetail(studentId) {
        return this.request(`/admin/students/${studentId}`);
    },

    async inviteStudent(data) {
        return this.request('/admin/students/invite', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async registerPayment(data) {
        return this.request('/admin/payments', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async listAdminPayments(params = {}) {
        const q = new URLSearchParams(params).toString();
        return this.request(`/admin/payments${q ? '?' + q : ''}`);
    },

    async listPendingDocuments(limit = 20, offset = 0) {
        return this.request(`/admin/documents/pending?limit=${limit}&offset=${offset}`);
    },

    async reviewDocument(docId, status, rejectionReason = '') {
        return this.request(`/admin/documents/${docId}/review`, {
            method: 'POST',
            body: JSON.stringify({ status, rejection_reason: rejectionReason })
        });
    },

    async listSubjects() {
        return this.request('/admin/subjects');
    },

    async createQuestion(data) {
        return this.request('/admin/questions', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async listQuestions(filters = {}) {
        let query = '';
        if (typeof filters === 'string') {
            query = filters ? `?subject_id=${filters}` : '';
        } else {
            const clean = {};
            for (const [k, v] of Object.entries(filters)) {
                if (v !== '' && v != null) clean[k] = v;
            }
            const qs = new URLSearchParams(clean).toString();
            query = qs ? `?${qs}` : '';
        }
        return this.request(`/admin/questions${query}`);
    },

    async getQuestion(id) {
        return this.request(`/admin/questions/${id}`);
    },

    async updateQuestion(id, data) {
        return this.request(`/admin/questions/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async deleteQuestion(id) {
        return this.request(`/admin/questions/${id}`, {
            method: 'DELETE'
        });
    },

    async uploadQuestionImage(file) {
        const token = this.getToken();
        const headers = {};
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const formData = new FormData();
        formData.append('image', file);

        const res = await fetch(`${this.baseUrl}/admin/questions/upload-image`, {
            method: 'POST',
            headers,
            body: formData
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Error al subir la imagen');
        }
        return data;
    },

    async createExam(data) {
        return this.request('/admin/exams', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async listAllExams() {
        return this.request('/admin/exams');
    },

    async getExam(id) {
        return this.request(`/admin/exams/${id}`);
    },

    async updateExam(id, data) {
        return this.request(`/admin/exams/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async deleteExam(id) {
        return this.request(`/admin/exams/${id}`, {
            method: 'DELETE'
        });
    },

    async publishExam(examId, isPublished) {
        return this.request(`/admin/exams/${examId}/publish`, {
            method: 'PUT',
            body: JSON.stringify({ is_published: isPublished })
        });
    },

    // 4. Estadísticas mensuales para gráficas
    async getPaymentsByMonth() {
        return this.request('/admin/stats/payments-by-month');
    },

    async getStudentsByMonth() {
        return this.request('/admin/stats/students-by-month');
    },

    // 5. Matrícula Digital Jean Piaget School
    async getEnrollmentConfig() {
        return this.request('/enrollments/config/grades');
    },

    async getMyEnrollments() {
        return this.request('/enrollments/my');
    },

    async createOrGetEnrollmentDraft(data = {}) {
        return this.request('/enrollments', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async getEnrollment(id) {
        return this.request(`/enrollments/${id}`);
    },

    async saveEnrollmentStep(id, stepData) {
        return this.request(`/enrollments/${id}/step`, {
            method: 'PUT',
            body: JSON.stringify(stepData)
        });
    },

    async generateEnrollmentDocuments(id) {
        return this.request(`/enrollments/${id}/documents/generate`, {
            method: 'POST'
        });
    },

    async getEnrollmentDocuments(id) {
        return this.request(`/enrollments/${id}/documents`);
    },

    async signEnrollment(id, signatureData) {
        return this.request(`/enrollments/${id}/sign`, {
            method: 'POST',
            body: JSON.stringify({ signature_data: signatureData })
        });
    },

    async submitEnrollment(id) {
        return this.request(`/enrollments/${id}/submit`, {
            method: 'POST'
        });
    },

    async getEnrollmentStatus(id) {
        return this.request(`/enrollments/${id}/status`);
    },

    // 6. Panel Administrativo de Matrículas
    async getEnrollmentStatistics() {
        return this.request('/admin/enrollments/statistics');
    },

    async listAdminEnrollments(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`/admin/enrollments?${params.toString()}`);
    },

    async getAdminEnrollmentDetail(id) {
        return this.request(`/admin/enrollments/${id}`);
    },

    async updateAdminEnrollmentStatus(id, status, observations = '') {
        return this.request(`/admin/enrollments/${id}/status`, {
            method: 'PUT',
            body: JSON.stringify({ status, observations })
        });
    },

    async regenerateAdminEnrollmentDocuments(id) {
        return this.request(`/admin/enrollments/${id}/documents/regenerate`, {
            method: 'POST'
        });
    }
};
