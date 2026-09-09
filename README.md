# App Móvil Nativa ICFES Saber 11° (React Native + Expo)

Aplicación móvil nativa construida con **React Native (Expo SDK 57)** diseñada para publicación directa en **Google Play Store** y **Apple App Store**.

---

## 🚀 1. Ejecución en Desarrollo (Emulador o Dispositivo Físico)

Para iniciar la aplicación en tu emulador de Android conectado:

```bash
cd mobile-app
npm run dev
# o también:
npm run android
```

Para ejecutar en iOS (en Mac con simulador de Xcode):
```bash
npm run ios
```

Para probar en tu teléfono físico:
1. Instala **Expo Go** desde Google Play o App Store.
2. Ejecuta `npx expo start` y escanea el código QR con la cámara de tu teléfono.

---

## 📦 2. Cómo compilar y subir a Google Play Store

Google Play Store exige que las aplicaciones se entreguen en formato **Android App Bundle (`.aab`)**.

### Paso 1: Instalar EAS CLI e Iniciar Sesión en Expo
```bash
npm install -g eas-cli
eas login
```

### Paso 2: Vincular el proyecto a tu cuenta Expo
```bash
eas build:configure
```

### Paso 3: Generar el archivo `.aab` para Google Play
```bash
eas build --platform android --profile production
```
*EAS compilará la aplicación en la nube y te entregará el enlace para descargar el archivo `.aab` firmado listo para subir a Google Play Console.*

### Paso 4: Generar un `.apk` de prueba directa (Opcional)
Si quieres instalarlo directamente en cualquier teléfono Android sin pasar por Play Store:
```bash
eas build --platform android --profile preview
```

---

## 🍏 3. Cómo compilar y subir a Apple App Store

Apple exige una cuenta en el **Apple Developer Program** ($99 USD/año).

### Generar la compilación para App Store / TestFlight
```bash
eas build --platform ios --profile production
```
*EAS gestionará automáticamente tus certificados de distribución y perfiles de aprovisionamiento de Apple.*

Para enviar la compilación directamente a TestFlight o App Store Connect:
```bash
eas submit --platform ios
```

---

## ⚙️ 4. Configuración del Proyecto

Todos los metadatos de las tiendas están centralizados en [`app.json`](./app.json):
- **Nombre de la App:** ICFES Saber 11°
- **ID de Paquete Android:** `com.femtribe.icfes`
- **Bundle ID iOS:** `com.femtribe.icfes`
- **Versión:** 1.0.0 (código de versión 1)
- **Permisos incluidos:** Cámara (para fotografiar la cédula) y Galería/Archivos.
- **Backend unificado:** `https://pruebas.femtribe.com.co/api/v1`

---

## 📂 5. Estructura de Pantallas Nativas

- `src/screens/HomeScreen.js`: Dashboard, progreso de matrícula ($700.000 COP), KPIs de simulacros y accesos rápidos.
- `src/screens/ContractScreen.js`: Lectura de cláusulas y firma electrónica del contrato digital.
- `src/screens/PaymentsScreen.js`: Medidor de abono del curso, recibos aprobados e instrucciones de consignación.
- `src/screens/DocumentScreen.js`: Carga nativa de cédula (cámara / galería o PDF) con estado de validación.
- `src/screens/ExamsScreen.js`: Catálogo de simulacros ICFES.
- `src/screens/ExamRunnerScreen.js`: Simulacro en vivo con cronómetro regresivo, preguntas y selector de opciones A/B/C/D.
- `src/screens/ResultsScreen.js`: Calificación ponderada en escala ICFES (0 a 500), desglose por materia y revisión pedagógica.
- `src/screens/ProfileScreen.js`: Datos del estudiante y cierre de sesión seguro.
