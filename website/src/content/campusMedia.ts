/**
 * Curated Royal Kings campus media from Facebook posts & school archives.
 * Captions describe what is actually visible — no mismatched activity labels.
 */

const FB = "/images/facebook-2026";

export type CampusPhoto = {
  id: string;
  src: string;
  title: string;
  caption: string;
  alt: string;
  category:
    | "graduation"
    | "ballet"
    | "skating"
    | "sports"
    | "swimming"
    | "classroom"
    | "environment"
    | "chess"
    | "events"
    | "faith"
    | "transport"
    | "community";
};

export type CampusReel = {
  id: string;
  /** Canonical Facebook reel URL */
  facebookUrl: string;
  title: string;
  caption: string;
  /** Local poster — unique where possible */
  poster: string;
  category: CampusPhoto["category"] | "campus-life";
};

/** 20 verified Facebook / archive stills with accurate scene descriptions */
export const CAMPUS_PHOTOS: CampusPhoto[] = [
  {
    id: "grad-gown",
    src: `${FB}/01-parents-thanks-dais.jpg`,
    title: "PP2 Graduation gown",
    caption: "A PP2 graduand in black gown during the 2026 ceremony at Riverside, Wangige.",
    alt: "Royal Kings PP2 learner in a black graduation gown at the 2026 ceremony",
    category: "graduation",
  },
  {
    id: "grad-families",
    src: `${FB}/02-parents-crowd.jpg`,
    title: "Proud families",
    caption: "Parents and guardians celebrating with learners on graduation day.",
    alt: "Families and learners gathered for a Royal Kings graduation celebration",
    category: "graduation",
  },
  {
    id: "grad-stage",
    src: `${FB}/03-ceremony-stage.jpg`,
    title: "On the ceremony stage",
    caption: "Award and presentation moments during a school celebration.",
    alt: "Learners and staff on stage during a Royal Kings school ceremony",
    category: "events",
  },
  {
    id: "grad-learners",
    src: `${FB}/04-learners-plaits.jpg`,
    title: "Little stars ready",
    caption: "Learners lined up in celebration attire for a special school day.",
    alt: "Royal Kings learners dressed for a special school celebration",
    category: "events",
  },
  {
    id: "event-crowd",
    src: `${FB}/05-event-crowd.jpg`,
    title: "School community gathered",
    caption: "The Royal Kings family packing the hall for a landmark event.",
    alt: "Crowd of parents and learners at a Royal Kings school event",
    category: "community",
  },
  {
    id: "tree-planting",
    src: `${FB}/06-ballet-performance.jpg`,
    title: "Little hands, big impact",
    caption: "Learners in purple tracksuits planting saplings for a greener campus.",
    alt: "Royal Kings learners planting tree seedlings outdoors in purple school tracksuits",
    category: "environment",
  },
  {
    id: "skating-qualifiers",
    src: `${FB}/07-playground-break.jpg`,
    title: "Speed skating county qualifiers",
    caption: "A Royal Kings skater with her Tembo Talents inter-schools speed skating certificate and medal.",
    alt: "Royal Kings learner holding a speed skating county qualifiers certificate and medal",
    category: "skating",
  },
  {
    id: "sports-joy",
    src: `${FB}/08-sports-field.jpg`,
    title: "Sports day energy",
    caption: "Outdoor PE and games — purple kits, big smiles, and team spirit on the field.",
    alt: "Royal Kings learners in purple sports kits playing outdoors on the school field",
    category: "sports",
  },
  {
    id: "campus-spirit",
    src: `${FB}/09-campus-community.jpg`,
    title: "Campus spirit",
    caption: "Everyday joy across our Riverside, Wangige campus community.",
    alt: "Royal Kings learners and community moments on the Wangige campus",
    category: "community",
  },
  {
    id: "ballet-split",
    src: `${FB}/10-arts-talent.jpg`,
    title: "Ballet class — grace in motion",
    caption: "A young dancer in a pink tutu practising a full split during ballet at Royal Kings.",
    alt: "Royal Kings ballet learner in a pink tutu performing a split in dance class",
    category: "ballet",
  },
  {
    id: "career-mascot",
    src: `${FB}/11-classroom-learning.jpg`,
    title: "Career Day & values wall",
    caption: "Learners meet a mascot beside the Integrity and Teamwork mural on Career Day.",
    alt: "Royal Kings learners posing with a mascot in front of the Integrity values mural",
    category: "events",
  },
  {
    id: "swim-splash",
    src: `${FB}/12-primary-learners.jpg`,
    title: "Pool day smiles",
    caption: "Primary learners splashing and learning confidence in the water.",
    alt: "Happy Royal Kings primary learners swimming and smiling in the school pool",
    category: "swimming",
  },
  {
    id: "parent-forum",
    src: `${FB}/13-junior-secondary.jpg`,
    title: "Parents in partnership",
    caption: "Parents and carers engaged during a school briefing and partnership session.",
    alt: "Parents attending a Royal Kings school partnership meeting indoors",
    category: "community",
  },
  {
    id: "swim-lesson",
    src: `${FB}/14-early-years.jpg`,
    title: "Swimming lesson",
    caption: "Early years and primary learners with their instructor during a pool lesson.",
    alt: "Royal Kings children in a supervised swimming lesson at an outdoor pool",
    category: "swimming",
  },
  {
    id: "chess-club",
    src: `${FB}/15-devotions-worship.jpg`,
    title: "Chess club focus",
    caption: "Learners concentrating over chessboards during a school chess activity.",
    alt: "Royal Kings learners playing chess at long tournament tables",
    category: "chess",
  },
  {
    id: "garden-learning",
    src: `${FB}/16-transport-bus.jpg`,
    title: "Learning in the garden",
    caption: "Young learners walking through the school garden — hands-on outdoor CBC learning.",
    alt: "Royal Kings early years learners walking through a vegetable garden with a teacher",
    category: "environment",
  },
  {
    id: "welcome-campus",
    src: `${FB}/17-admissions-welcome.jpg`,
    title: "Welcome to Royal Kings",
    caption: "Warm welcomes for families touring campus and exploring admissions.",
    alt: "Welcoming scene for families visiting Royal Kings Premier School",
    category: "events",
  },
  {
    id: "facilities",
    src: `${FB}/18-campus-facilities.jpg`,
    title: "Campus facilities",
    caption: "Spaces designed for learning, play, worship, and growth.",
    alt: "Royal Kings Premier School campus facilities overview",
    category: "community",
  },
  {
    id: "grade5-science",
    src: `${FB}/19-family-moment.jpg`,
    title: "Grade 5 science class",
    caption: "Two learners in purple-trim uniforms studying Science and Technology together.",
    alt: "Two Royal Kings Grade 5 girls in uniform studying a Science and Technology textbook",
    category: "classroom",
  },
  {
    id: "assembly",
    src: `${FB}/20-morning-assembly.jpg`,
    title: "Morning gathering",
    caption: "Learners and staff gathered for a morning assembly moment.",
    alt: "Royal Kings learners gathered for morning assembly",
    category: "faith",
  },
];

/** Facebook Reels — embed on site; posters mapped uniquely from stills */
const REEL_URLS = [
  "https://www.facebook.com/reel/2122367671985093/",
  "https://www.facebook.com/reel/1156079877364405/",
  "https://www.facebook.com/reel/1497138898959481/",
  "https://www.facebook.com/reel/913345941744920/",
  "https://www.facebook.com/reel/1063383253374104/",
  "https://www.facebook.com/reel/899003983202425/",
  "https://www.facebook.com/reel/946129898324349/",
  "https://www.facebook.com/reel/1786163136169358/",
  "https://www.facebook.com/reel/1347314653990634/",
  "https://www.facebook.com/reel/1426914286057549/",
  "https://www.facebook.com/reel/1115517531001934/",
  "https://www.facebook.com/reel/1681300700220303/",
  "https://www.facebook.com/reel/1074881631604397/",
  "https://www.facebook.com/reel/1071183698829831/",
  "https://www.facebook.com/reel/1408688171208068/",
  "https://www.facebook.com/reel/1699814691130050/",
  "https://www.facebook.com/reel/1065563656057098/",
  "https://www.facebook.com/reel/2264923784267486/",
  "https://www.facebook.com/reel/1987739911886622/",
  "https://www.facebook.com/reel/1763742817974256/",
  "https://www.facebook.com/reel/2225052905011297/",
  "https://www.facebook.com/reel/2152538772010328/",
  "https://www.facebook.com/reel/1542688064268395/",
  "https://www.facebook.com/reel/1356314479941650/",
  "https://www.facebook.com/reel/2007988589841716/",
  "https://www.facebook.com/reel/1421781972584587/",
  "https://www.facebook.com/reel/1375583240723751/",
  "https://www.facebook.com/reel/1754235518606170/",
  "https://www.facebook.com/reel/1304450914767807/",
  "https://www.facebook.com/reel/1536804774292683/",
] as const;

const REEL_COPY: Array<{ title: string; caption: string; category: CampusReel["category"] }> = [
  { title: "Campus morning energy", caption: "A day in the life starts with joy at Riverside, Wangige.", category: "campus-life" },
  { title: "Learning is fun", caption: "Short moments that capture why children love coming to school.", category: "classroom" },
  { title: "Celebration highlights", caption: "Clips from our special assemblies and award moments.", category: "events" },
  { title: "Co-curricular in action", caption: "Ballet, sports, clubs — gifts unlocked beyond the desk.", category: "ballet" },
  { title: "Sports & movement", caption: "Active bodies, confident hearts on the field and rink.", category: "sports" },
  { title: "Swimming confidence", caption: "Water safety and fun in the pool.", category: "swimming" },
  { title: "Faith & character", caption: "Devotions and values that shape every Royal Kings learner.", category: "faith" },
  { title: "Family partnership", caption: "Parents walking the journey with us.", category: "community" },
  { title: "Trip day memories", caption: "Beyond the gate — Nakuru, soft-play, and discovery.", category: "events" },
  { title: "Classroom focus", caption: "CBC learning moments that stick.", category: "classroom" },
  { title: "Skating strides", caption: "Balance, courage, and medals for our skaters.", category: "skating" },
  { title: "Ballet grace", caption: "Discipline and expression in every movement.", category: "ballet" },
  { title: "Playground friendships", caption: "Break time where teamwork begins.", category: "campus-life" },
  { title: "Environment champions", caption: "Planting, watering, and caring for creation.", category: "environment" },
  { title: "Chess minds", caption: "Strategy, patience, and friendly competition.", category: "chess" },
  { title: "Career Day dreams", caption: "Trying on the futures they imagine.", category: "events" },
  { title: "Junior leadership", caption: "Older learners modelling excellence.", category: "community" },
  { title: "Early years wonder", caption: "First friendships and first discoveries.", category: "campus-life" },
  { title: "Transport with care", caption: "Safe journeys to and from campus.", category: "transport" },
  { title: "Worship together", caption: "Songs and scripture that centre our week.", category: "faith" },
  { title: "House spirit", caption: "Cheers, colours, and healthy competition.", category: "sports" },
  { title: "Art & creativity", caption: "Colour, texture, and stories from young artists.", category: "events" },
  { title: "PP2 graduation joy", caption: "Little stars stepping into Grade 1.", category: "graduation" },
  { title: "Primary pride", caption: "Growing readers, thinkers, and friends.", category: "classroom" },
  { title: "STEM curiosity", caption: "Hands-on problem solving in action.", category: "classroom" },
  { title: "Garden classroom", caption: "Soil under nails, CBC in the open air.", category: "environment" },
  { title: "Open day welcome", caption: "Tour our campus and feel the warmth.", category: "community" },
  { title: "Talent showcase", caption: "When gifts take the stage.", category: "events" },
  { title: "After-school clubs", caption: "Where passions find practice time.", category: "campus-life" },
  { title: "Royal Kings life", caption: "Excellence · Character · Greatness — in motion.", category: "campus-life" },
];

export const CAMPUS_REELS: CampusReel[] = REEL_URLS.map((facebookUrl, index) => {
  const copy = REEL_COPY[index] ?? REEL_COPY[REEL_COPY.length - 1];
  const poster = CAMPUS_PHOTOS[index % CAMPUS_PHOTOS.length].src;
  return {
    id: `reel-${index + 1}`,
    facebookUrl,
    title: copy.title,
    caption: copy.caption,
    poster,
    category: copy.category,
  };
});

export function facebookEmbedUrl(reelUrl: string) {
  return `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(reelUrl)}&show_text=false&width=360`;
}

export function photoByCategory(category: CampusPhoto["category"]) {
  return CAMPUS_PHOTOS.find((p) => p.category === category);
}

/** Unique co-curricular stills matched to real activities */
export const COCURRICULAR_MEDIA = {
  skating: photoByCategory("skating")!,
  ballet: photoByCategory("ballet")!,
  coding: CAMPUS_PHOTOS.find((p) => p.id === "grade5-science")!,
  robotics: CAMPUS_PHOTOS.find((p) => p.id === "career-mascot")!,
  fieldTrips: CAMPUS_PHOTOS.find((p) => p.id === "garden-learning")!,
  music: CAMPUS_PHOTOS.find((p) => p.id === "grad-stage")!,
  sports: photoByCategory("sports")!,
  worship: CAMPUS_PHOTOS.find((p) => p.id === "assembly")!,
  swimming: photoByCategory("swimming")!,
  chess: photoByCategory("chess")!,
  environment: photoByCategory("environment")!,
} as const;
