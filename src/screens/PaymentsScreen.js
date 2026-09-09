import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { API } from '../api/client';
import { colors } from '../theme/colors';

export default function PaymentsScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [summary, setSummary] = useState({
    total: 0,
    paid: 0,
    pending: 0,
    percent: 0,
  });
  const [payments, setPayments] = useState([]);

  const loadData = async () => {
    try {
      const [sumRes, payRes] = await Promise.allSettled([
        API.getCourseSummary(),
        API.getPayments(),
      ]);

      if (sumRes.status === 'fulfilled' && sumRes.value?.data) {
        const d = sumRes.value.data;
        const s = d.summary || d;
        const total = Number(s.course_price || d.total_price || 0);
        const paid = Number(s.total_paid !== undefined ? s.total_paid : (d.total_paid || 0));
        const pending = Number(s.remaining_amount !== undefined ? s.remaining_amount : Math.max(0, total - paid));
        const percent = Number(s.percentage_paid !== undefined ? Math.round(s.percentage_paid) : (total > 0 ? Math.min(100, Math.round((paid / total) * 100)) : 0));
        setSummary({
          total,
          paid,
          pending,
          percent,
        });
      }

      if (payRes.status === 'fulfilled' && payRes.value?.data) {
        const list = Array.isArray(payRes.value.data) ? payRes.value.data : (payRes.value.data.payments || []);
        setPayments(list);
      }
    } catch (e) {
      console.warn('Error cargando pagos:', e);
    } finally {
      setLoading(false);
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

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <Text style={styles.title}>Estado de Cuenta & Pagos</Text>
      <Text style={styles.subtitle}>
        {summary.total > 0
          ? `Valor oficial del curso de preparación: $${summary.total.toLocaleString('es-CO')} COP`
          : 'Consulta y seguimiento de pagos de matrícula'}
      </Text>

      {/* Tarjeta de Resumen Financiero */}
      <View style={styles.financialCard}>
        <View style={styles.donutPlaceholder}>
          <Text style={styles.donutPercent}>{summary.percent}%</Text>
          <Text style={styles.donutLabel}>ABONADO</Text>
        </View>

        <View style={styles.balanceGrid}>
          <View style={styles.balanceCol}>
            <Text style={styles.balanceLabel}>TOTAL ABONADO</Text>
            <Text style={[styles.balanceVal, { color: colors.success }]}>
              +${summary.paid.toLocaleString('es-CO')}
            </Text>
          </View>
          <View style={styles.balanceCol}>
            <Text style={styles.balanceLabel}>SALDO PENDIENTE</Text>
            <Text style={[styles.balanceVal, { color: colors.danger }]}>
              ${summary.pending.toLocaleString('es-CO')}
            </Text>
          </View>
        </View>
      </View>

      {/* Historial de Recibos */}
      <View style={styles.historyCard}>
        <Text style={styles.historyTitle}>Historial de Recibos y Abonos</Text>

        {payments.length === 0 ? (
          <View style={[styles.paymentRow, { justifyContent: 'center', paddingVertical: 20 }]}>
            <Text style={{ color: colors.textMuted, fontSize: 13, textAlign: 'center' }}>
              ℹ️ Aún no registras abonos realizados en la plataforma.
            </Text>
          </View>
        ) : (
          payments.map((p, idx) => (
            <View key={p.id || idx} style={styles.paymentRow}>
              <View style={styles.paymentInfo}>
                <Text style={styles.paymentConcept}>{p.notes || 'Abono de Matrícula'}</Text>
                <Text style={styles.paymentMeta}>
                  {p.payment_method || 'Transferencia'} •{' '}
                  {new Date(p.payment_date || p.created_at).toLocaleDateString('es-CO')}
                </Text>
              </View>
              <View style={styles.paymentRight}>
                <Text style={styles.paymentAmount}>
                  +${Number(p.amount).toLocaleString('es-CO')}
                </Text>
                <View style={styles.appliedBadge}>
                  <Text style={styles.appliedBadgeText}>Aprobado</Text>
                </View>
              </View>
            </View>
          ))
        )}
      </View>

      {/* Información de Pago */}
      <View style={styles.bankCard}>
        <Text style={styles.bankIcon}>🏦</Text>
        <View style={styles.bankInfo}>
          <Text style={styles.bankTitle}>¿Cómo registrar un nuevo abono?</Text>
          <Text style={styles.bankText}>
            Realiza tu transferencia a la cuenta institucional Bancolombia / Nequi o presenta tu
            comprobante en secretaría académica para su acreditación inmediata.
          </Text>
        </View>
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
    paddingBottom: 36,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.bgApp,
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  subtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 4,
    marginBottom: 16,
  },
  financialCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 24,
    padding: 22,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  donutPlaceholder: {
    width: 110,
    height: 110,
    borderRadius: 55,
    borderWidth: 8,
    borderColor: colors.success,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 18,
  },
  donutPercent: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  donutLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
  },
  balanceGrid: {
    flexDirection: 'row',
    width: '100%',
    gap: 12,
  },
  balanceCol: {
    flex: 1,
    backgroundColor: colors.bgMuted,
    borderRadius: 14,
    padding: 12,
    alignItems: 'center',
  },
  balanceLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
    marginBottom: 4,
  },
  balanceVal: {
    fontSize: 15,
    fontWeight: '800',
  },
  historyCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  historyTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  paymentRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.bgMuted,
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  paymentInfo: {
    flex: 1,
  },
  paymentConcept: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  paymentMeta: {
    fontSize: 11,
    color: colors.textMuted,
    marginTop: 2,
  },
  paymentRight: {
    alignItems: 'flex-end',
  },
  paymentAmount: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.success,
    marginBottom: 3,
  },
  appliedBadge: {
    backgroundColor: colors.successSubtle,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
  },
  appliedBadgeText: {
    color: colors.successDark,
    fontSize: 10,
    fontWeight: '700',
  },
  bankCard: {
    backgroundColor: colors.primarySubtle,
    borderRadius: 18,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'flex-start',
    borderWidth: 1,
    borderColor: colors.borderFocus,
  },
  bankIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  bankInfo: {
    flex: 1,
  },
  bankTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.primaryDark,
    marginBottom: 4,
  },
  bankText: {
    fontSize: 11,
    color: colors.primaryDark,
    lineHeight: 16,
  },
});
