// Configuración de conexión con el Backend ICFES y Sistema de Matrícula
const getBaseApiUrl = () => {
    if (typeof window !== 'undefined') {
        const origin = window.location.origin;
        const host = window.location.host;
        const pathname = window.location.pathname;

        // Si el usuario ingresa por el subdominio no configurado pruebas.femtribe.com.co
        if (host === 'pruebas.femtribe.com.co') {
            return 'https://femtribe.com.co/pruebas/api/v1';
        }

        // Detecta automáticamente si se ejecuta en subcarpeta /pruebas (en Hostinger o MAMP local)
        if (pathname.includes('/pruebas')) {
            return `${origin}/pruebas/api/v1`;
        }

        return `${origin}/api/v1`;
    }
    return 'https://femtribe.com.co/pruebas/api/v1';
};

const CONFIG = {
    API_BASE_URL: getBaseApiUrl(),
    TOKEN_KEY: 'icfes_auth_token',
    USER_KEY: 'icfes_auth_user',
    STUDENT_EXAM_SESSION_KEY: 'icfes_exam_session',
    DEFAULT_COURSE_PRICE: 700000
};

// Utilidad para gestionar LocalStorage
const Storage = {
    getToken() {
        return localStorage.getItem(CONFIG.TOKEN_KEY);
    },
    setToken(token) {
        localStorage.setItem(CONFIG.TOKEN_KEY, token);
    },
    clearToken() {
        localStorage.removeItem(CONFIG.TOKEN_KEY);
        localStorage.removeItem(CONFIG.USER_KEY);
    },
    getUser() {
        const u = localStorage.getItem(CONFIG.USER_KEY);
        return u ? JSON.parse(u) : null;
    },
    setUser(user) {
        localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(user));
    }
};
