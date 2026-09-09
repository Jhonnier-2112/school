import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { API } from '../api/client';
import { colors } from '../theme/colors';

export default function ExamsScreen({ navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [exams, setExams] = useState([]);

  const loadExams = async () => {
    try {
      const res = await API.listExams();
      if (res && res.data && res.data.exams && res.data.exams.length > 0) {
        setExams(res.data.exams);
      } else {
        // Exámenes predeterminados
        setExams([
          {
            id: 'sim-diag-1',
            title: 'Simulacro Diagnóstico Saber 11°',
            type: 'DIAGNÓSTICO OFICIAL',
            duration_minutes: 45,
            description:
              'Mide tus conocimientos generales en Matemáticas, Lectura Crítica, Ciencias y Sociales.',
          },
          {
            id: 'sim-mat-1',
            title: 'Entrenamiento Específico: Razonamiento Cuantitativo',
            type: 'MATEMÁTICAS',
            duration_minutes: 30,
            description: 'Preguntas avanzadas de álgebra, cálculo y estadística tipo ICFES.',
          },
        ]);
      }
    } catch (e) {
      setExams([
        {
          id: 'sim-diag-1',
          title: 'Simulacro Diagnóstico Saber 11°',
          type: 'DIAGNÓSTICO OFICIAL',
          duration_minutes: 45,
          description:
            'Mide tus conocimientos generales en Matemáticas, Lectura Crítica, Ciencias y Sociales.',
        },
      ]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadExams();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadExams();
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
      <Text style={styles.title}>Simulacros Oficiales ICFES</Text>
      <Text style={styles.subtitle}>
        Evaluaciones cronometradas con calificación en escala estándar (0 a 500)
      </Text>

      {exams.map((exam) => (
        <View key={exam.id} style={styles.examCard}>
          <View style={styles.examCardHeader}>
            <View style={styles.typeBadge}>
              <Text style={styles.typeBadgeText}>{exam.type || 'SIMULACRO'}</Text>
            </View>
            <Text style={styles.timerBadge}>⏱️ {exam.duration_minutes || 45} Minutos</Text>
          </View>

          <Text style={styles.examTitle}>{exam.title}</Text>
          <Text style={styles.examDesc}>{exam.description}</Text>

          <TouchableOpacity
            style={styles.startButton}
            onPress={() => navigation.navigate('ExamRunner', { examId: exam.id, examTitle: exam.title })}
            activeOpacity={0.85}
          >
            <Text style={styles.startButtonText}>🚀 Iniciar Simulacro Ahora</Text>
          </TouchableOpacity>
        </View>
      ))}
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
  examCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  examCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  typeBadge: {
    backgroundColor: colors.primarySubtle,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  typeBadgeText: {
    color: colors.primaryDark,
    fontSize: 10,
    fontWeight: '800',
  },
  timerBadge: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textMuted,
  },
  examTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  examDesc: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 17,
    marginBottom: 16,
  },
  startButton: {
    backgroundColor: colors.primary,
    borderRadius: 14,
    paddingVertical: 13,
    alignItems: 'center',
  },
  startButtonText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
});
