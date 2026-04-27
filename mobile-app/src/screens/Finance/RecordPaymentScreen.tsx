import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    KeyboardAvoidingView,
    Platform,
    TouchableOpacity,
    Alert,
    Linking,
    Share,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Button } from '@components/common/Button';
import { Input } from '@components/common/Input';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { financeApi } from '@api/finance.api';
import { studentsApi } from '@api/students.api';
import { Student } from 'types/student.types';
import { Invoice } from 'types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { MpesaPromptModal } from '@components/MpesaPromptModal';
import { canUseMpesaFinanceTools } from '@utils/financeRoles';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import type { ApiError } from 'types/api.types';

interface RecordPaymentScreenProps {
    navigation: any;
    route: any;
}

export const RecordPaymentScreen: React.FC<RecordPaymentScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const { studentId } = route.params || {};
    const canFinanceMpesa = canUseMpesaFinanceTools(user);

    const [student, setStudent] = useState<Student | null>(null);
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [amount, setAmount] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [paymentDate, _setPaymentDate] = useState(new Date().toISOString().split('T')[0]);
    const [referenceNumber, setReferenceNumber] = useState('');
    const [notes, setNotes] = useState('');
    const [allocations, setAllocations] = useState<{ invoice_id: number; amount: number }[]>([]);
    const [loading, setLoading] = useState(false);
    const [dataLoading, setDataLoading] = useState(!!studentId);
    const [dataLoadError, setDataLoadError] = useState<string | null>(null);
    const [submitError, setSubmitError] = useState<string | null>(null);
    const [errors, setErrors] = useState<any>({});
    const [mpesaOpen, setMpesaOpen] = useState(false);

    const paymentMethods = [
        { value: 'cash', label: 'Cash', icon: 'money' },
        { value: 'mpesa', label: 'M-Pesa', icon: 'phone-android' },
        { value: 'bank_transfer', label: 'Bank Transfer', icon: 'account-balance' },
        { value: 'cheque', label: 'Cheque', icon: 'receipt' },
        { value: 'card', label: 'Card', icon: 'credit-card' },
    ];

    useEffect(() => {
        if (studentId) {
            loadStudentData();
        }
    }, [studentId]);

    const loadStudentData = async () => {
        if (!studentId) return;
        setDataLoading(true);
        setDataLoadError(null);
        try {
            const [studentRes, invoicesRes] = await Promise.all([
                studentsApi.getStudent(studentId),
                financeApi.getInvoices({ student_id: studentId, per_page: 100 }),
            ]);

            if (studentRes.success && studentRes.data) {
                setStudent(studentRes.data);
            } else {
                setStudent(null);
                setDataLoadError(studentRes.message || 'Could not load student.');
            }

            if (invoicesRes.success && invoicesRes.data) {
                setInvoices(invoicesRes.data.data.filter((inv: Invoice) => inv.balance > 0.009));
            } else {
                setInvoices([]);
            }
        } catch (error: any) {
            setStudent(null);
            setInvoices([]);
            setDataLoadError(error?.message || 'Failed to load student data');
        } finally {
            setDataLoading(false);
        }
    };

    const handleAllocationChange = (invoiceId: number, allocationAmount: string) => {
        const value = parseFloat(allocationAmount) || 0;
        setAllocations((prev) => {
            const existing = prev.find((a) => a.invoice_id === invoiceId);
            if (existing) {
                return prev.map((a) =>
                    a.invoice_id === invoiceId ? { ...a, amount: value } : a
                );
            }
            return [...prev, { invoice_id: invoiceId, amount: value }];
        });
    };

    const getTotalAllocated = () => {
        return allocations.reduce((sum, a) => sum + a.amount, 0);
    };

    const validate = (): boolean => {
        const newErrors: any = {};

        if (!amount || parseFloat(amount) <= 0) {
            newErrors.amount = 'Please enter a valid amount';
        }

        const totalAllocated = getTotalAllocated();
        const paymentAmount = parseFloat(amount) || 0;

        if (totalAllocated > paymentAmount) {
            newErrors.allocations = 'Total allocated amount cannot exceed payment amount';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const mapServerErrors = (apiErr: ApiError) => {
        const raw = apiErr.errors;
        if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return;
        setErrors((prev: Record<string, string>) => {
            const next = { ...prev };
            for (const [key, val] of Object.entries(raw)) {
                if (Array.isArray(val) && val.length) {
                    next[key] = String(val[0]);
                } else if (typeof val === 'string') {
                    next[key] = val;
                }
            }
            return next;
        });
    };

    const openPaymentLinkForParent = async () => {
        if (!studentId) return;
        try {
            const res = await financeApi.getMpesaPaymentLink(studentId);
            if (res.success && res.data?.url) {
                const url = res.data.url;
                Alert.alert('Payment link', url, [
                    { text: 'Open', onPress: () => Linking.openURL(url) },
                    { text: 'Share', onPress: () => Share.share({ message: url }) },
                    { text: 'Cancel', style: 'cancel' },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Could not get payment link');
        }
    };

    const handleSubmit = async () => {
        if (!studentId) {
            Alert.alert('Record payment', 'No student selected.');
            return;
        }
        setSubmitError(null);
        if (!validate()) return;

        setLoading(true);
        try {
            const response = await financeApi.createPayment({
                student_id: studentId,
                amount: parseFloat(amount),
                payment_method: paymentMethod,
                payment_date: paymentDate,
                reference_number: referenceNumber || undefined,
                notes: notes || undefined,
                invoice_allocations: allocations.filter((a) => a.amount > 0),
            });

            if (response.success) {
                Alert.alert('Success', response.message || 'Payment recorded successfully', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            } else {
                setSubmitError(response.message || 'Could not record payment.');
            }
        } catch (error: any) {
            const msg = error?.message || 'Failed to record payment';
            setSubmitError(msg);
            if (error?.errors) {
                mapServerErrors(error as ApiError);
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView
            style={[
                layoutStyles.flex1,
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : BRAND.bg },
            ]}
        >
            <KeyboardAvoidingView
                style={layoutStyles.flex1}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={0}
            >
                <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
                {!studentId ? (
                    <Text style={[styles.missingStudent, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Open this screen from a student profile (Record payment) so the app knows which learner to credit.
                    </Text>
                ) : null}

                {studentId && dataLoadError ? (
                    <LoadErrorBanner
                        message={dataLoadError}
                        onRetry={loadStudentData}
                        surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                        borderColor={isDark ? colors.borderDark : BRAND.border}
                        textColor={isDark ? colors.textMainDark : colors.textMainLight}
                        subColor={isDark ? colors.textSubDark : colors.textSubLight}
                        accentColor={colors.primary}
                    />
                ) : null}

                {submitError ? (
                    <LoadErrorBanner
                        message={submitError}
                        onRetry={() => setSubmitError(null)}
                        retryLabel="Dismiss"
                        surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                        borderColor={isDark ? colors.error : BRAND.border}
                        textColor={isDark ? colors.textMainDark : colors.textMainLight}
                        subColor={isDark ? colors.textSubDark : colors.textSubLight}
                        accentColor={colors.error}
                    />
                ) : null}

                {dataLoading ? (
                    <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, marginBottom: SPACING.md }}>
                        Loading student…
                    </Text>
                ) : null}

                {/* Student Info */}
                {student && (
                    <Card style={styles.studentCard}>
                        <View style={styles.studentInfo}>
                            <Avatar name={student.full_name} imageUrl={student.avatar} size={48} />
                            <View style={styles.studentDetails}>
                                <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {student.full_name}
                                </Text>
                                <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {student.admission_number}
                                </Text>
                                {student.fees_balance !== undefined && (
                                    <Text style={[styles.balance, { color: colors.error }]}>
                                        Balance: {formatters.formatCurrency(student.fees_balance)}
                                    </Text>
                                )}
                            </View>
                        </View>
                    </Card>
                )}

                {studentId && canFinanceMpesa && (
                    <Card style={{ borderRadius: RADIUS.card, borderColor: BRAND.border }}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            M-Pesa (same as web)
                        </Text>
                        <Text style={[styles.mpesaHint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Send STK to parent or copy the public pay link.
                        </Text>
                        <Button
                            title="Prompt parent (M-Pesa)"
                            onPress={() => setMpesaOpen(true)}
                            fullWidth
                            style={styles.mpesaBtn}
                        />
                        <Button
                            title="Parent payment link"
                            onPress={openPaymentLinkForParent}
                            variant="outline"
                            fullWidth
                            style={styles.mpesaBtn}
                        />
                    </Card>
                )}

                {/* Payment Details */}
                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Payment Details
                </Text>

                <Input
                    label="Amount *"
                    placeholder="Enter amount"
                    value={amount}
                    onChangeText={(v) => {
                        setAmount(v);
                        setSubmitError(null);
                    }}
                    keyboardType="numeric"
                    error={errors.amount}
                    icon="attach-money"
                />

                {/* Payment Method Selection */}
                <View style={styles.paymentMethods}>
                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Payment Method *
                    </Text>
                    <View style={styles.methodsGrid}>
                        {paymentMethods.map((method) => (
                            <TouchableOpacity
                                key={method.value}
                                style={[
                                    styles.methodButton,
                                    {
                                        backgroundColor: paymentMethod === method.value ? colors.primary + '20' : isDark ? colors.surfaceDark : colors.surfaceLight,
                                        borderColor: paymentMethod === method.value ? colors.primary : isDark ? colors.borderDark : colors.borderLight,
                                    },
                                ]}
                                onPress={() => setPaymentMethod(method.value)}
                            >
                                <Icon
                                    name={method.icon}
                                    size={24}
                                    color={paymentMethod === method.value ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight}
                                />
                                <Text
                                    style={[
                                        styles.methodText,
                                        {
                                            color: paymentMethod === method.value ? colors.primary : isDark ? colors.textMainDark : colors.textMainLight,
                                        },
                                    ]}
                                >
                                    {method.label}
                                </Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                </View>

                <Input
                    label="Reference Number"
                    placeholder="Transaction reference (optional)"
                    value={referenceNumber}
                    onChangeText={setReferenceNumber}
                    icon="tag"
                />

                <Input
                    label="Notes"
                    placeholder="Additional notes (optional)"
                    value={notes}
                    onChangeText={setNotes}
                    multiline
                    numberOfLines={3}
                    icon="notes"
                />

                {/* Invoice Allocations */}
                {invoices.length > 0 && (
                    <>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Allocate to Invoices (Optional)
                        </Text>
                        {errors.allocations && (
                            <Text style={[styles.errorText, { color: colors.error }]}>{errors.allocations}</Text>
                        )}

                        {invoices.map((invoice) => (
                            <Card key={invoice.id} style={styles.invoiceCard}>
                                <View style={styles.invoiceHeader}>
                                    <Text style={[styles.invoiceNumber, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {invoice.invoice_number}
                                    </Text>
                                    <Text style={[styles.invoiceBalance, { color: colors.error }]}>
                                        Balance: {formatters.formatCurrency(invoice.balance)}
                                    </Text>
                                </View>
                                <Input
                                    placeholder="Amount to allocate"
                                    keyboardType="numeric"
                                    value={allocations.find((a) => a.invoice_id === invoice.id)?.amount.toString() || ''}
                                    onChangeText={(value) => handleAllocationChange(invoice.id, value)}
                                    containerStyle={styles.allocationInput}
                                />
                            </Card>
                        ))}

                        <View style={styles.allocationSummary}>
                            <Text style={[styles.summaryLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Total Allocated:
                            </Text>
                            <Text style={[styles.summaryValue, { color: colors.primary }]}>
                                {formatters.formatCurrency(getTotalAllocated())}
                            </Text>
                        </View>
                    </>
                )}

                <Button
                    title="Record Payment"
                    onPress={handleSubmit}
                    loading={loading}
                    disabled={dataLoading || !studentId || !student}
                    fullWidth
                    style={styles.submitButton}
                />
                </ScrollView>
            </KeyboardAvoidingView>
            {studentId && (
                <MpesaPromptModal
                    visible={mpesaOpen}
                    studentId={studentId}
                    defaultAmount={
                        student?.fees_balance != null && student.fees_balance > 0
                            ? String(Math.ceil(student.fees_balance))
                            : ''
                    }
                    onClose={() => setMpesaOpen(false)}
                    navigation={navigation}
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    content: {
        padding: SPACING.xl,
        paddingTop: SPACING.md,
    },
    studentCard: {
        marginBottom: SPACING.lg,
    },
    studentInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    studentDetails: {
        flex: 1,
    },
    studentName: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    admissionNumber: {
        fontSize: FONT_SIZES.sm,
    },
    balance: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
        marginTop: 4,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.md,
        marginTop: SPACING.md,
    },
    paymentMethods: {
        marginBottom: SPACING.md,
    },
    label: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '500',
        marginBottom: SPACING.sm,
    },
    methodsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
    },
    methodButton: {
        width: '31%',
        aspectRatio: 1,
        borderWidth: 2,
        borderRadius: BORDER_RADIUS.lg,
        alignItems: 'center',
        justifyContent: 'center',
        gap: 4,
    },
    methodText: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        textAlign: 'center',
    },
    invoiceCard: {
        marginBottom: SPACING.sm,
    },
    invoiceHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: SPACING.sm,
    },
    invoiceNumber: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    invoiceBalance: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    allocationInput: {
        marginBottom: 0,
    },
    allocationSummary: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        padding: SPACING.md,
        marginTop: SPACING.sm,
        marginBottom: SPACING.lg,
    },
    summaryLabel: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    summaryValue: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    errorText: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.sm,
    },
    mpesaHint: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.md,
    },
    mpesaBtn: {
        marginBottom: SPACING.sm,
    },
    submitButton: {
        marginTop: SPACING.md,
    },
    missingStudent: {
        fontSize: FONT_SIZES.md,
        lineHeight: 22,
        marginBottom: SPACING.lg,
    },
});
