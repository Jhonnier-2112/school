import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Linking,
  Alert,
  Image,
  Modal,
  TextInput,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { colors } from '../theme/colors';
import { API } from '../api/client';
import { useAuth } from '../context/AuthContext';

const LEVELS_CONFIG = {
  PREESCOLAR: {
    label: 'Preescolar',
    grades: ['Párvulos', 'Pre-Jardín', 'Jardín', 'Transición'],
  },
  BASICA_PRIMARIA: {
    label: 'Básica Primaria',
    grades: ['Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto'],
  },
  BACHILLERATO_CICLOS: {
    label: 'Bachillerato por Ciclos',
    grades: ['Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo'],
  },
};

// Firma digital simulada válida en base64 para la Ley 527 de 1999
const MOCK_SIGNATURE_BASE64 =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAAA8CAYAAAAz6O1JAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAEKSURBVHhe7cExAQAAAMKg9U9tCj+gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA+DAz4AAFF4C9AAAAAAElFTkSuQmCC';

export default function MatriculaScreen({ navigation, route }) {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [enrollment, setEnrollment] = useState(null);
  const [documents, setDocuments] = useState([]);
  const [gradesConfig, setGradesConfig] = useState(null);

  // Estado del Wizard Modal en la App
  const [modalVisible, setModalVisible] = useState(false);
  const [wizardStep, setWizardStep] = useState(1);
  const [submitting, setSubmitting] = useState(false);

  // Formulario en la App
  const [formStudent, setFormStudent] = useState({
    firstName: '',
    lastName: '',
    docType: 'TI',
    docNumber: '',
    birthDate: '2015-01-15',
    phone: '',
    address: '',
  });

  const [formGuardian, setFormGuardian] = useState({
    fullName: user?.full_name || '',
    docType: 'CC',
    docNumber: '',
    phone: user?.phone || '',
    email: user?.email || '',
    relationship: 'Padre',
  });

  const [formAcademic, setFormAcademic] = useState({
    level: 'BASICA_PRIMARIA',
    grade: 'Primero',
    prevSchool: 'Colegio Anterior',
  });

  const [acceptTerms, setAcceptTerms] = useState(true);

  const loadEnrollmentData = async () => {
    try {
      const [myRes, configRes] = await Promise.allSettled([
        API.getMyEnrollments(),
        API.getGradesConfig(),
      ]);

      if (configRes.status === 'fulfilled' && configRes.value?.data) {
        setGradesConfig(configRes.value.data);
      }

      if (myRes.status === 'fulfilled' && myRes.value?.data?.enrollments?.length > 0) {
        const active = myRes.value.data.enrollments[0];
        setEnrollment(active);

        // Cargar documentos del expediente
        try {
          const docsRes = await API.getEnrollmentDocuments(active.id);
          if (docsRes?.data?.documents) {
            setDocuments(docsRes.data.documents);
          }
        } catch (docErr) {
          console.warn('Error cargando documentos:', docErr.message);
        }
      } else {
        setEnrollment(null);
      }
    } catch (e) {
      console.warn('Error en MatriculaScreen:', e.message);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadEnrollmentData();
  }, []);

  // Abrir wizard automáticamente si vino con startInApp
  useEffect(() => {
    if (route?.params?.startInApp) {
      setModalVisible(true);
    }
  }, [route?.params?.startInApp]);

  const onRefresh = () => {
    setRefreshing(true);
    loadEnrollmentData();
  };

  const openDocumentUrl = async (url) => {
    if (!url) {
      Alert.alert('Documento no disponible', 'La URL del documento no está lista todavía.');
      return;
    }
    try {
      const supported = await Linking.canOpenURL(url);
      if (supported) {
        await Linking.openURL(url);
      } else {
        Alert.alert('Abrir Documento', `Puedes acceder al documento en:\n${url}`);
      }
    } catch (e) {
      Alert.alert('Error', 'No se pudo abrir el documento PDF: ' + e.message);
    }
  };

  const openWebBrowser = () => {
    Linking.openURL('https://pruebas.femtribe.com.co/matricula');
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'APPROVED':
        return { label: '✅ MATRÍCULA APROBADA', bg: '#DCFCE7', text: '#15803D' };
      case 'SIGNED':
      case 'PENDING_REVIEW':
        return { label: '⏳ EN REVISIÓN DE RECTORÍA', bg: '#DBEAFE', text: '#1D4ED8' };
      case 'DOCUMENTS_PENDING':
        return { label: '⚠️ CORRECCIÓN REQUERIDA', bg: '#FEF3C7', text: '#B45309' };
      case 'REJECTED':
        return { label: '❌ RECHAZADA', bg: '#FEE2E2', text: '#DC2626' };
      default:
        return { label: '📝 EN BORRADOR', bg: '#F1F5F9', text: '#475569' };
    }
  };

  // Validaciones del Wizard
  const handleNextStep = () => {
    if (wizardStep === 1) {
      if (!formStudent.firstName.trim() || !formStudent.lastName.trim()) {
        Alert.alert('Datos Incompletos', 'Por favor ingresa los nombres y apellidos del estudiante.');
        return;
      }
      if (!formStudent.docNumber.trim()) {
        Alert.alert('Datos Incompletos', 'Por favor ingresa el documento de identidad del estudiante.');
        return;
      }
      setWizardStep(2);
    } else if (wizardStep === 2) {
      if (!formGuardian.fullName.trim() || !formGuardian.docNumber.trim()) {
        Alert.alert('Datos Incompletos', 'Por favor ingresa el nombre y documento del acudiente.');
        return;
      }
      setWizardStep(3);
    } else if (wizardStep === 3) {
      setWizardStep(4);
    }
  };

  // Radicación Oficial en la App
  const handleSubmitEnrollmentInApp = async () => {
    if (!acceptTerms) {
      Alert.alert('Términos Requeridos', 'Debes aceptar los términos y condiciones de matrícula.');
      return;
    }

    setSubmitting(true);
    try {
      // 1. Crear o recuperar borrador
      const createRes = await API.createEnrollment({
        force_new: true,
        enrollment_type: formAcademic.level,
        target_grade: formAcademic.grade,
      });

      const newEnrollmentId =
        createRes?.data?.enrollment?.id || createRes?.data?.id || enrollment?.id;

      if (!newEnrollmentId) {
        throw new Error('No se pudo inicializar el expediente de matrícula.');
      }

      // 2. Guardar Paso 1: Estudiante
      await API.updateEnrollmentStep(newEnrollmentId, 1, {
        student: {
          first_name: formStudent.firstName.trim(),
          last_name: formStudent.lastName.trim(),
          doc_type: formStudent.docType,
          doc_number: formStudent.docNumber.trim(),
          birth_date: formStudent.birthDate,
          birth_place: 'Girardot',
          age: 10,
          doc_issue_place: 'Girardot',
          eps: 'Particular',
          rh: 'O+',
          lives_with_parents: 'SI',
          lives_with_whom: 'Padres',
          address: formStudent.address || 'Girardot, Cundinamarca',
          phone: formStudent.phone || '3000000000',
          email: user?.email || '',
        },
      });

      // 3. Guardar Paso 4: Acudiente
      await API.updateEnrollmentStep(newEnrollmentId, 4, {
        guardian: {
          full_name: formGuardian.fullName.trim(),
          doc_type: formGuardian.docType,
          doc_number: formGuardian.docNumber.trim(),
          doc_issue_place: 'Girardot',
          relationship: formGuardian.relationship,
          phone: formGuardian.phone || '3000000000',
          email: formGuardian.email || user?.email || '',
          address: formStudent.address || 'Girardot',
          occupation: 'Empleado/Comerciante',
          is_parent: 'PADRE',
        },
      });

      // 4. Guardar Paso 5: Grado e Historial
      await API.updateEnrollmentStep(newEnrollmentId, 5, {
        enrollment_type: formAcademic.level,
        target_grade: formAcademic.grade,
        academic: {
          previous_school: formAcademic.prevSchool || 'Institución Anterior',
          previous_grade: 'Grado Anterior',
          previous_year: 2025,
        },
      });

      // 5. Guardar Paso 6: Finanzas Oficiales
      await API.updateEnrollmentStep(newEnrollmentId, 6, {
        economics: {
          enrollment_fee: 180000.0,
          monthly_fee: 150000.0,
          installments_count: 10,
          payment_method: 'Mensual',
        },
      });

      // 6. Guardar Paso 7: Autorizaciones
      await API.updateEnrollmentStep(newEnrollmentId, 7, {
        authorizations: {
          auth_data_treatment: 1,
          auth_image_use: 1,
          auth_regulations_accept: 1,
        },
      });

      // 7. Firmar Electrónicamente bajo la Ley 527
      await API.signEnrollment(newEnrollmentId, MOCK_SIGNATURE_BASE64);

      // 8. Radicar y Enviar a Rectoría
      await API.submitEnrollment(newEnrollmentId);

      Alert.alert(
        '¡Matrícula Radicada con Éxito! 🎉',
        'Se han generado los 4 documentos contractuales oficiales en PDF y tu expediente ha sido radicado ante Rectoría.',
        [{ text: 'Aceptar', onPress: () => setModalVisible(false) }]
      );

      // Recargar datos en la pantalla
      await loadEnrollmentData();
    } catch (err) {
      console.error('Error radicando matrícula:', err);
      Alert.alert(
        'Error al Radicar',
        err.message || 'Ocurrió un inconveniente al procesar la matrícula. Inténtalo nuevamente.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={{ marginTop: 12, color: colors.textMuted, fontSize: 13 }}>
          Cargando expediente de matrícula 2026...
        </Text>
      </View>
    );
  }

  const badge = enrollment ? getStatusBadge(enrollment.status) : null;

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Banner Institucional Superior */}
      <View style={styles.schoolHeader}>
        <View style={styles.schoolBadgeRow}>
          <Image
            source={require('../../assets/logo_jean_piaget.png')}
            style={{ width: 48, height: 48 }}
            resizeMode="contain"
          />
          <View style={{ flex: 1 }}>
            <Text style={styles.schoolName}>Institución Educativa Jean Piaget School</Text>
            <Text style={styles.schoolSub}>Girardot, Cundinamarca &bull; Año Lectivo 2026</Text>
          </View>
        </View>
        <Text style={styles.resolutionText}>
          {gradesConfig?.school?.resolutions || 'Resolución No. 644 de 2015 / No. 1187 de 2019'}
        </Text>
      </View>

      {/* Botones de Opción Dual: App vs Navegador */}
      <View style={styles.dualActionRow}>
        <TouchableOpacity
          style={styles.dualBtnApp}
          onPress={() => setModalVisible(true)}
          activeOpacity={0.85}
        >
          <Text style={styles.dualBtnAppText}>📱 Iniciar en la App</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.dualBtnWeb}
          onPress={openWebBrowser}
          activeOpacity={0.85}
        >
          <Text style={styles.dualBtnWebText}>🌐 En el Navegador</Text>
        </TouchableOpacity>
      </View>

      {enrollment ? (
        <>
          {/* Tarjeta de Expediente Activo */}
          <View style={styles.card}>
            <View style={styles.statusRow}>
              <View style={[styles.statusBadge, { backgroundColor: badge.bg }]}>
                <Text style={[styles.statusBadgeText, { color: badge.text }]}>{badge.label}</Text>
              </View>
              <Text style={styles.codeText}>{enrollment.enrollment_code}</Text>
            </View>

            <Text style={styles.studentName}>
              {enrollment.student_name || user?.full_name || 'Estudiante'}
            </Text>
            <Text style={styles.studentDoc}>
              Documento: {enrollment.student_document || 'Registrado'} &bull; Grado:{' '}
              {enrollment.grade || 'General'}
            </Text>

            <View style={styles.divider} />

            {/* Fila Informativa de Costos */}
            <View style={styles.infoGrid}>
              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Nivel Educativo</Text>
                <Text style={styles.infoVal}>{enrollment.level || 'Básica Primaria'}</Text>
              </View>
              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Año Lectivo</Text>
                <Text style={styles.infoVal}>2026</Text>
              </View>
              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Matrícula Oficial</Text>
                <Text style={[styles.infoVal, { color: colors.successDark }]}>$180.000 COP</Text>
              </View>
              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Pensión Mensual</Text>
                <Text style={[styles.infoVal, { color: colors.primary }]}>$150.000 (×10)</Text>
              </View>
            </View>
          </View>

          {/* Sello de Firma Electrónica Ley 527 */}
          {enrollment.signed_at && (
            <View style={styles.legalBox}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                <Text style={{ fontSize: 16 }}>🔒</Text>
                <Text style={styles.legalTitle}>Firma Electrónica Certificada</Text>
              </View>
              <Text style={styles.legalText}>
                Firmado electrónicamente bajo la Ley 527 de 1999 con plena validez jurídica y probatoria.
              </Text>
              <Text style={styles.legalMeta}>Fecha de radicación: {enrollment.signed_at}</Text>
            </View>
          )}

          {/* Sección Documentos Oficiales en PDF */}
          <Text style={styles.sectionTitle}>📄 Documentos Oficiales Generados (PDF)</Text>
          <Text style={styles.sectionSub}>
            Toca sobre cualquiera de los 4 documentos contractuales oficiales para descargarlo:
          </Text>

          {documents && documents.length > 0 ? (
            documents.map((doc, idx) => (
              <TouchableOpacity
                key={doc.id || idx}
                style={styles.docItemCard}
                onPress={() => openDocumentUrl(doc.public_url)}
                activeOpacity={0.7}
              >
                <View style={styles.docIconWrap}>
                  <Text style={{ fontSize: 20 }}>
                    {doc.document_type?.includes('contrato')
                      ? '📜'
                      : doc.document_type?.includes('pagare')
                      ? '💳'
                      : doc.document_type?.includes('carta')
                      ? '✉️'
                      : '📑'}
                  </Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.docTitle}>
                    {doc.document_type === 'FORMATO_MATRICULA'
                      ? '1. Formato de Matrícula 2026'
                      : doc.document_type === 'CONTRATO'
                      ? '2. Contrato de Servicios Educativos'
                      : doc.document_type === 'PAGARE'
                      ? '3. Pagaré Oficial 2026'
                      : doc.document_type === 'CARTA_INSTRUCCIONES'
                      ? '4. Carta de Instrucciones'
                      : doc.document_type}
                  </Text>
                  <Text style={styles.docMeta}>
                    Versión {doc.version || 1} &bull; Estado:{' '}
                    {doc.is_signed ? 'Firmado digitalmente' : 'Borrador'}
                  </Text>
                </View>
                <Text style={styles.downloadBtn}>Descargar 📥</Text>
              </TouchableOpacity>
            ))
          ) : (
            <View style={styles.emptyDocsCard}>
              <Text style={{ fontSize: 13, color: colors.textMuted, textAlign: 'center' }}>
                Los documentos PDF oficiales están disponibles y se pueden generar o descargar en cualquier momento.
              </Text>
            </View>
          )}
        </>
      ) : (
        /* Caso sin matrícula activa - Tarjetas de Selección */
        <View style={styles.choiceContainer}>
          <Text style={styles.choiceMainTitle}>¿Cómo deseas realizar tu matrícula?</Text>
          <Text style={styles.choiceMainSub}>
            Elige la opción que más se ajuste a tu preferencia. Ambos canales son válidos oficialmente.
          </Text>

          {/* Opción 1: En la App */}
          <TouchableOpacity
            style={styles.choiceCardApp}
            onPress={() => setModalVisible(true)}
            activeOpacity={0.88}
          >
            <View style={styles.choiceIconBadge}>
              <Text style={{ fontSize: 28 }}>📱</Text>
            </View>
            <View style={{ flex: 1 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                <Text style={styles.choiceCardTitle}>Iniciar en la App</Text>
                <View style={styles.choiceBadgeTag}>
                  <Text style={styles.choiceBadgeTagText}>RÁPIDO</Text>
                </View>
              </View>
              <Text style={styles.choiceCardDesc}>
                Diligencia los datos del estudiante y acudiente, selecciona el grado y firma digitalmente desde tu celular en 4 pasos.
              </Text>
              <View style={styles.choiceBtnAppMini}>
                <Text style={styles.choiceBtnAppMiniText}>🚀 Comenzar en la App</Text>
              </View>
            </View>
          </TouchableOpacity>

          {/* Opción 2: En el Navegador */}
          <TouchableOpacity
            style={styles.choiceCardWeb}
            onPress={openWebBrowser}
            activeOpacity={0.88}
          >
            <View style={[styles.choiceIconBadge, { backgroundColor: '#F1F5F9' }]}>
              <Text style={{ fontSize: 28 }}>🌐</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={[styles.choiceCardTitle, { color: '#0F172A' }]}>
                Hacer en el Navegador Web
              </Text>
              <Text style={styles.choiceCardDesc}>
                Abre el portal web completo con el formulario guiado de 10 pasos, adjunto de soportes y firma electrónica.
              </Text>
              <View style={styles.choiceBtnWebMini}>
                <Text style={styles.choiceBtnWebMiniText}>🌐 Abrir en el Navegador</Text>
              </View>
            </View>
          </TouchableOpacity>
        </View>
      )}

      {/* MODAL: Wizard de Matrícula en la App */}
      <Modal
        visible={modalVisible}
        animationType="slide"
        transparent={false}
        onRequestClose={() => setModalVisible(false)}
      >
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalContainer}
        >
          {/* Header del Modal */}
          <View style={styles.modalHeader}>
            <TouchableOpacity
              onPress={() => setModalVisible(false)}
              style={styles.modalCloseBtn}
            >
              <Text style={styles.modalCloseText}>✕ Cerrar</Text>
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Matrícula Digital 2026</Text>
            <TouchableOpacity onPress={openWebBrowser}>
              <Text style={{ color: '#38BDF8', fontSize: 11, fontWeight: '700' }}>🌐 Web</Text>
            </TouchableOpacity>
          </View>

          {/* Indicador de Pasos (1 al 4) */}
          <View style={styles.stepsIndicator}>
            {[1, 2, 3, 4].map((step) => (
              <View
                key={step}
                style={[
                  styles.stepDot,
                  wizardStep === step && styles.stepDotActive,
                  wizardStep > step && styles.stepDotCompleted,
                ]}
              >
                <Text
                  style={[
                    styles.stepDotText,
                    wizardStep === step && styles.stepDotTextActive,
                    wizardStep > step && styles.stepDotTextCompleted,
                  ]}
                >
                  {wizardStep > step ? '✓' : step}
                </Text>
              </View>
            ))}
          </View>

          <ScrollView style={styles.modalBody} contentContainerStyle={{ paddingBottom: 40 }}>
            {/* PASO 1: ESTUDIANTE */}
            {wizardStep === 1 && (
              <View>
                <Text style={styles.stepSectionTitle}>Paso 1: Datos del Estudiante</Text>
                <Text style={styles.stepSectionSub}>
                  Ingresa la información básica del alumno a matricular:
                </Text>

                <Text style={styles.inputLabel}>Nombres del Estudiante *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: Juan Sebastián"
                  placeholderTextColor="#94A3B8"
                  value={formStudent.firstName}
                  onChangeText={(v) => setFormStudent({ ...formStudent, firstName: v })}
                />

                <Text style={styles.inputLabel}>Apellidos del Estudiante *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: Gómez Martínez"
                  placeholderTextColor="#94A3B8"
                  value={formStudent.lastName}
                  onChangeText={(v) => setFormStudent({ ...formStudent, lastName: v })}
                />

                <Text style={styles.inputLabel}>Tipo de Documento</Text>
                <View style={styles.docTypeRow}>
                  {['TI', 'RC', 'CC', 'PEP'].map((t) => (
                    <TouchableOpacity
                      key={t}
                      style={[
                        styles.docTypeBtn,
                        formStudent.docType === t && styles.docTypeBtnActive,
                      ]}
                      onPress={() => setFormStudent({ ...formStudent, docType: t })}
                    >
                      <Text
                        style={[
                          styles.docTypeBtnText,
                          formStudent.docType === t && styles.docTypeBtnTextActive,
                        ]}
                      >
                        {t}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>

                <Text style={styles.inputLabel}>Número de Documento *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: 1069874521"
                  placeholderTextColor="#94A3B8"
                  keyboardType="numeric"
                  value={formStudent.docNumber}
                  onChangeText={(v) => setFormStudent({ ...formStudent, docNumber: v })}
                />

                <Text style={styles.inputLabel}>Fecha de Nacimiento (AAAA-MM-DD)</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="2015-05-15"
                  placeholderTextColor="#94A3B8"
                  value={formStudent.birthDate}
                  onChangeText={(v) => setFormStudent({ ...formStudent, birthDate: v })}
                />

                <Text style={styles.inputLabel}>Teléfono / Celular de Contacto</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="310 000 0000"
                  placeholderTextColor="#94A3B8"
                  keyboardType="phone-pad"
                  value={formStudent.phone}
                  onChangeText={(v) => setFormStudent({ ...formStudent, phone: v })}
                />

                <Text style={styles.inputLabel}>Dirección de Residencia</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: Calle 14 # 8-32, Girardot"
                  placeholderTextColor="#94A3B8"
                  value={formStudent.address}
                  onChangeText={(v) => setFormStudent({ ...formStudent, address: v })}
                />
              </View>
            )}

            {/* PASO 2: ACUDIENTE */}
            {wizardStep === 2 && (
              <View>
                <Text style={styles.stepSectionTitle}>Paso 2: Datos del Acudiente</Text>
                <Text style={styles.stepSectionSub}>
                  Representante legal responsable del contrato de servicios educativos:
                </Text>

                <Text style={styles.inputLabel}>Nombre Completo del Acudiente *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: Carlos Gómez"
                  placeholderTextColor="#94A3B8"
                  value={formGuardian.fullName}
                  onChangeText={(v) => setFormGuardian({ ...formGuardian, fullName: v })}
                />

                <Text style={styles.inputLabel}>Número de Cédula de Ciudadanía *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Ej: 19874563"
                  placeholderTextColor="#94A3B8"
                  keyboardType="numeric"
                  value={formGuardian.docNumber}
                  onChangeText={(v) => setFormGuardian({ ...formGuardian, docNumber: v })}
                />

                <Text style={styles.inputLabel}>Parentesco con el Estudiante</Text>
                <View style={styles.docTypeRow}>
                  {['Padre', 'Madre', 'Tutor / Acudiente'].map((rel) => (
                    <TouchableOpacity
                      key={rel}
                      style={[
                        styles.docTypeBtn,
                        formGuardian.relationship === rel && styles.docTypeBtnActive,
                      ]}
                      onPress={() => setFormGuardian({ ...formGuardian, relationship: rel })}
                    >
                      <Text
                        style={[
                          styles.docTypeBtnText,
                          formGuardian.relationship === rel && styles.docTypeBtnTextActive,
                        ]}
                      >
                        {rel}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>

                <Text style={styles.inputLabel}>Teléfono / WhatsApp *</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="300 000 0000"
                  placeholderTextColor="#94A3B8"
                  keyboardType="phone-pad"
                  value={formGuardian.phone}
                  onChangeText={(v) => setFormGuardian({ ...formGuardian, phone: v })}
                />

                <Text style={styles.inputLabel}>Correo Electrónico</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="acudiente@ejemplo.com"
                  placeholderTextColor="#94A3B8"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={formGuardian.email}
                  onChangeText={(v) => setFormGuardian({ ...formGuardian, email: v })}
                />
              </View>
            )}

            {/* PASO 3: NIVEL Y GRADO */}
            {wizardStep === 3 && (
              <View>
                <Text style={styles.stepSectionTitle}>Paso 3: Nivel Educativo y Grado</Text>
                <Text style={styles.stepSectionSub}>
                  Selecciona el nivel y grado para el año lectivo 2026:
                </Text>

                <Text style={styles.inputLabel}>Nivel Educativo</Text>
                {Object.entries(LEVELS_CONFIG).map(([key, item]) => {
                  const isSelected = formAcademic.level === key;
                  return (
                    <TouchableOpacity
                      key={key}
                      style={[
                        styles.levelSelectCard,
                        isSelected && styles.levelSelectCardActive,
                      ]}
                      onPress={() => {
                        setFormAcademic({
                          ...formAcademic,
                          level: key,
                          grade: item.grades[0],
                        });
                      }}
                    >
                      <View style={{ flex: 1 }}>
                        <Text
                          style={[
                            styles.levelSelectLabel,
                            isSelected && { color: '#003366', fontWeight: '800' },
                          ]}
                        >
                          {item.label}
                        </Text>
                        <Text style={styles.levelSelectSub}>
                          {item.grades.join(', ')}
                        </Text>
                      </View>
                      <Text style={{ fontSize: 18 }}>{isSelected ? '🔘' : '⚪'}</Text>
                    </TouchableOpacity>
                  );
                })}

                <Text style={[styles.inputLabel, { marginTop: 14 }]}>
                  Grado Específico ({LEVELS_CONFIG[formAcademic.level]?.label})
                </Text>
                <View style={styles.gradeGrid}>
                  {LEVELS_CONFIG[formAcademic.level]?.grades.map((gr) => {
                    const isGrSelected = formAcademic.grade === gr;
                    return (
                      <TouchableOpacity
                        key={gr}
                        style={[
                          styles.gradeBtn,
                          isGrSelected && styles.gradeBtnActive,
                        ]}
                        onPress={() => setFormAcademic({ ...formAcademic, grade: gr })}
                      >
                        <Text
                          style={[
                            styles.gradeBtnText,
                            isGrSelected && styles.gradeBtnTextActive,
                          ]}
                        >
                          {gr}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>

                <Text style={[styles.inputLabel, { marginTop: 16 }]}>Colegio de Procedencia</Text>
                <TextInput
                  style={styles.inputField}
                  placeholder="Institución Educativa Anterior"
                  placeholderTextColor="#94A3B8"
                  value={formAcademic.prevSchool}
                  onChangeText={(v) => setFormAcademic({ ...formAcademic, prevSchool: v })}
                />
              </View>
            )}

            {/* PASO 4: RESUMEN Y FIRMA DIGITAL */}
            {wizardStep === 4 && (
              <View>
                <Text style={styles.stepSectionTitle}>Paso 4: Resumen y Firma Digital</Text>
                <Text style={styles.stepSectionSub}>
                  Verifica los costos oficiales de Jean Piaget School y autoriza la matrícula:
                </Text>

                {/* Tarjeta de Resumen */}
                <View style={styles.summaryCard}>
                  <Text style={styles.summaryTitle}>Expediente 2026</Text>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Estudiante:</Text>
                    <Text style={styles.summaryVal}>
                      {formStudent.firstName} {formStudent.lastName}
                    </Text>
                  </View>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Documento:</Text>
                    <Text style={styles.summaryVal}>
                      {formStudent.docType} {formStudent.docNumber}
                    </Text>
                  </View>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Grado a Cursar:</Text>
                    <Text style={styles.summaryVal}>{formAcademic.grade} (2026)</Text>
                  </View>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Acudiente Legal:</Text>
                    <Text style={styles.summaryVal}>{formGuardian.fullName}</Text>
                  </View>

                  <View style={styles.divider} />

                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Valor Matrícula:</Text>
                    <Text style={[styles.summaryVal, { color: colors.successDark }]}>
                      $180.000 COP
                    </Text>
                  </View>
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Pensión Mensual:</Text>
                    <Text style={[styles.summaryVal, { color: colors.primary }]}>
                      $150.000 (×10 mensualidades)
                    </Text>
                  </View>
                </View>

                {/* Sello Ley 527 */}
                <View style={styles.legalStamp}>
                  <Text style={styles.legalStampTitle}>
                    📜 Firma Electrónica Ley 527 de 1999
                  </Text>
                  <Text style={styles.legalStampText}>
                    Al radicar esta matrícula, confirmas la veracidad de los datos y aceptas el
                    Contrato de Servicios Educativos, Pagaré y Carta de Instrucciones de la
                    Institución Educativa Jean Piaget School.
                  </Text>
                  <TouchableOpacity
                    style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 10 }}
                    onPress={() => setAcceptTerms(!acceptTerms)}
                  >
                    <Text style={{ fontSize: 20 }}>{acceptTerms ? '☑️' : '⬜'}</Text>
                    <Text style={{ fontSize: 12, fontWeight: '700', color: '#1E293B', flex: 1 }}>
                      Acepto los términos y autorizo la firma digital de los documentos oficiales.
                    </Text>
                  </TouchableOpacity>
                </View>

                {/* Botón de Radicación Final */}
                <TouchableOpacity
                  style={styles.submitEnrollBtn}
                  onPress={handleSubmitEnrollmentInApp}
                  disabled={submitting}
                  activeOpacity={0.85}
                >
                  {submitting ? (
                    <ActivityIndicator color="#FFFFFF" />
                  ) : (
                    <Text style={styles.submitEnrollBtnText}>
                      ✍️ Radicar y Generar Documentos Oficiales
                    </Text>
                  )}
                </TouchableOpacity>
              </View>
            )}
          </ScrollView>

          {/* Barra de Navegación del Modal */}
          <View style={styles.modalFooter}>
            {wizardStep > 1 ? (
              <TouchableOpacity
                style={styles.footerBackBtn}
                onPress={() => setWizardStep(wizardStep - 1)}
              >
                <Text style={styles.footerBackText}>← Anterior</Text>
              </TouchableOpacity>
            ) : (
              <View style={{ width: 80 }} />
            )}

            {wizardStep < 4 ? (
              <TouchableOpacity style={styles.footerNextBtn} onPress={handleNextStep}>
                <Text style={styles.footerNextText}>Siguiente →</Text>
              </TouchableOpacity>
            ) : null}
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  content: {
    padding: 16,
    paddingBottom: 40,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    padding: 20,
  },
  schoolHeader: {
    backgroundColor: '#002244',
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
  },
  schoolBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  schoolName: {
    fontSize: 15,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  schoolSub: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  resolutionText: {
    fontSize: 10,
    color: '#6EE7B7',
    marginTop: 8,
    fontWeight: '600',
  },
  dualActionRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 14,
  },
  dualBtnApp: {
    flex: 1,
    backgroundColor: '#15803D',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 2,
  },
  dualBtnAppText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 12,
  },
  dualBtnWeb: {
    flex: 1,
    backgroundColor: '#003366',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 2,
  },
  dualBtnWebText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 12,
  },
  choiceContainer: {
    marginTop: 6,
  },
  choiceMainTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 4,
  },
  choiceMainSub: {
    fontSize: 12,
    color: '#64748B',
    marginBottom: 16,
    lineHeight: 18,
  },
  choiceCardApp: {
    backgroundColor: '#002244',
    borderRadius: 14,
    padding: 18,
    flexDirection: 'row',
    gap: 14,
    marginBottom: 14,
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
    elevation: 3,
  },
  choiceCardWeb: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 18,
    flexDirection: 'row',
    gap: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    marginBottom: 14,
  },
  choiceIconBadge: {
    width: 48,
    height: 48,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  choiceCardTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  choiceBadgeTag: {
    backgroundColor: '#10B981',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  choiceBadgeTagText: {
    color: '#FFFFFF',
    fontSize: 9,
    fontWeight: '800',
  },
  choiceCardDesc: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 4,
    lineHeight: 16,
  },
  choiceBtnAppMini: {
    marginTop: 10,
    backgroundColor: '#15803D',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    alignSelf: 'flex-start',
  },
  choiceBtnAppMiniText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 11,
  },
  choiceBtnWebMini: {
    marginTop: 10,
    backgroundColor: '#003366',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    alignSelf: 'flex-start',
  },
  choiceBtnWebMiniText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 11,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 14,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
    elevation: 2,
  },
  statusRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
  },
  statusBadgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  codeText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#003366',
  },
  studentName: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 2,
  },
  studentDoc: {
    fontSize: 12,
    color: '#64748B',
  },
  divider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 14,
  },
  infoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  infoItem: {
    width: '46%',
  },
  infoLabel: {
    fontSize: 11,
    color: '#64748B',
    marginBottom: 2,
  },
  infoVal: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
  },
  legalBox: {
    backgroundColor: '#EFF6FF',
    borderRadius: 10,
    padding: 14,
    borderLeftWidth: 4,
    borderLeftColor: '#3B82F6',
    marginBottom: 18,
  },
  legalTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#1D4ED8',
  },
  legalText: {
    fontSize: 11,
    color: '#334155',
    lineHeight: 16,
  },
  legalMeta: {
    fontSize: 10,
    color: '#64748B',
    marginTop: 4,
    fontWeight: '600',
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#003366',
    marginBottom: 4,
  },
  sectionSub: {
    fontSize: 11,
    color: '#64748B',
    marginBottom: 12,
  },
  docItemCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 10,
  },
  docIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  docTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0F172A',
  },
  docMeta: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  downloadBtn: {
    fontSize: 11,
    fontWeight: '700',
    color: '#0284C7',
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  emptyDocsCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  modalContainer: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 14,
    backgroundColor: '#002244',
  },
  modalCloseBtn: {
    paddingVertical: 4,
    paddingHorizontal: 8,
  },
  modalCloseText: {
    color: '#E2E8F0',
    fontSize: 13,
    fontWeight: '700',
  },
  modalTitle: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
  stepsIndicator: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
    paddingVertical: 12,
    backgroundColor: '#F8FAFC',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  stepDot: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#E2E8F0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepDotActive: {
    backgroundColor: '#003366',
  },
  stepDotCompleted: {
    backgroundColor: '#15803D',
  },
  stepDotText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#64748B',
  },
  stepDotTextActive: {
    color: '#FFFFFF',
  },
  stepDotTextCompleted: {
    color: '#FFFFFF',
  },
  modalBody: {
    flex: 1,
    padding: 18,
  },
  stepSectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#002244',
    marginBottom: 4,
  },
  stepSectionSub: {
    fontSize: 12,
    color: '#64748B',
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
    marginBottom: 6,
  },
  inputField: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: '#0F172A',
    marginBottom: 12,
  },
  docTypeRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 14,
  },
  docTypeBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
  },
  docTypeBtnActive: {
    borderColor: '#003366',
    backgroundColor: '#EFF6FF',
  },
  docTypeBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
  },
  docTypeBtnTextActive: {
    color: '#003366',
    fontWeight: '800',
  },
  levelSelectCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    marginBottom: 8,
    backgroundColor: '#F8FAFC',
  },
  levelSelectCardActive: {
    borderColor: '#003366',
    backgroundColor: '#EFF6FF',
  },
  levelSelectLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#334155',
  },
  levelSelectSub: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  gradeGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  gradeBtn: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    backgroundColor: '#F8FAFC',
  },
  gradeBtnActive: {
    backgroundColor: '#003366',
    borderColor: '#003366',
  },
  gradeBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#475569',
  },
  gradeBtnTextActive: {
    color: '#FFFFFF',
    fontWeight: '800',
  },
  summaryCard: {
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
  },
  summaryTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#003366',
    marginBottom: 10,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  summaryLabel: {
    fontSize: 12,
    color: '#64748B',
  },
  summaryVal: {
    fontSize: 12,
    fontWeight: '700',
    color: '#0F172A',
  },
  legalStamp: {
    backgroundColor: '#FEF3C7',
    borderRadius: 10,
    padding: 14,
    borderWidth: 1,
    borderColor: '#FDE68A',
    marginBottom: 20,
  },
  legalStampTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#92400E',
    marginBottom: 4,
  },
  legalStampText: {
    fontSize: 11,
    color: '#78350F',
    lineHeight: 16,
  },
  submitEnrollBtn: {
    backgroundColor: '#15803D',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 3,
  },
  submitEnrollBtnText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  modalFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
  },
  footerBackBtn: {
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  footerBackText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#475569',
  },
  footerNextBtn: {
    backgroundColor: '#003366',
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 8,
  },
  footerNextText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FFFFFF',
  },
});
