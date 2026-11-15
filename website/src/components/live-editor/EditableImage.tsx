"use client";

import { useRef, useState, type ImgHTMLAttributes } from "react";
import { useLiveEditorOptional } from "./LiveEditorProvider";

type EditableImageProps = {
  editKey: string;
  src: string;
  alt: string;
} & Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "alt">;

export function EditableImage({
  editKey,
  src,
  alt,
  className = "",
  onClick,
  ...rest
}: EditableImageProps) {
  const editor = useLiveEditorOptional();
  const inputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const resolved = editor ? editor.getImage(editKey, src) : src;
  const editing = Boolean(editor?.editing);

  const pick = () => {
    if (!editing) return;
    inputRef.current?.click();
  };

  const onFile = async (file: File | undefined) => {
    if (!file || !editor) return;
    setError(null);
    setUploading(true);
    try {
      await editor.uploadImage(editKey, file);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Upload failed");
    } finally {
      setUploading(false);
      if (inputRef.current) inputRef.current.value = "";
    }
  };

  if (!editing) {
    return (
      // eslint-disable-next-line @next/next/no-img-element
      <img src={resolved} alt={alt} className={className} onClick={onClick} {...rest} />
    );
  }

  return (
    <span className={`rk-editable-image-wrap ${className}`}>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src={resolved}
        alt={alt}
        data-edit-key={editKey}
        className="rk-editable-image h-full w-full object-cover"
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          pick();
          onClick?.(e);
        }}
        {...rest}
      />
      <span
        role="button"
        tabIndex={0}
        className="rk-editable-image-badge"
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          pick();
        }}
        onKeyDown={(e) => {
          if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            e.stopPropagation();
            pick();
          }
        }}
      >
        {uploading ? "Uploading…" : "Replace photo"}
      </span>
      {error ? <span className="rk-editable-image-error">{error}</span> : null}
      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif"
        className="hidden"
        onChange={(e) => void onFile(e.target.files?.[0])}
      />
    </span>
  );
}
