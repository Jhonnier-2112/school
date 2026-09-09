// Configuración de la App del Estudiante
const CONFIG = {
    // Detecta automáticamente la URL base para funcionar en local (MAMP) o en producción (Hostinger)
    get API_BASE_URL() {
        const origin = window.location.origin;
        const path = window.location.pathname;
        if (path.includes('/pruebas')) {
            return `${origin}/pruebas/api/v1`;
        }
        return `${origin}/api/v1`;
    },
    TOKEN_KEY: 'icfes_student_token',
    USER_KEY: 'icfes_student_user',
    COURSE_PRICE: 700000
};

// Gestor de sesión y persistencia local
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
        try {
            return u ? JSON.parse(u) : null;
        } catch (e) {
            return null;
        }
    },
    setUser(user) {
        localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(user));
    },
    isAuthenticated() {
        return !!this.getToken();
    }
};
