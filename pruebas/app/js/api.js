// Cliente API para la App Móvil del Estudiante
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
                const err = new Error(errorMsg);
                err.status = res.status;
                err.data = json;
                throw err;
            }

            return json;
        } catch (err) {
            console.warn(`[Student API] ${endpoint}:`, err.message);
            throw err;
        }
    },

    // 1. Autenticación
    async login(email, password) {
        return this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    },

    async register(data) {
        return this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(data)
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

    // 2. Contrato Digital
    async getContract() {
        return this.request('/student/contract');
    },

    async acceptContract(contractId) {
        return this.request('/student/contract/accept', {
            method: 'POST',
            body: JSON.stringify({ contract_id: contractId, accept_terms: true })
        });
    },

    // 3. Curso y Pagos
    async getCourseSummary() {
        return this.request('/student/course-summary');
    },

    async getPayments() {
        return this.request('/student/payments');
    },

    // 4. Documento de Identidad
    async uploadDocument(formData) {
        return this.request('/student/document', {
            method: 'POST',
            body: formData
        });
    },

    async getDocument() {
        return this.request('/student/document');
    },

    // 5. Simulacros ICFES
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
    }
};
