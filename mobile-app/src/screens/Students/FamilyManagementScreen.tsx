import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { Button } from '@components/common/Button';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Guardian {
    id: number;
    name: string;
    relationship: string;
    phone: string;
    email?: string;
    is_primary: boolean;
    students: { id: number; name: string }[];
}

interface FamilyManagementScreenProps {
    navigation: any;
    route: any;
}

export const FamilyManagementScreen: React.FC<FamilyManagementScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { studentId } = route.params || {};

    const [guardians, setGuardians] = useState<Guardian[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    useEffect(() => {
        loadGuardians();
    }, [studentId]);

    const loadGuardians = async () => {
        try {
            setLoading(true);
            // Mock data
            setGuardians([
                {
                    id: 1,
                    name: 'John Doe Sr.',
                    relationship: 'Father',
                    phone: '+254712345678',
                    email: 'john.doe@email.com',
                    is_primary: true,
                    students: [{ id: 1, name: 'John Doe Jr.' }],
                },
                {
                    id: 2,
                    name: 'Jane Doe',
                    relationship: 'Mother',
                    phone: '+254723456789',
                    email: 'jane.doe@email.com',
                    is_primary: false,
                    students: [{ id: 1, name: 'John Doe Jr.' }],
                },
            ]);
        } catch (error: any) {
            Alert.alert('Error', 'Failed to load guardians');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        loadGuardians();
    };

    const handleDeleteGuardian = (id: number) => {
        Alert.alert(
            'Delete Guardian',
            'Are you sure you want to remove this guardian?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Delete',
                    style: 'destructive',
                    onPress: () => {
                        setGuardians((prev) => prev.filter((g) => g.id !== id));
                        Alert.alert('Success', 'Guardian removed');
                    },
                },
            ]
        );
    };

    const renderGuardian = ({ item }: { item: Guardian }) => (
        <Card>
            <View style={styles.guardianCard}>
                <Avatar name={item.name} size={50} />

                <View style={styles.guardianInfo}>
                    <View style={styles.nameRow}>
                        <Text style={[styles.guardianName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.name}
                        </Text>
                        {item.is_primary && (
                            <View style={[styles.primaryBadge, { backgroundColor: colors.primary + '20' }]}>
                                <Text style={[styles.primaryText, { color: colors.primary }]}>Primary</Text>
                            </View>
                        )}
                    </View>

                    <Text style={[styles.relationship, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.relationship}
                    </Text>

                    <View style={styles.contactRow}>
                        <Icon name="phone" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.contactText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.phone}
                        </Text>
                    </View>

                    {item.email && (
                        <View style={styles.contactRow}>
                            <Icon name="email" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.contactText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.email}
                            </Text>
                        </View>
                    )}

                    {item.students.length > 0 && (
                        <Text style={[styles.studentsText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Guardian to {item.students.length} student(s)
                        </Text>
                    )}
                </View>

                <View style={styles.actions}>
                    <TouchableOpacity
                        style={styles.actionButton}
                        onPress={() => navigation.navigate('EditGuardian', { guardianId: item.id })}
                    >
                        <Icon name="edit" size={20} color={colors.primary} />
                    </TouchableOpacity>
                    <TouchableOpacity
                        style={styles.actionButton}
                        onPress={() => handleDeleteGuardian(item.id)}
                    >
                        <Icon name="delete" size={20} color={colors.error} />
                    </TouchableOpacity>
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading guardians..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Family Management
                </Text>
                <TouchableOpacity onPress={() => navigation.navigate('AddGuardian', { studentId })}>
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            <FlatList
                data={guardians}
                renderItem={renderGuardian}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
                ListEmptyComponent={
                    <EmptyState
                        icon="people"
                        title="No Guardians"
                        message="No guardians added yet"
                    />
                }
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    listContent: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xl },
    guardianCard: { flexDirection: 'row', gap: SPACING.md },
    guardianInfo: { flex: 1, gap: 4 },
    nameRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm },
    guardianName: { fontSize: FONT_SIZES.md, fontWeight: 'bold' },
    primaryBadge: { paddingHorizontal: SPACING.xs, paddingVertical: 2, borderRadius: 4 },
    primaryText: { fontSize: 10, fontWeight: 'bold' },
    relationship: { fontSize: FONT_SIZES.sm, fontStyle: 'italic' },
    contactRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 2 },
    contactText: { fontSize: FONT_SIZES.xs },
    studentsText: { fontSize: FONT_SIZES.xs, marginTop: 4, fontWeight: '600' },
    actions: { gap: SPACING.xs },
    actionButton: { padding: SPACING.xs },
});
