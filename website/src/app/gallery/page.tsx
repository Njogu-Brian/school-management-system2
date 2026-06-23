import { RichPage, PageHero, SectionBlock, PhotoGrid, CtaBanner } from "@/components/layout/RichPage";
import { LEGACY_GALLERY, LEGACY_HEROES } from "@/content/legacyGallery";

export default function GalleryPage() {
  return (
    <RichPage>
      <PageHero
        title="Gallery"
        subtitle="A sneak peek of how Learning is Fun! — real photos from classrooms, sports, arts, devotions, and school events at Royal Kings Wangige."
        image={LEGACY_HEROES.talent}
      />
      <SectionBlock>
        <PhotoGrid photos={LEGACY_GALLERY.map((p) => ({ src: p.src, title: p.title, caption: p.caption }))} />
      </SectionBlock>
      <SectionBlock alt>
        <CtaBanner title="See It In Person" body="Book a campus tour and experience Royal Kings for yourself." href="/admissions" label="Book a Tour" />
      </SectionBlock>
    </RichPage>
  );
}
