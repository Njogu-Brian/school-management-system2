/** Aligns with `@erp/ui` `WidgetDisplayState` (core cannot depend on UI). */
export type WidgetQueryDisplayState = 'loading' | 'empty' | 'error' | 'success';

/** Map TanStack Query status flags to WidgetShell display state. */
export function mapQueryToWidgetState(input: {
  isPending: boolean;
  isLoading: boolean;
  isError: boolean;
  isSuccess: boolean;
  isEmpty?: boolean;
}): WidgetQueryDisplayState {
  if (input.isPending || input.isLoading) {
    return 'loading';
  }
  if (input.isError) {
    return 'error';
  }
  if (input.isSuccess && input.isEmpty) {
    return 'empty';
  }
  if (input.isSuccess) {
    return 'success';
  }
  return 'loading';
}
