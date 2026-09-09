import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { API } from '../api/client';
import { colors } from '../theme/colors';

export default function ContractScreen() {
  const [loading, setLoading] = useState(true);
  const [contract, setContract] = useState(null);
  const [isSigned, setIsSigned] = useState(false);
  const [signedAt, setSignedAt] = useState(null);
  const [agreed, setAgreed] = useState(false);
  const [signing, setSigning] = useState(false);

  useEffect(() => {
    loadContract();
  }, []);

  const loadContract = async () => {
    setLoading(true);
    try {
      const res = await API.getContract();
      if (res && res.data) {
        setContract(res.data.contract);
        setIsSigned(!!res.data.is_signed);
        setSignedAt(res.data.signed_at);
      }
    } catch (e) {
      // Fallback
      setIsSigned(false);
    } finally {
      setLoading(false);
    }
  };

  const handleSign = async () => {
    if (!agreed) {
      Alert.alert('Atención', 'Debes marcar la casilla aceptando los términos y condiciones.');
      return;
    }

    setSigning(true);
    try {
      const contractId = contract?.id || 'default-contract';
      await API.acceptContract(contractId);
      setIsSigned(true);
      setSignedAt(new Date().toISOString());
      Alert.alert('¡Éxito!', 'Tu contrato ha sido firmado digitalmente. Tu matrícula está activa.');
    } catch (err) {
      Alert.alert('Error', err.message || 'No se pudo firmar el contrato.');
    } finally {
      setSigning(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Contrato de Servicio Educativo</Text>
      <Text style={styles.subtitle}>
        Aceptación oficial de términos para habilitar tu preparación académica
      </Text>

      {/* Contenedor con Cláusulas del Contrato */}
      <View style={styles.contractBox}>
        <ScrollView style={styles.contractScroll} nestedScrollEnabled>
          <Text style={styles.contractHeading}>
            CONTRATO DE PRESTACIÓN DE SERVICIOS EDUCATIVOS - PREPARACIÓN ICFES SABER 11°
          </Text>
          <Text style={styles.contractParagraph}>
            Entre la institución educativa y el estudiante registrado en la plataforma, se conviene
            celebrar el presente acuerdo bajo las siguientes condiciones legales:
          </Text>
          <Text style={styles.clauseTitle}>PRIMERA. OBJETO:</Text>
          <Text style={styles.contractParagraph}>
            El presente servicio tiene como finalidad la preparación integral para la prueba de
            estado ICFES Saber 11°, mediante simulacros diagnósticos, módulos por materia y
            análisis de desempeño continuo.
          </Text>
          <Text style={styles.clauseTitle}>SEGUNDA. VALOR Y FORMA DE PAGO:</Text>
          <Text style={styles.contractParagraph}>
            El valor convenido para el curso de preparación académica es de $700.000 COP, los
            cuales se respaldan mediante recibos de abono expedidos por administración.
          </Text>
          <Text style={styles.clauseTitle}>TERCERA. OBLIGACIONES DEL ESTUDIANTE:</Text>
          <Text style={styles.contractParagraph}>
            Asistir puntualmente a los simulacros programados, realizar las evaluaciones y cargar su
            documento de identidad legal en la plataforma para efectos de certificación.
          </Text>
          <Text style={styles.clauseTitle}>CUARTA. FIRMA ELECTRÓNICA:</Text>
          <Text style={styles.contractParagraph}>
            Conforme a la Ley 527 de 1999 sobre mensajes de datos y comercio electrónico, la
            aceptación mediante este formulario constituye consentimiento y firma digital válida.
          </Text>
        </ScrollView>
      </View>

      {/* Estado de Firma */}
      {isSigned ? (
        <View style={styles.signedCard}>
          <Text style={styles.signedIcon}>✅</Text>
          <Text style={styles.signedTitle}>Contrato Firmado Digitalmente</Text>
          <Text style={styles.signedMeta}>
            Firmado por el estudiante el{' '}
            {signedAt ? new Date(signedAt).toLocaleDateString('es-CO') : 'recientemente'}. Matrícula
            activa.
          </Text>
        </View>
      ) : (
        <View style={styles.unsignedCard}>
          <TouchableOpacity
            style={styles.checkboxRow}
            onPress={() => setAgreed(!agreed)}
            activeOpacity={0.8}
          >
            <View style={[styles.checkbox, agreed && styles.checkboxChecked]}>
              {agreed && <Text style={styles.checkmark}>✓</Text>}
            </View>
            <Text style={styles.checkboxLabel}>
              He leído y acepto expresamente todos los términos y condiciones del contrato
              educativo.
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.signButton, !agreed && styles.signButtonDisabled]}
            onPress={handleSign}
            disabled={!agreed || signing}
          >
            {signing ? (
              <ActivityIndicator color={colors.textWhite} />
            ) : (
              <Text style={styles.signButtonText}>✍️ Firmar y Aceptar Contrato Digital</Text>
            )}
          </TouchableOpacity>
        </View>
      )}
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
  contractBox: {
    backgroundColor: colors.bgCard,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginBottom: 16,
  },
  contractScroll: {
    maxHeight: 280,
  },
  contractHeading: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 10,
    textAlign: 'center',
  },
  contractParagraph: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 18,
    marginBottom: 10,
  },
  clauseTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.primary,
    marginTop: 6,
    marginBottom: 2,
  },
  signedCard: {
    backgroundColor: colors.successSubtle,
    borderWidth: 1,
    borderColor: '#A7F3D0',
    borderRadius: 18,
    padding: 20,
    alignItems: 'center',
  },
  signedIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  signedTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.successDark,
    marginBottom: 4,
  },
  signedMeta: {
    fontSize: 12,
    color: '#065F46',
    textAlign: 'center',
  },
  unsignedCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 18,
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 16,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.primary,
    marginRight: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  checkboxChecked: {
    backgroundColor: colors.primary,
  },
  checkmark: {
    color: colors.textWhite,
    fontSize: 14,
    fontWeight: '800',
  },
  checkboxLabel: {
    flex: 1,
    fontSize: 12,
    fontWeight: '600',
    color: colors.textPrimary,
    lineHeight: 18,
  },
  signButton: {
    backgroundColor: colors.primary,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  signButtonDisabled: {
    opacity: 0.5,
  },
  signButtonText: {
    color: colors.textWhite,
    fontSize: 14,
    fontWeight: '700',
  },
});
