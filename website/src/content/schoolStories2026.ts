import { assetPath } from "@/lib/assetPath";

const google = (file: string) => assetPath(`/images/campus-life-2026/curated/${file}`);
/** Legacy CDN heroes for Facebook posts (no Google Photos mix-up) */
const legacy = (file: string) => `https://royalkingsschools.sc.ke/assets/images/${file}`;

export type StoryGalleryPhoto = { src: string; title: string; caption?: string };
export type StoryPost = {
  id: number;
  slug: string;
  title: string;
  excerpt: string;
  published_at: string;
  featured_image: string;
  bodyHtml: string;
  gallery: StoryGalleryPhoto[];
};

/** Google Photos only — preschool, lower primary, Nakuru, Career Day albums */
export const CAMPUS_GALLERY: StoryGalleryPhoto[] = [
  { src: google("05-preschool-trip.jpg"), title: "Preschool adventure", caption: "Soft-play smiles on our preschool trip" },
  { src: google("02-preschool-trip.jpg"), title: "Little explorers", caption: "Discovery beyond the classroom" },
  { src: google("07-preschool-trip.jpg"), title: "Trip day joy", caption: "Preschool learners making memories" },
  { src: google("09-preschool-trip.jpg"), title: "Growing together", caption: "Friendship on every outing" },
  { src: google("01-preschool-trip.jpg"), title: "Play with purpose", caption: "Active bodies, joyful hearts" },
  { src: google("11-lower-primary-trip.jpg"), title: "Lower primary outing", caption: "Curious minds in the open air" },
  { src: google("13-lower-primary-trip.jpg"), title: "Happy learners", caption: "Smiles that say Learning Is Fun" },
  { src: google("15-lower-primary-trip.jpg"), title: "Focused and ready", caption: "Lower primary learners on trip day" },
  { src: google("17-lower-primary-trip.jpg"), title: "Team spirit", caption: "Learning side by side" },
  { src: google("20-lower-primary-trip.jpg"), title: "Outdoor learning", caption: "CBC comes alive outside the classroom" },
  { src: google("21-nakuru-trip.jpg"), title: "Nakuru horizons", caption: "Grade 4–9 field experience" },
  { src: google("23-nakuru-trip.jpg"), title: "Beyond the gate", caption: "Excursions that widen every child’s world" },
  { src: google("25-nakuru-trip.jpg"), title: "Wildlife wonders", caption: "Buffalo sighting on our Nakuru trip" },
  { src: google("27-nakuru-trip.jpg"), title: "Nature classroom", caption: "Learning Kenya’s landscapes first-hand" },
  { src: google("30-nakuru-trip.jpg"), title: "Adventure & awe", caption: "Memories that last a lifetime" },
  { src: google("32-nakuru-trip.jpg"), title: "Field study", caption: "Grades 4–9 exploring together" },
  { src: google("33-career-day.jpg"), title: "Career Day", caption: "Dreaming big in costume and character" },
  { src: google("35-career-day.jpg"), title: "Future doctors & builders", caption: "Career Day role-play at Royal Kings" },
  { src: google("37-career-day.jpg"), title: "Confidence on show", caption: "Every child discovering their gifts" },
  { src: google("40-career-day.jpg"), title: "Ambition starts early", caption: "Career Day celebration" },
];

/**
 * Facebook posts = story copy only (no mismatched Google Photos galleries).
 * Google Photos albums = dedicated trip / Career Day stories below.
 */
export const SCHOOL_STORIES: StoryPost[] = [
  {
    id: 20261104,
    slug: "pp2-graduation-2026",
    title: "PP2 Graduation 2026: Our Little Stars Are Shining Bright",
    excerpt:
      "Celebrating our PP2 Class of 2026 as they step proudly into Grade 1 — certificates, culture, and proud family moments at Riverside, Wangige.",
    published_at: "3 November 2026",
    featured_image: assetPath("/images/pp2-graduation-2026.jpg"),
    bodyHtml: `
<p>On Tuesday, 3rd November 2026, Royal Kings Premier School celebrated a milestone of greatness as our <strong>PP2 Class of 2026</strong> graduated — little stars ready for their next big step into Grade 1.</p>
<p>From confident smiles and proud moments on stage to certificates awarded by our Guest of Honour and School Directors, the day was filled with joy, culture, and the promise of bright futures.</p>
<p>The journey continues, little stars. Keep shining!</p>
`.trim(),
    gallery: [],
  },
  {
    id: 20261014,
    slug: "ballet-grace-elegance-confidence",
    title: "Grace, Elegance & Confidence Through Ballet",
    excerpt:
      "Ballet at Royal Kings is a journey of creativity, discipline, balance, and self-expression — helping every child discover their gifts.",
    published_at: "14 October 2026",
    featured_image: legacy("441539411-1116959156184234-4545625582975216690-n-1020x765.jpg"),
    bodyHtml: `
<p>Ballet at Royal Kings Premier School is a beautiful journey of creativity, discipline, balance, and self-expression. Our young dancers grow in coordination, flexibility, and artistic talent — and in the quiet confidence that comes from practice and performance.</p>
<p>We believe every child deserves an opportunity to discover their gifts and reach their full potential. That is why co-curricular programmes like ballet sit at the heart of our <em>Learning Is Fun</em> philosophy.</p>
<p><strong>2027 admissions are open.</strong> Give your child a place to learn, grow, explore, and shine. Call/WhatsApp 0719 396 233 · Riverside, Wangige.</p>
`.trim(),
    gallery: [],
  },
  {
    id: 2026101402,
    slug: "little-hands-big-impact",
    title: "Little Hands, Big Impact — Growing a Greener Tomorrow",
    excerpt:
      "Grade 3 learners plant, water, and care for our environment — building responsible citizens beyond books and classrooms.",
    published_at: "14 October 2026",
    featured_image: legacy("353656422-744351687689951-6059922830462771828-n-1-2048x1365.jpg"),
    bodyHtml: `
<p>Small hands can make a big difference. Our Grade 3 learners are growing into environmental champions — planting, watering, and nurturing nature so we leave a healthier world for tomorrow.</p>
<p>Through hands-on CBC activities they learn how plants clean the air, prevent soil erosion, and beautify our surroundings. Education at Royal Kings is not only about books; it is about building responsible citizens who care for the world around them.</p>
<p>Royal Kings School — Where Learning Is Fun. <strong>2027 enrolment ongoing.</strong></p>
`.trim(),
    gallery: [],
  },
  {
    id: 20261013,
    slug: "break-time-at-royal-kings",
    title: "Break Time at Royal Kings — Learn · Grow · Excel",
    excerpt:
      "Break time is where friendships form — connect, share, play, and grow together beyond the classroom.",
    published_at: "13 October 2026",
    featured_image: legacy("424586410-911309330994185-5108768823401027837-n-2048x1365.jpg"),
    bodyHtml: `
<p>Break time at Royal Kings is more than a pause between lessons. It is a chance for learners to connect, share, play, and grow together — building lasting friendships, teamwork, respect, kindness, and cooperation.</p>
<p>We believe great learning goes beyond the classroom. Happy playgrounds grow confident hearts.</p>
<p>Royal Kings School — Learn · Grow · Excel!</p>
`.trim(),
    gallery: [],
  },
  {
    id: 20261008,
    slug: "creativity-in-every-stroke",
    title: "Creativity in Every Stroke — Art by Our Learners",
    excerpt:
      "From nature-inspired drawings to textured masterpieces, every piece tells a unique story from a young artist’s mind.",
    published_at: "8 October 2026",
    featured_image: legacy("20241024-img-1319-5184x3456.jpg"),
    bodyHtml: `
<p>At Royal Kings Premier School we nurture creativity, confidence, and talent. Learners explore imagination through colour, texture, and design — flowers, wildlife, and stories told with every stroke.</p>
<p>Art gives our learners a chance to learn by creating. Every piece tells a unique story from the mind of a young artist.</p>
`.trim(),
    gallery: [],
  },
  {
    id: 20261005,
    slug: "happy-learners-great-memories",
    title: "Happy Learners Make Great Memories",
    excerpt:
      "Every smile, friendship, and learning experience creates memories that last a lifetime at Riverside, Wangige.",
    published_at: "5 October 2026",
    featured_image: legacy("377541946-804798248311961-8642644574650503623-n-2048x1365.jpg"),
    bodyHtml: `
<p>At Royal Kings School, every smile, every friendship, and every learning experience creates memories that last a lifetime. Children learn best when they feel happy, valued, inspired, and free to discover their talents.</p>
<p>Royal Kings School — Where Learning Is Fun!<br/>
<strong>2027 enrolment &amp; intake ongoing</strong><br/>
+254 719 396 233 · Riverside, Wangige</p>
`.trim(),
    gallery: [],
  },
  {
    id: 20260726,
    slug: "the-royal-kings-advantage",
    title: "The Royal Kings Advantage — A Sure Foundation",
    excerpt:
      "CBC-compliant, Christian-centred, caring facilitators, safe transport, rich co-curriculars — and learning that is fun.",
    published_at: "26 July 2026",
    featured_image: assetPath("/images/2027-admissions-open.jpg"),
    bodyHtml: `
<p>Give your child the Royal Kings Advantage — where learning is fun, purposeful, and built on a sure foundation.</p>
<ul>
<li>CBC compliant</li>
<li>A Christian school nurturing values &amp; confidence</li>
<li>Loving, energetic &amp; trained facilitators</li>
<li>Strong academic support and confident readers</li>
<li>Affordable fees</li>
<li>Reliable, safe school transport</li>
<li>Wide variety of extracurricular activities</li>
<li>A caring environment that nurtures every learner’s potential</li>
</ul>
<p>Riverside, Wangige · 0719 396 233 · <a href="/admissions/apply">Apply for 2027</a></p>
`.trim(),
    gallery: [],
  },
  {
    id: 20260803,
    slug: "learning-beyond-the-gate-school-trips-2026",
    title: "Learning Beyond the Gate — School Trips 2026",
    excerpt:
      "Preschool soft-play adventures, lower primary outings, and a Nakuru wildlife experience for Grades 4–9.",
    published_at: "3 August 2026",
    featured_image: google("25-nakuru-trip.jpg"),
    bodyHtml: `
<p>Some of the best lessons happen beyond our Riverside campus. In 2026 our learners explored soft-play adventures for preschool, outdoor discovery for lower primary, and a memorable Nakuru trip for Grades 4–9 — buffalo, fever trees, and wide open skies included.</p>
<p>Field experiences deepen CBC learning: observation, teamwork, courage, and wonder. That is the Royal Kings way — Learning Is Fun, on campus and beyond.</p>
`.trim(),
    gallery: CAMPUS_GALLERY.filter((p) => /preschool|lower-primary|nakuru/i.test(p.src)),
  },
  {
    id: 20250801,
    slug: "career-day-dreams-in-action",
    title: "Career Day — Dreams in Action",
    excerpt:
      "Doctors, engineers, and builders for a day — Career Day helps every Royal Kings learner imagine their future with confidence.",
    published_at: "1 August 2025",
    featured_image: google("35-career-day.jpg"),
    bodyHtml: `
<p>White coats, hard hats, and big smiles — Career Day at Royal Kings turns imagination into practice. Learners try on roles, ask questions, and discover that their future can be as bold as their dreams.</p>
<p>We nurture not only bright minds, but confident speakers, caring hearts, and purposeful futures.</p>
`.trim(),
    gallery: CAMPUS_GALLERY.filter((p) => /career-day/i.test(p.src)),
  },
];

export const STORY_BY_SLUG: Record<string, StoryPost> = Object.fromEntries(
  SCHOOL_STORIES.map((s) => [s.slug, s])
);

export const STORY_LIST_POSTS = SCHOOL_STORIES.map((s) => ({
  id: s.id,
  slug: s.slug,
  title: s.title,
  excerpt: s.excerpt,
  published_at: s.published_at,
  featured_image: s.featured_image,
}));
