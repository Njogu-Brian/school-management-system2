"use client";

import {
  useEffect,
  useRef,
  type ElementType,
  type HTMLAttributes,
  type KeyboardEvent,
} from "react";
import { useLiveEditorOptional } from "./LiveEditorProvider";

type EditableTextProps = {
  editKey: string;
  as?: ElementType;
  children: string;
  multiline?: boolean;
  className?: string;
} & Omit<HTMLAttributes<HTMLElement>, "children" | "contentEditable" | "suppressContentEditableWarning">;

export function EditableText({
  editKey,
  as: Tag = "span",
  children,
  multiline = false,
  className = "",
  ...rest
}: EditableTextProps) {
  const editor = useLiveEditorOptional();
  const ref = useRef<HTMLElement>(null);
  const value = editor ? editor.getText(editKey, children) : children;
  const editing = Boolean(editor?.editing);

  useEffect(() => {
    if (!ref.current) return;
    if (ref.current.textContent !== value) {
      ref.current.textContent = value;
    }
  }, [value]);

  const commit = () => {
    if (!editor || !ref.current) return;
    const next = (ref.current.innerText || "").replace(/\u00a0/g, " ").trimEnd();
    if (next !== value) editor.setText(editKey, next);
  };

  const onKeyDown = (e: KeyboardEvent<HTMLElement>) => {
    if (!editing) return;
    if (!multiline && e.key === "Enter") {
      e.preventDefault();
      (e.currentTarget as HTMLElement).blur();
    }
    if (e.key === "Escape") {
      if (ref.current) ref.current.textContent = value;
      (e.currentTarget as HTMLElement).blur();
    }
  };

  return (
    <Tag
      ref={ref}
      data-edit-key={editKey}
      className={`${className}${editing ? " rk-editable-text" : ""}`}
      contentEditable={editing}
      suppressContentEditableWarning
      onBlur={editing ? commit : undefined}
      onKeyDown={editing ? onKeyDown : undefined}
      {...rest}
    >
      {value}
    </Tag>
  );
}
