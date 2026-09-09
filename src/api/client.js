import AsyncStorage from '@react-native-async-storage/async-storage';

export const DEFAULT_API_BASE_URL = 'https://pruebas.femtribe.com.co/api/v1';
export const SERVER_KEY = '@api_server_url';
export const TOKEN_KEY = '@icfes_token';
export const USER_KEY = '@icfes_user';

export const AVAILABLE_SERVERS = [
  { id: 'remote', label: '☁️ Servidor Remoto (Hostinger)', url: 'https://pruebas.femtribe.com.co/api/v1' },
  { id: 'emulator', label: '📱 Emulador Android (10.0.2.2:8888)', url: 'http://10.0.2.2:8888/api/v1' },
  { id: 'localhost', label: '💻 Localhost (localhost:8888)', url: 'http://localhost:8888/api/v1' },
];

let cachedBaseUrl = null;

export const getApiBaseUrl = async () => {
  if (cachedBaseUrl) return cachedBaseUrl;
  try {
    const stored = await AsyncStorage.getItem(SERVER_KEY);
    cachedBaseUrl = stored || DEFAULT_API_BASE_URL;
  } catch (e) {
    cachedBaseUrl = DEFAULT_API_BASE_URL;
  }
  return cachedBaseUrl;
};

export const setApiBaseUrl = async (newUrl) => {
  cachedBaseUrl = newUrl;
  await AsyncStorage.setItem(SERVER_KEY, newUrl);
};

export const API = {
  async getBaseUrl() {
    return await getApiBaseUrl();
  },

  async request(endpoint, options = {}) {
    const baseUrl = await getApiBaseUrl();
    const url = `${baseUrl}${endpoint}`;
    const headers = options.headers || {};

    const token = await AsyncStorage.getItem(TOKEN_KEY);
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    if (!(options.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000);

      const res = await fetch(url, {
        ...options,
        headers,
        signal: controller.signal,
      });
      clearTimeout(timeoutId);

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
      if (err.name === 'AbortError') {
        console.warn(`[Native API Timeout] ${endpoint}: Excedido tiempo de espera (15s)`);
        throw new Error('Tiempo de espera agotado al conectar con el servidor.');
      }
      console.warn(`[Native API Error] ${endpoint}:`, err.message);
      throw err;
    }
  },

  // 1. Auth
  async login(email, password) {
    return this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  },

  async register(data) {
    return this.request('/auth/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  async getMe() {
    return this.request('/auth/me');
  },

  // 2. Contrato
  async getContract() {
    return this.request('/student/contract');
  },

  async acceptContract(contractId) {
    return this.request('/student/contract/accept', {
      method: 'POST',
      body: JSON.stringify({ contract_id: contractId, accept_terms: true }),
    });
  },

  // 3. Curso y Pagos
  async getCourseSummary() {
    return this.request('/student/course-summary');
  },

  async getPayments() {
    return this.request('/student/payments');
  },

  // 4. Documento Cédula
  async uploadDocument(formData) {
    return this.request('/student/document', {
      method: 'POST',
      body: formData,
    });
  },

  async getDocument() {
    return this.request('/student/document');
  },

  // 5. Simulacros
  async listExams() {
    return this.request('/student/exams');
  },

  async startExam(examId) {
    return this.request(`/student/exams/${examId}/start`, {
      method: 'POST',
    });
  },

  async submitExam(sessionId, answers) {
    return this.request(`/student/exams/sessions/${sessionId}/submit`, {
      method: 'POST',
      body: JSON.stringify({ answers }),
    });
  },

  async getExamResults(sessionId) {
    return this.request(`/student/exams/sessions/${sessionId}/results`);
  },

  async getExamHistory() {
    return this.request('/student/exams/history');
  },

  // 6. Matrícula Digital Jean Piaget School 2026
  async getGradesConfig() {
    return this.request('/enrollments/config/grades');
  },

  async getMyEnrollments() {
    return this.request('/enrollments/my');
  },

  async getEnrollmentById(id) {
    return this.request(`/enrollments/${id}`);
  },

  async getEnrollmentStatus(id) {
    return this.request(`/enrollments/${id}/status`);
  },

  async getEnrollmentDocuments(id) {
    return this.request(`/enrollments/${id}/documents`);
  },

  async generateEnrollmentDocs(id) {
    return this.request(`/enrollments/${id}/documents/generate`, {
      method: 'POST',
    });
  },

  async signEnrollment(id, signatureBase64) {
    return this.request(`/enrollments/${id}/sign`, {
      method: 'POST',
      body: JSON.stringify({
        signature_data: signatureBase64,
        signature_base64: signatureBase64,
        sign_terms: true,
      }),
    });
  },

  async submitEnrollment(id) {
    return this.request(`/enrollments/${id}/submit`, {
      method: 'POST',
    });
  },

  async createEnrollment(data = {}) {
    return this.request('/enrollments', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  async updateEnrollmentStep(id, stepNumber, data = {}) {
    return this.request(`/enrollments/${id}/step`, {
      method: 'PUT',
      body: JSON.stringify({ step: stepNumber, ...data }),
    });
  },
};

