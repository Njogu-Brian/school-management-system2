"use client";

import { Suspense, type ReactNode } from "react";
import { LiveEditorProvider } from "./LiveEditorProvider";
import { LiveEditorToolbar } from "./LiveEditorToolbar";

export function LiveEditorRoot({ children }: { children: ReactNode }) {
  return (
    <Suspense fallback={children}>
      <LiveEditorProvider>
        {children}
        <LiveEditorToolbar />
      </LiveEditorProvider>
    </Suspense>
  );
}
