import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Image,
  Linking,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { API } from '../api/client';

export default function HomeScreen({ navigation }) {
  const { user } = useAuth();
  const [refreshing, setRefreshing] = useState(false);
  const [courseData, setCourseData] = useState({
    price: 700000,
    paid: 280000,
    progressPercent: 40,
  });
  const [contractSigned, setContractSigned] = useState(false);
  const [docStatus, setDocStatus] = useState('En revisión ⌛');
  const [activeEnrollment, setActiveEnrollment] = useState(null);

  const loadData = async () => {
    try {
      const [summaryRes, contractRes, docRes, enrollRes] = await Promise.allSettled([
        API.getCourseSummary(),
        API.getContract(),
        API.getDocument(),
        API.getMyEnrollments(),
      ]);

      if (summaryRes.status === 'fulfilled' && summaryRes.value?.data) {
        const d = summaryRes.value.data;
        const total = d.total_price || 700000;
        const paid = d.total_paid || 280000;
        setCourseData({
          price: total,
          paid: paid,
          progressPercent: Math.round((paid / total) * 100),
        });
      }

      if (contractRes.status === 'fulfilled' && contractRes.value?.data) {
        setContractSigned(!!contractRes.value.data.is_signed);
      }

      if (docRes.status === 'fulfilled' && docRes.value?.data?.document) {
        const s = docRes.value.data.document.status;
        if (s === 'approved') setDocStatus('Aprobado ✅');
        else if (s === 'rejected') setDocStatus('Rechazado ❌');
        else setDocStatus('En revisión ⌛');
      }

      if (enrollRes.status === 'fulfilled' && enrollRes.value?.data?.enrollments?.length > 0) {
        setActiveEnrollment(enrollRes.value.data.enrollments[0]);
      }
    } catch (e) {
      console.warn('Error en HomeScreen:', e);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  const firstName = user?.full_name ? user.full_name.split(' ')[0] : 'Estudiante';

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Saludo y Badge Institucional */}
      <View style={styles.topRow}>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1 }}>
          <Image
            source={require('../../assets/logo_jean_piaget.png')}
            style={{ width: 42, height: 42 }}
            resizeMode="contain"
          />
          <View style={{ flex: 1 }}>
            <Text style={styles.greeting}>¡Hola, {firstName}! 👋</Text>
            <Text style={styles.greetingSub}>Jean Piaget School &bull; Girardot</Text>
          </View>
        </View>
        <View
          style={[
            styles.statusBadge,
            { backgroundColor: contractSigned ? colors.successSubtle : colors.warningSubtle },
          ]}
        >
          <Text
            style={[
              styles.statusBadgeText,
              { color: contractSigned ? colors.successDark : '#B45309' },
            ]}
          >
            {contractSigned ? 'Matrícula Activa' : 'Firma Pendiente'}
          </Text>
        </View>
      </View>

      {/* Tarjeta Matrícula Digital Jean Piaget School 2026 */}
      <View style={styles.matriculaCard}>
        <View style={styles.matriculaTop}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1 }}>
            <Text style={{ fontSize: 22 }}>🎓</Text>
            <View style={{ flex: 1 }}>
              <Text style={styles.matriculaTitle}>Jean Piaget School &bull; Matrícula 2026</Text>
              <Text style={styles.matriculaSub} numberOfLines={1}>
                {activeEnrollment ? `Expediente: ${activeEnrollment.enrollment_code} (${activeEnrollment.grade || '2026'})` : 'Matrícula Oficial Digital 2026'}
              </Text>
            </View>
          </View>
          <View style={[
            styles.matriculaBadge,
            { backgroundColor: activeEnrollment?.status === 'APPROVED' ? '#DCFCE7' : '#EFF6FF' }
          ]}>
            <Text style={[
              styles.matriculaBadgeText,
              { color: activeEnrollment?.status === 'APPROVED' ? '#15803D' : '#1D4ED8' }
            ]}>
              {activeEnrollment?.status === 'APPROVED' ? 'Aprobada ✅' : activeEnrollment?.status ? 'En Trámite ⌛' : 'Ver 🚀'}
            </Text>
          </View>
        </View>
        <Text style={styles.matriculaPrompt}>
          {activeEnrollment
            ? 'Expediente digital activo. Consulta el estado o abre el formulario oficial:'
            : 'Costos oficiales: Matrícula $180.000 + 10 mensualidades de $150.000. Elige cómo deseas realizar tu matrícula:'}
        </Text>

        <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
          <TouchableOpacity
            style={{
              flex: 1,
              backgroundColor: '#15803D',
              paddingVertical: 9,
              paddingHorizontal: 10,
              borderRadius: 8,
              alignItems: 'center',
            }}
            onPress={() => navigation.navigate('Matricula', { startInApp: true })}
            activeOpacity={0.8}
          >
            <Text style={{ color: '#FFFFFF', fontWeight: '800', fontSize: 11 }}>
              📱 {activeEnrollment ? 'Ver en la App' : 'Iniciar en la App'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={{
              flex: 1,
              backgroundColor: 'rgba(255,255,255,0.12)',
              borderWidth: 1,
              borderColor: 'rgba(255,255,255,0.25)',
              paddingVertical: 9,
              paddingHorizontal: 10,
              borderRadius: 8,
              alignItems: 'center',
            }}
            onPress={() => Linking.openURL('https://pruebas.femtribe.com.co/matricula')}
            activeOpacity={0.8}
          >
            <Text style={{ color: '#FFFFFF', fontWeight: '700', fontSize: 11 }}>
              🌐 En el Navegador
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Tarjeta Hero del Curso */}
      <View style={styles.heroCard}>
        <View style={styles.heroTop}>
          <View style={styles.pill}>
            <Text style={styles.pillText}>CURSO OFICIAL</Text>
          </View>
          <Text style={styles.priceTag}>${courseData.price.toLocaleString('es-CO')} COP</Text>
        </View>

        <Text style={styles.courseTitle}>Preparación Académica ICFES Saber 11°</Text>
        <Text style={styles.courseDesc}>
          Acceso a simulacros interactivos, evaluación por materias y retroalimentación pedagógica.
        </Text>

        <View style={styles.progressContainer}>
          <View style={styles.progressHeader}>
            <Text style={styles.progressLabel}>Progreso de Matrícula</Text>
            <Text style={styles.progressVal}>{courseData.progressPercent}%</Text>
          </View>
          <View style={styles.progressBarBg}>
            <View style={[styles.progressBarFill, { width: `${courseData.progressPercent}%` }]} />
          </View>
        </View>

        <TouchableOpacity
          style={styles.heroButton}
          onPress={() => navigation.navigate('Simulacro')}
          activeOpacity={0.9}
        >
          <Text style={styles.heroButtonText}>📝 Presentar Simulacro Diagnóstico</Text>
        </TouchableOpacity>
      </View>

      {/* Grid de KPIs Rápidos */}
      <View style={styles.kpiGrid}>
        <View style={styles.kpiCard}>
          <Text style={styles.kpiLabel}>Simulacros</Text>
          <Text style={styles.kpiValue}>1 Realizado</Text>
          <Text style={styles.kpiSubSuccess}>+2 Disponibles</Text>
        </View>
        <View style={styles.kpiCard}>
          <Text style={styles.kpiLabel}>Mejor Puntaje</Text>
          <Text style={[styles.kpiValue, { color: colors.primary }]}>385 / 500</Text>
          <Text style={styles.kpiSub}>Nivel Alto</Text>
        </View>
      </View>

      <View style={styles.kpiGrid}>
        <TouchableOpacity
          style={styles.kpiCard}
          onPress={() => navigation.navigate('Contrato')}
        >
          <Text style={styles.kpiLabel}>Contrato Digital</Text>
          <Text style={styles.kpiValue}>{contractSigned ? 'Firmado ✅' : 'Pendiente ✍️'}</Text>
          <Text style={styles.kpiSub}>{contractSigned ? 'Términos aceptados' : 'Tocar para firmar'}</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.kpiCard}
          onPress={() => navigation.navigate('Documento')}
        >
          <Text style={styles.kpiLabel}>Documento Cédula</Text>
          <Text style={[styles.kpiValue, { color: colors.warning }]}>{docStatus}</Text>
          <Text style={styles.kpiSub}>Tocar para ver</Text>
        </TouchableOpacity>
      </View>

      {/* Ruta de Preparación */}
      <View style={styles.routeCard}>
        <Text style={styles.routeTitle}>Ruta de Preparación</Text>

        <TouchableOpacity
          style={styles.routeRow}
          onPress={() => navigation.navigate('Contrato')}
        >
          <Text style={styles.routeIcon}>📜</Text>
          <View style={styles.routeInfo}>
            <Text style={styles.routeHeading}>Contrato de Servicio</Text>
            <Text style={styles.routeSub}>Revisa tus términos digitales</Text>
          </View>
          <Text style={styles.routeArrow}>➔</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.routeRow}
          onPress={() => navigation.navigate('Pagos')}
        >
          <Text style={styles.routeIcon}>💳</Text>
          <View style={styles.routeInfo}>
            <Text style={styles.routeHeading}>Plan de Pagos</Text>
            <Text style={styles.routeSub}>Comprobantes y saldo pendiente</Text>
          </View>
          <Text style={styles.routeArrow}>➔</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.routeRow}
          onPress={() => navigation.navigate('Documento')}
        >
          <Text style={styles.routeIcon}>🪪</Text>
          <View style={styles.routeInfo}>
            <Text style={styles.routeHeading}>Documento de Identidad</Text>
            <Text style={styles.routeSub}>Carga tu documento para certificar</Text>
          </View>
          <Text style={styles.routeArrow}>➔</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.bgApp,
  },
  content: {
    padding: 18,
    paddingBottom: 32,
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  greeting: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  greetingSub: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
  },
  statusBadgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  heroCard: {
    backgroundColor: colors.primary,
    borderRadius: 24,
    padding: 20,
    marginBottom: 16,
    shadowColor: colors.primary,
    shadowOpacity: 0.35,
    shadowRadius: 14,
    elevation: 6,
  },
  heroTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  pill: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  pillText: {
    color: colors.textWhite,
    fontSize: 10,
    fontWeight: '700',
  },
  priceTag: {
    color: colors.textWhite,
    fontSize: 16,
    fontWeight: '800',
  },
  courseTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textWhite,
    marginBottom: 6,
  },
  courseDesc: {
    fontSize: 12,
    color: 'rgba(255, 255, 255, 0.85)',
    lineHeight: 17,
    marginBottom: 14,
  },
  progressContainer: {
    backgroundColor: 'rgba(0, 0, 0, 0.18)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 14,
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  progressLabel: {
    color: colors.textWhite,
    fontSize: 11,
  },
  progressVal: {
    color: colors.textWhite,
    fontSize: 11,
    fontWeight: '700',
  },
  progressBarBg: {
    height: 6,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: colors.success,
    borderRadius: 3,
  },
  heroButton: {
    backgroundColor: colors.bgCard,
    borderRadius: 14,
    paddingVertical: 12,
    alignItems: 'center',
  },
  heroButtonText: {
    color: colors.primary,
    fontWeight: '700',
    fontSize: 13,
  },
  kpiGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 12,
  },
  kpiCard: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
  },
  kpiLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textMuted,
    textTransform: 'uppercase',
    marginBottom: 4,
  },
  kpiValue: {
    fontSize: 17,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  kpiSubSuccess: {
    fontSize: 11,
    color: colors.success,
    fontWeight: '600',
    marginTop: 2,
  },
  kpiSub: {
    fontSize: 11,
    color: colors.textMuted,
    marginTop: 2,
  },
  routeCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: colors.border,
    marginTop: 4,
  },
  routeTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  routeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.bgMuted,
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  routeIcon: {
    fontSize: 20,
    marginRight: 12,
  },
  routeInfo: {
    flex: 1,
  },
  routeHeading: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  routeSub: {
    fontSize: 11,
    color: colors.textMuted,
    marginTop: 2,
  },
  routeArrow: {
    fontSize: 14,
    color: colors.textMuted,
  },
  matriculaCard: {
    backgroundColor: '#002244',
    borderRadius: 14,
    padding: 16,
    marginBottom: 14,
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
    elevation: 3,
  },
  matriculaTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  matriculaTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  matriculaSub: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 1,
  },
  matriculaBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  matriculaBadgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
  matriculaPrompt: {
    fontSize: 11,
    color: '#CBD5E1',
    lineHeight: 16,
  },
});
