import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { communicationApi } from '@api/communication.api';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface SendSMSScreenProps {
    navigation: any;
}

export const SendSMSScreen: React.FC<SendSMSScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(false);

    const [formData, setFormData] = useState({
        recipient_type: 'class',
        class_id: '',
        student_ids: [],
        message: '',
        send_to_parents: true,
    });

    const [charCount, setCharCount] = useState(0);
    const maxChars = 160;

    const handleInputChange = (field: string, value: any) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        if (field === 'message') {
            setCharCount(value.length);
        }
    };

    const validateForm = (): boolean => {
        if (!formData.message.trim()) {
            Alert.alert('Validation Error', 'Message is required');
            return false;
        }

        if (formData.recipient_type === 'class' && !formData.class_id) {
            Alert.alert('Validation Error', 'Please select a class');
            return false;
        }

        return true;
    };

    const handleSend = async () => {
        if (!validateForm()) return;

        setLoading(true);
        try {
            const response = await communicationApi.sendSMS({
                ...formData,
                class_id: formData.class_id ? parseInt(formData.class_id) : undefined,
            });

            if (response.success) {
                Alert.alert('Success', 'SMS sent successfully', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to send SMS');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView style={styles.content}>
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Recipients
                    </Text>

                    <Input
                        label="Recipient Type"
                        value={formData.recipient_type}
                        onChangeText={(value) => handleInputChange('recipient_type', value)}
                        placeholder="class, students, all"
                    />

                    {formData.recipient_type === 'class' && (
                        <Input
                            label="Class ID *"
                            value={formData.class_id}
                            onChangeText={(value) => handleInputChange('class_id', value)}
                            placeholder="Enter class ID"
                            keyboardType="numeric"
                        />
                    )}
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Message
                    </Text>

                    <Input
                        label={`Message (${charCount}/${maxChars})`}
                        value={formData.message}
                        onChangeText={(value) => handleInputChange('message', value)}
                        placeholder="Type your message..."
                        multiline
                        numberOfLines={6}
                        maxLength={maxChars}
                    />

                    <Text style={[styles.hint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Message will be sent to {formData.send_to_parents ? 'parents/guardians' : 'students'}
                    </Text>
                </Card>

                <View style={styles.buttonContainer}>
                    <Button
                        title="Cancel"
                        onPress={() => navigation.goBack()}
                        variant="outline"
                        fullWidth
                        style={styles.button}
                    />
                    <Button
                        title="Send SMS"
                        onPress={handleSend}
                        loading={loading}
                        icon="send"
                        fullWidth
                        style={styles.button}
                    />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { flex: 1, padding: SPACING.xl },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.md },
    hint: { fontSize: FONT_SIZES.xs, marginTop: SPACING.xs, fontStyle: 'italic' },
    buttonContainer: { flexDirection: 'row', gap: SPACING.md, marginTop: SPACING.lg, marginBottom: SPACING.xxl },
    button: { flex: 1 },
});
