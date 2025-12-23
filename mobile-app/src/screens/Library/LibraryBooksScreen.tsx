import React, { useState, useEffect, useCallback } from 'react';
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
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { libraryApi } from '@api/library.api';
import { Book } from '../types/library.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface LibraryBooksScreenProps {
    navigation: any;
}

export const LibraryBooksScreen: React.FC<LibraryBooksScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [books, setBooks] = useState<Book[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const fetchBooks = useCallback(
        async (pageNum: number = 1, search?: string) => {
            try {
                if (pageNum === 1) {
                    setLoading(true);
                }

                const response = await libraryApi.getBooks({
                    search: search || searchQuery,
                    page: pageNum,
                    per_page: 20,
                });

                if (response.success && response.data) {
                    if (pageNum === 1) {
                        setBooks(response.data.data);
                    } else {
                        setBooks((prev) => [...prev, ...response.data.data]);
                    }

                    setHasMore(response.data.current_page < response.data.last_page);
                    setPage(pageNum);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load books');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery]
    );

    useEffect(() => {
        fetchBooks(1);
    }, []);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchQuery !== undefined) {
                fetchBooks(1, searchQuery);
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchBooks(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchBooks(page + 1);
        }
    };

    const handleBookPress = (book: Book) => {
        navigation.navigate('BookDetail', { bookId: book.id });
    };

    const renderBookCard = ({ item }: { item: Book }) => (
        <Card onPress={() => handleBookPress(item)}>
            <View style={styles.bookCard}>
                <View style={styles.bookIconContainer}>
                    <Icon name="menu-book" size={40} color={colors.primary} />
                </View>

                <View style={styles.bookInfo}>
                    <Text style={[styles.bookTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.title}
                    </Text>
                    <Text style={[styles.bookAuthor, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        by {item.author}
                    </Text>

                    <View style={styles.detailsRow}>
                        <View style={styles.detail}>
                            <Icon name="label" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.category}
                            </Text>
                        </View>
                        {item.isbn && (
                            <View style={styles.detail}>
                                <Icon name="qr-code" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                                <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {item.isbn}
                                </Text>
                            </View>
                        )}
                    </View>

                    <View style={styles.availabilityRow}>
                        <Text style={[styles.availabilityText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Available: {item.available_copies}/{item.total_copies}
                        </Text>
                        <StatusBadge status={item.status} />
                    </View>
                </View>

                <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
            </View>
        </Card>
    );

    if (loading && page === 1) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading books..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Library Catalog
                </Text>
                <TouchableOpacity style={styles.addButton} onPress={() => navigation.navigate('AddBook')}>
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Input
                    placeholder="Search books by title, author, or ISBN..."
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
            </View>

            {/* Books List */}
            <FlatList
                data={books}
                renderItem={renderBookCard}
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
                onEndReached={handleLoadMore}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState icon="menu-book" title="No Books Found" message="No books match your search criteria" />
                }
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    addButton: {
        padding: SPACING.sm,
    },
    searchContainer: {
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
    },
    searchInput: {
        marginBottom: 0,
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    bookCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    bookIconContainer: {
        width: 60,
        height: 80,
        backgroundColor: '#f0f0f0',
        borderRadius: 4,
        alignItems: 'center',
        justifyContent: 'center',
    },
    bookInfo: {
        flex: 1,
        gap: 4,
    },
    bookTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
    bookAuthor: {
        fontSize: FONT_SIZES.sm,
        fontStyle: 'italic',
    },
    detailsRow: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginTop: 2,
    },
    detail: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    detailText: {
        fontSize: FONT_SIZES.xs,
    },
    availabilityRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginTop: 4,
    },
    availabilityText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
});
