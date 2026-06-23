import type { ImgHTMLAttributes } from "react";

export interface ResponsiveImageProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "srcSet"> {
  src: string;
  srcSet?: string;
  alt: string;
  sizes?: string;
  priority?: boolean;
}

/**
 * Renders an image with optional WebP srcset from the CMS optimization pipeline.
 */
export function ResponsiveImage({
  src,
  srcSet,
  alt,
  sizes = "(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw",
  className,
  loading,
  priority,
  ...rest
}: ResponsiveImageProps) {
  return (
    // eslint-disable-next-line @next/next/no-img-element
    <img
      src={src}
      srcSet={srcSet}
      sizes={srcSet ? sizes : undefined}
      alt={alt}
      className={className}
      loading={priority ? "eager" : loading ?? "lazy"}
      decoding="async"
      {...rest}
    />
  );
}
