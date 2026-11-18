"use client";

import { useLiveEditorOptional } from "./LiveEditorProvider";

type EditableVideoProps = {
  editKey: string;
  src: string;
  poster?: string;
  className?: string;
  label?: string;
};

/** Click-to-replace control for MP4 / Facebook Reel / external video URLs. */
export function EditableVideo({
  editKey,
  src,
  poster,
  className = "",
  label = "Replace video",
}: EditableVideoProps) {
  const editor = useLiveEditorOptional();
  const resolved = editor ? editor.getVideo(editKey, src) : src;
  const editing = Boolean(editor?.editing);
  const isFile = /\.(mp4|webm|mov)(\?|$)/i.test(resolved);

  if (!editing) {
    if (isFile) {
      return (
        <video
          className={className}
          poster={poster}
          autoPlay
          muted
          loop
          playsInline
          src={resolved}
        />
      );
    }
    return null;
  }

  return (
    <span className={`rk-editable-video-wrap ${className}`}>
      {isFile ? (
        <video className="rk-editable-video" poster={poster} muted playsInline src={resolved} />
      ) : poster ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={poster} alt="" className="rk-editable-video" />
      ) : (
        <span className="rk-editable-video rk-editable-video--empty">Video URL set</span>
      )}
      <span className="rk-editable-video-url" title={resolved}>
        {resolved}
      </span>
      <button
        type="button"
        className="rk-editable-image-badge"
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          editor?.openMediaPicker({
            key: editKey,
            kind: "video",
            target: "video",
            currentUrl: resolved,
            title: label,
          });
        }}
      >
        {label}
      </button>
    </span>
  );
}
