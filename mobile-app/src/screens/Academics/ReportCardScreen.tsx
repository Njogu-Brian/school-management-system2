import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { ReportCard } from '../types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface ReportCardScreenProps {
    navigation: any;
    route: any;
}

export const ReportCardScreen: React.FC<ReportCardScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { reportCardId } = route.params;

    const [reportCard, setReportCard] = useState<ReportCard | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadReportCard();
    }, [reportCardId]);

    const loadReportCard = async () => {
        try {
            setLoading(true);
            const response = await academicsApi.getReportCard(reportCardId);

            if (response.success && response.data) {
                setReportCard(response.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load report card');
        } finally {
            setLoading(false);
        }
    };

    const handleDownload = async () => {
        try {
            const response = await academicsApi.downloadReportCard(reportCardId);
            if (response.success) {
                Alert.alert('Success', 'Report card downloaded');
            }
        } catch (error: any) {
            Alert.alert('Error', 'Failed to download report card');
        }
    };

    const getGradeColor = (grade: string) => {
        switch (grade.toUpperCase()) {
            case 'A':
                return colors.success;
            case 'B':
                return '#10b981';
            case 'C':
                return '#f59e0b';
            case 'D':
                return '#f97316';
            case 'E':
                return colors.error;
            default:
                return isDark ? colors.textMainDark : colors.textMainLight;
        }
    };

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading report card..." />
            </SafeAreaView>
        );
    }

    if (!reportCard) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <Text style={[styles.error, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Report card not found
                </Text>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <View style={styles.headerInfo}>
                    <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Report Card
                    </Text>
                    <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {reportCard.student_name}
                    </Text>
                </View>
                <TouchableOpacity onPress={handleDownload}>
                    <Icon name="download" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            <ScrollView style={styles.content}>
                {/* Overall Performance */}
                <Card style={styles.overallCard}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Overall Performance
                    </Text>

                    <View style={styles.statsGrid}>
                        <View style={styles.statBox}>
                            <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Total Marks
                            </Text>
                            <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {reportCard.overall_marks}
                            </Text>
                        </View>

                        <View style={styles.statBox}>
                            <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Percentage
                            </Text>
                            <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {reportCard.overall_percentage.toFixed(1)}%
                            </Text>
                        </View>

                        <View style={styles.statBox}>
                            <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Grade
                            </Text>
                            <Text style={[styles.statValue, { color: getGradeColor(reportCard.overall_grade || 'E') }]}>
                                {reportCard.overall_grade || 'N/A'}
                            </Text>
                        </View>

                        {reportCard.class_position && (
                            <View style={styles.statBox}>
                                <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Position
                                </Text>
                                <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {reportCard.class_position}
                                </Text>
                            </View>
                        )}
                    </View>
                </Card>

                {/* Subjects Performance */}
                <Card style={styles.subjectsCard}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Subject Performance
                    </Text>

                    <View style={styles.tableHeader}>
                        <Text style={[styles.columnHeader, styles.subjectColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Subject
                        </Text>
                        <Text style={[styles.columnHeader, styles.marksColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Marks
                        </Text>
                        <Text style={[styles.columnHeader, styles.gradeColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Grade
                        </Text>
                    </View>

                    {reportCard.subjects.map((subject, index) => (
                        <View
                            key={index}
                            style={[
                                styles.subjectRow,
                                { borderBottomColor: isDark ? colors.borderDark : colors.borderLight },
                            ]}
                        >
                            <Text style={[styles.subjectName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {subject.subject_name}
                            </Text>
                            <Text style={[styles.subjectMarks, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {subject.marks}/{subject.total_marks}
                            </Text>
                            <Text style={[styles.subjectGrade, { color: getGradeColor(subject.grade) }]}>
                                {subject.grade}
                            </Text>
                        </View>
                    ))}
                </Card>

                {/* Skills Assessment (if available) */}
                {reportCard.skills && reportCard.skills.length > 0 && (
                    <Card style={styles.skillsCard}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Skills Assessment
                        </Text>

                        {reportCard.skills.map((skill, index) => (
                            <View key={index} style={styles.skillRow}>
                                <Text style={[styles.skillName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {skill.skill_name}
                                </Text>
                                <Text
                                    style={[
                                        styles.skillRating,
                                        {
                                            color:
                                                skill.rating === 'excellent' ? colors.success :
                                                    skill.rating === 'good' ? '#10b981' :
                                                        skill.rating === 'average' ? '#f59e0b' :
                                                            colors.error,
                                        },
                                    ]}
                                >
                                    {formatters.capitalize(skill.rating)}
                                </Text>
                            </View>
                        ))}
                    </Card>
                )}

                {/* Comments */}
                {(reportCard.teacher_comment || reportCard.principal_comment) && (
                    <Card style={styles.commentsCard}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Comments
                        </Text>

                        {reportCard.teacher_comment && (
                            <View style={styles.commentSection}>
                                <Text style={[styles.commentLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Class Teacher:
                                </Text>
                                <Text style={[styles.commentText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {reportCard.teacher_comment}
                                </Text>
                            </View>
                        )}

                        {reportCard.principal_comment && (
                            <View style={styles.commentSection}>
                                <Text style={[styles.commentLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Principal:
                                </Text>
                                <Text style={[styles.commentText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {reportCard.principal_comment}
                                </Text>
                            </View>
                        )}
                    </Card>
                )}

                <Button
                    title="Download PDF"
                    onPress={handleDownload}
                    icon="download"
                    fullWidth
                    style={styles.downloadButton}
                />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    headerInfo: {
        flex: 1,
        marginLeft: SPACING.md,
    },
    title: {
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: 2,
    },
    error: {
        textAlign: 'center',
        marginTop: SPACING.xxl,
    },
    content: {
        flex: 1,
    },
    overallCard: {
        marginHorizontal: SPACING.xl,
        marginTop: SPACING.md,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.md,
    },
    statsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    statBox: {
        flex: 1,
        minWidth: '45%',
        alignItems: 'center',
        padding: SPACING.md,
        backgroundColor: '#f8fafc',
        borderRadius: 8,
    },
    statLabel: {
        fontSize: FONT_SIZES.xs,
        marginBottom: 4,
    },
    statValue: {
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
    },
    subjectsCard: {
        marginHorizontal: SPACING.xl,
        marginTop: SPACING.md,
    },
    tableHeader: {
        flexDirection: 'row',
        paddingBottom: SPACING.xs,
        borderBottomWidth: 2,
        borderBottomColor: '#3b82f6',
        marginBottom: SPACING.sm,
    },
    columnHeader: {
        fontSize: FONT_SIZES.sm,
        fontWeight: 'bold',
    },
    subjectColumn: {
        flex: 2,
    },
    marksColumn: {
        flex: 1,
        textAlign: 'center',
    },
    gradeColumn: {
        flex: 0.8,
        textAlign: 'center',
    },
    subjectRow: {
        flexDirection: 'row',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
    },
    subjectName: {
        flex: 2,
        fontSize: FONT_SIZES.sm,
    },
    subjectMarks: {
        flex: 1,
        fontSize: FONT_SIZES.sm,
        textAlign: 'center',
    },
    subjectGrade: {
        flex: 0.8,
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
        textAlign: 'center',
    },
    skillsCard: {
        marginHorizontal: SPACING.xl,
        marginTop: SPACING.md,
    },
    skillRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.xs,
    },
    skillName: {
        fontSize: FONT_SIZES.sm,
    },
    skillRating: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    commentsCard: {
        marginHorizontal: SPACING.xl,
        marginTop: SPACING.md,
    },
    commentSection: {
        marginBottom: SPACING.md,
    },
    commentLabel: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
        marginBottom: 4,
    },
    commentText: {
        fontSize: FONT_SIZES.sm,
        lineHeight: 20,
    },
    downloadButton: {
        margin: SPACING.xl,
    },
});
