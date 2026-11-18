"use client";

import type { ImgHTMLAttributes, KeyboardEvent, MouseEvent } from "react";
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
  const resolved = editor ? editor.getImage(editKey, src) : src;
  const editing = Boolean(editor?.editing);

  if (!editing) {
    return (
      // eslint-disable-next-line @next/next/no-img-element
      <img src={resolved} alt={alt} className={className} onClick={onClick} {...rest} />
    );
  }

  const openPicker = (e?: MouseEvent | KeyboardEvent) => {
    e?.preventDefault();
    e?.stopPropagation();
    editor?.openMediaPicker({
      key: editKey,
      kind: "image",
      target: "image",
      currentUrl: resolved,
      title: "Replace photo",
    });
  };

  return (
    <span className={`rk-editable-image-wrap ${className}`}>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src={resolved}
        alt={alt}
        data-edit-key={editKey}
        className="rk-editable-image h-full w-full object-cover"
        onClick={(e) => {
          openPicker(e);
          onClick?.(e);
        }}
        {...rest}
      />
      <span
        role="button"
        tabIndex={0}
        className="rk-editable-image-badge"
        onClick={openPicker}
        onKeyDown={(e) => {
          if (e.key === "Enter" || e.key === " ") openPicker(e);
        }}
      >
        Replace media
      </span>
    </span>
  );
}
