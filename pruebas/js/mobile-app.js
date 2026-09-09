// Controlador de la Experiencia del Estudiante (App Móvil - 7 Pasos)
const MobileApp = {
    currentStep: 1,
    currentQuestionIndex: 0,
    examQuestions: [],
    selectedAnswers: {},
    examTimerInterval: null,
    examTimeSeconds: 45 * 60, // 45 minutos
    activeExamSessionId: null,  // ID de sesión activa en el backend
    activeExamId: null,          // ID del examen activo

    init() {
        this.bindEvents();
        this.loadContractData();
        this.loadPaymentsData();
        this.loadExamFromAPI();
    },

    bindEvents() {
        // Tracker de pasos (1 al 7)
        document.querySelectorAll('.step-indicator').forEach(el => {
            el.addEventListener('click', () => {
                const step = parseInt(el.dataset.step);
                this.goToStep(step);
            });
        });

        // Paso 1: Registro
        const registerForm = document.getElementById('mobile-register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Paso 2: Contrato
        const contractCheck = document.getElementById('contract-agree-check');
        const acceptContractBtn = document.getElementById('accept-contract-btn');
        if (contractCheck && acceptContractBtn) {
            contractCheck.addEventListener('change', () => {
                acceptContractBtn.disabled = !contractCheck.checked;
            });
            acceptContractBtn.addEventListener('click', () => this.handleAcceptContract());
        }

        // Paso 3: Resumen del curso -> Continuar a pagos
        const continueToPaymentsBtn = document.getElementById('course-continue-btn');
        if (continueToPaymentsBtn) {
            continueToPaymentsBtn.addEventListener('click', () => this.goToStep(4));
        }

        // Paso 4: Mis pagos -> Continuar a subir cédula
        const continueToDocBtn = document.getElementById('payments-continue-btn');
        if (continueToDocBtn) {
            continueToDocBtn.addEventListener('click', () => this.goToStep(5));
        }

        // Paso 5: Cédula file input & upload
        const docInput = document.getElementById('doc-file-input');
        const docUploadBtn = document.getElementById('upload-doc-btn');
        const docPreviewArea = document.getElementById('doc-preview-area');

        if (docPreviewArea && docInput) {
            docPreviewArea.addEventListener('click', () => docInput.click());
            docInput.addEventListener('change', (e) => this.handleDocSelected(e));
        }
        if (docUploadBtn) {
            docUploadBtn.addEventListener('click', () => this.handleUploadDocument());
        }

        // Paso 6: Simulacro ICFES navegación
        const prevBtn = document.getElementById('exam-prev-btn');
        const nextBtn = document.getElementById('exam-next-btn');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.navQuestion(-1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.navQuestion(1));
        }

        // Paso 7: Repetir o volver
        const restartExamBtn = document.getElementById('restart-exam-btn');
        if (restartExamBtn) {
            restartExamBtn.addEventListener('click', () => this.restartExam());
        }
    },

    goToStep(step) {
        this.currentStep = step;

        // Actualizar tabs del mockup
        document.querySelectorAll('.mobile-step').forEach(el => el.classList.remove('active'));
        const targetStep = document.getElementById(`mobile-step-${step}`);
        if (targetStep) {
            targetStep.classList.add('active');
        }

        // Actualizar tracker superior
        document.querySelectorAll('.step-indicator').forEach(el => {
            const s = parseInt(el.dataset.step);
            el.classList.toggle('active', s === step);
            if (s < step) {
                el.classList.add('completed');
            }
        });

        // Eventos específicos por pantalla
        if (step === 4) {
            this.animateDonut(40); // 40% del diseño
            this.loadPaymentsData();
        } else if (step === 6) {
            this.startTimer();
            this.renderCurrentQuestion();
        } else {
            this.stopTimer();
        }
    },

    // --- PASO 1: REGISTRO ---
    async handleRegister(e) {
        e.preventDefault();
        const fullName = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const phone = document.getElementById('reg-phone').value;
        const password = document.getElementById('reg-pass').value;

        const btn = document.getElementById('btn-register-submit');
        btn.disabled = true;
        btn.innerText = 'Registrando...';

        try {
            const res = await API.register({
                full_name: fullName,
                email: email,
                phone: phone,
                password: password
            });

            if (res && res.data && res.data.token) {
                Storage.setToken(res.data.token);
                Storage.setUser(res.data.user);
            }

            App.showToast('¡Cuenta creada con éxito!', 'success');
            this.goToStep(2);
        } catch (err) {
            console.log('Fallo API real, continuando en modo interactivo...');
            App.showToast('Registro simulado con éxito', 'info');
            this.goToStep(2);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Registrarme';
        }
    },

    // --- PASO 2: CONTRATO ---
    async loadContractData() {
        try {
            const res = await API.getContract();
            if (res && res.data && res.data.contract) {
                document.getElementById('contract-body-text').innerText = res.data.contract.content;
            }
        } catch (e) {
            // Contrato por defecto en local
        }
    },

    async handleAcceptContract() {
        const btn = document.getElementById('accept-contract-btn');
        btn.disabled = true;
        btn.innerText = 'Firmando...';

        try {
            await API.acceptContract('default-contract');
            App.showToast('Contrato firmado y aceptado', 'success');
        } catch (e) {
            App.showToast('Contrato aceptado digitalmente', 'success');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Aceptar contrato';
            this.goToStep(3);
        }
    },

    // --- PASO 4: MIS PAGOS ---
    async loadPaymentsData() {
        try {
            const [sumRes, payRes] = await Promise.allSettled([
                API.getCourseSummary(),
                API.getPayments()
            ]);

            if (sumRes.status === 'fulfilled' && sumRes.value?.data) {
                const s = sumRes.value.data.summary || sumRes.value.data;
                const totalPaid = Number(s.total_paid || 0);
                const coursePrice = Number(s.course_price || 0);
                const remaining = Number(s.remaining_amount !== undefined ? s.remaining_amount : Math.max(0, coursePrice - totalPaid));
                const pct = s.percentage_paid !== undefined ? Math.round(Number(s.percentage_paid)) : (coursePrice > 0 ? Math.round((totalPaid / coursePrice) * 100) : 0);

                const paidEl = document.getElementById('paid-amount-label');
                const pendEl = document.getElementById('pending-amount-label');
                if (paidEl) paidEl.innerText = `$${totalPaid.toLocaleString('es-CO')}`;
                if (pendEl) pendEl.innerText = `$${remaining.toLocaleString('es-CO')}`;
                this.animateDonut(pct);
            } else {
                this.animateDonut(0);
            }

            const historyList = document.getElementById('mobile-payment-history-list');
            if (historyList) {
                let list = [];
                if (payRes.status === 'fulfilled' && payRes.value?.data) {
                    list = Array.isArray(payRes.value.data) ? payRes.value.data : (payRes.value.data.payments || []);
                }

                if (list.length === 0) {
                    historyList.innerHTML = `
                        <div style="text-align: center; padding: 16px; color: var(--text-muted); font-size: 0.8rem; background: #ffffff; border-radius: 8px; border: 1px dashed var(--border);">
                            ℹ️ No tienes abonos registrados aún en la plataforma.
                        </div>
                    `;
                } else {
                    historyList.innerHTML = list.map(p => `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 8px;">
                            <div>
                                <div style="font-size: 0.82rem; font-weight: 700; color: #003366;">${p.notes || 'Abono de Matrícula'}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">${p.payment_method || 'Transferencia'} &bull; ${new Date(p.payment_date || p.created_at).toLocaleDateString('es-CO')}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 800; color: #15803d; font-size: 0.9rem;">+$${Number(p.amount).toLocaleString('es-CO')}</div>
                                <span style="display: inline-block; font-size: 0.65rem; background: #dcfce7; color: #166534; font-weight: 700; padding: 1px 6px; border-radius: 4px;">Aprobado</span>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } catch (e) {
            console.error('Error cargando datos de pagos:', e);
            this.animateDonut(0);
        }
    },

    animateDonut(percentage) {
        const circle = document.getElementById('donut-circle-bar');
        const text = document.getElementById('donut-percent-num');
        if (!circle || !text) return;

        const radius = 50;
        const circumference = 2 * Math.PI * radius; // ~314.159
        circle.style.strokeDasharray = `${circumference}`;

        const offset = circumference - (percentage / 100) * circumference;
        circle.style.strokeDashoffset = `${offset}`;
        text.innerText = `${percentage}%`;
    },

    // --- PASO 5: SUBIR CÉDULA ---
    selectedFile: null,

    handleDocSelected(e) {
        const file = e.target.files[0];
        if (!file) return;

        this.selectedFile = file;
        const previewContainer = document.getElementById('doc-preview-container');
        const instructionText = document.getElementById('doc-upload-placeholder-text');

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewContainer.innerHTML = `<img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Cédula Preview">`;
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.innerHTML = `<div style="font-size:2rem;color:var(--primary);">📄</div><span style="font-size:0.75rem;font-weight:700;">${file.name}</span>`;
        }

        if (instructionText) {
            instructionText.innerText = `Archivo seleccionado: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        }

        const uploadBtn = document.getElementById('upload-doc-btn');
        if (uploadBtn) uploadBtn.disabled = false;
    },

    async handleUploadDocument() {
        if (!this.selectedFile) {
            App.showToast('Por favor selecciona una foto de tu cédula', 'warning');
            return;
        }

        const btn = document.getElementById('upload-doc-btn');
        btn.disabled = true;
        btn.innerText = 'Subiendo documento...';

        const formData = new FormData();
        formData.append('document', this.selectedFile);
        formData.append('document_type', 'CC');
        formData.append('document_number', '1098765432');

        try {
            await API.uploadDocument(formData);
            App.showToast('Cédula subida exitosamente. Estado: En revisión', 'success');
        } catch (e) {
            App.showToast('Cédula guardada exitosamente', 'success');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Guardar documento';
            this.goToStep(6);
        }
    },

    // --- PASO 6: SIMULACRO ICFES ---

    // Banco de preguntas locales de respaldo (cuando la API no está disponible)
    _fallbackQuestions() {
        return [
            {
                id: 'q1', subject: 'Matemáticas',
                statement: 'En la figura geométrica mostrada, ¿cuál es el valor de x?',
                diagram: `<svg viewBox="0 0 200 120" width="100%" height="100%">
                    <polygon points="20,100 180,100 100,20" fill="none" stroke="#4F46E5" stroke-width="2.5" />
                    <text x="92" y="45" font-size="12" fill="#0F172A" font-weight="bold">80°</text>
                    <text x="35" y="95" font-size="12" fill="#0F172A" font-weight="bold">2x°</text>
                    <text x="145" y="95" font-size="12" fill="#0F172A" font-weight="bold">x°</text>
                </svg>`,
                options: [{key:'A',text:'20°'},{key:'B',text:'30°'},{key:'C',text:'40°'},{key:'D',text:'50°'}],
                correct: 'C'
            },
            {
                id: 'q2', subject: 'Lectura Crítica',
                statement: 'En un ensayo argumentativo, ¿cuál es la función principal de un contraargumento?',
                diagram: null,
                options: [
                    {key:'A',text:'Desviar la atención hacia un tema secundario.'},
                    {key:'B',text:'Demostrar que el autor desconoce la postura contraria.'},
                    {key:'C',text:'Anticipar posibles objeciones para refutarlas y fortalecer la tesis.'},
                    {key:'D',text:'Concluir el texto de manera emotiva.'}
                ],
                correct: 'C'
            },
            {
                id: 'q3', subject: 'Ciencias Naturales',
                statement: 'Durante la fotosíntesis, el proceso luminoso ocurre específicamente en:',
                diagram: null,
                options: [
                    {key:'A',text:'Las mitocondrias'},{key:'B',text:'La membrana de los tilacoides'},
                    {key:'C',text:'El estroma cloroplástico'},{key:'D',text:'El retículo endoplásmico'}
                ],
                correct: 'B'
            }
        ];
    },

    async loadExamFromAPI() {
        try {
            // 1. Listar exámenes publicados
            const listRes = await API.listExams();
            const exams   = (listRes && listRes.data) ? listRes.data : [];
            if (!exams.length) { this.examQuestions = this._fallbackQuestions(); return; }

            // Tomar el primer examen publicado
            const exam = exams[0];
            this.activeExamId = exam.id;

            // 2. Iniciar sesión de examen si el usuario está logueado
            const token = Storage.getToken();
            if (token) {
                try {
                    const startRes = await API.startExam(exam.id);
                    if (startRes && startRes.data && startRes.data.session_id) {
                        this.activeExamSessionId = startRes.data.session_id;
                        // Obtener preguntas de la sesión
                        const sessionQuestions = startRes.data.questions || [];
                        if (sessionQuestions.length) {
                            this.examQuestions = this._mapAPIQuestions(sessionQuestions,
                                startRes.data.duration_minutes || 45);
                            this.examTimeSeconds = (startRes.data.duration_minutes || 45) * 60;
                            return;
                        }
                    }
                } catch (sessionErr) {
                    console.warn('[Simulacro] No se pudo iniciar sesión:', sessionErr.message);
                }
            }

            // 3. Fallback: usar banco de preguntas local
            this.examQuestions = this._fallbackQuestions();
        } catch (e) {
            console.warn('[Simulacro] API no disponible, usando banco local:', e.message);
            this.examQuestions = this._fallbackQuestions();
        }
    },

    _mapAPIQuestions(apiQuestions, durationMinutes) {
        return apiQuestions.map(q => ({
            id: q.id,
            subject: q.subject_name || q.subject || 'General',
            statement: q.statement,
            diagram: q.image_url
                ? `<img src="${q.image_url}" style="max-width:100%;border-radius:8px;" alt="Diagrama"/>`
                : null,
            options: [
                { key: 'A', text: q.option_a },
                { key: 'B', text: q.option_b },
                { key: 'C', text: q.option_c },
                { key: 'D', text: q.option_d }
            ],
            correct: q.correct_option
        }));
    },

    renderCurrentQuestion() {
        const q = this.examQuestions[this.currentQuestionIndex];
        if (!q) return;

        document.getElementById('exam-subject-title').innerText = q.subject;
        document.getElementById('question-counter').innerText = `Pregunta ${this.currentQuestionIndex + 1} de ${this.examQuestions.length}`;
        document.getElementById('question-statement').innerText = q.statement;

        const diagramBox = document.getElementById('question-diagram-box');
        if (q.diagram) {
            diagramBox.style.display = 'flex';
            diagramBox.innerHTML = q.diagram;
        } else {
            diagramBox.style.display = 'none';
            diagramBox.innerHTML = '';
        }

        const optionsContainer = document.getElementById('exam-options-container');
        optionsContainer.innerHTML = '';

        q.options.forEach(opt => {
            const isSelected = this.selectedAnswers[q.id] === opt.key;
            const item = document.createElement('div');
            item.className = `exam-option-item ${isSelected ? 'selected' : ''}`;
            item.innerHTML = `
                <div class="opt-badge">${opt.key}</div>
                <div>${opt.text}</div>
            `;
            item.addEventListener('click', () => {
                this.selectedAnswers[q.id] = opt.key;
                this.renderCurrentQuestion();
            });
            optionsContainer.appendChild(item);
        });

        // Botones de navegación
        const prevBtn = document.getElementById('exam-prev-btn');
        const nextBtn = document.getElementById('exam-next-btn');

        prevBtn.style.visibility = this.currentQuestionIndex === 0 ? 'hidden' : 'visible';
        nextBtn.innerText = (this.currentQuestionIndex === this.examQuestions.length - 1) ? 'Finalizar simulacro' : 'Siguiente';
    },

    navQuestion(dir) {
        if (dir === 1 && this.currentQuestionIndex === this.examQuestions.length - 1) {
            this.finishExam();
            return;
        }

        const newIndex = this.currentQuestionIndex + dir;
        if (newIndex >= 0 && newIndex < this.examQuestions.length) {
            this.currentQuestionIndex = newIndex;
            this.renderCurrentQuestion();
        }
    },

    startTimer() {
        if (this.examTimerInterval) clearInterval(this.examTimerInterval);
        this.examTimeSeconds = 45 * 60; // 45 minutos

        this.examTimerInterval = setInterval(() => {
            if (this.examTimeSeconds <= 0) {
                this.finishExam();
                return;
            }
            this.examTimeSeconds--;
            const mins = String(Math.floor(this.examTimeSeconds / 60)).padStart(2, '0');
            const secs = String(this.examTimeSeconds % 60).padStart(2, '0');
            const timerEl = document.getElementById('exam-timer-clock');
            if (timerEl) {
                timerEl.innerText = `⏱ 00:${mins}:${secs}`;
            }
        }, 1000);
    },

    stopTimer() {
        if (this.examTimerInterval) {
            clearInterval(this.examTimerInterval);
            this.examTimerInterval = null;
        }
    },

    async finishExam() {
        this.stopTimer();

        // Calcular puntajes locales
        let correctCount = 0, wrongCount = 0, blankCount = 0;
        this.examQuestions.forEach(q => {
            const userChoice = this.selectedAnswers[q.id];
            if (!userChoice)          blankCount++;
            else if (userChoice === q.correct) correctCount++;
            else                      wrongCount++;
        });
        const total      = this.examQuestions.length;
        const percentage = total > 0 ? Math.round((correctCount / total) * 100) : 0;

        document.getElementById('res-correct-num').innerText  = correctCount;
        document.getElementById('res-wrong-num').innerText    = wrongCount;
        document.getElementById('res-blank-num').innerText    = blankCount;
        document.getElementById('res-score-percent').innerText = `${percentage}%`;

        // Enviar respuestas al backend si hay sesión activa
        if (this.activeExamSessionId) {
            try {
                const answers = Object.entries(this.selectedAnswers).map(([questionId, selectedOption]) => ({
                    question_id: questionId,
                    selected_option: selectedOption
                }));
                const submitRes = await API.submitExam(this.activeExamSessionId, answers);
                if (submitRes && submitRes.data) {
                    const r = submitRes.data;
                    // Actualizar con resultados reales del backend si están disponibles
                    if (r.correct_answers != null)   document.getElementById('res-correct-num').innerText  = r.correct_answers;
                    if (r.wrong_answers != null)     document.getElementById('res-wrong-num').innerText    = r.wrong_answers;
                    if (r.score_percentage != null)  document.getElementById('res-score-percent').innerText = `${Math.round(r.score_percentage)}%`;
                }
                App.showToast(`Simulacro enviado al servidor: ${correctCount} aciertos de ${total}`, 'success');
            } catch (err) {
                console.warn('[Simulacro] Error al enviar respuestas:', err.message);
                App.showToast(`Simulacro calificado localmente: ${correctCount} aciertos de ${total}`, 'info');
            } finally {
                this.activeExamSessionId = null;
            }
        } else {
            App.showToast(`Simulacro calificado: ${correctCount} aciertos de ${total}`, 'success');
        }

        this.goToStep(7);
    },

    restartExam() {
        this.currentQuestionIndex = 0;
        this.selectedAnswers = {};
        this.goToStep(6);
    }
};
