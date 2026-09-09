/**
 * ===================================================================
 * WIZARD DE MATRÍCULA DIGITAL 2026 - JEAN PIAGET SCHOOL
 * ===================================================================
 */

const MatriculaWizard = {
    currentStep: 1,
    totalSteps: 10,
    enrollmentId: null,
    enrollmentCode: null,
    enrollmentData: null,
    schoolConfig: null,
    isDrawing: false,
    hasSignature: false,
    canvas: null,
    ctx: null,

    // Inicialización al cargar la página
    async init() {
        this.initCanvas();
        await this.loadConfig();
        await this.loadOrInitDraft();
        this.bindEvents();
        this.renderStep(this.currentStep);
    },

    // 1. Cargar configuración de niveles y grados dinámicos
    async loadConfig() {
        try {
            const res = await API.getEnrollmentConfig();
            if (res.success && res.data) {
                this.schoolConfig = res.data;
                this.renderLevelOptions();
            }
        } catch (err) {
            console.error('Error al cargar configuración de grados:', err);
        }
    },

    // 2. Cargar borrador existente o crear uno nuevo
    async loadOrInitDraft() {
        const token = Storage.getToken();
        if (!token) {
            // Si el usuario no ha iniciado sesión, mostrar modal de autenticación o redirigir
            this.showAuthNotice();
            return;
        }

        try {
            const res = await API.createOrGetEnrollmentDraft();
            if (res.success && res.data) {
                this.enrollmentData = res.data;
                this.enrollmentId = res.data.enrollment.id;
                this.enrollmentCode = res.data.enrollment.code;
                
                document.getElementById('headerEnrollmentCode').textContent = this.enrollmentCode;
                
                // Rellenar formularios con los datos existentes
                this.populateForms(res.data);

                // Si ya estaba en un paso avanzado
                const savedStep = parseInt(res.data.enrollment.current_step || 1);
                if (savedStep > 1 && savedStep <= 10) {
                    this.currentStep = savedStep;
                }
            }
        } catch (err) {
            console.error('Error al cargar matrícula:', err);
            this.showToast('Error al inicializar matrícula: ' + err.message, 'danger');
        }
    },

    // 3. Renderizar opciones de nivel educativo y grados dinámicos
    renderLevelOptions() {
        if (!this.schoolConfig || !this.schoolConfig.levels) return;
        const levels = this.schoolConfig.levels;
        const container = document.getElementById('levelSelectorContainer');
        if (!container) return;

        container.innerHTML = '';
        Object.entries(levels).forEach(([key, lvl]) => {
            const card = document.createElement('div');
            card.className = `level-card ${key === 'BASICA_PRIMARIA' ? 'selected' : ''}`;
            card.dataset.level = key;
            card.innerHTML = `
                <div class="level-card-icon">🎓</div>
                <div class="level-card-title">${lvl.label}</div>
                <div class="level-card-desc">${lvl.grades.length} Grados disponibles</div>
            `;
            card.addEventListener('click', () => this.selectLevel(key));
            container.appendChild(card);
        });

        // Actualizar grados para el nivel por defecto
        this.updateGradesDropdown('BASICA_PRIMARIA');
    },

    selectLevel(levelKey) {
        document.querySelectorAll('.level-card').forEach(c => {
            c.classList.toggle('selected', c.dataset.level === levelKey);
        });
        document.getElementById('enrollmentTypeInput').value = levelKey;
        this.updateGradesDropdown(levelKey);
    },

    updateGradesDropdown(levelKey, selectedGrade = null) {
        if (!this.schoolConfig || !this.schoolConfig.levels[levelKey]) return;
        const grades = this.schoolConfig.levels[levelKey].grades;
        const select = document.getElementById('targetGradeSelect');
        if (!select) return;

        select.innerHTML = '<option value="">-- Seleccionar grado aspirado --</option>';
        grades.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g;
            opt.textContent = g;
            if (selectedGrade && selectedGrade === g) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        // Actualizar costos informativos
        this.updateEconomicsDisplay();
    },

    // 4. Rellenar campos desde datos del backend
    populateForms(data) {
        const s = data.student || {};
        const f = data.father || {};
        const m = data.mother || {};
        const g = data.guardian || {};
        const a = data.academic || {};
        const ec = data.economics || {};
        const e = data.enrollment || {};

        // Estudiante
        this.setVal('student_first_name', s.first_name);
        this.setVal('student_last_name', s.last_name);
        this.setVal('student_birth_date', s.birth_date);
        this.setVal('student_birth_place', s.birth_place);
        this.setVal('student_age', s.age);
        this.setVal('student_doc_type', s.doc_type || 'TI');
        this.setVal('student_doc_number', s.doc_number && !s.doc_number.startsWith('TEMP-') ? s.doc_number : '');
        this.setVal('student_doc_issue_place', s.doc_issue_place);
        this.setVal('student_eps', s.eps);
        this.setVal('student_rh', s.rh || 'O+');
        this.setVal('student_lives_with_parents', s.lives_with_parents || 'SI');
        this.setVal('student_lives_with_whom', s.lives_with_whom);
        this.setVal('student_address', s.address);
        this.setVal('student_phone', s.phone);
        this.setVal('student_email', s.email);

        // Padre
        this.setVal('father_name', f.full_name);
        this.setVal('father_doc_type', f.doc_type || 'CC');
        this.setVal('father_doc_number', f.doc_number);
        this.setVal('father_doc_place', f.doc_issue_place);
        this.setVal('father_occupation', f.occupation);
        this.setVal('father_phone', f.phone);
        this.setVal('father_email', f.email);
        this.setVal('father_address', f.address);

        // Madre
        this.setVal('mother_name', m.full_name);
        this.setVal('mother_doc_type', m.doc_type || 'CC');
        this.setVal('mother_doc_number', m.doc_number);
        this.setVal('mother_doc_place', m.doc_issue_place);
        this.setVal('mother_occupation', m.occupation);
        this.setVal('mother_phone', m.phone);
        this.setVal('mother_email', m.email);
        this.setVal('mother_address', m.address);

        // Acudiente
        this.setVal('guardian_name', g.full_name);
        this.setVal('guardian_doc_type', g.doc_type || 'CC');
        this.setVal('guardian_doc_number', g.doc_number);
        this.setVal('guardian_doc_place', g.doc_issue_place);
        this.setVal('guardian_relationship', g.relationship);
        this.setVal('guardian_phone', g.phone);
        this.setVal('guardian_email', g.email);
        this.setVal('guardian_address', g.address);
        this.setVal('guardian_occupation', g.occupation);

        // Nivel y Grado
        if (e.enrollment_type) {
            this.selectLevel(e.enrollment_type);
            this.updateGradesDropdown(e.enrollment_type, e.target_grade);
        }

        // Académico
        this.setVal('previous_school', a.previous_school);
        this.setVal('previous_grade', a.previous_grade);
        this.setVal('previous_year', a.previous_year || (new Date().getFullYear() - 1));
        this.setVal('academic_notes', a.academic_notes);

        // Términos
        if (e.terms_accepted) {
            document.getElementById('check_terms_service').checked = true;
        }
        if (e.data_processing_accepted) {
            document.getElementById('check_terms_privacy').checked = true;
            document.getElementById('check_terms_central').checked = true;
            document.getElementById('check_terms_image').checked = true;
            document.getElementById('check_terms_notifications').checked = true;
        }

        // Si ya está firmada
        if (data.signature) {
            this.hasSignature = true;
            const img = new Image();
            img.onload = () => {
                this.ctx.drawImage(img, 0, 0);
            };
            img.src = data.signature.signature_data;
        }
    },

    setVal(id, val) {
        const el = document.getElementById(id);
        if (el && val !== undefined && val !== null) {
            el.value = val;
        }
    },

    getVal(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    },

    // 5. Cambio de Paso en el Wizard
    async goToStep(step) {
        if (step < 1 || step > this.totalSteps) return;

        // Si intenta avanzar, validar el paso actual primero
        if (step > this.currentStep) {
            const isValid = this.validateStep(this.currentStep);
            if (!isValid) return;

            // Guardar datos en el backend antes de avanzar
            await this.saveCurrentStepData(this.currentStep);
        }

        this.currentStep = step;
        this.renderStep(step);

        // Acciones específicas según paso
        if (step === 8) {
            this.buildReviewSummary();
        } else if (step === 9) {
            await this.prepareSignatureStep();
        } else if (step === 10) {
            await this.loadFinalDocuments();
        }

        window.scrollTo({ top: 120, behavior: 'smooth' });
    },

    renderStep(step) {
        // Ocultar todos los pasos
        for (let i = 1; i <= this.totalSteps; i++) {
            const stepEl = document.getElementById(`stepContainer${i}`);
            if (stepEl) stepEl.style.display = (i === step) ? 'block' : 'none';
        }

        // Actualizar barra de progreso y stepper bubbles
        const progressPct = (step / this.totalSteps) * 100;
        document.getElementById('stepperProgressBar').style.width = `${progressPct}%`;

        document.querySelectorAll('.step-item').forEach((item, idx) => {
            const stepNum = idx + 1;
            item.classList.toggle('active', stepNum === step);
            item.classList.toggle('completed', stepNum < step);
        });

        // Botones de navegación
        const btnPrev = document.getElementById('btnPrevStep');
        const btnNext = document.getElementById('btnNextStep');
        if (btnPrev) btnPrev.style.display = (step === 1 || step === 10) ? 'none' : 'inline-flex';
        if (btnNext) {
            if (step === 8) {
                btnNext.textContent = 'Continuar a Firma Electrónica ✍️';
                btnNext.style.display = 'inline-flex';
            } else if (step === 9 || step === 10) {
                btnNext.style.display = 'none';
            } else {
                btnNext.textContent = 'Siguiente Paso →';
                btnNext.style.display = 'inline-flex';
            }
        }
    },

    // 6. Validaciones por Paso
    validateStep(step) {
        let isValid = true;
        this.clearValidationErrors();

        if (step === 1) {
            // Estudiante
            isValid = this.checkRequired('student_first_name', 'Ingrese los nombres del estudiante') && isValid;
            isValid = this.checkRequired('student_last_name', 'Ingrese los apellidos del estudiante') && isValid;
            isValid = this.checkRequired('student_birth_date', 'Seleccione la fecha de nacimiento') && isValid;
            isValid = this.checkRequired('student_birth_place', 'Ingrese el lugar de nacimiento') && isValid;
            isValid = this.checkRequired('student_doc_number', 'Ingrese el número de documento') && isValid;
            isValid = this.checkRequired('student_doc_issue_place', 'Ingrese el lugar de expedición') && isValid;
            isValid = this.checkRequired('student_eps', 'Ingrese la E.P.S.') && isValid;
            isValid = this.checkRequired('student_address', 'Ingrese la dirección de residencia') && isValid;
            isValid = this.checkRequired('student_phone', 'Ingrese el teléfono o celular') && isValid;
        } else if (step === 2) {
            // Padre (opcional pero si ingresa nombre, validar teléfono)
            const fName = this.getVal('father_name');
            if (fName) {
                isValid = this.checkRequired('father_phone', 'Ingrese el celular del padre') && isValid;
            }
        } else if (step === 3) {
            // Madre
            const mName = this.getVal('mother_name');
            if (mName) {
                isValid = this.checkRequired('mother_phone', 'Ingrese el celular de la madre') && isValid;
            }
        } else if (step === 4) {
            // Acudiente (OBLIGATORIO)
            isValid = this.checkRequired('guardian_name', 'Ingrese el nombre del acudiente') && isValid;
            isValid = this.checkRequired('guardian_doc_number', 'Ingrese el documento de identidad') && isValid;
            isValid = this.checkRequired('guardian_relationship', 'Indique el parentesco') && isValid;
            isValid = this.checkRequired('guardian_phone', 'Ingrese el celular de contacto') && isValid;
            isValid = this.checkEmail('guardian_email', 'Ingrese un correo electrónico válido') && isValid;
            isValid = this.checkRequired('guardian_address', 'Ingrese la dirección del acudiente') && isValid;
        } else if (step === 5) {
            // Académica
            isValid = this.checkRequired('targetGradeSelect', 'Seleccione el grado al que aspira el estudiante') && isValid;
            isValid = this.checkRequired('previous_school', 'Ingrese el nombre de la institución anterior') && isValid;
            isValid = this.checkRequired('previous_grade', 'Ingrese el último grado cursado') && isValid;
        } else if (step === 7) {
            // Autorizaciones
            const t1 = document.getElementById('check_terms_service')?.checked;
            const t2 = document.getElementById('check_terms_privacy')?.checked;
            const t3 = document.getElementById('check_terms_central')?.checked;

            if (!t1 || !t2 || !t3) {
                this.showToast('Debe aceptar los términos, política de datos y autorizaciones para continuar.', 'danger');
                return false;
            }
        }

        return isValid;
    },

    checkRequired(id, msg) {
        const val = this.getVal(id);
        const el = document.getElementById(id);
        if (!val) {
            el?.classList.add('is-invalid');
            const errEl = document.getElementById(`${id}_err`);
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.add('visible');
            }
            return false;
        }
        el?.classList.remove('is-invalid');
        return true;
    },

    checkEmail(id, msg) {
        const val = this.getVal(id);
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const el = document.getElementById(id);
        if (!val || !re.test(val)) {
            el?.classList.add('is-invalid');
            const errEl = document.getElementById(`${id}_err`);
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.add('visible');
            }
            return false;
        }
        el?.classList.remove('is-invalid');
        return true;
    },

    clearValidationErrors() {
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.field-error-msg').forEach(el => el.classList.remove('visible'));
    },

    // 7. Guardar datos del paso en el backend
    async saveCurrentStepData(step) {
        if (!this.enrollmentId) return;

        const payload = {
            step: step
        };

        if (step === 1) {
            payload.student = {
                first_name: this.getVal('student_first_name'),
                last_name: this.getVal('student_last_name'),
                birth_date: this.getVal('student_birth_date'),
                birth_place: this.getVal('student_birth_place'),
                age: parseInt(this.getVal('student_age') || 0),
                doc_type: this.getVal('student_doc_type'),
                doc_number: this.getVal('student_doc_number'),
                doc_issue_place: this.getVal('student_doc_issue_place'),
                eps: this.getVal('student_eps'),
                rh: this.getVal('student_rh'),
                lives_with_parents: this.getVal('student_lives_with_parents'),
                lives_with_whom: this.getVal('student_lives_with_whom'),
                address: this.getVal('student_address'),
                phone: this.getVal('student_phone'),
                email: this.getVal('student_email')
            };
        } else if (step === 2) {
            payload.father = {
                full_name: this.getVal('father_name'),
                doc_type: this.getVal('father_doc_type'),
                doc_number: this.getVal('father_doc_number'),
                doc_issue_place: this.getVal('father_doc_place'),
                occupation: this.getVal('father_occupation'),
                phone: this.getVal('father_phone'),
                email: this.getVal('father_email'),
                address: this.getVal('father_address')
            };
        } else if (step === 3) {
            payload.mother = {
                full_name: this.getVal('mother_name'),
                doc_type: this.getVal('mother_doc_type'),
                doc_number: this.getVal('mother_doc_number'),
                doc_issue_place: this.getVal('mother_doc_place'),
                occupation: this.getVal('mother_occupation'),
                phone: this.getVal('mother_phone'),
                email: this.getVal('mother_email'),
                address: this.getVal('mother_address')
            };
        } else if (step === 4) {
            payload.guardian = {
                full_name: this.getVal('guardian_name'),
                doc_type: this.getVal('guardian_doc_type'),
                doc_number: this.getVal('guardian_doc_number'),
                doc_issue_place: this.getVal('guardian_doc_place'),
                relationship: this.getVal('guardian_relationship'),
                phone: this.getVal('guardian_phone'),
                email: this.getVal('guardian_email'),
                address: this.getVal('guardian_address'),
                occupation: this.getVal('guardian_occupation'),
                is_parent: this.getVal('guardian_is_parent') || 'OTRO'
            };
        } else if (step === 5) {
            payload.enrollment_type = this.getVal('enrollmentTypeInput');
            payload.target_grade = this.getVal('targetGradeSelect');
            payload.academic = {
                previous_school: this.getVal('previous_school'),
                previous_grade: this.getVal('previous_grade'),
                previous_year: parseInt(this.getVal('previous_year') || 2025),
                academic_notes: this.getVal('academic_notes')
            };
        } else if (step === 6) {
            payload.economics = {
                enrollment_fee: 180000.00,
                monthly_fee: 150000.00,
                installments_count: 10,
                payment_method: 'Mensual'
            };
        } else if (step === 7) {
            payload.terms_accepted = document.getElementById('check_terms_service')?.checked ? 1 : 0;
            payload.data_processing_accepted = document.getElementById('check_terms_privacy')?.checked ? 1 : 0;
        }

        try {
            const res = await API.saveEnrollmentStep(this.enrollmentId, payload);
            if (res.success && res.data) {
                this.enrollmentData = res.data;
            }
        } catch (err) {
            console.error('Error al guardar paso:', err);
            this.showToast('Atención: No se pudo guardar el borrador en el servidor: ' + err.message, 'warning');
        }
    },

    // 8. Auto-completar datos de acudiente
    copyFromFather() {
        this.setVal('guardian_name', this.getVal('father_name'));
        this.setVal('guardian_doc_type', this.getVal('father_doc_type'));
        this.setVal('guardian_doc_number', this.getVal('father_doc_number'));
        this.setVal('guardian_doc_place', this.getVal('father_doc_place'));
        this.setVal('guardian_relationship', 'Padre');
        this.setVal('guardian_phone', this.getVal('father_phone'));
        this.setVal('guardian_email', this.getVal('father_email'));
        this.setVal('guardian_address', this.getVal('father_address'));
        this.setVal('guardian_occupation', this.getVal('father_occupation'));
        this.setVal('guardian_is_parent', 'PADRE');
        this.showToast('Datos copiados del Padre', 'info');
    },

    copyFromMother() {
        this.setVal('guardian_name', this.getVal('mother_name'));
        this.setVal('guardian_doc_type', this.getVal('mother_doc_type'));
        this.setVal('guardian_doc_number', this.getVal('mother_doc_number'));
        this.setVal('guardian_doc_place', this.getVal('mother_doc_place'));
        this.setVal('guardian_relationship', 'Madre');
        this.setVal('guardian_phone', this.getVal('mother_phone'));
        this.setVal('guardian_email', this.getVal('mother_email'));
        this.setVal('guardian_address', this.getVal('mother_address'));
        this.setVal('guardian_occupation', this.getVal('mother_occupation'));
        this.setVal('guardian_is_parent', 'MADRE');
        this.showToast('Datos copiados de la Madre', 'info');
    },

    // 9. Cálculo automático de edad según fecha de nacimiento
    calculateAge() {
        const bdate = this.getVal('student_birth_date');
        if (!bdate) return;
        const dob = new Date(bdate);
        const diff = Date.now() - dob.getTime();
        const ageDate = new Date(diff);
        const age = Math.abs(ageDate.getUTCFullYear() - 1970);
        if (!isNaN(age)) {
            this.setVal('student_age', age);
        }
    },

    updateEconomicsDisplay() {
        const matFee = 180000;
        const penFee = 150000;
        const installments = 10;
        const total = matFee + (penFee * installments);

        const fmt = (n) => '$' + n.toLocaleString('es-CO');
        const elMat = document.getElementById('econMatFee');
        const elPen = document.getElementById('econPenFee');
        const elTot = document.getElementById('econTotalFee');

        if (elMat) elMat.textContent = fmt(matFee);
        if (elPen) elPen.textContent = fmt(penFee);
        if (elTot) elTot.textContent = fmt(total);
    },

    // 10. Resumen consolidado para el Paso 8
    buildReviewSummary() {
        const sName = `${this.getVal('student_first_name')} ${this.getVal('student_last_name')}`;
        const sDoc = `${this.getVal('student_doc_type')} ${this.getVal('student_doc_number')}`;
        const gName = this.getVal('guardian_name');
        const gDoc = `${this.getVal('guardian_doc_type')} ${this.getVal('guardian_doc_number')}`;
        const level = this.getVal('enrollmentTypeInput');
        const grade = this.getVal('targetGradeSelect');

        const mapLevels = {
            'PREESCOLAR': 'Preescolar',
            'BASICA_PRIMARIA': 'Básica Primaria',
            'BACHILLERATO_CICLOS': 'Bachillerato por Ciclos'
        };

        const container = document.getElementById('reviewSummaryContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="review-section">
                <div class="review-section-header">
                    <h3>1. Estudiante</h3>
                    <button type="button" class="btn-edit-step" onclick="MatriculaWizard.goToStep(1)">Editar</button>
                </div>
                <table class="review-table">
                    <tr><td>Nombre Completo:</td><td><strong>${sName}</strong></td></tr>
                    <tr><td>Documento:</td><td>${sDoc} (Exp. ${this.getVal('student_doc_issue_place')})</td></tr>
                    <tr><td>Nacimiento:</td><td>${this.getVal('student_birth_date')} (${this.getVal('student_age')} años) en ${this.getVal('student_birth_place')}</td></tr>
                    <tr><td>E.P.S. / RH:</td><td>${this.getVal('student_eps')} / ${this.getVal('student_rh')}</td></tr>
                    <tr><td>Dirección / Teléfono:</td><td>${this.getVal('student_address')} / ${this.getVal('student_phone')}</td></tr>
                </table>
            </div>

            <div class="review-section">
                <div class="review-section-header">
                    <h3>2. Acudiente Responsable</h3>
                    <button type="button" class="btn-edit-step" onclick="MatriculaWizard.goToStep(4)">Editar</button>
                </div>
                <table class="review-table">
                    <tr><td>Nombre:</td><td><strong>${gName}</strong> (${this.getVal('guardian_relationship')})</td></tr>
                    <tr><td>Documento:</td><td>${gDoc}</td></tr>
                    <tr><td>Celular / Correo:</td><td>${this.getVal('guardian_phone')} / ${this.getVal('guardian_email')}</td></tr>
                    <tr><td>Dirección:</td><td>${this.getVal('guardian_address')}</td></tr>
                </table>
            </div>

            <div class="review-section">
                <div class="review-section-header">
                    <h3>3. Matrícula y Académica</h3>
                    <button type="button" class="btn-edit-step" onclick="MatriculaWizard.goToStep(5)">Editar</button>
                </div>
                <table class="review-table">
                    <tr><td>Nivel Educativo:</td><td><strong>${mapLevels[level] || level}</strong></td></tr>
                    <tr><td>Grado Aspirado:</td><td><strong style="color: var(--piaget-navy); font-size: 1rem;">${grade}</strong> (Año 2026)</td></tr>
                    <tr><td>Colegio Anterior:</td><td>${this.getVal('previous_school')} (${this.getVal('previous_grade')})</td></tr>
                </table>
            </div>

            <div class="review-section">
                <div class="review-section-header">
                    <h3>4. Costos Educativos</h3>
                    <button type="button" class="btn-edit-step" onclick="MatriculaWizard.goToStep(6)">Editar</button>
                </div>
                <table class="review-table">
                    <tr><td>Matrícula:</td><td>$180.000 M/CTE</td></tr>
                    <tr><td>Pensión (10 cuotas):</td><td>$150.000 M/CTE cada una</td></tr>
                    <tr><td>Total Año Escolar:</td><td><strong>$1.680.000 M/CTE</strong></td></tr>
                </table>
            </div>
        `;
    },

    // 11. Canvas de Firma Digital (Táctil y Mouse)
    initCanvas() {
        this.canvas = document.getElementById('signatureCanvas');
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.ctx.lineWidth = 2.5;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.strokeStyle = '#002244';

        const getPos = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        };

        const startDrawing = (e) => {
            e.preventDefault();
            this.isDrawing = true;
            this.hasSignature = true;
            const pos = getPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        };

        const draw = (e) => {
            if (!this.isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        };

        const stopDrawing = () => {
            this.isDrawing = false;
        };

        // Mouse events
        this.canvas.addEventListener('mousedown', startDrawing);
        this.canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        // Touch events for mobile/tablets
        this.canvas.addEventListener('touchstart', startDrawing, { passive: false });
        this.canvas.addEventListener('touchmove', draw, { passive: false });
        this.canvas.addEventListener('touchend', stopDrawing);
    },

    clearSignature() {
        if (!this.ctx || !this.canvas) return;
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.hasSignature = false;
    },

    // 12. Paso 9: Generación previa y firma
    async prepareSignatureStep() {
        this.showToast('Preparando documentos para firma...', 'info');
        try {
            await this.saveCurrentStepData(7);
            const res = await API.generateEnrollmentDocuments(this.enrollmentId);
            if (res.success && res.data) {
                this.renderSignatureDocumentsList(res.data.documents);
            }
        } catch (err) {
            console.error('Error generando documentos previos:', err);
        }
    },

    renderSignatureDocumentsList(docs) {
        const container = document.getElementById('signatureDocsContainer');
        if (!container || !docs) return;

        container.innerHTML = '';
        docs.forEach(d => {
            const card = document.createElement('div');
            card.className = 'doc-card';
            card.innerHTML = `
                <div class="doc-card-info">
                    <div class="doc-icon">📄</div>
                    <div class="doc-details">
                        <h4>${d.title}</h4>
                        <p>Versión ${d.version} &bull; PDF Oficial</p>
                    </div>
                </div>
                <a href="${d.file_url}" target="_blank" class="btn-download-doc">Ver Documento ↗</a>
            `;
            container.appendChild(card);
        });
    },

    async confirmSignatureAndSubmit() {
        if (!this.hasSignature) {
            this.showToast('Por favor dibuje su firma en el recuadro antes de continuar.', 'danger');
            return;
        }

        const btn = document.getElementById('btnConfirmSignature');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Procesando firma electrónica... ⏳';
        }

        try {
            const sigData = this.canvas.toDataURL('image/png');
            const res = await API.signEnrollment(this.enrollmentId, sigData);

            if (res.success) {
                // Realizar el envío formal para revisión
                await API.submitEnrollment(this.enrollmentId);
                this.showToast('¡Matrícula firmada y radicada con éxito!', 'success');
                this.goToStep(10);
            } else {
                throw new Error(res.message || 'Error al firmar');
            }
        } catch (err) {
            console.error('Error al firmar:', err);
            this.showToast('Error al firmar: ' + err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Firmar y Radicar Matrícula ✍️';
            }
        }
    },

    // 13. Paso 10: Finalización y descarga
    async loadFinalDocuments() {
        try {
            const res = await API.getEnrollmentDocuments(this.enrollmentId);
            const container = document.getElementById('finalDocsContainer');
            if (!container) return;

            document.getElementById('finalEnrollmentCode').textContent = this.enrollmentCode;

            container.innerHTML = '';
            if (res.success && res.data) {
                res.data.forEach(d => {
                    const card = document.createElement('div');
                    card.className = 'doc-card';
                    card.innerHTML = `
                        <div class="doc-card-info">
                            <div class="doc-icon">📑</div>
                            <div class="doc-details">
                                <h4>${d.title}</h4>
                                <p>Firmado Digitalmente &bull; Hash: ${d.file_hash.substr(0, 12)}...</p>
                            </div>
                        </div>
                        <a href="${d.file_url}" target="_blank" download class="btn btn-primary" style="padding: 6px 12px; font-size: 0.82rem;">
                            Descargar PDF 📥
                        </a>
                    `;
                    container.appendChild(card);
                });
            }
        } catch (err) {
            console.error('Error cargando documentos finales:', err);
        }
    },

    bindEvents() {
        // Botones Next / Prev
        document.getElementById('btnNextStep')?.addEventListener('click', () => {
            if (this.currentStep === 8) {
                this.goToStep(9);
            } else {
                this.goToStep(this.currentStep + 1);
            }
        });

        document.getElementById('btnPrevStep')?.addEventListener('click', () => {
            this.goToStep(this.currentStep - 1);
        });

        // Guardar Borrador
        document.getElementById('btnSaveDraft')?.addEventListener('click', async () => {
            await this.saveCurrentStepData(this.currentStep);
            this.showToast('Borrador guardado exitosamente en el servidor.', 'success');
        });

        // Limpiar firma
        document.getElementById('btnClearSignature')?.addEventListener('click', () => {
            this.clearSignature();
        });

        // Confirmar firma
        document.getElementById('btnConfirmSignature')?.addEventListener('click', () => {
            this.confirmSignatureAndSubmit();
        });

        // Cálculo de edad
        document.getElementById('student_birth_date')?.addEventListener('change', () => {
            this.calculateAge();
        });

        // Stepper click directo si ya completó paso
        document.querySelectorAll('.step-item').forEach((item, idx) => {
            item.addEventListener('click', () => {
                const target = idx + 1;
                if (target <= this.currentStep || this.enrollmentData?.enrollment?.current_step >= target) {
                    this.goToStep(target);
                }
            });
        });
    },

    showToast(msg, type = 'info') {
        const existing = document.getElementById('toastNotice');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'toastNotice';
        toast.className = `alert-box alert-${type}`;
        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 400px;';
        toast.innerHTML = `<span>${msg}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 4500);
    },

    showAuthNotice() {
        const container = document.querySelector('.matricula-container');
        if (container) {
            container.innerHTML = `
                <div class="form-step-card" style="text-align: center; padding: 48px 24px;">
                    <div style="font-size: 3rem; margin-bottom: 16px;">🔐</div>
                    <h2 style="color: var(--piaget-navy); margin-bottom: 12px;">Inicio de Sesión Requerido</h2>
                    <p style="color: var(--piaget-text-muted); max-width: 500px; margin: 0 auto 24px;">
                        Para iniciar o continuar con el proceso de matrícula digital de la <strong>Institución Educativa Jean Piaget School</strong>, debe ingresar con su cuenta de padre de familia o acudiente.
                    </p>
                    <a href="index.html#login" class="btn btn-primary" style="padding: 12px 28px; font-size: 1rem;">
                        Iniciar Sesión / Registrarse
                    </a>
                </div>
            `;
        }
    }
};

// Auto-inicio en DOM ready
document.addEventListener('DOMContentLoaded', () => {
    MatriculaWizard.init();
});
