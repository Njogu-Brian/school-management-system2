"use client";

import { useSearchParams } from "next/navigation";
import { useLiveEditorOptional } from "./LiveEditorProvider";

export function LiveEditorToolbar() {
  const editor = useLiveEditorOptional();
  const searchParams = useSearchParams();
  const editParam = searchParams.get("edit") === "1";

  if (!editor?.allowed) return null;

  // Enter only via ?edit=1 (or resume from localStorage after that). No public FAB.
  if (!editor.editing) {
    if (!editParam) return null;
    return (
      <button
        type="button"
        onClick={() => editor.setEditing(true)}
        className="rk-live-editor-fab"
        title="Edit website text and media"
      >
        Edit website
      </button>
    );
  }

  const textCount = Object.keys(editor.overrides.texts).length;
  const imageCount = Object.keys(editor.overrides.images).length;
  const videoCount = Object.keys(editor.overrides.videos).length;

  return (
    <div className="rk-live-editor-bar" role="region" aria-label="Website editor">
      <div className="rk-live-editor-bar__left">
        <span className="rk-live-editor-pill">Live editor</span>
        <span className="rk-live-editor-hint">
          Click highlighted text · Replace photos/videos from library or upload
        </span>
        <span className="rk-live-editor-meta">
          {textCount} text · {imageCount} photos · {videoCount} videos
          {editor.dirty ? " · unsaved" : editor.overrides.updatedAt ? " · saved" : ""}
        </span>
      </div>
      <div className="rk-live-editor-bar__right">
        <button
          type="button"
          className="rk-live-editor-btn"
          onClick={() => void editor.reload()}
          disabled={editor.saving}
        >
          Reload
        </button>
        <button
          type="button"
          className="rk-live-editor-btn rk-live-editor-btn--primary"
          onClick={() => void editor.save()}
          disabled={editor.saving || !editor.dirty}
        >
          {editor.saving ? "Saving…" : "Save draft"}
        </button>
        <button type="button" className="rk-live-editor-btn" onClick={() => editor.setEditing(false)}>
          Done
        </button>
      </div>
    </div>
  );
}
