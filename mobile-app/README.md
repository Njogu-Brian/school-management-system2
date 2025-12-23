# School ERP Mobile App

A comprehensive React Native mobile application for school management system covering students, academics, attendance, finance, HR, transport, inventory, POS, library, hostel, communication, and document management.

## Features

- **Role-Based Access Control** - Different interfaces for Admin, Teacher, Parent, Student, Finance, and Driver
- **Authentication** - Login with email/password, password reset via email or OTP
- **Dark Mode** - Full light/dark theme support
- **Offline Support** - Works offline with data sync
- **Real-time Notifications** - Push notifications for important updates

## Technology Stack

- React Native 0.73+
- TypeScript
- React Navigation (Stack, Tab, Drawer)
- React Native Paper (Material Design 3)
- Axios for API calls
- AsyncStorage for local data
- React Hook Form for form handling

## Getting Started

### Prerequisites

- Node.js 18+
- React Native development environment set up
- Android Studio (for Android development)
- Physical Android device or emulator

### Installation

1. Navigate to the mobile-app directory:
```bash
cd mobile-app
```

2. Install dependencies:
```bash
npm install
```

3. Configure environment variables:
- Copy `.env.example` to `.env`
- Update `API_BASE_URL` to point to your Laravel backend

### Running the App

#### Android

```bash
npm run android
```

Or using React Native CLI:
```bash
npx react-native run-android
```

#### Development

Start the Metro bundler:
```bash
npm start
```

### Project Structure

```
src/
├── api/                 # API service layer
├── components/          # Reusable components
│   ├── common/          # Common UI components
│   └── layout/          # Layout components
├── screens/             # Screen components
│   ├── Auth/            # Authentication screens
│   ├── Dashboard/       # Dashboard screens
│   ├── Students/        # Student management
│   ├── Attendance/      # Attendance tracking
│   ├── Finance/         # Finance & payments
│   └── ...              # Other modules
├── navigation/          # Navigation configuration
├── contexts/            # React contexts
├── hooks/               # Custom hooks
├── utils/               # Utility functions
├── types/               # TypeScript types
├── constants/           # Constants & config
└── theme/               # Theme configuration
```

## Development Phases

### Phase 1: Core & Authentication ✅
- Project setup
- Authentication (Login, Password Reset, OTP)
- Theme system (Light/Dark mode)
- Role-based navigation
- Basic dashboard

### Phase 2: Students & Attendance (In Progress)
- Students list and management
- Family management
- Attendance marking
- Attendance analytics

### Phase 3: Finance & Payments (Planned)
- Fee structures
- Invoice generation
- Payment recording
- Online payments (M-Pesa, Stripe, PayPal)
- Student statements

### Phase 4-5: Remaining Modules (Planned)
- HR & Payroll
- Transport
- Inventory & Requisitions
- POS (School Shop)
- Library
- Hostel
- Communication
- Documents

## API Integration

The app connects to a Laravel backend. Ensure your Laravel API is running and accessible. Default URL: `http://localhost:8000/api`

Update the `.env` file with your backend URL:
```
API_BASE_URL=http://your-backend-url/api
```

## Building for Production

```bash
# Android
cd android
./gradlew assembleRelease
```

The APK will be available at: `android/app/build/outputs/apk/release/app-release.apk`

## License

Proprietary - School ERP System

## Support

For support, contact the development team.
