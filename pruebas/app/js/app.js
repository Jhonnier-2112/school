// ===================================================================
// CONTROLADOR PRINCIPAL DE LA APP MÓVIL DEL ESTUDIANTE
// ===================================================================

const StudentApp = {
    currentView: 'view-home',
    currentUser: null,
    
    // Estado del Simulacro Activo
    activeExamSession: null,
    activeExamData: null,
    currentQuestionIdx: 0,
    userAnswers: {},
    examTimerInterval: null,
    remainingSeconds: 45 * 60,

    async init() {
        this.bindNavigationEvents();
        this.bindAuthEvents();
        this.bindContractEvents();
        this.bindDocEvents();
        this.bindExamRunnerEvents();

        // Verificar sesión existente o iniciar en modo demo/login
        await this.checkAuthStatus();
    },

    // -------------------------------------------------------------
    // 1. GESTIÓN DE AUTENTICACIÓN Y SESIÓN
    // -------------------------------------------------------------
    async checkAuthStatus() {
        const token = Storage.getToken();
        const user  = Storage.getUser();

        if (token && user) {
            this.currentUser = user;
            this.updateUserUI(user);
            await this.loadAllStudentData();
            this.switchView('view-home');
        } else {
            // Sin sesión -> mostrar pantalla de login
            this.switchView('view-auth');
        }
    },

    updateUserUI(user) {
        const name = user.full_name || 'Estudiante';
        const initial = name.charAt(0).toUpperCase();

        const avatarEl   = document.getElementById('header-avatar');
        const usernameEl = document.getElementById('header-username');
        const greetingEl = document.getElementById('home-greeting');
        const profNameEl = document.getElementById('profile-name');
        const profMailEl = document.getElementById('profile-email');
        const profAvatEl = document.getElementById('profile-avatar-large');

        if (avatarEl)   avatarEl.textContent = initial;
        if (usernameEl) usernameEl.textContent = name.split(' ')[0];
        if (greetingEl) greetingEl.textContent = `¡Hola, ${name.split(' ')[0]}! 👋`;
        if (profNameEl) profNameEl.textContent = name;
        if (profMailEl) profMailEl.textContent = user.email || '';
        if (profAvatEl) profAvatEl.textContent = initial;
    },

    bindAuthEvents() {
        // Switch entre Login y Registro
        const tabLogin = document.getElementById('auth-tab-login');
        const tabReg   = document.getElementById('auth-tab-register');
        const formLogin = document.getElementById('form-login');
        const formReg   = document.getElementById('form-register');

        if (tabLogin && tabReg) {
            tabLogin.addEventListener('click', () => {
                tabLogin.style.background = 'var(--bg-card)';
                tabLogin.style.color = 'var(--primary)';
                tabLogin.style.boxShadow = 'var(--shadow-sm)';
                tabReg.style.background = 'transparent';
                tabReg.style.color = 'var(--text-muted)';
                tabReg.style.boxShadow = 'none';
                formLogin.style.display = 'block';
                formReg.style.display = 'none';
            });

            tabReg.addEventListener('click', () => {
                tabReg.style.background = 'var(--bg-card)';
                tabReg.style.color = 'var(--primary)';
                tabReg.style.boxShadow = 'var(--shadow-sm)';
                tabLogin.style.background = 'transparent';
                tabLogin.style.color = 'var(--text-muted)';
                tabLogin.style.boxShadow = 'none';
                formLogin.style.display = 'none';
                formReg.style.display = 'block';
            });
        }

        // Envío de Login
        if (formLogin) {
            formLogin.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('login-email').value.trim();
                const pass  = document.getElementById('login-password').value;
                const btn   = document.getElementById('btn-login-submit');

                btn.disabled = true;
                btn.textContent = 'Verificando...';

                try {
                    const res = await API.login(email, pass);
                    if (res && res.data && res.data.token) {
                        Storage.setToken(res.data.token);
                        Storage.setUser(res.data.user);
                        this.currentUser = res.data.user;
                        this.updateUserUI(res.data.user);
                        this.showToast('¡Bienvenido/a de nuevo!', 'success');
                        await this.loadAllStudentData();
                        this.switchView('view-home');
                    }
                } catch (err) {
                    this.showToast(err.message || 'Error al iniciar sesión', 'error');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Entrar a la Plataforma';
                }
            });
        }

        // Envío de Registro
        if (formReg) {
            formReg.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name  = document.getElementById('reg-name').value.trim();
                const email = document.getElementById('reg-email').value.trim();
                const phone = document.getElementById('reg-phone').value.trim();
                const pass  = document.getElementById('reg-password').value;
                const btn   = document.getElementById('btn-reg-submit');

                btn.disabled = true;
                btn.textContent = 'Creando cuenta...';

                try {
                    const res = await API.register({
                        full_name: name,
                        email: email,
                        phone: phone,
                        password: pass
                    });

                    if (res && res.data && res.data.token) {
                        Storage.setToken(res.data.token);
                        Storage.setUser(res.data.user);
                        this.currentUser = res.data.user;
                        this.updateUserUI(res.data.user);
                        this.showToast('¡Cuenta creada exitosamente!', 'success');
                        await this.loadAllStudentData();
                        this.switchView('view-home');
                    } else {
                        this.showToast(res.message || 'Registro exitoso. Inicia sesión.', 'success');
                        tabLogin.click();
                    }
                } catch (err) {
                    this.showToast(err.message || 'Error al crear cuenta', 'error');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Registrarme como Estudiante';
                }
            });
        }

        // Acceso Demo Rápido
        const btnDemo = document.getElementById('btn-quick-demo');
        if (btnDemo) {
            btnDemo.addEventListener('click', async () => {
                btnDemo.disabled = true;
                btnDemo.textContent = 'Conectando demo...';
                try {
                    // Intenta login con estudiante registrado o simula perfil demo
                    const demoUser = {
                        id: 'demo-student-id',
                        full_name: 'Santiago Mendoza García',
                        email: 'santiago.estudiante@icfes.edu.co',
                        role: 'student'
                    };
                    Storage.setToken('demo-valid-student-jwt-token');
                    Storage.setUser(demoUser);
                    this.currentUser = demoUser;
                    this.updateUserUI(demoUser);
                    this.showToast('Sesión de prueba activada', 'info');
                    await this.loadAllStudentData();
                    this.switchView('view-home');
                } finally {
                    btnDemo.disabled = false;
                    btnDemo.textContent = '⚡ Acceso Rápido de Prueba (Demo Estudiante)';
                }
            });
        }

        // Botón Logout
        const btnLogout = document.getElementById('btn-logout');
        if (btnLogout) {
            btnLogout.addEventListener('click', () => {
                Storage.clearToken();
                this.currentUser = null;
                this.showToast('Sesión cerrada correctamente', 'info');
                this.switchView('view-auth');
            });
        }
    },

    // -------------------------------------------------------------
    // 2. NAVEGACIÓN ENTRE PANTALLAS
    // -------------------------------------------------------------
    bindNavigationEvents() {
        // Bottom Nav Items
        document.querySelectorAll('.bottom-nav .nav-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetView = btn.dataset.view;
                this.switchView(targetView);
            });
        });

        // Header User Badge
        const userBadge = document.getElementById('header-user-badge');
        if (userBadge) {
            userBadge.addEventListener('click', () => {
                if (Storage.isAuthenticated()) {
                    this.switchView('view-profile');
                } else {
                    this.switchView('view-auth');
                }
            });
        }

        // Accesos rápidos desde el Home
        const qContract = document.getElementById('quick-action-contract');
        const qPayments = document.getElementById('quick-action-payments');
        const qDoc      = document.getElementById('quick-action-doc');
        const btnHero   = document.getElementById('btn-hero-action');

        if (qContract) qContract.addEventListener('click', () => this.switchView('view-contract'));
        if (qPayments) qPayments.addEventListener('click', () => this.switchView('view-payments'));
        if (qDoc)      qDoc.addEventListener('click',      () => this.switchView('view-doc'));
        if (btnHero)   btnHero.addEventListener('click',   () => this.switchView('view-exams'));

        // Botón volver a simulacros desde resultados
        const btnBack = document.getElementById('btn-back-to-exams');
        if (btnBack)   btnBack.addEventListener('click',   () => this.switchView('view-exams'));
    },

    switchView(viewId) {
        this.currentView = viewId;

        // Mostrar vista activa
        document.querySelectorAll('.app-view').forEach(view => {
            view.classList.toggle('active', view.id === viewId);
        });

        // Actualizar icono activo en Bottom Nav
        document.querySelectorAll('.bottom-nav .nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.view === viewId);
        });

        // Si la vista es Auth, ocultar Bottom Nav
        const bottomNav = document.querySelector('.bottom-nav');
        if (bottomNav) {
            bottomNav.style.display = (viewId === 'view-auth') ? 'none' : 'flex';
        }

        // Scroll suave al inicio
        const content = document.querySelector('.app-content');
        if (content) content.scrollTop = 0;
    },

    // -------------------------------------------------------------
    // 3. CARGA DE DATOS DEL ESTUDIANTE (API & FALLBACK)
    // -------------------------------------------------------------
    async loadAllStudentData() {
        await Promise.allSettled([
            this.loadContract(),
            this.loadCourseSummary(),
            this.loadPayments(),
            this.loadDocumentStatus(),
            this.loadExams()
        ]);
    },

    // 3.1 Contrato Digital
    async loadContract() {
        try {
            const res = await API.getContract();
            const isSigned = res.data?.is_signed || false;
            this.renderContractState(isSigned, res.data?.signed_at);
        } catch (e) {
            // Fallback por defecto
            this.renderContractState(false);
        }
    },

    renderContractState(isSigned, signedAt = null) {
        const unsignedBox = document.getElementById('contract-unsigned-box');
        const signedBox   = document.getElementById('contract-signed-box');
        const metaEl      = document.getElementById('contract-signed-meta');
        const badgeHome   = document.getElementById('home-enrollment-badge');
        const kpiContract = document.getElementById('kpi-contract-status');

        if (isSigned) {
            if (unsignedBox) unsignedBox.style.display = 'none';
            if (signedBox)   signedBox.style.display = 'block';
            if (metaEl && signedAt) {
                metaEl.textContent = `Firmado digitalmente el ${new Date(signedAt).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' })}. Matrícula activa.`;
            }
            if (badgeHome) {
                badgeHome.className = 'badge badge-success';
                badgeHome.textContent = 'Matrícula Activa';
            }
            if (kpiContract) kpiContract.textContent = 'Firmado ✅';
        } else {
            if (unsignedBox) unsignedBox.style.display = 'block';
            if (signedBox)   signedBox.style.display = 'none';
            if (badgeHome) {
                badgeHome.className = 'badge badge-warning';
                badgeHome.textContent = 'Firma Pendiente';
            }
            if (kpiContract) kpiContract.textContent = 'Pendiente ✍️';
        }
    },

    bindContractEvents() {
        const check = document.getElementById('contract-agree-check');
        const btn   = document.getElementById('btn-sign-contract');

        if (check && btn) {
            check.addEventListener('change', () => {
                btn.disabled = !check.checked;
            });

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.textContent = 'Firmando digitalmente...';
                try {
                    // Obtiene ID de contrato activo
                    const contractRes = await API.getContract().catch(() => null);
                    const contractId = contractRes?.data?.contract?.id || 'default-contract-id';

                    await API.acceptContract(contractId);
                    this.showToast('¡Contrato firmado exitosamente! Matrícula activada.', 'success');
                    this.renderContractState(true, new Date().toISOString());
                } catch (err) {
                    this.showToast(err.message || 'Error al firmar contrato', 'error');
                    btn.disabled = false;
                    btn.textContent = '✍️ Firmar y Aceptar Contrato Digital';
                }
            });
        }
    },

    // 3.2 Resumen de Curso y Pagos
    async loadCourseSummary() {
        try {
            const res = await API.getCourseSummary();
            if (res && res.data) {
                const s = res.data.summary || res.data;
                const total = Number(s.course_price || res.data.total_price || 0);
                const paid  = Number(s.total_paid !== undefined ? s.total_paid : (res.data.total_paid || 0));
                const pending = Number(s.remaining_amount !== undefined ? s.remaining_amount : Math.max(0, total - paid));
                const percent = Number(s.percentage_paid !== undefined ? Math.round(s.percentage_paid) : (total > 0 ? Math.min(100, Math.round((paid / total) * 100)) : 0));

                const bar = document.getElementById('home-progress-bar');
                const pct = document.getElementById('home-progress-percent');
                if (bar) bar.style.width = `${percent}%`;
                if (pct) pct.textContent = `${percent}%`;

                const payPct = document.getElementById('pay-percent');
                const payPaid = document.getElementById('pay-amount-paid');
                const payPend = document.getElementById('pay-amount-pending');

                if (payPct)  payPct.textContent = `${percent}%`;
                if (payPaid) payPaid.textContent = `$${paid.toLocaleString('es-CO')}`;
                if (payPend) payPend.textContent = `$${pending.toLocaleString('es-CO')}`;
            }
        } catch (e) {
            console.error('Error cargando resumen:', e);
        }
    },

    async loadPayments() {
        const container = document.getElementById('payments-history-list');
        if (!container) return;

        try {
            const res = await API.getPayments();
            const list = (res && res.data) ? (Array.isArray(res.data) ? res.data : (res.data.payments || [])) : [];

            if (list.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 18px; color: var(--text-muted); font-size: 0.82rem; background: var(--bg-muted); border-radius: var(--radius-sm);">
                        ℹ️ No tienes abonos registrados aún en la plataforma.
                    </div>
                `;
                return;
            }

            container.innerHTML = list.map(p => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-muted); border-radius: var(--radius-sm); margin-bottom: 8px;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 700;">${p.notes || 'Abono de Matrícula'}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">${p.payment_method || 'Transferencia'} • ${new Date(p.payment_date || p.created_at).toLocaleDateString('es-CO')}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 800; color: var(--success); font-size: 0.95rem;">+$${Number(p.amount).toLocaleString('es-CO')}</div>
                        <span class="badge badge-success" style="font-size: 0.65rem;">Aprobado</span>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            console.error('Error cargando pagos:', e);
        }
    },

    // 3.3 Documento de Identidad (Cédula)
    async loadDocumentStatus() {
        try {
            const res = await API.getDocument();
            const doc = res && res.data && res.data.document;
            const kpiDoc = document.getElementById('kpi-doc-status');

            if (doc) {
                const status = doc.status || 'pending';
                const statusMap = {
                    'pending':  { icon: '⌛', title: 'Documento en Revisión', desc: 'Verificando con administración.', badge: 'En revisión ⌛', color: 'var(--warning)' },
                    'approved': { icon: '✅', title: 'Documento Aprobado',  desc: 'Tu documento de identidad fue verificado.', badge: 'Aprobado ✅', color: 'var(--success)' },
                    'rejected': { icon: '❌', title: 'Documento Rechazado', desc: doc.rejection_reason || 'Vuelve a subirlo con mayor claridad.', badge: 'Rechazado ❌', color: 'var(--danger)' }
                };

                const current = statusMap[status] || statusMap['pending'];
                document.getElementById('doc-status-icon').textContent  = current.icon;
                document.getElementById('doc-status-title').textContent = current.title;
                document.getElementById('doc-status-desc').textContent  = current.desc;
                if (kpiDoc) {
                    kpiDoc.textContent = current.badge;
                    kpiDoc.style.color = current.color;
                }
            }
        } catch (e) {
            // fallback
        }
    },

    bindDocEvents() {
        const dropZone = document.getElementById('doc-drop-zone');
        const fileInput = document.getElementById('doc-file-input');
        const fileNameEl = document.getElementById('doc-file-name');
        const uploadBtn = document.getElementById('btn-upload-doc');

        if (dropZone && fileInput) {
            dropZone.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    fileNameEl.textContent = `📎 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    fileNameEl.style.color = 'var(--primary)';
                    uploadBtn.disabled = false;
                }
            });
        }

        if (uploadBtn) {
            uploadBtn.addEventListener('click', async () => {
                if (!fileInput.files || !fileInput.files[0]) return;
                const file = fileInput.files[0];

                const formData = new FormData();
                formData.append('document', file);

                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Subiendo documento...';

                try {
                    await API.uploadDocument(formData);
                    this.showToast('¡Documento cargado correctamente! Pasó a revisión.', 'success');
                    await this.loadDocumentStatus();
                } catch (err) {
                    this.showToast(err.message || 'Error al subir documento', 'error');
                } finally {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = '⬆️ Subir Documento a la Plataforma';
                }
            });
        }
    },

    // 3.4 Simulacros ICFES
    async loadExams() {
        const container = document.getElementById('exams-list-container');
        if (!container) return;

        try {
            const res = await API.listExams();
            const exams = (res && res.data && res.data.exams) || [];

            if (exams.length === 0) {
                // Presentar simulacro inicial predeterminado
                container.innerHTML = `
                    <div class="card card-elevated">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <span class="badge badge-primary">OFICIAL ICFES</span>
                            <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">⏱️ 45 Minutos</span>
                        </div>
                        <h3 class="card-title">Simulacro Diagnóstico Saber 11°</h3>
                        <p class="card-subtitle" style="margin-bottom: 14px;">
                            Evalúa tus competencias en Matemáticas, Lectura Crítica, Ciencias y Sociales.
                        </p>
                        <button class="btn btn-primary btn-start-exam-trigger" data-exam-id="default-diagnostic">
                            🚀 Iniciar Simulacro Ahora
                        </button>
                    </div>
                `;
            } else {
                container.innerHTML = exams.map(exam => `
                    <div class="card card-elevated">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <span class="badge badge-primary">${exam.type || 'SIMULACRO'}</span>
                            <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">⏱️ ${exam.duration_minutes || 45} Min</span>
                        </div>
                        <h3 class="card-title">${exam.title}</h3>
                        <p class="card-subtitle" style="margin-bottom: 14px;">
                            ${exam.description || 'Simulacro oficial con preguntas estructuradas tipo ICFES.'}
                        </p>
                        <button class="btn btn-primary btn-start-exam-trigger" data-exam-id="${exam.id}">
                            🚀 Iniciar Simulacro Ahora
                        </button>
                    </div>
                `).join('');
            }

            // Bind triggers
            container.querySelectorAll('.btn-start-exam-trigger').forEach(btn => {
                btn.addEventListener('click', () => {
                    const examId = btn.dataset.examId;
                    this.launchExam(examId);
                });
            });
        } catch (e) {
            container.innerHTML = `
                <div class="card card-elevated">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <span class="badge badge-primary">OFICIAL ICFES</span>
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">⏱️ 45 Minutos</span>
                    </div>
                    <h3 class="card-title">Simulacro Diagnóstico Saber 11°</h3>
                    <p class="card-subtitle" style="margin-bottom: 14px;">
                        Evalúa tus competencias en Matemáticas, Lectura Crítica, Ciencias y Sociales.
                    </p>
                    <button class="btn btn-primary btn-start-exam-trigger" data-exam-id="default-diagnostic">
                        🚀 Iniciar Simulacro Ahora
                    </button>
                </div>
            `;
            container.querySelector('.btn-start-exam-trigger')?.addEventListener('click', () => {
                this.launchExam('default-diagnostic');
            });
        }
    },

    // -------------------------------------------------------------
    // 4. REPRODUCTOR DE SIMULACRO EN VIVO (EXAM RUNNER)
    // -------------------------------------------------------------
    async launchExam(examId) {
        this.userAnswers = {};
        this.currentQuestionIdx = 0;
        this.remainingSeconds = 45 * 60; // 45 minutos

        // Banco de preguntas de fallback de alta calidad tipo ICFES si la API está en modo inicial
        const defaultQuestions = [
            {
                id: 'q1',
                subject: 'Matemáticas',
                statement: 'Un tanque de agua de 1.200 litros se llena con dos llaves. La primera vierte 30 litros por minuto y la segunda 20 litros por minuto. Si ambas llaves se abren al mismo tiempo, ¿cuántos minutos tardará en llenarse por completo el tanque?',
                option_a: '20 minutos',
                option_b: '24 minutos',
                option_c: '30 minutos',
                option_d: '40 minutos',
                correct: 'B',
                explanation: 'Juntas vierten 30 + 20 = 50 L/min. Tiempo = 1.200 / 50 = 24 minutos.'
            },
            {
                id: 'q2',
                subject: 'Lectura Crítica',
                statement: 'En un ensayo argumentativo, la tesis del autor representa:',
                option_a: 'Un resumen descriptivo de los hechos históricos citados.',
                option_b: 'La postura o afirmación central que se busca defender mediante argumentos.',
                option_c: 'Una lista exhaustiva de contraejemplos sin conclusión.',
                option_d: 'Una cita textual obligatoria del diccionario de la RAE.',
                correct: 'B',
                explanation: 'La tesis es la afirmación nuclear que el autor sostiene y argumenta a lo largo del texto.'
            },
            {
                id: 'q3',
                subject: 'Ciencias Naturales',
                statement: 'Durante la fotosíntesis, las plantas absorben dióxido de carbono y agua para transformarlos principalmente en:',
                option_a: 'Glucosa y oxígeno gaseoso.',
                option_b: 'Ácido sulfúrico y nitrógeno.',
                option_c: 'Monóxido de carbono e hidrógeno.',
                option_d: 'Metano y sales minerales.',
                correct: 'A',
                explanation: 'La ecuación fotosintética básica produce glucosa (C6H12O6) y libera oxígeno (O2).'
            },
            {
                id: 'q4',
                subject: 'Sociales y Ciudadanas',
                statement: 'De acuerdo con la Constitución Política de Colombia de 1991, el mecanismo fundamental para la protección inmediata de los derechos constitucionales fundamentales vulnerados es:',
                option_a: 'La Acción de Cumplimiento.',
                option_b: 'La Acción de Tutela.',
                option_c: 'El Plebiscito vinculante.',
                option_d: 'La Consulta Popular.',
                correct: 'B',
                explanation: 'El Artículo 86 de la Constitución establece la Acción de Tutela para reclamar la protección inmediata de derechos fundamentales.'
            },
            {
                id: 'q5',
                subject: 'Inglés',
                statement: 'Choose the correct sentence to complete: "If she _______ hard, she will pass her ICFES exam."',
                option_a: 'studies',
                option_b: 'studied',
                option_c: 'study',
                option_d: 'will study',
                correct: 'A',
                explanation: 'El primer condicional en inglés utiliza presente simple en la cláusula de "if" ("studies") y "will + verbo" en la principal.'
            }
        ];

        try {
            // Intentar inicio en backend
            const res = await API.startExam(examId).catch(() => null);
            if (res && res.data && res.data.questions && res.data.questions.length > 0) {
                this.activeExamData = {
                    sessionId: res.data.session_id,
                    questions: res.data.questions
                };
            } else {
                this.activeExamData = {
                    sessionId: 'local-session-' + Date.now(),
                    questions: defaultQuestions
                };
            }
        } catch (e) {
            this.activeExamData = {
                sessionId: 'local-session-' + Date.now(),
                questions: defaultQuestions
            };
        }

        // Abrir runner
        const runner = document.getElementById('exam-runner');
        if (runner) runner.classList.add('active');

        this.startTimer();
        this.renderQuestion(0);
    },

    startTimer() {
        clearInterval(this.examTimerInterval);
        const timerDisplay = document.getElementById('runner-timer-display');

        this.examTimerInterval = setInterval(() => {
            this.remainingSeconds--;
            if (this.remainingSeconds <= 0) {
                clearInterval(this.examTimerInterval);
                this.finishExam();
                return;
            }

            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            if (timerDisplay) {
                timerDisplay.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
        }, 1000);
    },

    renderQuestion(index) {
        const questions = this.activeExamData?.questions || [];
        if (index < 0 || index >= questions.length) return;

        this.currentQuestionIdx = index;
        const q = questions[index];

        document.getElementById('runner-subject-badge').textContent = q.subject || 'Saber 11°';
        document.getElementById('runner-question-counter').textContent = `PREGUNTA ${index + 1} DE ${questions.length}`;
        document.getElementById('runner-question-text').textContent = q.statement || q.question_text || '';

        const scoreBadge = document.getElementById('runner-score-badge');
        if (scoreBadge) {
            const scoreType = q.score_type === 'percentage' ? '%' : 'Pts';
            const scoreWeight = q.score_weight || 1;
            scoreBadge.textContent = `Valor: ${scoreWeight} ${scoreType}`;
        }

        // Imagen ilustrativa
        const imgContainer = document.getElementById('runner-image-container');
        const questionImg  = document.getElementById('runner-question-img');
        if (imgContainer && questionImg) {
            if (q.image_url && q.image_url.trim() !== '') {
                questionImg.src = q.image_url;
                imgContainer.style.display = 'block';
            } else {
                imgContainer.style.display = 'none';
                questionImg.src = '';
            }
        }

        // Opciones según tipo
        const optsContainer = document.getElementById('runner-options-container');
        optsContainer.innerHTML = '';
        const currentAnswer = this.userAnswers[q.id] || '';
        const qType = q.question_type || 'multiple_choice';

        if (qType === 'open_text') {
            const textCard = document.createElement('div');
            textCard.style.cssText = 'background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; padding: 14px; margin-top: 10px;';
            textCard.innerHTML = `
                <label style="display: block; font-size: 0.8rem; color: #94A3B8; margin-bottom: 6px; font-weight: 600;">Escribe tu respuesta a continuación:</label>
                <textarea id="runner-open-input" placeholder="Ingresa aquí tu respuesta..." style="width: 100%; min-height: 90px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; padding: 10px; font-size: 0.95rem; resize: vertical; box-sizing: border-box; outline: none;">${currentAnswer}</textarea>
            `;
            optsContainer.appendChild(textCard);

            const textarea = textCard.querySelector('#runner-open-input');
            textarea.addEventListener('input', (e) => {
                this.userAnswers[q.id] = e.target.value;
            });
        } else {
            let options = [];
            if (qType === 'true_false') {
                options = [
                    { letter: 'A', text: q.option_a || 'Verdadero' },
                    { letter: 'B', text: q.option_b || 'Falso' }
                ];
            } else {
                options = [
                    { letter: 'A', text: q.option_a || 'Opción A' },
                    { letter: 'B', text: q.option_b || 'Opción B' },
                    { letter: 'C', text: q.option_c || 'Opción C' },
                    { letter: 'D', text: q.option_d || 'Opción D' }
                ];
            }

            options.forEach(opt => {
                const btn = document.createElement('button');
                btn.className = `option-btn ${currentAnswer === opt.letter ? 'selected' : ''}`;
                btn.innerHTML = `
                    <div class="option-letter">${opt.letter}</div>
                    <div style="line-height: 1.35;">${opt.text}</div>
                `;
                btn.addEventListener('click', () => {
                    this.userAnswers[q.id] = opt.letter;
                    this.renderQuestion(index);
                });
                optsContainer.appendChild(btn);
            });
        }

        // Botones Footer
        const prevBtn   = document.getElementById('btn-runner-prev');
        const nextBtn   = document.getElementById('btn-runner-next');
        const submitBtn = document.getElementById('btn-runner-submit');

        if (prevBtn) prevBtn.disabled = (index === 0);

        if (index === questions.length - 1) {
            if (nextBtn)   nextBtn.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'block';
        } else {
            if (nextBtn)   nextBtn.style.display = 'block';
            if (submitBtn) submitBtn.style.display = 'none';
        }
    },

    bindExamRunnerEvents() {
        const prevBtn = document.getElementById('btn-runner-prev');
        const nextBtn = document.getElementById('btn-runner-next');
        const submitBtn = document.getElementById('btn-runner-submit');
        const exitBtn = document.getElementById('btn-exit-exam');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.renderQuestion(this.currentQuestionIdx - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.renderQuestion(this.currentQuestionIdx + 1);
            });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', () => {
                this.finishExam();
            });
        }

        if (exitBtn) {
            exitBtn.addEventListener('click', () => {
                if (confirm('¿Deseas salir del simulacro? Se perderán las respuestas no enviadas.')) {
                    clearInterval(this.examTimerInterval);
                    const runner = document.getElementById('exam-runner');
                    if (runner) runner.classList.remove('active');
                }
            });
        }
    },

    async finishExam() {
        clearInterval(this.examTimerInterval);

        const runner = document.getElementById('exam-runner');
        if (runner) runner.classList.remove('active');

        // Enviar a backend si es posible
        if (this.activeExamData?.sessionId) {
            try {
                await API.submitExam(this.activeExamData.sessionId, this.userAnswers).catch(() => null);
            } catch (e) {}
        }

        // Calcular puntaje
        let correctCount = 0;
        const questions = this.activeExamData?.questions || [];

        questions.forEach(q => {
            const userChoice = this.userAnswers[q.id];
            const correctOpt = q.correct || q.correct_option || 'A';
            if (userChoice === correctOpt) {
                correctCount++;
            }
        });

        // Ponderación a escala ICFES 0 - 500
        const factor = questions.length > 0 ? (correctCount / questions.length) : 0.8;
        const finalScore = Math.round(factor * 500);

        // Actualizar vista de resultados
        const scoreVal = document.getElementById('results-score-value');
        const kpiBest  = document.getElementById('kpi-best-score');
        const kpiCount = document.getElementById('kpi-exam-count');

        if (scoreVal) scoreVal.textContent = finalScore;
        if (kpiBest)  kpiBest.textContent = `${finalScore} / 500`;
        if (kpiCount) kpiCount.textContent = `2 Realizados`;

        this.showToast(`¡Simulacro entregado! Puntaje obtenido: ${finalScore} / 500`, 'success');
        this.switchView('view-results');
    },

    // -------------------------------------------------------------
    // 5. UTILIDAD DE TOASTS
    // -------------------------------------------------------------
    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

        toast.innerHTML = `
            <div style="font-size: 1.2rem;">${icons[type] || 'ℹ️'}</div>
            <div style="font-weight: 600; color: var(--text-primary); line-height: 1.3;">${message}</div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3800);
    }
};

// Inicialización en DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    StudentApp.init();
});
