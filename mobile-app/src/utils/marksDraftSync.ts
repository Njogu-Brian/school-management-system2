import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import { academicsApi } from '@api/academics.api';

const QUEUE_KEY = '@marks_draft_queue';

export type MarksQueueItem = {
    id: string;
    type: 'bulk' | 'matrix' | 'submit';
    payload: Record<string, unknown>;
    createdAt: number;
};

async function loadQueue(): Promise<MarksQueueItem[]> {
    const raw = await AsyncStorage.getItem(QUEUE_KEY);
    if (!raw) return [];
    try {
        return JSON.parse(raw) as MarksQueueItem[];
    } catch {
        return [];
    }
}

async function persistQueue(items: MarksQueueItem[]): Promise<void> {
    await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(items));
}

export async function removeMarksDraftQueueItem(id: string): Promise<void> {
    const queue = await loadQueue();
    await persistQueue(queue.filter((q) => q.id !== id));
}

export async function queueMarksDraft(
    item: Omit<MarksQueueItem, 'createdAt'> & { createdAt?: number }
): Promise<void> {
    const queue = await loadQueue();
    const next: MarksQueueItem = {
        ...item,
        createdAt: item.createdAt ?? Date.now(),
    };
    const filtered = queue.filter((q) => q.id !== next.id);
    filtered.push(next);
    await persistQueue(filtered);
}

export async function flushMarksDraftQueue(): Promise<{ flushed: number; failed: number }> {
    const net = await NetInfo.fetch();
    if (!net.isConnected) {
        return { flushed: 0, failed: 0 };
    }

    const queue = await loadQueue();
    if (queue.length === 0) {
        return { flushed: 0, failed: 0 };
    }

    const remaining: MarksQueueItem[] = [];
    let flushed = 0;
    let failed = 0;

    for (const item of [...queue].sort((a, b) => a.createdAt - b.createdAt)) {
        try {
            if (item.type === 'bulk') {
                const res = await academicsApi.enterMarks(item.payload as Parameters<typeof academicsApi.enterMarks>[0]);
                if (!res.success) throw new Error('bulk save failed');
            } else if (item.type === 'matrix') {
                const res = await academicsApi.enterMarksMatrix(
                    item.payload as Parameters<typeof academicsApi.enterMarksMatrix>[0]
                );
                if (!res.success) throw new Error('matrix save failed');
            } else if (item.type === 'submit') {
                const examId = item.payload.exam_id as number;
                const res = await academicsApi.submitExamMarks(examId);
                if (!res.success) throw new Error('submit failed');
            }
            flushed++;
        } catch {
            failed++;
            remaining.push(item);
        }
    }

    await persistQueue(remaining);
    return { flushed, failed };
}

export async function getPendingMarksQueueCount(): Promise<number> {
    return (await loadQueue()).length;
}
