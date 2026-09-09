// Controlador del Panel Administrativo (Web) — DINÁMICO con API
const AdminPanel = {
    activeTab: 'dashboard',
    isInitialized: false,

    init() {
        window.AdminPanel = this;
        if (!this.isInitialized) {
            this.bindEvents();
            this.isInitialized = true;
        }
        this.renderKPIs();
        this.renderCharts();
        this.renderUsersTable();
        this.renderQuestionsTable();
        this.renderExamsTable();
        this.renderDocumentsTable();
        this.loadStudentsSelect();
    },

    bindEvents() {
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = item.dataset.tab;
                if (tab) this.switchTab(tab);
            });
        });

        const paymentForm = document.getElementById('admin-payment-form');
        if (paymentForm) {
            paymentForm.addEventListener('submit', (e) => this.handleRegisterPayment(e));
        }

        const btnNewUser = document.getElementById('btn-new-user');
        if (btnNewUser) {
            btnNewUser.addEventListener('click', () => this.openModal('user-modal'));
        }

        const formNewUser = document.getElementById('form-modal-user');
        if (formNewUser) {
            formNewUser.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = document.getElementById('btn-send-invite');
                const origText = submitBtn ? submitBtn.textContent : '';

                const fullName = document.getElementById('modal-user-name')?.value?.trim() || '';
                const email    = document.getElementById('modal-user-email')?.value?.trim() || '';
                const phone    = document.getElementById('modal-user-phone')?.value?.trim() || '';

                if (!fullName || !email) {
                    App.showToast('Por favor completa nombre y correo electrónico', 'warning');
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Enviando invitación...';
                }

                try {
                    const res = await API.inviteStudent({
                        full_name: fullName,
                        email:     email,
                        phone:     phone
                    });

                    const msg = res.message || 'Invitación enviada con éxito';
                    App.showToast(msg, res.data?.email_sent ? 'success' : 'info');

                    formNewUser.reset();
                    this.closeModal('user-modal');
                    await this.renderUsersTable();
                    await this.renderKPIs();
                } catch (err) {
                    App.showToast(err.message || 'Error al invitar estudiante', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = origText;
                    }
                }
            });
        }

        this.setupQuestionsAndExamsListeners();

        document.querySelectorAll('.modal-close-btn, .modal-cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) modal.classList.remove('active');
            });
        });
    },

    switchTab(tabId) {
        this.activeTab = tabId;
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.tab === tabId);
        });
        document.querySelectorAll('.admin-tab-pane').forEach(pane => pane.classList.remove('active'));
        const target = document.getElementById(`pane-${tabId}`);
        if (target) target.classList.add('active');

        if (tabId === 'matriculas') {
            this.loadMatriculasData();
        } else if (tabId === 'cronograma') {
            this.initCronograma();
        } else if (tabId === 'preguntas') {
            this.initPreguntas();
        } else if (tabId === 'simulacros') {
            this.renderExamsTable();
        } else if (tabId === 'pagos') {
            this.loadAdminPaymentsData();
        }
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    },

    // ─── UTILIDADES ────────────────────────────────────────────────────────────

    setKpiValue(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerText = val;
    },

    setKpiLoading(...ids) {
        ids.forEach(id => this.setKpiValue(id, '…'));
    },

    formatCOP(num) {
        return '$' + Number(num).toLocaleString('es-CO');
    },

    renderTableSkeleton(tbodyId, cols) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        const cell = '<td><span style="display:inline-block;width:80%;height:14px;background:#1e293b;border-radius:4px;opacity:.4;"></span></td>';
        tbody.innerHTML = Array(3).fill('<tr>' + Array(cols).fill(cell).join('') + '</tr>').join('');
    },

    renderTableEmpty(tbodyId, cols, msg) {
        msg = msg || 'Sin datos disponibles';
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="' + cols + '" style="text-align:center;color:#64748b;padding:2rem;">' + msg + '</td></tr>';
    },

    // ─── 1. KPIs ───────────────────────────────────────────────────────────────

    async renderKPIs() {
        this.setKpiLoading('kpi-students', 'kpi-revenue', 'kpi-pending', 'kpi-exams');
        try {
            const res = await API.getDashboardStats();
            const d   = res.data;
            this.setKpiValue('kpi-students', Number(d.total_students).toLocaleString('es-CO'));
            this.setKpiValue('kpi-revenue',  this.formatCOP(d.total_revenue));
            this.setKpiValue('kpi-pending',  this.formatCOP(d.pending_payments));
            this.setKpiValue('kpi-exams',    Number(d.completed_exams).toLocaleString('es-CO'));
        } catch (e) {
            console.warn('[KPIs] Error:', e.message);
            this.setKpiValue('kpi-students', 'N/D');
            this.setKpiValue('kpi-revenue',  'N/D');
            this.setKpiValue('kpi-pending',  'N/D');
            this.setKpiValue('kpi-exams',    'N/D');
        }
    },

    // ─── 2. GRÁFICAS ───────────────────────────────────────────────────────────

    async renderCharts() {
        const areaChart = document.getElementById('chart-payments-area');
        if (areaChart) {
            try {
                const res    = await API.getPaymentsByMonth();
                const months = (res && res.data && res.data.months) ? res.data.months : [];
                this._drawLineChart(areaChart, months, 'total', '#2563EB');
            } catch (e) {
                this._drawLineChartFallback(areaChart);
            }
        }

        const barChart = document.getElementById('chart-students-bars');
        if (barChart) {
            try {
                const res2    = await API.getStudentsByMonth();
                const months2 = (res2 && res2.data && res2.data.months) ? res2.data.months : [];
                this._drawBarChart(barChart, months2);
            } catch (e) {
                this._drawBarChartFallback(barChart);
            }
        }
    },

    _drawLineChart(container, months, key, color) {
        if (!months.length) { this._drawLineChartFallback(container); return; }
        const W = 450, H = 160, pad = 10;
        const values = months.map(m => m[key]);
        const max    = Math.max(...values) || 1;
        const pts    = months.map((m, i) => ({
            x: pad + (i / (months.length - 1 || 1)) * (W - pad * 2),
            y: H - pad - ((m[key] / max) * (H - pad * 2)),
            label: m.month
        }));
        const lineD   = pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');
        const areaD   = `${lineD} L ${pts[pts.length-1].x.toFixed(1)} ${H} L ${pts[0].x.toFixed(1)} ${H} Z`;
        const circles = pts.map((p, i) => {
            const extra = i === pts.length-1 ? 'stroke="#fff" stroke-width="2"' : '';
            return `<circle cx="${p.x.toFixed(1)}" cy="${p.y.toFixed(1)}" r="${i===pts.length-1?5:4}" fill="${color}" ${extra}/>`;
        }).join('');
        const labels  = pts.map(p => `<text x="${p.x.toFixed(1)}" y="${H+14}">${p.label}</text>`).join('');
        container.innerHTML = `<svg viewBox="0 0 ${W} ${H+20}" width="100%" height="100%" style="overflow:visible;">
            <defs><linearGradient id="chartGradDyn" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="${color}" stop-opacity="0.35"/>
                <stop offset="100%" stop-color="${color}" stop-opacity="0.0"/>
            </linearGradient></defs>
            <path d="${areaD}" fill="url(#chartGradDyn)"/>
            <path d="${lineD}" fill="none" stroke="${color}" stroke-width="3" stroke-linecap="round"/>
            ${circles}
            <g font-size="10" fill="#94A3B8" text-anchor="middle">${labels}</g>
        </svg>`;
    },

    _drawLineChartFallback(container) {
        container.innerHTML = `<svg viewBox="0 0 450 160" width="100%" height="100%" style="overflow:visible;">
            <defs><linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.35"/>
                <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.0"/>
            </linearGradient></defs>
            <path d="M 10 120 L 70 100 L 130 115 L 190 70 L 250 85 L 310 40 L 370 50 L 430 20 L 430 150 L 10 150 Z" fill="url(#chartGrad)"/>
            <path d="M 10 120 L 70 100 L 130 115 L 190 70 L 250 85 L 310 40 L 370 50 L 430 20" fill="none" stroke="#2563EB" stroke-width="3" stroke-linecap="round"/>
            <g font-size="10" fill="#94A3B8" text-anchor="middle">
                <text x="10" y="155">Ene</text><text x="70" y="155">Feb</text>
                <text x="130" y="155">Mar</text><text x="190" y="155">Abr</text>
                <text x="250" y="155">May</text><text x="310" y="155">Jun</text>
                <text x="370" y="155">Jul</text><text x="430" y="155">Ago</text>
            </g>
        </svg>`;
    },

    _drawBarChart(container, months) {
        if (!months.length) { this._drawBarChartFallback(container); return; }
        const maxVal = Math.max(...months.map(m => m.total)) || 1;
        container.innerHTML = months.map(item => {
            const h = Math.max(4, Math.round((item.total / maxVal) * 100));
            return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;">
                <span style="font-size:0.7rem;font-weight:700;color:#4F46E5;margin-bottom:4px;">${item.total}</span>
                <div style="width:28px;height:${h}%;background:linear-gradient(180deg,#4F46E5 0%,#818CF8 100%);border-radius:6px 6px 0 0;transition:height .5s ease;"></div>
                <span style="font-size:0.75rem;color:#94A3B8;margin-top:8px;">${item.month}</span>
            </div>`;
        }).join('');
    },

    _drawBarChartFallback(container) {
        this._drawBarChart(container, [
            {month:'Ene',total:35},{month:'Feb',total:55},{month:'Mar',total:90},
            {month:'Abr',total:70},{month:'May',total:120},{month:'Jun',total:85},{month:'Jul',total:140}
        ]);
    },

    // ─── 3. TABLAS ─────────────────────────────────────────────────────────────

    async renderUsersTable() {
        this.renderTableSkeleton('tbody-users', 6);
        try {
            const res      = await API.listStudents('', 50, 0);
            const students = (res && res.data && res.data.students) ? res.data.students : [];
            const tbody    = document.getElementById('tbody-users');
            if (!tbody) return;
            if (!students.length) { this.renderTableEmpty('tbody-users', 6, 'No hay estudiantes registrados'); return; }
            tbody.innerHTML = students.map(u => `
                <tr>
                    <td><strong>${u.full_name}</strong></td>
                    <td>${u.email}</td>
                    <td>${u.phone || '—'}</td>
                    <td><span style="font-size:0.72rem;background:#EEF2FF;color:#4338CA;padding:2px 8px;border-radius:999px;font-weight:700;">💻 Web & 📱 Móvil</span></td>
                    <td><span class="table-badge ${u.is_active ? 'active' : 'inactive'}">${u.is_active ? 'Activo' : 'Inactivo'}</span></td>
                    <td><div class="table-actions">
                        <button class="tbl-action-btn" title="Ver detalle"
                            onclick="AdminPanel.viewStudentDetail('${u.id}','${(u.full_name||'').replace(/'/g,"\\'")}')">👁️</button>
                    </div></td>
                </tr>`).join('');
        } catch (e) {
            this.renderTableEmpty('tbody-users', 6, '⚠️ Error al cargar estudiantes');
        }
    },

    async viewStudentDetail(id, name) {
        try {
            const res = await API.getStudentDetail(id);
            const ps  = res && res.data && res.data.payment_summary;
            if (!ps) return;
            App.showToast(`${name} — Pagado: ${this.formatCOP(ps.total_paid)} | Pendiente: ${this.formatCOP(ps.remaining_amount)} (${ps.percentage_paid}%)`, 'info');
        } catch (e) {
            App.showToast('No se pudo cargar el detalle', 'error');
        }
    },

    async renderDocumentsTable() {
        this.renderTableSkeleton('tbody-documents', 5);
        try {
            const res   = await API.listPendingDocuments(50, 0);
            const docs  = (res && res.data && res.data.documents) ? res.data.documents : [];
            const tbody = document.getElementById('tbody-documents');
            if (!tbody) return;
            if (!docs.length) { this.renderTableEmpty('tbody-documents', 5, 'Sin documentos pendientes 🎉'); return; }
            tbody.innerHTML = docs.map(d => `
                <tr>
                    <td><strong>${d.student_name || '—'}</strong></td>
                    <td>${d.document_type || 'CC'}</td>
                    <td>${(d.created_at || '').substring(0,10) || '—'}</td>
                    <td><span class="table-badge pending">Pendiente</span></td>
                    <td><div class="table-actions">
                        <button class="tbl-action-btn" title="Aprobar"
                            onclick="AdminPanel.reviewDoc('${d.id}','approved')">✅</button>
                        <button class="tbl-action-btn delete" title="Rechazar"
                            onclick="AdminPanel.reviewDoc('${d.id}','rejected')">❌</button>
                    </div></td>
                </tr>`).join('');
        } catch (e) {
            this.renderTableEmpty('tbody-documents', 5, '⚠️ Error al cargar documentos');
        }
    },

    async reviewDoc(docId, status) {
        try {
            await API.reviewDocument(docId, status, status === 'rejected' ? 'Documentación inválida' : '');
            App.showToast(status === 'approved' ? 'Documento aprobado ✅' : 'Documento rechazado ❌',
                status === 'approved' ? 'success' : 'error');
            this.renderDocumentsTable();
            this.renderKPIs();
        } catch (e) {
            App.showToast('Error al revisar documento', 'error');
        }
    },

    // ─── GESTIÓN DE PREGUNTAS Y SIMULACROS ──────────────────────────────────

    cachedSubjects: [],
    cachedAllQuestions: [],

    async initPreguntas() {
        await this.loadSubjectsForQuestions();
        await this.renderQuestionsTable();
    },

    async loadSubjectsForQuestions() {
        try {
            const res = await API.listSubjects();
            this.cachedSubjects = (res && res.data) ? res.data : [];

            const selFilter = document.getElementById('filter-q-subject');
            if (selFilter) {
                const cur = selFilter.value;
                selFilter.innerHTML = '<option value="">Todas las materias</option>'
                    + this.cachedSubjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
                selFilter.value = cur;
            }

            const selModal = document.getElementById('modal-q-subject');
            if (selModal) {
                selModal.innerHTML = this.cachedSubjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            }
        } catch (e) {
            console.warn('Error al cargar materias:', e);
        }
    },

    setupQuestionsAndExamsListeners() {
        const btnNewQuestion = document.getElementById('btn-new-question');
        if (btnNewQuestion) {
            btnNewQuestion.addEventListener('click', () => this.openQuestionModal());
        }

        const typeSelect = document.getElementById('modal-q-type');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => this.toggleQuestionTypeFields());
        }

        const scoreTypeSelect = document.getElementById('modal-q-score-type');
        if (scoreTypeSelect) {
            scoreTypeSelect.addEventListener('change', () => {
                const lbl = document.getElementById('lbl-score-val');
                const inp = document.getElementById('modal-q-score-weight');
                if (scoreTypeSelect.value === 'percentage') {
                    if (lbl) lbl.textContent = 'Ponderación (%)';
                    if (inp && (inp.value === '1.0' || inp.value === '1')) inp.value = '10';
                } else {
                    if (lbl) lbl.textContent = 'Valor (Puntos)';
                    if (inp && inp.value === '10') inp.value = '1.0';
                }
            });
        }

        // Subida de imagen ilustrativa
        const btnUploadImg = document.getElementById('btn-upload-q-img');
        const fileInput = document.getElementById('modal-q-image-file');
        if (btnUploadImg && fileInput) {
            btnUploadImg.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                try {
                    btnUploadImg.textContent = 'Subiendo...';
                    btnUploadImg.disabled = true;
                    const res = await API.uploadQuestionImage(file);
                    const imgUrl = res?.data?.image_url;
                    if (imgUrl) {
                        document.getElementById('modal-q-image-url').value = imgUrl;
                        const preview = document.getElementById('modal-q-image-preview');
                        const wrap = document.getElementById('modal-q-image-preview-wrap');
                        const nameEl = document.getElementById('modal-q-image-name');
                        if (preview) preview.src = imgUrl;
                        if (wrap) wrap.style.display = 'flex';
                        if (nameEl) nameEl.textContent = file.name;
                        App.showToast('Imagen cargada con éxito', 'success');
                    }
                } catch (err) {
                    App.showToast(err.message || 'Error al subir imagen', 'error');
                } finally {
                    btnUploadImg.textContent = 'Subir Imagen';
                    btnUploadImg.disabled = false;
                }
            });
        }

        const btnRemoveImg = document.getElementById('btn-remove-q-img');
        if (btnRemoveImg) {
            btnRemoveImg.addEventListener('click', () => {
                const urlEl = document.getElementById('modal-q-image-url');
                const fileEl = document.getElementById('modal-q-image-file');
                const wrap = document.getElementById('modal-q-image-preview-wrap');
                const prev = document.getElementById('modal-q-image-preview');
                if (urlEl) urlEl.value = '';
                if (fileEl) fileEl.value = '';
                if (prev) prev.src = '';
                if (wrap) wrap.style.display = 'none';
            });
        }

        // Formulario Guardar Pregunta
        const formQ = document.getElementById('form-modal-question');
        if (formQ) {
            formQ.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.saveQuestion();
            });
        }

        // Vista previa desde el modal de edición
        const btnPrevInModal = document.getElementById('btn-preview-question');
        if (btnPrevInModal) {
            btnPrevInModal.addEventListener('click', () => {
                const formData = this.getQuestionFormData();
                this.showPreviewModal(formData);
            });
        }

        // Filtros reactivos
        const filterSearch = document.getElementById('filter-q-search');
        let searchTimer = null;
        if (filterSearch) {
            filterSearch.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => this.renderQuestionsTable(), 300);
            });
        }
        ['filter-q-subject', 'filter-q-type', 'filter-q-difficulty'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => this.renderQuestionsTable());
        });

        const btnResetFilters = document.getElementById('btn-reset-q-filters');
        if (btnResetFilters) {
            btnResetFilters.addEventListener('click', () => {
                if (filterSearch) filterSearch.value = '';
                const fSub = document.getElementById('filter-q-subject');
                const fTyp = document.getElementById('filter-q-type');
                const fDif = document.getElementById('filter-q-difficulty');
                if (fSub) fSub.value = '';
                if (fTyp) fTyp.value = '';
                if (fDif) fDif.value = '';
                this.renderQuestionsTable();
            });
        }

        // Simulacros
        const btnNewExam = document.getElementById('btn-new-exam');
        if (btnNewExam) {
            btnNewExam.addEventListener('click', () => this.openExamModal());
        }

        const formExam = document.getElementById('form-modal-exam');
        if (formExam) {
            formExam.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.saveExam();
            });
        }

        const searchExamQ = document.getElementById('modal-exam-search-q');
        if (searchExamQ) {
            searchExamQ.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                document.querySelectorAll('#modal-exam-questions-list label').forEach(lbl => {
                    const text = lbl.textContent.toLowerCase();
                    lbl.style.display = text.includes(term) ? 'flex' : 'none';
                });
            });
        }

        const btnSelectAllQ = document.getElementById('btn-select-all-q');
        if (btnSelectAllQ) {
            btnSelectAllQ.addEventListener('click', () => {
                const cbs = Array.from(document.querySelectorAll('#modal-exam-questions-list input[type="checkbox"]'))
                    .filter(cb => cb.closest('label').style.display !== 'none');
                const allChecked = cbs.every(cb => cb.checked);
                cbs.forEach(cb => { cb.checked = !allChecked; });
                this.updateExamSelectedCount();
            });
        }
    },

    toggleQuestionTypeFields() {
        const type = document.getElementById('modal-q-type')?.value || 'multiple_choice';
        const panelMC = document.getElementById('panel-opt-multiple-choice');
        const panelTF = document.getElementById('panel-opt-true-false');
        const panelOT = document.getElementById('panel-opt-open-text');

        if (panelMC) panelMC.style.display = (type === 'multiple_choice') ? 'block' : 'none';
        if (panelTF) panelTF.style.display = (type === 'true_false') ? 'block' : 'none';
        if (panelOT) panelOT.style.display = (type === 'open_text') ? 'block' : 'none';
    },

    openQuestionModal(questionData = null) {
        const titleEl = document.getElementById('modal-q-title');
        const idEl = document.getElementById('modal-q-id');
        const subEl = document.getElementById('modal-q-subject');
        const typeEl = document.getElementById('modal-q-type');
        const scoreTypeEl = document.getElementById('modal-q-score-type');
        const scoreWeightEl = document.getElementById('modal-q-score-weight');
        const timeLimitEl = document.getElementById('modal-q-time-limit');
        const stmtEl = document.getElementById('modal-q-statement');
        const expEl = document.getElementById('modal-q-explanation');
        const imgUrlEl = document.getElementById('modal-q-image-url');
        const imgFileEl = document.getElementById('modal-q-image-file');
        const imgWrap = document.getElementById('modal-q-image-preview-wrap');
        const imgPrev = document.getElementById('modal-q-image-preview');
        const imgName = document.getElementById('modal-q-image-name');
        const optA = document.getElementById('modal-q-optA');
        const optB = document.getElementById('modal-q-optB');
        const optC = document.getElementById('modal-q-optC');
        const optD = document.getElementById('modal-q-optD');
        const correctText = document.getElementById('modal-q-correct-text');

        if (imgFileEl) imgFileEl.value = '';

        if (questionData) {
            if (titleEl) titleEl.textContent = 'Editar Pregunta ICFES';
            if (idEl) idEl.value = questionData.id || '';
            if (subEl) subEl.value = questionData.subject_id || (this.cachedSubjects[0]?.id || '');
            if (typeEl) typeEl.value = questionData.question_type || 'multiple_choice';
            if (scoreTypeEl) scoreTypeEl.value = questionData.score_type || 'points';
            if (scoreWeightEl) scoreWeightEl.value = questionData.score_weight || 1.0;
            if (timeLimitEl) timeLimitEl.value = questionData.time_limit_seconds || 0;
            if (stmtEl) stmtEl.value = questionData.statement || '';
            if (expEl) expEl.value = questionData.explanation || '';
            if (optA) optA.value = questionData.option_a || '';
            if (optB) optB.value = questionData.option_b || '';
            if (optC) optC.value = questionData.option_c || '';
            if (optD) optD.value = questionData.option_d || '';
            if (correctText) correctText.value = questionData.correct_answer_text || '';

            // Dificultad
            const diff = questionData.difficulty || 'medium';
            const radioDiff = document.querySelector(`input[name="q-diff-radio"][value="${diff}"]`);
            if (radioDiff) radioDiff.checked = true;

            // Opción correcta MC
            const correctOpt = questionData.correct_option || 'A';
            const radioOpt = document.querySelector(`input[name="q-correct-option"][value="${correctOpt}"]`);
            if (radioOpt) radioOpt.checked = true;

            // Opción correcta TF
            const radioTF = document.querySelector(`input[name="q-correct-tf"][value="${correctOpt}"]`);
            if (radioTF) radioTF.checked = true;

            // Imagen
            if (questionData.image_url) {
                if (imgUrlEl) imgUrlEl.value = questionData.image_url;
                if (imgPrev) imgPrev.src = questionData.image_url;
                if (imgName) imgName.textContent = questionData.image_url.split('/').pop();
                if (imgWrap) imgWrap.style.display = 'flex';
            } else {
                if (imgUrlEl) imgUrlEl.value = '';
                if (imgWrap) imgWrap.style.display = 'none';
            }
        } else {
            if (titleEl) titleEl.textContent = 'Nueva Pregunta ICFES';
            if (idEl) idEl.value = '';
            if (typeEl) typeEl.value = 'multiple_choice';
            if (scoreTypeEl) scoreTypeEl.value = 'points';
            if (scoreWeightEl) scoreWeightEl.value = '1.0';
            if (timeLimitEl) timeLimitEl.value = '0';
            if (stmtEl) stmtEl.value = '';
            if (expEl) expEl.value = '';
            if (optA) optA.value = '';
            if (optB) optB.value = '';
            if (optC) optC.value = '';
            if (optD) optD.value = '';
            if (correctText) correctText.value = '';
            if (imgUrlEl) imgUrlEl.value = '';
            if (imgWrap) imgWrap.style.display = 'none';

            const rMed = document.querySelector('input[name="q-diff-radio"][value="medium"]');
            if (rMed) rMed.checked = true;
            const rA = document.querySelector('input[name="q-correct-option"][value="A"]');
            if (rA) rA.checked = true;
        }

        const scoreLbl = document.getElementById('lbl-score-val');
        if (scoreLbl) {
            scoreLbl.textContent = (scoreTypeEl?.value === 'percentage') ? 'Ponderación (%)' : 'Valor (Puntos)';
        }

        this.toggleQuestionTypeFields();
        this.openModal('question-modal');
    },

    getQuestionFormData() {
        const id = document.getElementById('modal-q-id')?.value || '';
        const subjectId = document.getElementById('modal-q-subject')?.value || '';
        const statement = document.getElementById('modal-q-statement')?.value || '';
        const questionType = document.getElementById('modal-q-type')?.value || 'multiple_choice';
        const scoreType = document.getElementById('modal-q-score-type')?.value || 'points';
        const scoreWeight = parseFloat(document.getElementById('modal-q-score-weight')?.value || '1.0');
        const timeLimitSeconds = parseInt(document.getElementById('modal-q-time-limit')?.value || '0', 10);
        const difficulty = document.querySelector('input[name="q-diff-radio"]:checked')?.value || 'medium';
        const imageUrl = document.getElementById('modal-q-image-url')?.value || null;
        const explanation = document.getElementById('modal-q-explanation')?.value || '';

        let optionA = null;
        let optionB = null;
        let optionC = null;
        let optionD = null;
        let correctOption = null;
        let correctAnswerText = null;

        if (questionType === 'multiple_choice') {
            optionA = document.getElementById('modal-q-optA')?.value || '';
            optionB = document.getElementById('modal-q-optB')?.value || '';
            optionC = document.getElementById('modal-q-optC')?.value || '';
            optionD = document.getElementById('modal-q-optD')?.value || '';
            correctOption = document.querySelector('input[name="q-correct-option"]:checked')?.value || 'A';
        } else if (questionType === 'true_false') {
            optionA = 'Verdadero';
            optionB = 'Falso';
            correctOption = document.querySelector('input[name="q-correct-tf"]:checked')?.value || 'A';
        } else if (questionType === 'open_text') {
            correctAnswerText = document.getElementById('modal-q-correct-text')?.value || '';
        }

        const subObj = this.cachedSubjects.find(s => s.id === subjectId);

        return {
            id,
            subject_id: subjectId,
            subject_name: subObj?.name || 'Materia',
            statement,
            question_type: questionType,
            score_type: scoreType,
            score_weight: scoreWeight,
            time_limit_seconds: timeLimitSeconds,
            difficulty,
            image_url: imageUrl,
            explanation,
            option_a: optionA,
            option_b: optionB,
            option_c: optionC,
            option_d: optionD,
            correct_option: correctOption,
            correct_answer_text: correctAnswerText,
        };
    },

    async saveQuestion() {
        const data = this.getQuestionFormData();
        const btnSave = document.getElementById('btn-save-question');
        const origText = btnSave?.textContent;

        try {
            if (btnSave) {
                btnSave.textContent = 'Guardando...';
                btnSave.disabled = true;
            }

            if (data.id) {
                await API.updateQuestion(data.id, data);
                App.showToast('Pregunta actualizada correctamente', 'success');
            } else {
                await API.createQuestion(data);
                App.showToast('Pregunta creada y añadida al banco', 'success');
            }

            this.closeModal('question-modal');
            await this.renderQuestionsTable();
        } catch (err) {
            App.showToast(err.message || 'Error al guardar pregunta', 'error');
        } finally {
            if (btnSave) {
                btnSave.textContent = origText;
                btnSave.disabled = false;
            }
        }
    },

    async renderQuestionsTable() {
        this.renderTableSkeleton('tbody-questions', 8);

        const filters = {
            search: document.getElementById('filter-q-search')?.value?.trim() || '',
            subject_id: document.getElementById('filter-q-subject')?.value || '',
            type: document.getElementById('filter-q-type')?.value || '',
            difficulty: document.getElementById('filter-q-difficulty')?.value || '',
            limit: 150,
        };

        try {
            const res = await API.listQuestions(filters);
            const questions = (res && res.data && res.data.questions) ? res.data.questions : [];
            const tbody = document.getElementById('tbody-questions');
            if (!tbody) return;

            // Actualizar Mini KPIs
            const total = questions.length;
            const mcCount = questions.filter(q => (q.question_type || 'multiple_choice') === 'multiple_choice').length;
            const tfCount = questions.filter(q => q.question_type === 'true_false').length;
            const openCount = questions.filter(q => q.question_type === 'open_text').length;

            this.setKpiValue('kpi-q-total', total);
            this.setKpiValue('kpi-q-mc', mcCount);
            this.setKpiValue('kpi-q-tf', tfCount);
            this.setKpiValue('kpi-q-open', openCount);

            if (!questions.length) {
                this.renderTableEmpty('tbody-questions', 8, 'No se encontraron preguntas con los filtros seleccionados');
                return;
            }

            const diffMap = { easy: 'Fácil', medium: 'Medio', hard: 'Difícil' };
            const badgeMap = { easy: 'active', medium: 'pending', hard: 'inactive' };

            tbody.innerHTML = questions.map(q => {
                const qType = q.question_type || 'multiple_choice';
                let typeBadge = '';
                if (qType === 'multiple_choice') {
                    typeBadge = '<span style="background:#EFF6FF;color:#1D4ED8;font-size:0.72rem;font-weight:800;padding:3px 7px;border-radius:6px;">🔘 Opción Múltiple</span>';
                } else if (qType === 'true_false') {
                    typeBadge = '<span style="background:#FAF5FF;color:#7C3AED;font-size:0.72rem;font-weight:800;padding:3px 7px;border-radius:6px;">⚖️ Verdadero / Falso</span>';
                } else {
                    typeBadge = '<span style="background:#F0FDF4;color:#15803D;font-size:0.72rem;font-weight:800;padding:3px 7px;border-radius:6px;">✍️ Respuesta Abierta</span>';
                }

                const scoreLabel = (q.score_type === 'percentage')
                    ? `<span style="background:#ECFDF5;color:#059669;font-size:0.75rem;font-weight:800;padding:2px 6px;border-radius:4px;">${q.score_weight}%</span>`
                    : `<span style="background:#F1F5F9;color:#334155;font-size:0.75rem;font-weight:800;padding:2px 6px;border-radius:4px;">${parseFloat(q.score_weight).toFixed(1)} pts</span>`;

                const timerLabel = (q.time_limit_seconds > 0)
                    ? `<span style="color:#B45309;font-weight:700;font-size:0.75rem;">⏱️ ${q.time_limit_seconds}s</span>`
                    : `<span style="color:var(--text-muted);font-size:0.75rem;">Global</span>`;

                const imgThumb = q.image_url
                    ? `<img src="${q.image_url}" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0;cursor:pointer;" onclick="AdminPanel.previewQuestion('${q.id}')" title="Ver imagen">`
                    : `<div style="width:36px;height:36px;background:#F8FAFC;border:1px dashed #CBD5E1;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#94A3B8;">📝</div>`;

                const diff = diffMap[q.difficulty] || 'Medio';
                const badge = badgeMap[q.difficulty] || 'pending';

                return `<tr>
                    <td style="text-align:center;">${imgThumb}</td>
                    <td style="max-width:320px;">
                        <div style="font-weight:700;color:#0F172A;line-height:1.4;margin-bottom:2px;">${q.statement}</div>
                        ${q.explanation ? `<div style="font-size:0.72rem;color:var(--text-muted);" title="${q.explanation}">💡 ${q.explanation.substring(0, 55)}...</div>` : ''}
                    </td>
                    <td><span style="font-size:0.78rem;font-weight:700;color:#334155;">${q.subject_name || '—'}</span></td>
                    <td>${typeBadge}</td>
                    <td>${scoreLabel}</td>
                    <td>${timerLabel}</td>
                    <td><span class="table-badge ${badge}">${diff}</span></td>
                    <td style="text-align:right;">
                        <div class="table-actions" style="justify-content:flex-end;">
                            <button class="tbl-action-btn" title="Vista Previa" onclick="AdminPanel.previewQuestion('${q.id}')">👁️</button>
                            <button class="tbl-action-btn" title="Editar" onclick="AdminPanel.editQuestion('${q.id}')">✏️</button>
                            <button class="tbl-action-btn delete" title="Eliminar" onclick="AdminPanel.deleteQuestion('${q.id}')">🗑️</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            this.renderTableEmpty('tbody-questions', 8, '⚠️ Error al cargar las preguntas: ' + e.message);
        }
    },

    async editQuestion(id) {
        try {
            App.showToast('Cargando datos de la pregunta...', 'info');
            const res = await API.getQuestion(id);
            if (res?.data) {
                this.openQuestionModal(res.data);
            }
        } catch (e) {
            App.showToast('No se pudo cargar la pregunta', 'error');
        }
    },

    async previewQuestion(id) {
        try {
            const res = await API.getQuestion(id);
            if (res?.data) {
                this.showPreviewModal(res.data);
            }
        } catch (e) {
            App.showToast('Error al previsualizar pregunta', 'error');
        }
    },

    showPreviewModal(q) {
        const subBadge = document.getElementById('prev-q-subject-badge');
        const typeBadge = document.getElementById('prev-q-type-badge');
        const scoreBadge = document.getElementById('prev-q-score-badge');
        const timerBadge = document.getElementById('prev-q-timer-badge');
        const imgWrap = document.getElementById('prev-q-img-wrap');
        const imgEl = document.getElementById('prev-q-img');
        const stmtEl = document.getElementById('prev-q-statement');
        const optionsEl = document.getElementById('prev-q-options');
        const expWrap = document.getElementById('prev-q-exp-wrap');
        const expText = document.getElementById('prev-q-exp-text');

        if (subBadge) subBadge.textContent = (q.subject_name || 'MATERIA').toUpperCase();

        const qType = q.question_type || 'multiple_choice';
        if (typeBadge) {
            typeBadge.textContent = (qType === 'multiple_choice') ? '🔘 Selección Múltiple'
                : (qType === 'true_false') ? '⚖️ Verdadero / Falso'
                : '✍️ Respuesta Abierta';
        }

        if (scoreBadge) {
            scoreBadge.textContent = (q.score_type === 'percentage') ? `${q.score_weight}%` : `${parseFloat(q.score_weight || 1).toFixed(1)} pts`;
        }

        if (timerBadge) {
            timerBadge.textContent = (q.time_limit_seconds > 0) ? `⏱️ ${q.time_limit_seconds}s` : '⏱️ Tiempo Global';
        }

        if (q.image_url) {
            if (imgEl) imgEl.src = q.image_url;
            if (imgWrap) imgWrap.style.display = 'block';
        } else {
            if (imgWrap) imgWrap.style.display = 'none';
        }

        if (stmtEl) stmtEl.textContent = q.statement || 'Sin enunciado';

        if (optionsEl) {
            if (qType === 'multiple_choice') {
                const opts = [
                    { k: 'A', v: q.option_a },
                    { k: 'B', v: q.option_b },
                    { k: 'C', v: q.option_c },
                    { k: 'D', v: q.option_d }
                ];
                optionsEl.innerHTML = opts.map(o => {
                    const isCorrect = (q.correct_option === o.k);
                    return `<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;border:1.5px solid ${isCorrect ? '#10B981' : '#E2E8F0'};background:${isCorrect ? '#ECFDF5' : '#FFFFFF'};">
                        <span style="font-weight:800;color:${isCorrect ? '#047857' : 'var(--primary)'};width:20px;">${o.k})</span>
                        <span style="flex:1;font-size:0.88rem;color:#1E293B;">${o.v || '—'}</span>
                        ${isCorrect ? '<span style="font-size:0.75rem;font-weight:800;color:#047857;">CORRECTA ✅</span>' : ''}
                    </div>`;
                }).join('');
            } else if (qType === 'true_false') {
                const isTrue = (q.correct_option === 'A');
                optionsEl.innerHTML = `
                    <div style="display:flex;gap:12px;">
                        <div style="flex:1;padding:12px;border-radius:8px;border:1.5px solid ${isTrue ? '#10B981' : '#E2E8F0'};background:${isTrue ? '#ECFDF5' : '#FFF'};font-weight:700;display:flex;justify-content:space-between;align-items:center;">
                            <span>✅ Verdadero</span>
                            ${isTrue ? '<span style="font-size:0.72rem;color:#047857;font-weight:800;">CORRECTA</span>' : ''}
                        </div>
                        <div style="flex:1;padding:12px;border-radius:8px;border:1.5px solid ${!isTrue ? '#10B981' : '#E2E8F0'};background:${!isTrue ? '#ECFDF5' : '#FFF'};font-weight:700;display:flex;justify-content:space-between;align-items:center;">
                            <span>❌ Falso</span>
                            ${!isTrue ? '<span style="font-size:0.72rem;color:#047857;font-weight:800;">CORRECTA</span>' : ''}
                        </div>
                    </div>
                `;
            } else {
                // open_text
                optionsEl.innerHTML = `
                    <div style="background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:8px;padding:14px;">
                        <div style="font-size:0.78rem;font-weight:700;color:#64748B;margin-bottom:6px;">Campo de respuesta del estudiante (escribe directamente):</div>
                        <input type="text" class="mobile-input" placeholder="El estudiante redactará su respuesta aquí..." disabled style="background:#FFF;margin-bottom:10px;">
                        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:6px;padding:8px 12px;font-size:0.8rem;color:#065F46;">
                            <strong>Respuesta de referencia:</strong> ${q.correct_answer_text || '—'}
                        </div>
                    </div>
                `;
            }
        }

        if (q.explanation && expText && expWrap) {
            expText.textContent = q.explanation;
            expWrap.style.display = 'block';
        } else if (expWrap) {
            expWrap.style.display = 'none';
        }

        this.openModal('preview-question-modal');
    },

    async deleteQuestion(id) {
        if (!confirm('¿Estás seguro de eliminar esta pregunta del banco? Si está asignada a simulacros, se desvinculará de ellos.')) {
            return;
        }
        try {
            await API.deleteQuestion(id);
            App.showToast('Pregunta eliminada con éxito', 'success');
            await this.renderQuestionsTable();
        } catch (err) {
            App.showToast(err.message || 'Error al eliminar pregunta', 'error');
        }
    },

    // ─── GESTIÓN DE SIMULACROS ───────────────────────────────────────────────

    async renderExamsTable() {
        this.renderTableSkeleton('tbody-exams', 6);
        try {
            const res   = await API.listAllExams();
            const exams = (res && res.data && res.data.exams) ? res.data.exams : [];
            const tbody = document.getElementById('tbody-exams');
            if (!tbody) return;
            if (!exams.length) { this.renderTableEmpty('tbody-exams', 6, 'No hay simulacros creados'); return; }

            const timerModeMap = {
                exam: 'Global',
                per_question: 'Por Pregunta',
                both: 'Ambos (Global & Pregunta)'
            };

            tbody.innerHTML = exams.map(e => {
                const scoreBadge = (e.scoring_mode === 'percentage')
                    ? '<span style="background:#ECFDF5;color:#059669;font-size:0.75rem;font-weight:800;padding:3px 7px;border-radius:6px;">Porcentajes (%)</span>'
                    : '<span style="background:#EFF6FF;color:#1D4ED8;font-size:0.75rem;font-weight:800;padding:3px 7px;border-radius:6px;">Puntos Directos</span>';

                let timeInfo = '';
                if (e.timer_mode === 'per_question') {
                    timeInfo = `⏱️ ${e.time_per_question_seconds || 60}s por pregunta`;
                } else if (e.timer_mode === 'both') {
                    timeInfo = `⏱️ ${e.duration_minutes || 60}m global + ${e.time_per_question_seconds || 60}s/preg`;
                } else {
                    timeInfo = `⏱️ ${e.duration_minutes || 60} min global`;
                }

                return `<tr>
                    <td>
                        <div style="font-weight:800;color:#0F172A;">${e.title}</div>
                        ${e.description ? `<div style="font-size:0.75rem;color:var(--text-muted);">${e.description.substring(0, 60)}...</div>` : ''}
                    </td>
                    <td>${scoreBadge}</td>
                    <td><span style="font-size:0.78rem;font-weight:700;color:#334155;">${timeInfo}</span></td>
                    <td><span style="font-weight:800;color:var(--primary);">${e.questions_count != null ? e.questions_count : 0} preguntas</span></td>
                    <td><span class="table-badge ${e.is_published ? 'active' : 'inactive'}">${e.is_published ? 'Publicado 🚀' : 'Borrador 🔒'}</span></td>
                    <td style="text-align:right;">
                        <div class="table-actions" style="justify-content:flex-end;">
                            <button class="tbl-action-btn" title="Editar" onclick="AdminPanel.editExam('${e.id}')">✏️</button>
                            <button class="tbl-action-btn" title="${e.is_published ? 'Despublicar' : 'Publicar'}"
                                onclick="AdminPanel.togglePublish('${e.id}',${!!e.is_published})">
                                ${e.is_published ? '🔒' : '🚀'}
                            </button>
                            <button class="tbl-action-btn delete" title="Eliminar" onclick="AdminPanel.deleteExam('${e.id}')">🗑️</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            this.renderTableEmpty('tbody-exams', 6, '⚠️ Error al cargar simulacros: ' + e.message);
        }
    },

    async openExamModal(examData = null) {
        const head = document.getElementById('modal-exam-heading');
        const idEl = document.getElementById('modal-exam-id');
        const titleEl = document.getElementById('modal-exam-title');
        const descEl = document.getElementById('modal-exam-description');
        const scoringEl = document.getElementById('modal-exam-scoring-mode');
        const timerEl = document.getElementById('modal-exam-timer-mode');
        const durEl = document.getElementById('modal-exam-duration');
        const timeQEl = document.getElementById('modal-exam-time-per-q');
        const listEl = document.getElementById('modal-exam-questions-list');
        const countEl = document.getElementById('modal-exam-selected-count');

        if (examData) {
            if (head) head.textContent = 'Editar Simulacro ICFES';
            if (idEl) idEl.value = examData.id || '';
            if (titleEl) titleEl.value = examData.title || '';
            if (descEl) descEl.value = examData.description || '';
            if (scoringEl) scoringEl.value = examData.scoring_mode || 'points';
            if (timerEl) timerEl.value = examData.timer_mode || 'exam';
            if (durEl) durEl.value = examData.duration_minutes || 60;
            if (timeQEl) timeQEl.value = examData.time_per_question_seconds || 60;
        } else {
            if (head) head.textContent = 'Crear Nuevo Simulacro ICFES';
            if (idEl) idEl.value = '';
            if (titleEl) titleEl.value = '';
            if (descEl) descEl.value = '';
            if (scoringEl) scoringEl.value = 'points';
            if (timerEl) timerEl.value = 'exam';
            if (durEl) durEl.value = 60;
            if (timeQEl) timeQEl.value = 60;
        }

        // Cargar preguntas disponibles para asociar
        try {
            if (listEl) listEl.innerHTML = '<div style="padding:10px;text-align:center;font-size:0.8rem;color:var(--text-muted);">Cargando banco de preguntas...</div>';
            const res = await API.listQuestions({ limit: 200 });
            this.cachedAllQuestions = (res && res.data && res.data.questions) ? res.data.questions : [];

            const selectedIds = new Set(examData?.question_ids || []);

            if (listEl) {
                if (!this.cachedAllQuestions.length) {
                    listEl.innerHTML = '<div style="padding:10px;text-align:center;font-size:0.8rem;color:var(--text-muted);">No hay preguntas creadas aún. Crea preguntas primero.</div>';
                } else {
                    listEl.innerHTML = this.cachedAllQuestions.map(q => {
                        const checked = selectedIds.has(q.id) ? 'checked' : '';
                        const qType = (q.question_type === 'true_false') ? 'V/F' : (q.question_type === 'open_text') ? 'Abierta' : 'Múltiple';
                        return `<label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-bottom:1px solid #F1F5F9;font-size:0.78rem;cursor:pointer;">
                            <input type="checkbox" class="exam-q-checkbox" value="${q.id}" ${checked} onchange="AdminPanel.updateExamSelectedCount()">
                            <span style="font-weight:700;color:var(--primary);width:70px;">[${q.subject_code || 'ICFES'}]</span>
                            <span style="flex:1;color:#1E293B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${q.statement}</span>
                            <span style="font-size:0.7rem;color:var(--text-muted);">(${qType} &bull; ${q.score_weight || 1} ${q.score_type === 'percentage' ? '%' : 'pts'})</span>
                        </label>`;
                    }).join('');
                }
            }

            this.updateExamSelectedCount();
            this.openModal('exam-modal');
        } catch (e) {
            App.showToast('Error al cargar preguntas para el simulacro', 'error');
        }
    },

    updateExamSelectedCount() {
        const count = document.querySelectorAll('.exam-q-checkbox:checked').length;
        const el = document.getElementById('modal-exam-selected-count');
        if (el) el.textContent = count;
    },

    async saveExam() {
        const id = document.getElementById('modal-exam-id')?.value || '';
        const title = document.getElementById('modal-exam-title')?.value?.trim() || '';
        const description = document.getElementById('modal-exam-description')?.value?.trim() || '';
        const scoringMode = document.getElementById('modal-exam-scoring-mode')?.value || 'points';
        const timerMode = document.getElementById('modal-exam-timer-mode')?.value || 'exam';
        const duration = parseInt(document.getElementById('modal-exam-duration')?.value || '60', 10);
        const timePerQ = parseInt(document.getElementById('modal-exam-time-per-q')?.value || '60', 10);
        const questionIds = Array.from(document.querySelectorAll('.exam-q-checkbox:checked')).map(cb => cb.value);

        if (!title) {
            App.showToast('El título del simulacro es requerido', 'error');
            return;
        }
        if (!questionIds.length) {
            App.showToast('Debes seleccionar al menos una pregunta para el simulacro', 'error');
            return;
        }

        const payload = {
            title,
            description,
            scoring_mode: scoringMode,
            timer_mode: timerMode,
            duration_minutes: duration,
            time_per_question_seconds: timePerQ,
            question_ids: questionIds
        };

        const btnSave = document.getElementById('btn-save-exam');
        const origText = btnSave?.textContent;

        try {
            if (btnSave) {
                btnSave.textContent = 'Guardando...';
                btnSave.disabled = true;
            }

            if (id) {
                await API.updateExam(id, payload);
                App.showToast('Simulacro actualizado exitosamente', 'success');
            } else {
                await API.createExam(payload);
                App.showToast('Simulacro creado exitosamente', 'success');
            }

            this.closeModal('exam-modal');
            await this.renderExamsTable();
        } catch (err) {
            App.showToast(err.message || 'Error al guardar simulacro', 'error');
        } finally {
            if (btnSave) {
                btnSave.textContent = origText;
                btnSave.disabled = false;
            }
        }
    },

    async editExam(id) {
        try {
            App.showToast('Cargando simulacro...', 'info');
            const res = await API.getExam(id);
            if (res?.data) {
                this.openExamModal(res.data);
            }
        } catch (e) {
            App.showToast('Error al cargar simulacro', 'error');
        }
    },

    async deleteExam(id) {
        if (!confirm('¿Estás seguro de eliminar este simulacro? Las preguntas permanecerán en el banco.')) {
            return;
        }
        try {
            await API.deleteExam(id);
            App.showToast('Simulacro eliminado con éxito', 'success');
            await this.renderExamsTable();
        } catch (err) {
            App.showToast(err.message || 'Error al eliminar simulacro', 'error');
        }
    },

    async togglePublish(examId, isPublished) {
        try {
            await API.publishExam(examId, !isPublished);
            App.showToast(!isPublished ? 'Simulacro publicado 🚀' : 'Simulacro despublicado 🔒', 'success');
            await this.renderExamsTable();
        } catch (e) {
            App.showToast('Error al cambiar estado del simulacro', 'error');
        }
    },

    // ─── SELECT DINÁMICO DE ESTUDIANTE ─────────────────────────────────────────

    async loadStudentsSelect() {
        const select = document.getElementById('pay-student-select');
        if (!select) return;
        try {
            const res      = await API.listStudents('', 100, 0);
            const students = (res && res.data && res.data.students) ? res.data.students : [];
            if (!students.length) return;
            select.innerHTML = '<option value="">— Seleccionar estudiante —</option>'
                + students.map(s => `<option value="${s.id}">${s.full_name} (${s.email})</option>`).join('');
        } catch (e) { /* deja el select como está */ }
    },

    async loadAdminPaymentsData() {
        this.loadStudentsSelect();
        const tbody = document.getElementById('admin-payments-table-body');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">Cargando historial de abonos... ⏳</td></tr>`;

        try {
            const res = await API.listAdminPayments({ limit: 50 });
            const list = (res && res.data) ? (Array.isArray(res.data) ? res.data : (res.data.payments || [])) : [];

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">No se han registrado abonos hasta el momento.</td></tr>`;
                return;
            }

            const methodBadges = {
                'transfer': '<span class="badge badge-info" style="font-size: 0.72rem;">Transferencia</span>',
                'cash': '<span class="badge badge-success" style="font-size: 0.72rem;">Efectivo</span>',
                'card': '<span class="badge badge-warning" style="font-size: 0.72rem;">Tarjeta</span>',
                'nequi': '<span class="badge badge-info" style="font-size: 0.72rem; background: #6b21a8; color: #fff;">Nequi/Daviplata</span>'
            };

            tbody.innerHTML = list.map(p => `
                <tr>
                    <td style="font-weight: 600; color: #003366;">${p.student_name || 'Estudiante'}</td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">${p.student_email || ''}</td>
                    <td style="font-family: monospace; font-size: 0.8rem;">${p.receipt_number || 'S/N'}</td>
                    <td style="font-weight: 700; color: #15803d; font-size: 0.92rem;">${this.formatCOP(p.amount)}</td>
                    <td>${methodBadges[p.payment_method] || p.payment_method}</td>
                    <td style="font-size: 0.8rem; color: var(--text-muted);">${p.payment_date ? new Date(p.payment_date).toLocaleDateString('es-CO') : 'Reciente'}</td>
                    <td style="font-size: 0.8rem; color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${p.notes || ''}">${p.notes || '—'}</td>
                </tr>
            `).join('');
        } catch (err) {
            console.error('Error cargando abonos:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #ef4444;">Error al cargar abonos: ${err.message}</td></tr>`;
        }
    },

    // ─── 4. REGISTRAR ABONO ────────────────────────────────────────────────────

    async handleRegisterPayment(e) {
        e.preventDefault();
        const student = document.getElementById('pay-student-select').value;
        const amount  = document.getElementById('pay-amount').value;
        const date    = document.getElementById('pay-date').value;
        const method  = document.getElementById('pay-method').value;
        const notes   = document.getElementById('pay-notes').value;

        if (!student) { App.showToast('Selecciona un estudiante', 'warning'); return; }
        if (!amount || parseFloat(amount) <= 0) { App.showToast('Ingresa un monto válido', 'warning'); return; }

        const btn = document.getElementById('btn-submit-payment');
        btn.disabled  = true;
        btn.innerText = 'Registrando abono...';

        try {
            await API.registerPayment({
                user_id:        student,
                amount:         parseFloat(amount),
                payment_date:   date,
                payment_method: method,
                notes:          notes
            });
            App.showToast(`Abono de ${this.formatCOP(parseFloat(amount))} registrado exitosamente`, 'success');
            this.renderKPIs();
            this.loadAdminPaymentsData();
        } catch (err) {
            App.showToast(err.message || 'Error al registrar abono', 'error');
        } finally {
            btn.disabled  = false;
            btn.innerText = 'Registrar abono';
            document.getElementById('pay-amount').value = '';
            document.getElementById('pay-notes').value  = '';
        }
    },

    // ─── 5. GESTIÓN DE MATRÍCULAS 2026 (JEAN PIAGET SCHOOL) ────────────────────

    selectedMatriculaId: null,

    async loadMatriculasData() {
        // Cargar estadísticas
        try {
            const statsRes = await API.getEnrollmentStatistics();
            if (statsRes && statsRes.success && statsRes.data) {
                const s = statsRes.data.summary || {};
                this.setKpiValue('kpi-mat-total', s.total || 0);
                this.setKpiValue('kpi-mat-pending', s.pending_review || 0);
                this.setKpiValue('kpi-mat-signed', s.signed || 0);
                this.setKpiValue('kpi-mat-approved', s.approved || 0);
                this.setKpiValue('kpi-mat-draft', s.draft || 0);
                this.setKpiValue('kpi-mat-rejected', s.rejected || 0);
            }
        } catch (err) {
            console.warn('Error cargando estadísticas de matrícula:', err);
        }

        // Cargar lista con filtros
        const search = document.getElementById('mat-filter-search')?.value?.trim() || '';
        const status = document.getElementById('mat-filter-status')?.value || '';
        const level = document.getElementById('mat-filter-level')?.value || '';

        const tbody = document.getElementById('matriculas-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">Cargando matrículas... ⏳</td></tr>`;
        }

        try {
            const res = await API.listAdminEnrollments({
                search,
                status,
                level,
                year: 2026
            });

            if (res && res.success && res.data) {
                this.renderMatriculasTable(res.data.data || []);
            } else {
                if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">No se encontraron solicitudes de matrícula.</td></tr>`;
            }
        } catch (err) {
            console.error('Error al listar matrículas:', err);
            if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 24px; color: #ef4444;">Error al cargar matrículas: ${err.message}</td></tr>`;
        }

        // Bind evento de guardar decisión si no está vinculado
        const btnSave = document.getElementById('btn-save-mat-decision');
        if (btnSave && !btnSave.dataset.bound) {
            btnSave.dataset.bound = 'true';
            btnSave.addEventListener('click', () => this.saveMatriculaDecision());
        }
    },

    renderMatriculasTable(rows) {
        const tbody = document.getElementById('matriculas-table-body');
        if (!tbody) return;

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">No se encontraron matrículas con los filtros seleccionados.</td></tr>`;
            return;
        }

        const statusBadges = {
            'DRAFT': '<span style="background: #e2e8f0; color: #334155; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">BORRADOR</span>',
            'PENDING_REVIEW': '<span style="background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">PEND. REVISIÓN</span>',
            'SIGNED': '<span style="background: #e0e7ff; color: #4338ca; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">FIRMADA</span>',
            'APPROVED': '<span style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">APROBADA</span>',
            'REJECTED': '<span style="background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">RECHAZADA</span>',
            'DOCUMENTS_PENDING': '<span style="background: #ffedd5; color: #c2410c; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">CORRECCIONES</span>'
        };

        const levelShort = {
            'PREESCOLAR': 'Preescolar',
            'BASICA_PRIMARIA': 'Primaria',
            'BACHILLERATO_CICLOS': 'Ciclos'
        };

        tbody.innerHTML = rows.map(r => `
            <tr>
                <td><strong>${r.code}</strong></td>
                <td>
                    <div style="font-weight: 600;">${r.student_first_name || ''} ${r.student_last_name || ''}</div>
                </td>
                <td>${r.student_doc_type || 'TI'} ${r.student_doc_number || ''}</td>
                <td>
                    <span style="font-size: 0.78rem; color: #64748b;">${levelShort[r.enrollment_type] || r.enrollment_type}</span><br>
                    <strong>${r.target_grade}</strong>
                </td>
                <td>
                    <div>${r.guardian_name || 'N/A'}</div>
                    <span style="font-size: 0.75rem; color: #64748b;">${r.guardian_relationship || ''}</span>
                </td>
                <td>${r.guardian_phone || 'N/A'}</td>
                <td>${statusBadges[r.status] || r.status}</td>
                <td>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #003366;">
                        📑 ${r.documents_count || 0} docs
                    </span>
                    ${r.signatures_count > 0 ? '<span title="Firmado digitalmente" style="color: #16a34a; margin-left: 4px;">✔</span>' : ''}
                </td>
                <td style="text-align: right;">
                    <button class="tbl-action-btn view btn-view-matricula" data-id="${r.id}" title="Revisar expediente completo" onclick="AdminPanel.viewMatriculaDetail('${r.id}')" style="padding: 4px 10px; font-size: 0.8rem; background: #003366; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        🔍 Revisar
                    </button>
                </td>
            </tr>
        `).join('');

        tbody.querySelectorAll('.btn-view-matricula').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                if (id) {
                    if (window.AdminPanel && window.AdminPanel.viewMatriculaDetail) {
                        window.AdminPanel.viewMatriculaDetail(id);
                    } else {
                        AdminPanel.viewMatriculaDetail(id);
                    }
                }
            });
        });
    },

    async viewMatriculaDetail(enrollmentId) {
        this.selectedMatriculaId = enrollmentId;
        const modal = document.getElementById('matricula-detail-modal');
        const content = document.getElementById('modal-mat-content');
        if (!content) return;

        content.innerHTML = `<div style="text-align: center; padding: 30px;">Cargando expediente completo... ⏳</div>`;
        this.openModal('matricula-detail-modal');

        try {
            const res = await API.getAdminEnrollmentDetail(enrollmentId);
            if (!res || !res.success || !res.data) {
                content.innerHTML = `<div style="color: #ef4444; padding: 20px; text-align: center;">Error al obtener expediente: ${res ? res.message : 'Sin respuesta'}</div>`;
                return;
            }

            const d = res.data;
            const e = d.enrollment || {};
            const s = d.student || {};
            const f = d.father || {};
            const m = d.mother || {};
            const g = d.guardian || {};
            const ac = d.academic || {};
            const ec = d.economics || {};
            const sig = d.signature;
            const docs = Array.isArray(d.documents) ? d.documents : [];
            const logs = Array.isArray(d.audit_logs) ? d.audit_logs : [];

            const titleEl = document.getElementById('modal-mat-title');
            if (titleEl) titleEl.textContent = `Expediente: ${s.first_name || ''} ${s.last_name || ''}`.trim() || `Expediente: ${e.code || ''}`;
            const codeEl = document.getElementById('modal-mat-code');
            if (codeEl) codeEl.textContent = e.code || 'MAT-2026';
            const actionStatus = document.getElementById('modal-mat-action-status');
            if (actionStatus) actionStatus.value = e.status === 'APPROVED' ? 'APPROVED' : (e.status === 'REJECTED' ? 'REJECTED' : (e.status === 'DOCUMENTS_PENDING' ? 'DOCUMENTS_PENDING' : 'APPROVED'));
            const actionObs = document.getElementById('modal-mat-action-obs');
            if (actionObs) actionObs.value = e.observations || '';

            const fmt = (n) => '$' + Number(n || 0).toLocaleString('es-CO');

            let docsHtml = docs.map(doc => {
                const hashStr = doc.file_hash ? (doc.file_hash.length > 16 ? doc.file_hash.substr(0, 16) + '...' : doc.file_hash) : 'Verificado';
                const fileUrl = doc.file_url || '#';
                return `
                <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; background: #ffffff;">
                    <div>
                        <strong style="color: #0f172a; font-size: 0.88rem;">📄 ${doc.title || 'Documento Oficial'}</strong><br>
                        <span style="font-size: 0.75rem; color: #64748b;">Versión ${doc.version || '1.0'} &bull; SHA-256: ${hashStr}</span>
                    </div>
                    <a href="${fileUrl}" target="_blank" download class="admin-action-btn" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; background: #003366; color: white;">
                        📥 Descargar PDF
                    </a>
                </div>
            `;}).join('');

            if (docs.length === 0) {
                docsHtml = `<p style="font-size: 0.82rem; color: #64748b;">No hay documentos generados aún.</p>`;
            }

            let sigHtml = '';
            if (sig) {
                sigHtml = `
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; margin-top: 14px;">
                        <h4 style="color: #003366; margin: 0 0 8px; font-size: 0.9rem;">✍️ Constancia de Firma Electrónica (Ley 527 de 1999)</h4>
                        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                            <div style="background: #ffffff; border: 1px dashed #94a3b8; border-radius: 6px; padding: 6px 12px;">
                                <img src="${sig.signature_data}" style="max-height: 50px; max-width: 160px;" alt="Firma">
                            </div>
                            <div style="font-size: 0.8rem; color: #334155; line-height: 1.4;">
                                <div><strong>Firmante:</strong> ${sig.signer_name} (${sig.signer_doc_type} ${sig.signer_doc_number})</div>
                                <div><strong>Fecha y Hora:</strong> ${sig.signed_at}</div>
                                <div><strong>Dirección IP:</strong> ${sig.ip_address}</div>
                                <div><strong>Hash Verificador:</strong> <code style="font-size: 0.72rem; color: #003366;">${sig.hash_verification}</code></div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                sigHtml = `
                    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 10px; margin-top: 14px; color: #92400e; font-size: 0.82rem;">
                        ⚠️ Documentos pendientes de firma por el acudiente.
                    </div>
                `;
            }

            let auditHtml = logs.map(l => `
                <div style="font-size: 0.78rem; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; display: flex; justify-content: space-between;">
                    <div>
                        <strong>${l.action}</strong>
                        ${l.notes ? ` &bull; <span style="color: #475569;">${l.notes}</span>` : ''}
                    </div>
                    <div style="color: #64748b;">${l.created_at}</div>
                </div>
            `).join('');

            content.innerHTML = `
                <!-- Cuadrícula de Resumen -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <h4 style="color: #003366; margin: 0 0 8px; font-size: 0.9rem;">1. Estudiante</h4>
                        <div style="font-size: 0.82rem; line-height: 1.5;">
                            <div><strong>Nombre:</strong> ${s.first_name || ''} ${s.last_name || ''}</div>
                            <div><strong>Documento:</strong> ${s.doc_type || 'TI'} ${s.doc_number || ''} (Exp. ${s.doc_issue_place || 'N/A'})</div>
                            <div><strong>Nacimiento:</strong> ${s.birth_date || ''} (${s.age || ''} años) en ${s.birth_place || ''}</div>
                            <div><strong>E.P.S. / RH:</strong> ${s.eps || ''} / ${s.rh || ''}</div>
                            <div><strong>Dirección:</strong> ${s.address || ''}</div>
                            <div><strong>Teléfono:</strong> ${s.phone || ''} &bull; <strong>Correo:</strong> ${s.email || 'N/A'}</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <h4 style="color: #003366; margin: 0 0 8px; font-size: 0.9rem;">2. Acudiente Responsable</h4>
                        <div style="font-size: 0.82rem; line-height: 1.5;">
                            <div><strong>Nombre:</strong> ${g.full_name || 'N/A'} (${g.relationship || 'Acudiente'})</div>
                            <div><strong>Documento:</strong> ${g.doc_type || 'CC'} ${g.doc_number || ''}</div>
                            <div><strong>Celular:</strong> ${g.phone || 'N/A'}</div>
                            <div><strong>Correo:</strong> ${g.email || 'N/A'}</div>
                            <div><strong>Dirección:</strong> ${g.address || 'N/A'}</div>
                            <div><strong>Ocupación:</strong> ${g.occupation || 'N/A'}</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <h4 style="color: #003366; margin: 0 0 8px; font-size: 0.9rem;">3. Datos Académicos</h4>
                        <div style="font-size: 0.82rem; line-height: 1.5;">
                            <div><strong>Nivel:</strong> ${e.enrollment_type}</div>
                            <div><strong>Grado Aspirado:</strong> <strong style="color: #003366; font-size: 0.9rem;">${e.target_grade} (Año ${e.school_year})</strong></div>
                            <div><strong>Colegio Anterior:</strong> ${ac.previous_school || 'N/A'}</div>
                            <div><strong>Último Grado:</strong> ${ac.previous_grade || 'N/A'} (${ac.previous_year || ''})</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <h4 style="color: #003366; margin: 0 0 8px; font-size: 0.9rem;">4. Valores Económicos</h4>
                        <div style="font-size: 0.82rem; line-height: 1.5;">
                            <div><strong>Matrícula:</strong> ${fmt(ec.enrollment_fee || 180000)}</div>
                            <div><strong>Pensión Mensual:</strong> ${fmt(ec.monthly_fee || 150000)} (10 cuotas)</div>
                            <div><strong>Total Año Escolar:</strong> <strong style="color: #15803d;">${fmt(ec.total_tuition || 1680000)}</strong></div>
                            <div><strong>Forma de Pago:</strong> Mensual dentro de primeros 5 días</div>
                        </div>
                    </div>
                </div>

                <!-- Documentos Oficiales Generados -->
                <h4 style="color: #003366; margin: 18px 0 8px; font-size: 0.95rem;">📑 Documentos Oficiales Generados (PDF)</h4>
                ${docsHtml}

                <!-- Firma Digital -->
                ${sigHtml}

                <!-- Historial de Auditoría -->
                <div style="margin-top: 20px;">
                    <h4 style="color: #475569; font-size: 0.85rem; margin-bottom: 8px;">Historial de Auditoría:</h4>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; max-height: 130px; overflow-y: auto;">
                        ${auditHtml || '<p style="font-size: 0.75rem; color: #94a3b8; margin: 0;">Sin eventos registrados.</p>'}
                    </div>
                </div>
            `;
        } catch (err) {
            content.innerHTML = `<div style="color: red; padding: 20px;">Error al cargar detalle: ${err.message}</div>`;
        }
    },

    async saveMatriculaDecision() {
        if (!this.selectedMatriculaId) return;

        const newStatus = document.getElementById('modal-mat-action-status').value;
        const observations = document.getElementById('modal-mat-action-obs').value.trim();
        const btn = document.getElementById('btn-save-mat-decision');

        btn.disabled = true;
        btn.textContent = 'Guardando y notificando... ⏳';

        try {
            const res = await API.updateAdminEnrollmentStatus(this.selectedMatriculaId, newStatus, observations);
            if (res.success) {
                App.showToast(`Matrícula actualizada a estado '${newStatus}' exitosamente`, 'success');
                this.closeModal('matricula-detail-modal');
                this.loadMatriculasData();
            } else {
                throw new Error(res.message || 'Error al actualizar');
            }
        } catch (err) {
            App.showToast(err.message || 'Error al actualizar matrícula', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '💾 Guardar y Notificar por Correo';
        }
    },

    // ─── GESTIÓN DE CRONOGRAMA & ALCANCE 2026 ────────────────────────────────

    cronogramaData: [],
    cronogramaInitialized: false,

    getDefaultCronogramaData() {
        return [
            {
                id: 'hito-1',
                fase: 'Fase 1',
                title: 'Fase 1: Matrículas Ordinarias y Digitales 2026',
                period: 'Nov 2025 - Feb 2026',
                area: 'Rectoría',
                status: 'EN EJECUCIÓN',
                progress: 100,
                desc: 'Recepción y diligenciamiento del wizard de 10 pasos para Preescolar, Básica Primaria y Bachillerato por Ciclos. Firma electrónica digital (Ley 527 de 1999) y compilación automática de los 4 documentos oficiales PDF (Ficha, Contrato, Pagaré y Carta de Instrucciones).'
            },
            {
                id: 'hito-2',
                fase: 'Fase 2',
                title: 'Fase 2: Apertura del Año Lectivo e Inducción a la Plataforma',
                period: 'Febrero 2026',
                area: 'Secretaría Académica',
                status: 'PROGRAMADO',
                progress: 80,
                desc: 'Activación de cuentas institucionales mediante enlace de correo con vigencia de 48h. Asignación a cursos virtuales, grupos de nivel y entrega de credenciales para la App Móvil.'
            },
            {
                id: 'hito-3',
                fase: 'Fase 3',
                title: 'Fase 3: Ciclo de Pensiones Escolares (10 Cuotas Mensuales)',
                period: 'Feb - Nov 2026',
                area: 'Tesorería',
                status: 'PERMANENTE',
                progress: 20,
                desc: 'Control de recaudo mensual ($150.000) exigible dentro de los primeros 5 días calendario de cada mes. Aplicación de recargos extemporáneos del 5%, seguimiento de cartera morosa y aviso de reporte a Datacrédito a los 60 días con 19 días de preaviso.'
            },
            {
                id: 'hito-4',
                fase: 'Fase 4',
                title: 'Fase 4: Ciclo de Simulacros Diagnósticos y Pruebas Saber 11°',
                period: 'Mar - Oct 2026',
                area: 'Coordinación',
                status: 'PROGRAMADO',
                progress: 40,
                desc: 'Aplicación de exámenes cronometrados por componentes ICFES (Matemáticas, Lectura Crítica, Ciencias Naturales, Sociales y Ciudadanas, Inglés) con cálculo inmediato de puntajes y estadísticas mensuales.'
            },
            {
                id: 'hito-5',
                fase: 'Fase 5',
                title: 'Fase 5: Auditoría de Expedientes Físicos vs Digitales',
                period: 'Mayo - Julio 2026',
                area: 'Rectoría',
                status: 'PROGRAMADO',
                progress: 15,
                desc: 'Revisión y validación de expedientes de matrícula radicados, verificación de solvencia económica, actualización de estado a Aprobado y cotejo de documentos de identidad.'
            },
            {
                id: 'hito-6',
                fase: 'Fase 6',
                title: 'Fase 6: Clausura Académica, Certificaciones y Matrículas 2027',
                period: 'Noviembre 2026',
                area: 'Secretaría Académica',
                status: 'PROGRAMADO',
                progress: 0,
                desc: 'Emisión de constancias de paz y salvo, certificados de estudio según Sentencia SU-624/99, actas de graduación y apertura de renovación anticipada de matrícula para 2027.'
            }
        ];
    },

    initCronograma() {
        const stored = localStorage.getItem('jean_piaget_cronograma_2026');
        if (stored) {
            try {
                this.cronogramaData = JSON.parse(stored);
            } catch (e) {
                this.cronogramaData = this.getDefaultCronogramaData();
            }
        } else {
            this.cronogramaData = this.getDefaultCronogramaData();
            localStorage.setItem('jean_piaget_cronograma_2026', JSON.stringify(this.cronogramaData));
        }

        if (!this.cronogramaInitialized) {
            this.bindCronogramaEvents();
            this.cronogramaInitialized = true;
        }

        this.renderCronograma();
    },

    bindCronogramaEvents() {
        const searchInput = document.getElementById('input-search-cronograma');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.renderCronograma());
        }

        const statusSelect = document.getElementById('select-status-cronograma');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => this.renderCronograma());
        }

        const areaSelect = document.getElementById('select-area-cronograma');
        if (areaSelect) {
            areaSelect.addEventListener('change', () => this.renderCronograma());
        }

        const btnAdd = document.getElementById('btn-add-cronograma');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openCronogramaModal());
        }

        const btnPrint = document.getElementById('btn-print-cronograma');
        if (btnPrint) {
            btnPrint.addEventListener('click', () => window.print());
        }

        const btnReset = document.getElementById('btn-reset-cronograma');
        if (btnReset) {
            btnReset.addEventListener('click', () => {
                if (confirm('¿Deseas restablecer el cronograma oficial de Jean Piaget School a sus valores iniciales?')) {
                    this.cronogramaData = this.getDefaultCronogramaData();
                    localStorage.setItem('jean_piaget_cronograma_2026', JSON.stringify(this.cronogramaData));
                    this.renderCronograma();
                    App.showToast('Cronograma oficial restablecido', 'info');
                }
            });
        }

        // Scope Tabs
        document.querySelectorAll('.scope-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const scopeKey = e.currentTarget.dataset.scope;
                document.querySelectorAll('.scope-tab-btn').forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');

                document.querySelectorAll('.scope-pane-content').forEach(pane => pane.classList.remove('active'));
                const target = document.getElementById(`scope-pane-${scopeKey}`);
                if (target) target.classList.add('active');
            });
        });

        // Modal Cronograma Range
        const progressRange = document.getElementById('modal-cronograma-progress-range');
        const progressVal = document.getElementById('modal-cronograma-progress-val');
        if (progressRange && progressVal) {
            progressRange.addEventListener('input', (e) => {
                progressVal.textContent = e.target.value + '%';
            });
        }

        // Form Submit
        const formCronograma = document.getElementById('form-modal-cronograma');
        if (formCronograma) {
            formCronograma.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveCronogramaMilestone();
            });
        }
    },

    renderCronograma() {
        const container = document.getElementById('cronograma-milestones-container');
        if (!container) return;

        const search = (document.getElementById('input-search-cronograma')?.value || '').toLowerCase().trim();
        const statusFilter = document.getElementById('select-status-cronograma')?.value || 'ALL';
        const areaFilter = document.getElementById('select-area-cronograma')?.value || 'ALL';

        const filtered = this.cronogramaData.filter(item => {
            const matchSearch = !search ||
                item.title.toLowerCase().includes(search) ||
                item.desc.toLowerCase().includes(search) ||
                item.period.toLowerCase().includes(search);

            const matchStatus = statusFilter === 'ALL' || item.status === statusFilter;
            const matchArea = areaFilter === 'ALL' || item.area.includes(areaFilter);

            return matchSearch && matchStatus && matchArea;
        });

        // Actualizar contador y KPI
        const countLabel = document.getElementById('cronograma-count-label');
        if (countLabel) {
            countLabel.textContent = `Mostrando ${filtered.length} de ${this.cronogramaData.length} hitos`;
        }

        const kpiTotal = document.getElementById('kpi-cronograma-total');
        if (kpiTotal) {
            kpiTotal.textContent = `${this.cronogramaData.length} Fases`;
        }

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                    <div style="font-size: 2rem; margin-bottom: 8px;">🔍</div>
                    <p style="margin: 0; font-size: 0.9rem;">No se encontraron hitos con los filtros seleccionados.</p>
                </div>
            `;
            return;
        }

        let html = '';
        filtered.forEach(item => {
            let statusClass = 'badge-status-programado';
            let borderLeftColor = '#10b981';
            let progressColor = '#10b981';

            if (item.status === 'EN EJECUCIÓN') {
                statusClass = 'badge-status-ejecucion';
                borderLeftColor = '#3b82f6';
                progressColor = '#3b82f6';
            } else if (item.status === 'PERMANENTE') {
                statusClass = 'badge-status-permanente';
                borderLeftColor = '#d97706';
                progressColor = '#d97706';
            } else if (item.status === 'CONCLUIDO') {
                statusClass = 'badge-status-concluido';
                borderLeftColor = '#475569';
                progressColor = '#475569';
            }

            html += `
                <div class="cronograma-item-card" style="border-left: 4px solid ${borderLeftColor};">
                    <div class="cronograma-item-header">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                <strong style="font-size: 0.95rem; color: #0f172a;">${item.title}</strong>
                                <span class="cronograma-badge-status ${statusClass}">${item.status}</span>
                            </div>
                            <div style="display: flex; gap: 14px; font-size: 0.78rem; color: var(--text-muted); flex-wrap: wrap;">
                                <span>📅 <strong>Periodo:</strong> ${item.period}</span>
                                <span>🏛️ <strong>Área:</strong> ${item.area}</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="tbl-action-btn" title="Editar hito" onclick="AdminPanel.openCronogramaModal('${item.id}')" style="width: auto; padding: 4px 10px; font-size: 0.75rem;">
                                ✏️ Editar
                            </button>
                            <button type="button" class="tbl-action-btn" title="Cambiar estado rápido" onclick="AdminPanel.toggleMilestoneStatus('${item.id}')" style="width: auto; padding: 4px 10px; font-size: 0.75rem;">
                                🔄 Estado
                            </button>
                        </div>
                    </div>

                    <p style="font-size: 0.82rem; color: #475569; margin: 8px 0 0; line-height: 1.5;">
                        ${item.desc}
                    </p>

                    <div class="cronograma-progress-container">
                        <div class="cronograma-progress-bar-bg">
                            <div class="cronograma-progress-bar-fill" style="width: ${item.progress}%; background: ${progressColor};"></div>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 700; color: #334155; min-width: 40px; text-align: right;">
                            ${item.progress}%
                        </span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    },

    openCronogramaModal(id = null) {
        const modal = document.getElementById('cronograma-modal');
        if (!modal) return;

        const headerTitle = document.getElementById('modal-cronograma-header-title');
        const inputId = document.getElementById('modal-cronograma-id');
        const inputTitle = document.getElementById('modal-cronograma-title');
        const selectFase = document.getElementById('modal-cronograma-fase');
        const inputFechas = document.getElementById('modal-cronograma-fechas');
        const selectArea = document.getElementById('modal-cronograma-area');
        const selectStatus = document.getElementById('modal-cronograma-status');
        const rangeProgress = document.getElementById('modal-cronograma-progress-range');
        const valProgress = document.getElementById('modal-cronograma-progress-val');
        const textDesc = document.getElementById('modal-cronograma-desc');

        if (id) {
            const item = this.cronogramaData.find(i => i.id === id);
            if (item) {
                headerTitle.textContent = '✏️ Editar Hito de Cronograma';
                inputId.value = item.id;
                inputTitle.value = item.title;
                selectFase.value = item.fase || 'Fase 1';
                inputFechas.value = item.period;
                selectArea.value = item.area.includes('Rectoría') ? 'Rectoría' :
                                   item.area.includes('Secretaría') ? 'Secretaría Académica' :
                                   item.area.includes('Tesorería') ? 'Tesorería' :
                                   item.area.includes('Coordinación') ? 'Coordinación' : 'Tecnología';
                selectStatus.value = item.status;
                rangeProgress.value = item.progress;
                valProgress.textContent = item.progress + '%';
                textDesc.value = item.desc;
            }
        } else {
            headerTitle.textContent = '➕ Nuevo Hito de Cronograma 2026';
            inputId.value = '';
            inputTitle.value = '';
            selectFase.value = 'Fase 1';
            inputFechas.value = '';
            selectArea.value = 'Rectoría';
            selectStatus.value = 'PROGRAMADO';
            rangeProgress.value = 0;
            valProgress.textContent = '0%';
            textDesc.value = '';
        }

        modal.classList.add('active');
    },

    saveCronogramaMilestone() {
        const id = document.getElementById('modal-cronograma-id').value;
        const title = document.getElementById('modal-cronograma-title').value.trim();
        const fase = document.getElementById('modal-cronograma-fase').value;
        const period = document.getElementById('modal-cronograma-fechas').value.trim();
        const area = document.getElementById('modal-cronograma-area').value;
        const status = document.getElementById('modal-cronograma-status').value;
        const progress = parseInt(document.getElementById('modal-cronograma-progress-range').value, 10) || 0;
        const desc = document.getElementById('modal-cronograma-desc').value.trim();

        if (!title || !period) {
            App.showToast('Por favor completa el nombre y el periodo del hito', 'error');
            return;
        }

        if (id) {
            const index = this.cronogramaData.findIndex(i => i.id === id);
            if (index !== -1) {
                this.cronogramaData[index] = { id, fase, title, period, area, status, progress, desc };
                App.showToast('Hito actualizado correctamente', 'success');
            }
        } else {
            const newId = 'hito-' + Date.now();
            this.cronogramaData.push({ id: newId, fase, title, period, area, status, progress, desc });
            App.showToast('Nuevo hito agregado al cronograma', 'success');
        }

        localStorage.setItem('jean_piaget_cronograma_2026', JSON.stringify(this.cronogramaData));
        this.closeModal('cronograma-modal');
        this.renderCronograma();
    },

    toggleMilestoneStatus(id) {
        const index = this.cronogramaData.findIndex(i => i.id === id);
        if (index === -1) return;

        const current = this.cronogramaData[index].status;
        const cycle = ['PROGRAMADO', 'EN EJECUCIÓN', 'PERMANENTE', 'CONCLUIDO'];
        const nextIdx = (cycle.indexOf(current) + 1) % cycle.length;
        const nextStatus = cycle[nextIdx];

        this.cronogramaData[index].status = nextStatus;
        if (nextStatus === 'CONCLUIDO') {
            this.cronogramaData[index].progress = 100;
        } else if (nextStatus === 'PROGRAMADO' && this.cronogramaData[index].progress === 100) {
            this.cronogramaData[index].progress = 50;
        }

        localStorage.setItem('jean_piaget_cronograma_2026', JSON.stringify(this.cronogramaData));
        App.showToast(`Estado actualizado a ${nextStatus}`, 'info');
        this.renderCronograma();
    }
};

window.AdminPanel = AdminPanel;

