import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  SafeAreaView,
  TextInput,
  Image,
} from 'react-native';
import { colors } from '../theme/colors';
import { API } from '../api/client';

const DEFAULT_QUESTIONS = [
  {
    id: 'q1',
    subject: 'Matemáticas',
    statement:
      'Un tanque de agua de 1.200 litros se llena con dos llaves. La primera vierte 30 litros por minuto y la segunda 20 litros por minuto. Si ambas llaves se abren al mismo tiempo, ¿cuántos minutos tardará en llenarse por completo el tanque?',
    options: [
      { letter: 'A', text: '20 minutos' },
      { letter: 'B', text: '24 minutos' },
      { letter: 'C', text: '30 minutos' },
      { letter: 'D', text: '40 minutos' },
    ],
    correct: 'B',
    explanation: 'Juntas vierten 30 + 20 = 50 L/min. Tiempo = 1.200 / 50 = 24 minutos.',
  },
  {
    id: 'q2',
    subject: 'Lectura Crítica',
    statement: 'En un ensayo argumentativo, la tesis del autor representa:',
    options: [
      { letter: 'A', text: 'Un resumen descriptivo de los hechos históricos citados.' },
      { letter: 'B', text: 'La postura o afirmación central que se busca defender mediante argumentos.' },
      { letter: 'C', text: 'Una lista exhaustiva de contraejemplos sin conclusión.' },
      { letter: 'D', text: 'Una cita textual obligatoria del diccionario.' },
    ],
    correct: 'B',
    explanation: 'La tesis es la postura o afirmación nuclear defendida por el autor a lo largo del texto.',
  },
  {
    id: 'q3',
    subject: 'Ciencias Naturales',
    statement:
      'Durante la fotosíntesis, las plantas absorben dióxido de carbono y agua para transformarlos principalmente en:',
    options: [
      { letter: 'A', text: 'Glucosa y oxígeno gaseoso.' },
      { letter: 'B', text: 'Ácido sulfúrico y nitrógeno.' },
      { letter: 'C', text: 'Monóxido de carbono e hidrógeno.' },
      { letter: 'D', text: 'Metano y sales minerales.' },
    ],
    correct: 'A',
    explanation: 'La fotosíntesis produce glucosa (alimento de la planta) y libera oxígeno a la atmósfera.',
  },
  {
    id: 'q4',
    subject: 'Sociales y Ciudadanas',
    statement:
      'De acuerdo con la Constitución Política de Colombia de 1991, el mecanismo principal para la protección inmediata de los derechos fundamentales vulnerados es:',
    options: [
      { letter: 'A', text: 'La Acción de Cumplimiento.' },
      { letter: 'B', text: 'La Acción de Tutela.' },
      { letter: 'C', text: 'El Plebiscito.' },
      { letter: 'D', text: 'La Consulta Popular.' },
    ],
    correct: 'B',
    explanation: 'El Artículo 86 de la Constitución consagra la Acción de Tutela para salvaguardar derechos fundamentales.',
  },
  {
    id: 'q5',
    subject: 'Inglés',
    statement: 'Choose the correct option: "If she _______ hard, she will pass her ICFES exam."',
    options: [
      { letter: 'A', text: 'studies' },
      { letter: 'B', text: 'studied' },
      { letter: 'C', text: 'study' },
      { letter: 'D', text: 'will study' },
    ],
    correct: 'A',
    explanation: 'El primer condicional en inglés usa presente simple ("studies") en la cláusula del condicional.',
  },
];

export default function ExamRunnerScreen({ route, navigation }) {
  const { examId, examTitle } = route.params || {};
  const [questions, setQuestions] = useState(DEFAULT_QUESTIONS);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [answers, setAnswers] = useState({});
  const [sessionId, setSessionId] = useState(null);
  const [timeLeft, setTimeLeft] = useState(45 * 60);

  useEffect(() => {
    initExam();
    const interval = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(interval);
          finishExam();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, []);

  const initExam = async () => {
    try {
      const res = await API.startExam(examId || 'default');
      if (res && res.data && res.data.questions && res.data.questions.length > 0) {
        const sId = res.data.session?.id || res.data.student_exam_id || null;
        if (sId) setSessionId(sId);

        if (res.data.exam?.duration_minutes) {
          setTimeLeft(res.data.exam.duration_minutes * 60);
        }

        const formatted = res.data.questions.map((q) => {
          const type = q.question_type || 'multiple_choice';
          let options = [];
          if (type === 'true_false') {
            options = [
              { letter: 'A', text: q.option_a || 'Verdadero' },
              { letter: 'B', text: q.option_b || 'Falso' },
            ];
          } else if (type === 'open_text') {
            options = [];
          } else {
            options = [
              { letter: 'A', text: q.option_a || 'Opción A' },
              { letter: 'B', text: q.option_b || 'Opción B' },
              { letter: 'C', text: q.option_c || 'Opción C' },
              { letter: 'D', text: q.option_d || 'Opción D' },
            ];
          }

          return {
            id: q.id,
            subject: q.subject_name || q.subject || 'Saber 11°',
            statement: q.statement || q.question_text,
            type,
            imageUrl: q.image_url || null,
            scoreType: q.score_type || 'points',
            scoreWeight: q.score_weight || 1,
            options,
            correct: q.correct_option || '',
            explanation: q.explanation || '',
          };
        });
        setQuestions(formatted);
      }
    } catch (e) {
      // Usa DEFAULT_QUESTIONS
    }
  };

  const handleSelectOption = (value) => {
    const q = questions[currentIndex];
    setAnswers((prev) => ({
      ...prev,
      [q.id]: value,
    }));
  };

  const finishExam = async () => {
    let backendResult = null;
    if (sessionId) {
      try {
        backendResult = await API.submitExam(sessionId, answers).catch(() => null);
      } catch (e) {}
    }

    let correctCount = 0;
    questions.forEach((q) => {
      if (answers[q.id] && answers[q.id] === q.correct) {
        correctCount++;
      }
    });

    const factor = questions.length > 0 ? correctCount / questions.length : 0.8;
    const finalScore = backendResult?.data?.total_score != null
      ? Math.round((backendResult.data.total_score / (backendResult.data.max_score || 1)) * 500)
      : Math.round(factor * 500);

    navigation.replace('Results', {
      score: finalScore,
      totalQuestions: questions.length,
      correctCount: backendResult?.data?.total_correct ?? correctCount,
      questions,
      answers,
    });
  };

  const handleExit = () => {
    Alert.alert(
      '¿Abandonar simulacro?',
      'Si sales ahora no se guardarán tus respuestas.',
      [
        { text: 'Continuar prueba', style: 'cancel' },
        { text: 'Salir', style: 'destructive', onPress: () => navigation.goBack() },
      ]
    );
  };

  const q = questions[currentIndex];
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  const isLast = currentIndex === questions.length - 1;

  return (
    <SafeAreaView style={styles.container}>
      {/* Barra Superior con Cronómetro */}
      <View style={styles.topBar}>
        <TouchableOpacity onPress={handleExit} style={styles.closeBtn}>
          <Text style={styles.closeText}>✕</Text>
        </TouchableOpacity>
        <View style={styles.timerBadge}>
          <Text style={styles.timerIcon}>⏱️</Text>
          <Text style={styles.timerText}>
            {minutes.toString().padStart(2, '0')}:{seconds.toString().padStart(2, '0')}
          </Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        {/* Materia, Puntaje y Contador */}
        <View style={styles.metaRow}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
            <View style={styles.subjectPill}>
              <Text style={styles.subjectPillText}>{q.subject}</Text>
            </View>
            <View style={styles.scorePill}>
              <Text style={styles.scorePillText}>
                {q.scoreWeight} {q.scoreType === 'percentage' ? '%' : 'Pts'}
              </Text>
            </View>
          </View>
          <Text style={styles.counterText}>
            PREGUNTA {currentIndex + 1} DE {questions.length}
          </Text>
        </View>

        {/* Imagen Ilustrativa */}
        {q.imageUrl ? (
          <View style={styles.imageWrapper}>
            <Image source={{ uri: q.imageUrl }} style={styles.questionImage} resizeMode="contain" />
          </View>
        ) : null}

        {/* Enunciado */}
        <View style={styles.statementCard}>
          <Text style={styles.statementText}>{q.statement}</Text>
        </View>

        {/* Respuesta: Abierta o Selección */}
        {q.type === 'open_text' ? (
          <View style={styles.openTextCard}>
            <Text style={styles.openTextLabel}>Escribe tu respuesta a continuación:</Text>
            <TextInput
              style={styles.openTextInput}
              placeholder="Ingresa tu respuesta..."
              placeholderTextColor="#64748B"
              value={answers[q.id] || ''}
              onChangeText={handleSelectOption}
              multiline
            />
          </View>
        ) : (
          <View style={styles.optionsContainer}>
            {q.options.map((opt) => {
              const isSelected = answers[q.id] === opt.letter;
              return (
                <TouchableOpacity
                  key={opt.letter}
                  style={[styles.optionButton, isSelected && styles.optionButtonSelected]}
                  onPress={() => handleSelectOption(opt.letter)}
                  activeOpacity={0.8}
                >
                  <View style={[styles.letterCircle, isSelected && styles.letterCircleSelected]}>
                    <Text style={[styles.letterText, isSelected && styles.letterTextSelected]}>
                      {opt.letter}
                    </Text>
                  </View>
                  <Text style={[styles.optionText, isSelected && styles.optionTextSelected]}>
                    {opt.text}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>
        )}
      </ScrollView>

      {/* Botones de Navegación Inferior */}
      <View style={styles.footer}>
        <TouchableOpacity
          style={[styles.navBtn, currentIndex === 0 && styles.navBtnDisabled]}
          onPress={() => setCurrentIndex((prev) => Math.max(0, prev - 1))}
          disabled={currentIndex === 0}
        >
          <Text style={styles.navBtnText}>◀ Anterior</Text>
        </TouchableOpacity>

        {isLast ? (
          <TouchableOpacity style={[styles.navBtn, styles.submitBtn]} onPress={finishExam}>
            <Text style={styles.submitBtnText}>Entregar ✔</Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity
            style={[styles.navBtn, styles.nextBtn]}
            onPress={() => setCurrentIndex((prev) => Math.min(questions.length - 1, prev + 1))}
          >
            <Text style={styles.nextBtnText}>Siguiente ▶</Text>
          </TouchableOpacity>
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.1)',
  },
  closeBtn: {
    padding: 6,
  },
  closeText: {
    color: colors.textWhite,
    fontSize: 20,
    fontWeight: '700',
  },
  timerBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239, 68, 68, 0.2)',
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
  },
  timerIcon: {
    marginRight: 6,
  },
  timerText: {
    color: '#FCA5A5',
    fontWeight: '800',
    fontSize: 14,
    fontVariant: ['tabular-nums'],
  },
  content: {
    padding: 20,
    paddingBottom: 24,
  },
  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  subjectPill: {
    backgroundColor: 'rgba(79, 70, 229, 0.3)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  subjectPillText: {
    color: '#C7D2FE',
    fontSize: 11,
    fontWeight: '800',
  },
  counterText: {
    color: colors.textMuted,
    fontSize: 11,
    fontWeight: '700',
  },
  statementCard: {
    backgroundColor: '#1E293B',
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    marginBottom: 18,
  },
  statementText: {
    color: colors.textWhite,
    fontSize: 14,
    lineHeight: 22,
    fontWeight: '500',
  },
  optionsContainer: {
    gap: 10,
  },
  optionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.04)',
    borderWidth: 1.5,
    borderColor: 'rgba(255, 255, 255, 0.12)',
    borderRadius: 16,
    padding: 14,
  },
  optionButtonSelected: {
    backgroundColor: 'rgba(79, 70, 229, 0.25)',
    borderColor: colors.primaryLight,
  },
  letterCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  letterCircleSelected: {
    backgroundColor: colors.primary,
  },
  letterText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '800',
  },
  letterTextSelected: {
    color: colors.textWhite,
  },
  optionText: {
    flex: 1,
    color: 'rgba(255, 255, 255, 0.9)',
    fontSize: 13,
    lineHeight: 18,
  },
  optionTextSelected: {
    color: colors.textWhite,
    fontWeight: '600',
  },
  footer: {
    flexDirection: 'row',
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255, 255, 255, 0.1)',
    gap: 12,
  },
  navBtn: {
    flex: 1,
    backgroundColor: '#1E293B',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
  },
  navBtnDisabled: {
    opacity: 0.35,
  },
  navBtnText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
  nextBtn: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  nextBtnText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
  submitBtn: {
    backgroundColor: colors.success,
    borderColor: colors.success,
  },
  submitBtnText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
  scorePill: {
    backgroundColor: 'rgba(16, 185, 129, 0.2)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.35)',
  },
  scorePillText: {
    color: '#6EE7B7',
    fontSize: 11,
    fontWeight: '800',
  },
  imageWrapper: {
    backgroundColor: '#0F172A',
    borderRadius: 16,
    padding: 8,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  questionImage: {
    width: '100%',
    height: 180,
    borderRadius: 12,
  },
  openTextCard: {
    backgroundColor: '#1E293B',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.12)',
  },
  openTextLabel: {
    color: '#94A3B8',
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 10,
  },
  openTextInput: {
    backgroundColor: 'rgba(0, 0, 0, 0.35)',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
    padding: 14,
    color: colors.textWhite,
    fontSize: 14,
    minHeight: 100,
    textAlignVertical: 'top',
  },
});
