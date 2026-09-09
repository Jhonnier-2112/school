import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Image,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function AuthScreen() {
  const [isLogin, setIsLogin] = useState(true);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);

  const { login, register, quickDemoLogin } = useAuth();

  const handleSubmit = async () => {
    if (!email || !password) {
      Alert.alert('Campos requeridos', 'Por favor completa correo y contraseña.');
      return;
    }

    setLoading(true);
    try {
      if (isLogin) {
        await login(email, password);
      } else {
        if (!name) {
          Alert.alert('Nombre requerido', 'Por favor ingresa tu nombre completo.');
          setLoading(false);
          return;
        }
        await register({
          full_name: name,
          email,
          phone,
          password,
        });
        Alert.alert('Registro exitoso', '¡Tu cuenta ha sido creada!');
      }
    } catch (err) {
      Alert.alert('Error', err.message || 'No se pudo completar la operación.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Logo & Encabezado Institucional */}
        <View style={styles.header}>
          <Image
            source={require('../../assets/logo_jean_piaget.png')}
            style={{ width: 110, height: 95, alignSelf: 'center', marginBottom: 12 }}
            resizeMode="contain"
          />
          <Text style={styles.title}>Institución Educativa Jean Piaget School</Text>
          <Text style={styles.subtitle}>
            {isLogin
              ? 'Sistema Completo de Matrícula Digital & Preparación Académica 2026'
              : 'Regístrate para comenzar tu proceso oficial de matrícula'}
          </Text>
        </View>

        {/* Tarjeta de Formulario */}
        <View style={styles.card}>
          {/* Switch Login / Registro */}
          <View style={styles.tabSwitch}>
            <TouchableOpacity
              style={[styles.tabButton, isLogin && styles.tabButtonActive]}
              onPress={() => setIsLogin(true)}
            >
              <Text style={[styles.tabText, isLogin && styles.tabTextActive]}>Iniciar Sesión</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.tabButton, !isLogin && styles.tabButtonActive]}
              onPress={() => setIsLogin(false)}
            >
              <Text style={[styles.tabText, !isLogin && styles.tabTextActive]}>Crear Cuenta</Text>
            </TouchableOpacity>
          </View>

          {!isLogin && (
            <>
              <Text style={styles.label}>Nombres Completos</Text>
              <TextInput
                style={styles.input}
                placeholder="Ej: Santiago Mendoza"
                placeholderTextColor={colors.textMuted}
                value={name}
                onChangeText={setName}
              />

              <Text style={styles.label}>Teléfono / Celular</Text>
              <TextInput
                style={styles.input}
                placeholder="300 000 0000"
                placeholderTextColor={colors.textMuted}
                keyboardType="phone-pad"
                value={phone}
                onChangeText={setPhone}
              />
            </>
          )}

          <Text style={styles.label}>Correo Electrónico</Text>
          <TextInput
            style={styles.input}
            placeholder="estudiante@ejemplo.com"
            placeholderTextColor={colors.textMuted}
            keyboardType="email-address"
            autoCapitalize="none"
            value={email}
            onChangeText={setEmail}
          />

          <Text style={styles.label}>Contraseña</Text>
          <TextInput
            style={styles.input}
            placeholder="••••••••"
            placeholderTextColor={colors.textMuted}
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />

          <TouchableOpacity style={styles.primaryButton} onPress={handleSubmit} disabled={loading}>
            {loading ? (
              <ActivityIndicator color={colors.textWhite} />
            ) : (
              <Text style={styles.primaryButtonText}>
                {isLogin ? 'Entrar a la Plataforma' : 'Crear mi Cuenta'}
              </Text>
            )}
          </TouchableOpacity>

          <View style={styles.divider}>
            <View style={styles.dividerLine} />
            <Text style={styles.dividerText}>O ACCESO RÁPIDO</Text>
            <View style={styles.dividerLine} />
          </View>

          <TouchableOpacity
            style={styles.demoButton}
            onPress={quickDemoLogin}
            activeOpacity={0.8}
          >
            <Text style={styles.demoButtonText}>⚡ Modo Exploración (Demo)</Text>
          </TouchableOpacity>

          {/* Versión de la App */}
          <View style={{ marginTop: 24, alignItems: 'center' }}>
            <Text style={{ fontSize: 11, color: colors.textMuted, fontWeight: '600', letterSpacing: 0.5 }}>
              Versión 1.0.0 (Build 2026)
            </Text>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.bgDark,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  header: {
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    width: '100%',
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textWhite,
    marginBottom: 6,
    textAlign: 'center',
    alignSelf: 'center',
    lineHeight: 26,
    width: '100%',
  },
  subtitle: {
    fontSize: 13,
    color: colors.textMuted,
    textAlign: 'center',
    alignSelf: 'center',
    paddingHorizontal: 16,
  },
  card: {
    backgroundColor: colors.bgCard,
    borderRadius: 24,
    padding: 22,
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 4,
  },
  tabSwitch: {
    flexDirection: 'row',
    backgroundColor: colors.bgMuted,
    borderRadius: 12,
    padding: 4,
    marginBottom: 18,
  },
  tabButton: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 10,
  },
  tabButtonActive: {
    backgroundColor: colors.bgCard,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
  },
  tabText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textMuted,
  },
  tabTextActive: {
    color: colors.primary,
  },
  label: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
    marginBottom: 6,
  },
  input: {
    backgroundColor: colors.bgApp,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: colors.textPrimary,
    marginBottom: 14,
  },
  primaryButton: {
    backgroundColor: colors.primary,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 6,
  },
  primaryButtonText: {
    color: colors.textWhite,
    fontSize: 15,
    fontWeight: '700',
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 18,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: colors.border,
  },
  dividerText: {
    paddingHorizontal: 10,
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
  },
  demoButton: {
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: 14,
    paddingVertical: 12,
    alignItems: 'center',
    backgroundColor: colors.bgMuted,
  },
  demoButtonText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textPrimary,
  },
});
