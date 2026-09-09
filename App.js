import React from 'react';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';

import { AuthProvider, useAuth } from './src/context/AuthContext';
import { colors } from './src/theme/colors';

// Pantallas
import AuthScreen from './src/screens/AuthScreen';
import HomeScreen from './src/screens/HomeScreen';
import ContractScreen from './src/screens/ContractScreen';
import PaymentsScreen from './src/screens/PaymentsScreen';
import DocumentScreen from './src/screens/DocumentScreen';
import ExamsScreen from './src/screens/ExamsScreen';
import ExamRunnerScreen from './src/screens/ExamRunnerScreen';
import ResultsScreen from './src/screens/ResultsScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import MatriculaScreen from './src/screens/MatriculaScreen';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

function BottomTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: true,
        headerStyle: {
          backgroundColor: colors.bgCard,
          elevation: 0,
          shadowOpacity: 0,
          borderBottomWidth: 1,
          borderBottomColor: colors.border,
        },
        headerTitleStyle: {
          fontWeight: '800',
          fontSize: 16,
          color: colors.textPrimary,
        },
        tabBarStyle: {
          backgroundColor: colors.bgCard,
          borderTopColor: colors.border,
          height: 62,
          paddingBottom: 8,
          paddingTop: 6,
        },
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: {
          fontSize: 10,
          fontWeight: '700',
        },
        tabBarIcon: ({ color, size }) => {
          let icon = '🏠';
          if (route.name === 'Inicio') icon = '🏠';
          else if (route.name === 'Matricula') icon = '🎓';
          else if (route.name === 'Contrato') icon = '📜';
          else if (route.name === 'Pagos') icon = '💳';
          else if (route.name === 'Simulacro') icon = '📝';
          else if (route.name === 'Perfil') icon = '👤';

          return <Text style={{ fontSize: 18 }}>{icon}</Text>;
        },
      })}
    >
      <Tab.Screen name="Inicio" component={HomeScreen} options={{ title: 'Inicio' }} />
      <Tab.Screen name="Matricula" component={MatriculaScreen} options={{ title: 'Matrícula 2026' }} />
      <Tab.Screen name="Simulacro" component={ExamsScreen} options={{ title: 'Simulacros' }} />
      <Tab.Screen name="Pagos" component={PaymentsScreen} options={{ title: 'Pagos' }} />
      <Tab.Screen name="Contrato" component={ContractScreen} options={{ title: 'Contrato' }} />
      <Tab.Screen name="Perfil" component={ProfileScreen} options={{ title: 'Perfil' }} />
    </Tab.Navigator>
  );
}

function MainNavigator() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {!user ? (
        <Stack.Screen name="Auth" component={AuthScreen} />
      ) : (
        <>
          <Stack.Screen name="Tabs" component={BottomTabs} />
          <Stack.Screen
            name="ExamRunner"
            component={ExamRunnerScreen}
            options={{ presentation: 'fullScreenModal' }}
          />
          <Stack.Screen
            name="Results"
            component={ResultsScreen}
            options={{ presentation: 'card' }}
          />
        </>
      )}
    </Stack.Navigator>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <NavigationContainer>
        <StatusBar style="auto" />
        <MainNavigator />
      </NavigationContainer>
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    backgroundColor: colors.bgApp,
    justifyContent: 'center',
    alignItems: 'center',
  },
});
