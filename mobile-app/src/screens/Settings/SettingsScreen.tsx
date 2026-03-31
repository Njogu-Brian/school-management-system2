import React, { useMemo, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    TextInput,
    Switch,
    Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { useNotificationPreferences } from '@contexts/NotificationPreferencesContext';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { Palette } from '@styles/palette';
import { UserRole } from '@constants/roles';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import { checkForAppUpdate } from '@services/update.service';
import { authenticateWithBiometrics, canUseBiometrics, getBiometricCredentials, getBiometricEnabled, setBiometricEnabled } from '@utils/biometrics';
import { authApi } from '@api/auth.api';
import { staffClockApi } from '@api/staffClock.api';
import * as Location from 'expo-location';

interface SettingsScreenProps {
    navigation: any;
}

export const SettingsScreen: React.FC<SettingsScreenProps> = ({ navigation }) => {
    const { isDark, colors, toggleTheme } = useTheme();
    const { user } = useAuth();
    const { preferences, updatePreferences } = useNotificationPreferences();
    const appVersion = Constants.expoConfig?.version ?? '1.0.0';
    const buildNumber = String(
        Constants.expoConfig?.android?.versionCode ?? Constants.expoConfig?.ios?.buildNumber ?? '100'
    );
    const isAdminUser = user?.role === UserRole.ADMIN || user?.role === UserRole.SUPER_ADMIN;
    const [updateChannel, setUpdateChannel] = useState('—');
    const [runtimeVersion, setRuntimeVersion] = useState(
        () => String(Constants.expoConfig?.runtimeVersion ?? 'N/A')
    );
    const [updateId, setUpdateId] = useState('—');

    const [notifBusy, setNotifBusy] = useState(false);

    const [biometrics, setBiometrics] = useState({
        enabled: false,
        touchId: false,
        faceId: false,
    });
    const [deviceSupportsBiometrics, setDeviceSupportsBiometrics] = useState(false);
    const [changingPassword, setChangingPassword] = useState(false);
    const [passwordForm, setPasswordForm] = useState({
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
    });
    const [geoLoading, setGeoLoading] = useState(false);
    const [schoolLatitude, setSchoolLatitude] = useState('');
    const [schoolLongitude, setSchoolLongitude] = useState('');
    const [radiusMeters, setRadiusMeters] = useState('100');

    React.useEffect(() => {
        (async () => {
            const [supported, enabled] = await Promise.all([canUseBiometrics(), getBiometricEnabled()]);
            setDeviceSupportsBiometrics(supported);
            setBiometrics((prev) => ({ ...prev, enabled: supported ? enabled : false }));
        })();
    }, []);

    React.useEffect(() => {
        if (!isAdminUser) return;
        let cancelled = false;
        const t = setTimeout(() => {
            try {
                if (cancelled) return;
                setUpdateChannel(Updates.channel ?? 'N/A');
                setRuntimeVersion(String(Updates.runtimeVersion ?? Constants.expoConfig?.runtimeVersion ?? 'N/A'));
                setUpdateId(Updates.updateId ?? 'N/A');
            } catch {
                // expo-updates not ready yet
            }
        }, 300);
        return () => {
            cancelled = true;
            clearTimeout(t);
        };
    }, [isAdminUser]);

    React.useEffect(() => {
        (async () => {
            try {
                const geo = await staffClockApi.getGeofenceConfig();
                if (geo.success && geo.data) {
                    setSchoolLatitude(geo.data.latitude?.toString() ?? '');
                    setSchoolLongitude(geo.data.longitude?.toString() ?? '');
                    setRadiusMeters(String(Math.round(geo.data.radius_meters || 100)));
                }
            } catch {
                // ignore geofence loading errors for non-admin users
            }
        })();
    }, []);

    const handleToggle = (category: string, setting: string) => {
        if (category === 'biometrics') {
            if (setting === 'enabled') {
                (async () => {
                    const currentlyEnabled = biometrics.enabled;
                    const nextValue = !currentlyEnabled;

                    if (nextValue) {
                        const supported = await canUseBiometrics();
                        if (!supported) {
                            Alert.alert('Biometrics unavailable', 'Set up fingerprint/face ID on this device first.');
                            setDeviceSupportsBiometrics(false);
                            setBiometrics((prev) => ({ ...prev, enabled: false }));
                            return;
                        }

                        const ok = await authenticateWithBiometrics('Enable biometric sign-in');
                        if (!ok) {
                            Alert.alert('Cancelled', 'Biometric verification was not completed.');
                            setBiometrics((prev) => ({ ...prev, enabled: false }));
                            return;
                        }

                        await setBiometricEnabled(true);
                        setBiometrics((prev) => ({ ...prev, enabled: true }));

                        const creds = await getBiometricCredentials();
                        if (!creds) {
                            Alert.alert(
                                'Biometrics enabled',
                                'Biometric sign-in is enabled. To use it on the login screen, sign out and sign in once with your password to securely save credentials.'
                            );
                        }
                        return;
                    }

                    await setBiometricEnabled(false);
                    setBiometrics((prev) => ({ ...prev, enabled: false }));
                    Alert.alert('Biometrics disabled', 'Biometric sign-in has been disabled on this device.');
                })().catch(() => {
                    Alert.alert('Error', 'Could not update biometric settings.');
                });
                return;
            }

            setBiometrics((prev) => ({ ...prev, [setting]: !prev[setting as keyof typeof prev] }));
        }
    };

    const mapPreference = useMemo(
        () => ({
            pushEnabled: preferences.push_enabled,
            emailEnabled: preferences.email_enabled,
            smsEnabled: preferences.sms_enabled,
            attendanceAlerts: preferences.attendance_alerts,
            feeReminders: preferences.fee_reminders,
            announcements: preferences.announcements,
        }),
        [preferences]
    );

    const handleNotificationToggle = async (
        key:
            | 'push_enabled'
            | 'email_enabled'
            | 'sms_enabled'
            | 'attendance_alerts'
            | 'fee_reminders'
            | 'announcements'
    ) => {
        const next = { ...preferences, [key]: !preferences[key] };
        setNotifBusy(true);
        try {
            await updatePreferences(next);
        } finally {
            setNotifBusy(false);
        }
    };

    const handleClearCache = async () => {
        Alert.alert(
            'Clear Cache',
            'This will clear all cached data. Are you sure?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Clear',
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            // Clear specific cache items, not auth token
                            await AsyncStorage.multiRemove(['@cached_students', '@cached_staff', '@cached_classes']);
                            Alert.alert('Success', 'Cache cleared successfully');
                        } catch (error) {
                            Alert.alert('Error', 'Failed to clear cache');
                        }
                    },
                },
            ]
        );
    };

    const renderSettingRow = (title: string, value: boolean, onToggle: () => void, description?: string) => (
        <View style={styles.settingRow}>
            <View style={styles.settingInfo}>
                <Text style={[styles.settingTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {title}
                </Text>
                {description && (
                    <Text style={[styles.settingDescription, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {description}
                    </Text>
                )}
            </View>
            <Switch
                value={value}
                onValueChange={onToggle}
                trackColor={{ false: Palette.switchTrackOff, true: colors.primary + '80' }}
                thumbColor={value ? colors.primary : Palette.switchThumbOff}
            />
        </View>
    );

    const handleCheckUpdates = async () => {
        await checkForAppUpdate({ silent: false, showNoUpdateMessage: true });
    };

    const isStrongPassword = (value: string): boolean => {
        return /[a-z]/.test(value) && /[A-Z]/.test(value) && /\d/.test(value) && /[^A-Za-z0-9]/.test(value);
    };

    const handleChangePassword = async () => {
        if (!passwordForm.currentPassword || !passwordForm.newPassword || !passwordForm.confirmPassword) {
            Alert.alert('Missing fields', 'Enter your current password, new password, and confirmation.');
            return;
        }
        if (passwordForm.newPassword.length < 8 || !isStrongPassword(passwordForm.newPassword)) {
            Alert.alert(
                'Weak password',
                'Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.'
            );
            return;
        }
        if (passwordForm.newPassword !== passwordForm.confirmPassword) {
            Alert.alert('Mismatch', 'New password and confirmation do not match.');
            return;
        }

        setChangingPassword(true);
        try {
            const response = await authApi.changePassword({
                current_password: passwordForm.currentPassword,
                new_password: passwordForm.newPassword,
                new_password_confirmation: passwordForm.confirmPassword,
            });
            if (response.success) {
                setPasswordForm({ currentPassword: '', newPassword: '', confirmPassword: '' });
                Alert.alert('Success', response.message || 'Password changed successfully.');
            } else {
                Alert.alert('Could not change password', response.message || 'Please try again.');
            }
        } catch (error: any) {
            Alert.alert('Could not change password', error?.message || 'Please check your current password and try again.');
        } finally {
            setChangingPassword(false);
        }
    };

    const setSchoolLocationFromDevice = async () => {
        setGeoLoading(true);
        try {
            const permission = await Location.requestForegroundPermissionsAsync();
            if (permission.status !== 'granted') {
                Alert.alert('Location required', 'Allow location permission to set school coordinates from this device.');
                return;
            }
            const current = await Location.getCurrentPositionAsync({
                accuracy: Location.Accuracy.High,
            });
            setSchoolLatitude(String(current.coords.latitude));
            setSchoolLongitude(String(current.coords.longitude));
        } catch {
            Alert.alert('Location error', 'Failed to read current location.');
        } finally {
            setGeoLoading(false);
        }
    };

    const saveSchoolGeofence = async () => {
        const lat = Number(schoolLatitude);
        const lng = Number(schoolLongitude);
        const radius = Number(radiusMeters);
        if (Number.isNaN(lat) || Number.isNaN(lng) || Number.isNaN(radius)) {
            Alert.alert('Invalid values', 'Provide valid latitude, longitude, and radius.');
            return;
        }

        setGeoLoading(true);
        try {
            const response = await staffClockApi.updateGeofenceConfig({
                latitude: lat,
                longitude: lng,
                radius_meters: radius,
            });
            if (response.success) {
                Alert.alert('Saved', 'School geofence updated.');
            } else {
                Alert.alert('Error', response.message || 'Could not save geofence.');
            }
        } catch (error: any) {
            Alert.alert('Error', error?.message || 'Could not save geofence.');
        } finally {
            setGeoLoading(false);
        }
    };

    return (
        <SafeAreaView
            edges={['bottom']}
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView style={styles.content}>
                {/* Appearance */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Appearance
                    </Text>
                    {renderSettingRow('Dark Mode', isDark, toggleTheme, 'Use dark theme throughout the app')}
                </Card>

                {/* Notifications */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Notifications
                    </Text>
                    {renderSettingRow(
                        'Push Notifications',
                        mapPreference.pushEnabled,
                        () => handleNotificationToggle('push_enabled'),
                        'Receive push notifications'
                    )}
                    {renderSettingRow(
                        'Email Notifications',
                        mapPreference.emailEnabled,
                        () => handleNotificationToggle('email_enabled')
                    )}
                    {renderSettingRow(
                        'SMS Notifications',
                        mapPreference.smsEnabled,
                        () => handleNotificationToggle('sms_enabled')
                    )}
                    {renderSettingRow(
                        'Attendance Alerts',
                        mapPreference.attendanceAlerts,
                        () => handleNotificationToggle('attendance_alerts')
                    )}
                    {renderSettingRow(
                        'Fee Reminders',
                        mapPreference.feeReminders,
                        () => handleNotificationToggle('fee_reminders')
                    )}
                    {renderSettingRow(
                        'Announcements',
                        mapPreference.announcements,
                        () => handleNotificationToggle('announcements')
                    )}
                    {notifBusy ? (
                        <Text style={[styles.settingDescription, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Saving preferences...
                        </Text>
                    ) : null}
                </Card>

                {/* Security */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Security
                    </Text>
                    {renderSettingRow(
                        'Biometric Login',
                        biometrics.enabled && deviceSupportsBiometrics,
                        () => handleToggle('biometrics', 'enabled'),
                        deviceSupportsBiometrics
                            ? 'Use fingerprint or face recognition'
                            : 'Biometric login not available on this device'
                    )}
                    <Text style={[styles.settingDescription, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Use your current password, then set a strong new password (8+ chars, upper/lowercase, number, symbol).
                    </Text>
                    <TextInput
                        secureTextEntry
                        autoCapitalize="none"
                        placeholder="Current password"
                        placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                        style={[
                            styles.input,
                            {
                                color: isDark ? colors.textMainDark : colors.textMainLight,
                                backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                borderColor: isDark ? colors.borderDark : colors.borderLight,
                            },
                        ]}
                        value={passwordForm.currentPassword}
                        onChangeText={(value) => setPasswordForm((prev) => ({ ...prev, currentPassword: value }))}
                    />
                    <TextInput
                        secureTextEntry
                        autoCapitalize="none"
                        placeholder="New password"
                        placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                        style={[
                            styles.input,
                            {
                                color: isDark ? colors.textMainDark : colors.textMainLight,
                                backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                borderColor: isDark ? colors.borderDark : colors.borderLight,
                            },
                        ]}
                        value={passwordForm.newPassword}
                        onChangeText={(value) => setPasswordForm((prev) => ({ ...prev, newPassword: value }))}
                    />
                    <TextInput
                        secureTextEntry
                        autoCapitalize="none"
                        placeholder="Confirm new password"
                        placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                        style={[
                            styles.input,
                            {
                                color: isDark ? colors.textMainDark : colors.textMainLight,
                                backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                borderColor: isDark ? colors.borderDark : colors.borderLight,
                            },
                        ]}
                        value={passwordForm.confirmPassword}
                        onChangeText={(value) => setPasswordForm((prev) => ({ ...prev, confirmPassword: value }))}
                    />
                    <Button
                        title="Update Password"
                        onPress={handleChangePassword}
                        loading={changingPassword}
                        fullWidth
                        style={styles.actionButton}
                    />
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Teacher Attendance Clock
                    </Text>
                    <Button
                        title="Open Clock In / Out"
                        onPress={() => navigation.navigate('TeacherClock')}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
                    <Text style={[styles.settingDescription, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Teachers can clock in/out only within the school geofence.
                    </Text>
                </Card>

                {isAdminUser ? (
                    <Card style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            School Geofence (Admin)
                        </Text>
                        <TextInput
                            keyboardType="decimal-pad"
                            placeholder="Latitude"
                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                            style={[
                                styles.input,
                                {
                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                },
                            ]}
                            value={schoolLatitude}
                            onChangeText={setSchoolLatitude}
                        />
                        <TextInput
                            keyboardType="decimal-pad"
                            placeholder="Longitude"
                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                            style={[
                                styles.input,
                                {
                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                },
                            ]}
                            value={schoolLongitude}
                            onChangeText={setSchoolLongitude}
                        />
                        <TextInput
                            keyboardType="numeric"
                            placeholder="Radius in meters (e.g. 100)"
                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                            style={[
                                styles.input,
                                {
                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                },
                            ]}
                            value={radiusMeters}
                            onChangeText={setRadiusMeters}
                        />
                        <Button
                            title="Use Current Device Location"
                            onPress={setSchoolLocationFromDevice}
                            variant="outline"
                            fullWidth
                            style={styles.actionButton}
                            loading={geoLoading}
                        />
                        <Button
                            title="Save Geofence"
                            onPress={saveSchoolGeofence}
                            fullWidth
                            style={styles.actionButton}
                            loading={geoLoading}
                        />
                    </Card>
                ) : null}

                {/* Data & Storage */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Data & Storage
                    </Text>
                    <Button
                        title="Check for Updates"
                        onPress={handleCheckUpdates}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
                    <Button
                        title="Clear Cache"
                        onPress={handleClearCache}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
                </Card>

                {/* About */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        About
                    </Text>
                    <View style={styles.infoRow}>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Version
                        </Text>
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {appVersion}
                        </Text>
                    </View>
                    <View style={styles.infoRow}>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Build
                        </Text>
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {buildNumber}
                        </Text>
                    </View>
                </Card>

                {isAdminUser ? (
                    <Card style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Update Diagnostics (Admin)
                        </Text>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Channel
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {updateChannel}
                            </Text>
                        </View>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Runtime Version
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {runtimeVersion}
                            </Text>
                        </View>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Update ID
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {updateId}
                            </Text>
                        </View>
                    </Card>
                ) : null}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { flex: 1, padding: SPACING.xl, paddingTop: SPACING.md },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.md },
    settingRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: Palette.borderSlate,
    },
    settingInfo: { flex: 1, marginRight: SPACING.md },
    settingTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    settingDescription: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    input: {
        borderWidth: 1,
        borderRadius: 10,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        marginTop: SPACING.sm,
    },
    actionButton: { marginTop: SPACING.sm },
    infoRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: Palette.borderSlate,
    },
    infoLabel: { fontSize: FONT_SIZES.sm },
    infoValue: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
