import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { StaffClockHistoryItem, staffClockApi } from '@api/staffClock.api';

type ClockStatus = {
    checkInTime: string | null;
    checkOutTime: string | null;
};

export const TeacherClockScreen: React.FC = () => {
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(false);
    const [busyAction, setBusyAction] = useState<'in' | 'out' | null>(null);
    const [radius, setRadius] = useState(100);
    const [isConfigured, setIsConfigured] = useState(false);
    const [clockStatus, setClockStatus] = useState<ClockStatus>({
        checkInTime: null,
        checkOutTime: null,
    });
    const [history, setHistory] = useState<StaffClockHistoryItem[]>([]);

    const [configError, setConfigError] = useState<string | null>(null);

    const loadData = useCallback(async () => {
        setLoading(true);
        setConfigError(null);
        try {
            // Run in sequence so that a transient network fault on one call
            // doesn't mark the screen as "unconfigured" unnecessarily.
            const geo = await staffClockApi.getGeofenceConfig();
            if (geo.success && geo.data) {
                setRadius(Math.round(geo.data.radius_meters || 100));
                setIsConfigured(Boolean(geo.data.is_configured));
            }

            const today = await staffClockApi.getTodayClockStatus();
            if (today.success && today.data) {
                setClockStatus({
                    checkInTime: today.data.check_in_time,
                    checkOutTime: today.data.check_out_time,
                });
            } else {
                setClockStatus({ checkInTime: null, checkOutTime: null });
            }

            const historyRes = await staffClockApi.getClockHistory(14);
            if (historyRes.success && historyRes.data) {
                setHistory(historyRes.data);
            } else {
                setHistory([]);
            }
        } catch (error: any) {
            // Don't flip isConfigured on a network error — keep last good state so
            // teachers aren't blocked by transient connectivity issues.
            setConfigError(error?.message || 'Could not load clock data. Check your connection.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadData();
    }, [loadData]);

    const requestCurrentCoordinates = async () => {
        const permission = await Location.requestForegroundPermissionsAsync();
        if (permission.status !== 'granted') {
            throw new Error('Location permission is required to clock in or out.');
        }

        const pos = await Location.getCurrentPositionAsync({
            accuracy: Location.Accuracy.High,
        });

        return {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy_meters: pos.coords.accuracy ?? undefined,
        };
    };

    const performClock = async (mode: 'in' | 'out') => {
        if (!isConfigured) {
            Alert.alert(
                'Geofence not configured',
                'School geofence has not been configured by admin. Ask an admin to set the coordinates in Settings first.'
            );
            return;
        }

        setBusyAction(mode);
        try {
            const payload = await requestCurrentCoordinates();
            const response = mode === 'in' ? await staffClockApi.clockIn(payload) : await staffClockApi.clockOut(payload);
            if (response.success) {
                Alert.alert('Success', response.message || `Clock-${mode} recorded.`);
                await loadData();
            } else {
                Alert.alert('Unable to continue', response.message || 'Request failed.');
            }
        } catch (error: any) {
            // Backend 422 already provides a clear distance-vs-radius message.
            // Location permission errors throw here too — surface whatever message we get.
            Alert.alert('Unable to continue', error?.message || 'Please try again.');
        } finally {
            setBusyAction(null);
        }
    };

    const statusLabel = useMemo(() => {
        if (clockStatus.checkInTime && clockStatus.checkOutTime) return 'Completed (clocked in and out)';
        if (clockStatus.checkInTime) return 'Clocked in, pending clock out';
        return 'Not clocked in yet';
    }, [clockStatus.checkInTime, clockStatus.checkOutTime]);

    return (
        <SafeAreaView edges={['bottom']} style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <ScrollView contentContainerStyle={styles.content}>
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Teacher clock-in / clock-out
                    </Text>
                    <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        You must be within {radius}m of the configured school location to record attendance.
                    </Text>
                    {configError ? (
                        <Text style={[styles.warning]}>{configError}</Text>
                    ) : null}
                    {!configError && !isConfigured && !loading ? (
                        <Text style={[styles.warning]}>
                            Geofence not configured. Ask an admin to set school coordinates in Settings.
                        </Text>
                    ) : null}
                    <Text style={[styles.info, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Status: {statusLabel}
                    </Text>
                    <Text style={[styles.info, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Clock in: {clockStatus.checkInTime || '--'}
                    </Text>
                    <Text style={[styles.info, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Clock out: {clockStatus.checkOutTime || '--'}
                    </Text>
                    <Button
                        title="Clock In"
                        onPress={() => performClock('in')}
                        loading={busyAction === 'in'}
                        disabled={loading || Boolean(clockStatus.checkInTime)}
                        fullWidth
                        style={styles.action}
                    />
                    <Button
                        title="Clock Out"
                        onPress={() => performClock('out')}
                        loading={busyAction === 'out'}
                        disabled={loading || !clockStatus.checkInTime || Boolean(clockStatus.checkOutTime)}
                        variant="outline"
                        fullWidth
                        style={styles.action}
                    />
                    <Button title="Refresh" onPress={loadData} variant="outline" fullWidth style={styles.action} loading={loading} />
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Attendance history
                    </Text>
                    {history.length === 0 ? (
                        <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            No attendance history yet.
                        </Text>
                    ) : (
                        history.map((item) => (
                            <View key={item.id} style={styles.historyRow}>
                                <Text style={[styles.historyDate, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {item.date || '--'}
                                </Text>
                                <Text style={[styles.historyMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    In: {item.check_in_time || '--'} ({item.check_in_distance_meters != null ? `${item.check_in_distance_meters}m` : '--'})
                                </Text>
                                <Text style={[styles.historyMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Out: {item.check_out_time || '--'} ({item.check_out_distance_meters != null ? `${item.check_out_distance_meters}m` : '--'})
                                </Text>
                            </View>
                        ))
                    )}
                </Card>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    section: { marginBottom: SPACING.md },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', marginBottom: SPACING.sm },
    desc: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    info: { fontSize: FONT_SIZES.sm, marginBottom: 4 },
    action: { marginTop: SPACING.sm },
    historyRow: {
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
        borderBottomColor: '#9993',
    },
    historyDate: { fontSize: FONT_SIZES.sm, fontWeight: '700' },
    historyMeta: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    warning: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.sm,
        padding: SPACING.sm,
        backgroundColor: '#FFF4E5',
        borderRadius: 6,
        color: '#8A5000',
    },
});
