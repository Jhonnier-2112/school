// Aplicación Principal - Inicializador, Autenticación Directiva & Gestor de Vistas
const App = {
    currentView: 'auth', // 'auth' | 'student' | 'admin'
    isAdminAuthenticated: false,

    async init() {
        this.bindGlobalEvents();
        this.bindPortalAuthEvents();
        this.bindAdminAuthEvents();
        MobileApp.init();
        await this.checkAdminSession();
        this.checkActivationFlow();
    },

    async checkActivationFlow() {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        if (!token) return;

        const modal = document.getElementById('activation-modal');
        const tokenInput = document.getElementById('activation-token');
        const welcomeText = document.getElementById('activation-welcome-text');
        const errorMsg = document.getElementById('activation-error-msg');
        const form = document.getElementById('form-modal-activation');
        const submitBtn = document.getElementById('btn-submit-activation');

        if (!modal || !form) return;

        // Validar token con el servidor
        try {
            const res = await API.checkActivationToken(token);
            if (tokenInput) tokenInput.value = token;
            if (welcomeText && res.data?.full_name) {
                welcomeText.textContent = `Hola ${res.data.full_name}, define tu contraseña para activar tu acceso y comenzar tu preparación.`;
            }
            modal.classList.add('active');
        } catch (err) {
            this.showToast(err.message || 'El enlace de activación es inválido o expiró', 'error');
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pass = document.getElementById('modal-act-pass')?.value || '';
            const confirm = document.getElementById('modal-act-pass-confirm')?.value || '';

            if (pass.length < 8) {
                if (errorMsg) {
                    errorMsg.textContent = 'La contraseña debe tener al menos 8 caracteres';
                    errorMsg.style.display = 'block';
                }
                return;
            }

            if (pass !== confirm) {
                if (errorMsg) {
                    errorMsg.textContent = 'Las contraseñas no coinciden';
                    errorMsg.style.display = 'block';
                }
                return;
            }

            if (errorMsg) errorMsg.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Activando cuenta...';
            }

            try {
                const res = await API.setPassword({
                    token: token,
                    password: pass,
                    password_confirmation: confirm
                });

                if (res.data?.token) {
                    Storage.setToken(res.data.token);
                    Storage.setUser(res.data.user);
                }

                this.showToast('🎉 ¡Tu cuenta ha sido activada con éxito!', 'success');
                modal.classList.remove('active');

                // Limpiar query params de la URL sin recargar
                window.history.replaceState({}, document.title, window.location.pathname);

                // Cambiar a vista estudiante y recargar app móvil
                this.switchView('student');
                if (typeof MobileApp !== 'undefined' && MobileApp.init) {
                    MobileApp.init();
                }
            } catch (err) {
                if (errorMsg) {
                    errorMsg.textContent = err.message || 'Error al activar cuenta';
                    errorMsg.style.display = 'block';
                }
                this.showToast(err.message || 'Error al activar cuenta', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🚀 Activar cuenta y entrar';
                }
            }
        });
    },

    // -------------------------------------------------------------
    // GESTIÓN DE SESIÓN DE ADMINISTRADOR
    // -------------------------------------------------------------
    async checkAdminSession() {
        const token = Storage.getToken();

        if (token) {
            try {
                const res = await API.getMe();
                if (res && res.data && res.data.role === 'admin') {
                    this.updateAdminSessionUI(true, res.data);
                    // Si ya está autenticado y se accede con hash #estudiante, ir a estudiante
                    if (window.location.hash === '#estudiante') {
                        this.switchView('student');
                    } else {
                        this.switchView('admin');
                    }
                    return true;
                }
            } catch (e) {
                // Token expirado o inválido
                Storage.clearToken();
            }
        }

        this.updateAdminSessionUI(false, null);
        // Si no está autenticado, la vista principal por defecto es 'auth' (Login y Registro)
        this.switchView('auth');
        return false;
    },

    updateAdminSessionUI(isAuth, user = null) {
        this.isAdminAuthenticated = isAuth;
        const btnOpenLogin    = document.getElementById('btn-open-admin-login');
        const adminTopSession = document.getElementById('admin-top-session');
        const guestTopActions = document.getElementById('guest-top-actions');
        const mainSwitcher    = document.getElementById('main-view-switcher');
        const topAdminName    = document.getElementById('top-admin-name');
        const sidebarName     = document.getElementById('admin-sidebar-name');
        const sidebarEmail    = document.getElementById('admin-sidebar-email');
        const sidebarAvatar   = document.getElementById('admin-sidebar-avatar');

        if (isAuth && user) {
            if (btnOpenLogin) btnOpenLogin.style.display = 'none';
            if (guestTopActions) guestTopActions.style.display = 'none';
            if (adminTopSession) adminTopSession.style.display = 'inline-flex';
            if (mainSwitcher) mainSwitcher.style.display = 'inline-flex';
            if (topAdminName) topAdminName.textContent = user.full_name || 'Admin';

            if (sidebarName) sidebarName.textContent = user.full_name || 'Administrador';
            if (sidebarEmail) sidebarEmail.textContent = user.email || 'admin@icfes.com';
            if (sidebarAvatar) {
                const initial = (user.full_name || user.email || 'A').charAt(0).toUpperCase();
                sidebarAvatar.textContent = initial;
            }
        } else {
            if (btnOpenLogin) btnOpenLogin.style.display = 'inline-flex';
            if (guestTopActions) guestTopActions.style.display = 'flex';
            if (adminTopSession) adminTopSession.style.display = 'none';
            if (mainSwitcher) mainSwitcher.style.display = 'none';
        }
    },

    // -------------------------------------------------------------
    // EVENTOS DEL PORTAL PRINCIPAL (LOGIN Y REGISTRO)
    // -------------------------------------------------------------
    bindPortalAuthEvents() {
        const tabLogin      = document.getElementById('auth-tab-login');
        const tabRegister   = document.getElementById('auth-tab-register');
        const formLogin     = document.getElementById('portal-login-form');
        const formReg       = document.getElementById('portal-register-form');
        const errLogin      = document.getElementById('portal-login-error');
        const errLoginTxt   = document.getElementById('portal-login-error-text');
        const btnDemoFill   = document.getElementById('btn-fill-admin-demo');
        const btnQuickAdmin = document.getElementById('btn-quick-admin-login');
        const btnTogglePwd  = document.getElementById('portal-toggle-pwd');
        const pwdInput      = document.getElementById('portal-password');
        const emailInput    = document.getElementById('portal-email');
        const btnOpenTop    = document.getElementById('btn-open-admin-login');

        // Toggle entre pestañas Iniciar Sesión / Registrarse
        if (tabLogin && tabRegister && formLogin && formReg) {
            tabLogin.addEventListener('click', () => {
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
                formLogin.style.display = 'block';
                formReg.style.display = 'none';
            });

            tabRegister.addEventListener('click', () => {
                tabRegister.classList.add('active');
                tabLogin.classList.remove('active');
                formReg.style.display = 'block';
                formLogin.style.display = 'none';
            });
        }

        // Toggle visibilidad de contraseña
        if (btnTogglePwd && pwdInput) {
            btnTogglePwd.addEventListener('click', () => {
                const isPass = pwdInput.type === 'password';
                pwdInput.type = isPass ? 'text' : 'password';
                btnTogglePwd.textContent = isPass ? '🙈' : '👁️';
            });
        }

        // Rellenar credenciales demo de administrador
        if (btnDemoFill) {
            btnDemoFill.addEventListener('click', () => {
                if (emailInput) emailInput.value = 'admin@icfes.com';
                if (pwdInput) {
                    pwdInput.value = 'Admin123456!';
                    pwdInput.focus();
                }
                this.showToast('Credenciales directivas cargadas (admin@icfes.com)', 'info');
            });
        }

        // Botón en barra superior: "Ingresar como Administrador"
        if (btnOpenTop) {
            btnOpenTop.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchView('auth');
                if (tabLogin) tabLogin.click();
                if (emailInput) emailInput.value = 'admin@icfes.com';
                if (pwdInput) {
                    pwdInput.value = 'Admin123456!';
                    pwdInput.focus();
                }
                formLogin?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

        // Función central para procesar login de portal
        const doPortalLogin = async (email, password, triggerBtn) => {
            if (!email || !password) {
                if (errLogin && errLoginTxt) {
                    errLoginTxt.textContent = 'Por favor ingresa tu correo y contraseña.';
                    errLogin.style.display = 'flex';
                }
                return;
            }

            if (errLogin) errLogin.style.display = 'none';
            const originalBtnHtml = triggerBtn ? triggerBtn.innerHTML : '';
            if (triggerBtn) {
                triggerBtn.disabled = true;
                triggerBtn.innerHTML = '<span>⏳</span> Verificando credenciales...';
            }

            try {
                const res = await API.login(email, password);
                if (!res.data?.token) {
                    throw new Error('No se recibió token de autenticación del servidor');
                }

                // Guardar token y datos del usuario
                Storage.setToken(res.data.token);
                Storage.setUser(res.data.user);

                if (res.data.user?.role === 'admin') {
                    this.updateAdminSessionUI(true, res.data.user);
                    this.switchView('admin');
                    this.showToast(`¡Bienvenido al Panel Directivo, ${res.data.user.full_name || 'Administrador'}!`, 'success');
                } else {
                    this.updateAdminSessionUI(false, null);
                    this.switchView('student');
                    this.showToast(`¡Bienvenido a la plataforma, ${res.data.user.full_name || 'Estudiante'}!`, 'success');
                }
            } catch (err) {
                if (errLogin && errLoginTxt) {
                    errLoginTxt.textContent = err.message || 'Credenciales inválidas. Verifica tu correo y contraseña.';
                    errLogin.style.display = 'flex';
                }
                this.showToast(err.message || 'Error al iniciar sesión', 'error');
            } finally {
                if (triggerBtn) {
                    triggerBtn.disabled = false;
                    triggerBtn.innerHTML = originalBtnHtml;
                }
            }
        };

        // Clic en "Entrar directo como Administrador" (1 Clic)
        if (btnQuickAdmin) {
            btnQuickAdmin.addEventListener('click', async (e) => {
                e.preventDefault();
                if (emailInput) emailInput.value = 'admin@icfes.com';
                if (pwdInput) pwdInput.value = 'Admin123456!';
                await doPortalLogin('admin@icfes.com', 'Admin123456!', btnQuickAdmin);
            });
        }

        // Envío del Formulario de Login Principal
        if (formLogin) {
            formLogin.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = emailInput?.value?.trim() || '';
                const pass  = pwdInput?.value || '';
                const btnSubmit = document.getElementById('portal-btn-login');
                await doPortalLogin(email, pass, btnSubmit);
            });
        }

        // Envío del Formulario de Registro Principal
        if (formReg) {
            formReg.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name  = document.getElementById('reg-name')?.value?.trim() || '';
                const email = document.getElementById('reg-email')?.value?.trim() || '';
                const phone = document.getElementById('reg-phone')?.value?.trim() || '';
                const pass  = document.getElementById('reg-password')?.value || '';
                const btnReg = document.getElementById('portal-btn-register');
                const errReg = document.getElementById('portal-register-error');
                const errRegTxt = document.getElementById('portal-register-error-text');
                const sucReg = document.getElementById('portal-register-success');

                if (!name || !email || !phone || !pass) {
                    if (errReg && errRegTxt) {
                        errRegTxt.textContent = 'Todos los campos son obligatorios.';
                        errReg.style.display = 'flex';
                    }
                    return;
                }

                if (errReg) errReg.style.display = 'none';
                if (sucReg) sucReg.style.display = 'none';

                if (btnReg) {
                    btnReg.disabled = true;
                    btnReg.innerHTML = '<span>⏳</span> Creando cuenta...';
                }

                try {
                    const res = await API.register({
                        full_name: name,
                        email: email,
                        phone: phone,
                        password: pass
                    });

                    if (sucReg) sucReg.style.display = 'flex';
                    this.showToast('¡Cuenta creada con éxito! Iniciando sesión...', 'success');

                    // Auto-login automático
                    setTimeout(async () => {
                        if (emailInput) emailInput.value = email;
                        if (pwdInput) pwdInput.value = pass;
                        if (tabLogin) tabLogin.click();
                        await doPortalLogin(email, pass, null);
                    }, 1000);

                } catch (err) {
                    if (errReg && errRegTxt) {
                        errRegTxt.textContent = err.message || 'Error al registrar la cuenta.';
                        errReg.style.display = 'flex';
                    }
                    this.showToast(err.message || 'Error al registrar', 'error');
                } finally {
                    if (btnReg) {
                        btnReg.disabled = false;
                        btnReg.innerHTML = '<span>📝</span> Crear Cuenta de Estudiante / Acudiente';
                    }
                }
            });
        }
    },

    openAdminLoginModal() {
        const modal = document.getElementById('admin-login-modal');
        const err = document.getElementById('admin-login-error');
        if (err) err.style.display = 'none';
        if (modal) modal.classList.add('active');

        const emailInput = document.getElementById('admin-login-email');
        if (emailInput) {
            setTimeout(() => emailInput.focus(), 150);
        }
    },

    closeAdminLoginModal() {
        const modal = document.getElementById('admin-login-modal');
        if (modal) modal.classList.remove('active');
    },

    bindAdminAuthEvents() {
        const modal          = document.getElementById('admin-login-modal');
        const form           = document.getElementById('form-admin-login');
        const btnClose       = document.getElementById('btn-close-admin-login');
        const btnLogoutTop   = document.getElementById('btn-top-logout');
        const btnLogoutSide  = document.getElementById('btn-admin-logout');
        const btnTogglePass  = document.getElementById('btn-toggle-admin-password');
        const passInput      = document.getElementById('admin-login-password');
        const emailInput     = document.getElementById('admin-login-email');
        const errorContainer = document.getElementById('admin-login-error');
        const errorText      = document.getElementById('admin-login-error-text');

        // Cerrar modal
        if (btnClose) {
            btnClose.addEventListener('click', () => this.closeAdminLoginModal());
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeAdminLoginModal();
            });
        }

        // Toggle visibilidad de contraseña en modal
        if (btnTogglePass && passInput) {
            btnTogglePass.addEventListener('click', () => {
                const isPass = passInput.type === 'password';
                passInput.type = isPass ? 'text' : 'password';
                btnTogglePass.textContent = isPass ? '🙈' : '👁️';
            });
        }

        // Cierre de Sesión (Desloguearme)
        const handleLogout = (e) => {
            if (e) e.preventDefault();
            Storage.clearToken();
            this.updateAdminSessionUI(false, null);
            this.switchView('auth');
            this.showToast('Has cerrado sesión correctamente.', 'info');
        };

        if (btnLogoutTop) btnLogoutTop.addEventListener('click', handleLogout);
        if (btnLogoutSide) btnLogoutSide.addEventListener('click', handleLogout);

        // Envío de Login desde modal (si se utiliza)
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = emailInput?.value?.trim() || '';
                const password = passInput?.value || '';
                const submitBtn = document.getElementById('btn-submit-admin-login');

                if (!email || !password) {
                    if (errorContainer && errorText) {
                        errorText.textContent = 'Por favor ingresa tu correo y contraseña.';
                        errorContainer.style.display = 'flex';
                    }
                    return;
                }

                if (errorContainer) errorContainer.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span>⏳</span> Verificando credenciales...';
                }

                try {
                    const res = await API.login(email, password);
                    if (!res.data?.token) {
                        throw new Error('No se recibió token de autenticación del servidor');
                    }

                    if (res.data.user?.role !== 'admin') {
                        throw new Error('Esta cuenta no posee privilegios de administrador.');
                    }

                    // Guardar token y usuario
                    Storage.setToken(res.data.token);
                    Storage.setUser(res.data.user);

                    this.updateAdminSessionUI(true, res.data.user);
                    this.closeAdminLoginModal();

                    this.showToast(`¡Bienvenido al panel directivo, ${res.data.user.full_name}!`, 'success');

                    // Cambiar a vista admin e inicializar panel
                    this.switchView('admin');
                } catch (err) {
                    if (errorContainer && errorText) {
                        errorText.textContent = err.message || 'Credenciales inválidas. Verifica tu correo y contraseña.';
                        errorContainer.style.display = 'flex';
                    }
                    this.showToast(err.message || 'Error de autenticación', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span>🔐</span> Ingresar al Panel Administrativo';
                    }
                }
            });
        }
    },

    bindGlobalEvents() {
        const btnStudent = document.getElementById('switch-to-student');
        const btnAdmin   = document.getElementById('switch-to-admin');

        if (btnStudent) {
            btnStudent.addEventListener('click', () => this.switchView('student'));
        }

        if (btnAdmin) {
            btnAdmin.addEventListener('click', () => {
                if (this.isAdminAuthenticated) {
                    this.switchView('admin');
                } else {
                    this.switchView('auth');
                }
            });
        }
    },

    switchView(viewName) {
        if (viewName === 'admin' && !this.isAdminAuthenticated) {
            this.switchView('auth');
            return;
        }

        this.currentView = viewName;

        const viewAuth     = document.getElementById('view-auth');
        const viewStudent  = document.getElementById('view-student-app');
        const viewAdmin    = document.getElementById('view-admin-panel');
        const btnStudent   = document.getElementById('switch-to-student');
        const btnAdmin     = document.getElementById('switch-to-admin');
        const mainSwitcher = document.getElementById('main-view-switcher');

        // Desactivar todas las secciones principales
        if (viewAuth)    viewAuth.classList.remove('active');
        if (viewStudent) viewStudent.classList.remove('active');
        if (viewAdmin)   viewAdmin.classList.remove('active');

        if (viewName === 'auth') {
            if (viewAuth) viewAuth.classList.add('active');
            if (mainSwitcher) mainSwitcher.style.display = 'none';
            window.location.hash = '';
        } else if (viewName === 'student') {
            if (viewStudent) viewStudent.classList.add('active');
            if (btnStudent)  btnStudent.classList.add('active');
            if (btnAdmin)    btnAdmin.classList.remove('active');
            if (mainSwitcher && this.isAdminAuthenticated) mainSwitcher.style.display = 'inline-flex';
            window.location.hash = '#estudiante';
        } else if (viewName === 'admin') {
            if (viewAdmin)   viewAdmin.classList.add('active');
            if (btnAdmin)    btnAdmin.classList.add('active');
            if (btnStudent)  btnStudent.classList.remove('active');
            if (mainSwitcher) mainSwitcher.style.display = 'inline-flex';
            window.location.hash = '#admin';

            // Cargar datos del panel directivo si no se han cargado
            if (typeof AdminPanel !== 'undefined' && AdminPanel.init) {
                AdminPanel.init();
            }
        }
    },

    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

        toast.innerHTML = `
            <div style="font-size: 1.2rem;">${icons[type] || 'ℹ️'}</div>
            <div class="toast-content">
                <p style="font-weight: 600; color: var(--text-main);">${message}</p>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

