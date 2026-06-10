import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface CbcLearningArea {
  id: number;
  code?: string | null;
  name: string;
  description?: string | null;
  level_category?: string | null;
  levels?: string[] | null;
  is_core: boolean;
  strands_count: number;
}

export interface CbcStrand {
  id: number;
  code?: string | null;
  name: string;
  description?: string | null;
  level?: string | null;
  learning_area?: string | null;
  substrands_count: number;
}

export interface CbcSubstrandSummary {
  id: number;
  code?: string | null;
  name: string;
  description?: string | null;
  suggested_lessons?: number | null;
  competencies_count: number;
}

export interface CbcCompetency {
  id: number;
  code?: string | null;
  name: string;
  description?: string | null;
  indicators: string[];
  competency_level?: string | null;
}

export interface CbcSubstrandDetail {
  id: number;
  code?: string | null;
  name: string;
  description?: string | null;
  strand?: string | null;
  learning_area?: string | null;
  learning_outcomes: string[];
  key_inquiry_questions: string[];
  core_competencies: string[];
  values: string[];
  pclc: string[];
  suggested_lessons?: number | null;
  competencies: CbcCompetency[];
}

export const cbcApi = {
  listLearningAreas(): Promise<ApiResponse<CbcLearningArea[]>> {
    return apiClient.get<CbcLearningArea[]>('/cbc/learning-areas');
  },

  listStrands(params?: { learning_area_id?: number }): Promise<ApiResponse<CbcStrand[]>> {
    return apiClient.get<CbcStrand[]>('/cbc/strands', params);
  },

  listSubstrands(params?: { strand_id?: number }): Promise<ApiResponse<CbcSubstrandSummary[]>> {
    return apiClient.get<CbcSubstrandSummary[]>('/cbc/substrands', params);
  },

  getSubstrand(id: number): Promise<ApiResponse<CbcSubstrandDetail>> {
    return apiClient.get<CbcSubstrandDetail>(`/cbc/substrands/${id}`);
  },
};
