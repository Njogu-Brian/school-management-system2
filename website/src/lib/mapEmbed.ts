import { CONTACT } from "@/content/schoolContent";

export function mapEmbedSrc(googleMap?: string | null): string {
  if (!googleMap) {
    return CONTACT.mapsEmbed;
  }

  const match = googleMap.match(/src=["']([^"']+)["']/);
  if (match?.[1]) {
    return match[1];
  }

  if (googleMap.startsWith("http")) {
    return googleMap;
  }

  return CONTACT.mapsEmbed;
}
