import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { colors } from '../theme/colors';

export default function ResultsScreen({ route, navigation }) {
  const { score = 385, correctCount = 4, totalQuestions = 5, questions = [], answers = {} } =
    route.params || {};

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Resultados del Simulacro</Text>
      <Text style={styles.subtitle}>
        Calificación oficial estandarizada en escala ICFES (0 a 500 puntos)
      </Text>

      {/* Tarjeta de Puntaje Principal */}
      <View style={styles.scoreCard}>
        <View style={styles.pill}>
          <Text style={styles.pillText}>PUNTAJE GLOBAL ICFES</Text>
        </View>

        <Text style={styles.scoreBig}>{score}</Text>
        <Text style={styles.scoreMax}>de 500 Puntos Posibles</Text>

        <View style={styles.feedbackBox}>
          <Text style={styles.feedbackText}>
            {score >= 350
              ? '🎉 ¡Excelente desempeño! Tienes un percentil competitivo para programas de alta exigencia académica.'
              : '👍 Buen inicio. Continúa reforzando las materias con menor puntaje para subir tu percentil.'}
          </Text>
        </View>

        <View style={styles.correctRatioRow}>
          <Text style={styles.correctRatioText}>
            Respuestas correctas: {correctCount} de {totalQuestions}
          </Text>
        </View>
      </View>

      {/* Desglose por Materia */}
      <View style={styles.breakdownCard}>
        <Text style={styles.cardHeading}>Desglose por Materia</Text>

        <View style={styles.subjectRow}>
          <View style={styles.subjectTop}>
            <Text style={styles.subjectName}>Matemáticas</Text>
            <Text style={[styles.subjectScore, { color: colors.primary }]}>82 / 100</Text>
          </View>
          <View style={styles.barBg}>
            <View style={[styles.barFill, { width: '82%', backgroundColor: colors.primary }]} />
          </View>
        </View>

        <View style={styles.subjectRow}>
          <View style={styles.subjectTop}>
            <Text style={styles.subjectName}>Lectura Crítica</Text>
            <Text style={[styles.subjectScore, { color: colors.secondary }]}>78 / 100</Text>
          </View>
          <View style={styles.barBg}>
            <View style={[styles.barFill, { width: '78%', backgroundColor: colors.secondary }]} />
          </View>
        </View>

        <View style={styles.subjectRow}>
          <View style={styles.subjectTop}>
            <Text style={styles.subjectName}>Ciencias Naturales</Text>
            <Text style={[styles.subjectScore, { color: colors.success }]}>75 / 100</Text>
          </View>
          <View style={styles.barBg}>
            <View style={[styles.barFill, { width: '75%', backgroundColor: colors.success }]} />
          </View>
        </View>

        <View style={styles.subjectRow}>
          <View style={styles.subjectTop}>
            <Text style={styles.subjectName}>Sociales y Ciudadanas</Text>
            <Text style={[styles.subjectScore, { color: colors.warning }]}>74 / 100</Text>
          </View>
          <View style={styles.barBg}>
            <View style={[styles.barFill, { width: '74%', backgroundColor: colors.warning }]} />
          </View>
        </View>

        <View style={styles.subjectRow}>
          <View style={styles.subjectTop}>
            <Text style={styles.subjectName}>Inglés</Text>
            <Text style={[styles.subjectScore, { color: '#8B5CF6' }]}>76 / 100</Text>
          </View>
          <View style={styles.barBg}>
            <View style={[styles.barFill, { width: '76%', backgroundColor: '#8B5CF6' }]} />
          </View>
        </View>
      </View>

      {/* Revisión Pedagógica de Preguntas */}
      {questions.length > 0 && (
        <View style={styles.reviewCard}>
          <Text style={styles.cardHeading}>Revisión y Explicaciones</Text>
          {questions.map((q, idx) => {
            const userChoice = answers[q.id];
            const isRight = userChoice === q.correct;

            return (
              <View key={q.id || idx} style={styles.questionReviewBox}>
                <View style={styles.reviewHeader}>
                  <Text style={styles.reviewNum}>Pregunta {idx + 1}</Text>
                  <Text style={styles.reviewResultBadge}>{isRight ? '✅ Correcta' : '❌ Incorrecta'}</Text>
                </View>
                <Text style={styles.reviewStatement}>{q.statement}</Text>
                <Text style={styles.reviewAnswer}>
                  Tu respuesta: <Text style={{ fontWeight: '800' }}>{userChoice || 'Sin responder'}</Text> | Correcta:{' '}
                  <Text style={{ fontWeight: '800', color: colors.success }}>{q.correct}</Text>
                </Text>
                {q.explanation ? (
                  <View style={styles.explanationBox}>
                    <Text style={styles.explanationTitle}>💡 Explicación pedagógica:</Text>
                    <Text style={styles.explanationText}>{q.explanation}</Text>
                  </View>
                ) : null}
              </View>
            );
          })}
        </View>
      )}

      {/* Botón Volver */}
      <TouchableOpacity
        style={styles.returnButton}
        onPress={() => navigation.navigate('Tabs')}
        activeOpacity={0.85}
      >
        <Text style={styles.returnButtonText}>🔄 Volver al Inicio</Text>
      </TouchableOpacity>
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
  scoreCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 24,
    padding: 22,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  pill: {
    backgroundColor: colors.primarySubtle,
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
    marginBottom: 12,
  },
  pillText: {
    color: colors.primaryDark,
    fontSize: 11,
    fontWeight: '800',
  },
  scoreBig: {
    fontSize: 54,
    fontWeight: '900',
    color: colors.primary,
    letterSpacing: -1,
  },
  scoreMax: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textSecondary,
    marginBottom: 14,
  },
  feedbackBox: {
    backgroundColor: colors.successSubtle,
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#A7F3D0',
    marginBottom: 12,
  },
  feedbackText: {
    fontSize: 12,
    color: colors.successDark,
    lineHeight: 17,
    textAlign: 'center',
    fontWeight: '600',
  },
  correctRatioRow: {
    paddingTop: 8,
  },
  correctRatioText: {
    fontSize: 12,
    color: colors.textMuted,
    fontWeight: '600',
  },
  breakdownCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  cardHeading: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 14,
  },
  subjectRow: {
    marginBottom: 14,
  },
  subjectTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 5,
  },
  subjectName: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  subjectScore: {
    fontSize: 13,
    fontWeight: '800',
  },
  barBg: {
    height: 8,
    backgroundColor: colors.bgMuted,
    borderRadius: 4,
    overflow: 'hidden',
  },
  barFill: {
    height: '100%',
    borderRadius: 4,
  },
  reviewCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  questionReviewBox: {
    backgroundColor: colors.bgMuted,
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
  },
  reviewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  reviewNum: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  reviewResultBadge: {
    fontSize: 11,
    fontWeight: '700',
  },
  reviewStatement: {
    fontSize: 12,
    color: colors.textPrimary,
    lineHeight: 18,
    marginBottom: 8,
  },
  reviewAnswer: {
    fontSize: 11,
    color: colors.textSecondary,
    marginBottom: 6,
  },
  explanationBox: {
    backgroundColor: 'rgba(255, 255, 255, 0.7)',
    borderRadius: 10,
    padding: 10,
    marginTop: 4,
  },
  explanationTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.primaryDark,
    marginBottom: 2,
  },
  explanationText: {
    fontSize: 11,
    color: colors.textSecondary,
    lineHeight: 16,
  },
  returnButton: {
    backgroundColor: colors.primary,
    borderRadius: 16,
    paddingVertical: 15,
    alignItems: 'center',
  },
  returnButtonText: {
    color: colors.textWhite,
    fontSize: 14,
    fontWeight: '700',
  },
});
