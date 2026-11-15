"use client";

import { useLiveEditorOptional } from "./LiveEditorProvider";

export function LiveEditorToolbar() {
  const editor = useLiveEditorOptional();
  if (!editor?.allowed) return null;

  if (!editor.editing) {
    return (
      <button
        type="button"
        onClick={() => editor.setEditing(true)}
        className="rk-live-editor-fab"
        title="Edit website text and photos"
      >
        Edit website
      </button>
    );
  }

  const textCount = Object.keys(editor.overrides.texts).length;
  const imageCount = Object.keys(editor.overrides.images).length;

  return (
    <div className="rk-live-editor-bar" role="region" aria-label="Website editor">
      <div className="rk-live-editor-bar__left">
        <span className="rk-live-editor-pill">Live editor</span>
        <span className="rk-live-editor-hint">
          Click any highlighted text to edit · Click photos to replace
        </span>
        <span className="rk-live-editor-meta">
          {textCount} text · {imageCount} photos
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
        <button
          type="button"
          className="rk-live-editor-btn"
          onClick={() => editor.setEditing(false)}
        >
          Done
        </button>
      </div>
    </div>
  );
}
