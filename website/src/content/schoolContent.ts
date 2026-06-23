/**
 * Curated from https://royalkingsschools.sc.ke/
 * Logo purple sampled: #B400FF
 */

const BASE = "https://royalkingsschools.sc.ke/assets/images";

export const BRAND = {
  name: "Royal Kings Premier School LTD",
  shortName: "Royal Kings Premier School",
  legalName: "Royal Kings Premier School LTD",
  tagline: "Where Little Steps Grow Into Great Futures",
  heroHeadline: "Building a Sure Foundation for Lifelong Learning",
  heroSubheadline: "Where Learning is Fun!",
  heroIntro:
    "A warm Christian school where children grow in faith, confidence, and excellence from age 3 to Grade 9.",
  founded: 2006,
  location: "Wangige, Kiambu County, Kenya",
  purpleBright: "#B400FF",
  purple: "#9B1FE8",
  purpleDark: "#5C0D96",
  purpleDeep: "#4A0078",
  gold: "#D4AF37",
  logoUrl: `${BASE}/royal-logo-small-192x192.png`,
};

export const SOCIAL = {
  facebook: "https://web.facebook.com/royalkingschools",
  instagram: "https://www.instagram.com/royalkings_schools/",
  tiktok: "https://www.tiktok.com/@royalkings_schools",
};

export const CONTACT = {
  phone: "+254 719 396 233",
  phoneRaw: "254719396233",
  whatsapp: "254719396233",
  email: "info@royalkingsschools.sc.ke",
  admissionsEmail: "admissions@royalkingsschools.sc.ke",
  address: "Wangige, Kiambu County, Kenya",
  postal: "P.O. BOX 10804-00100, Nairobi",
  hours: {
    weekdays: "Mon – Fri: 7:30 AM – 5:00 PM",
    saturday: "Sat: 8:30 AM – 1:00 PM",
  },
  mapsUrl: "https://www.google.com/maps/search/?api=1&query=Royal+Kings+Premier+School+Wangige+Kenya",
  mapsEmbed:
    "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.0!2d36.705!3d-1.245!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sRoyal%20Kings%20School%20Wangige!5e0!3m2!1sen!2ske!4v1719000000000!5m2!1sen!2ske",
};

export const LEGACY_IMAGES = {
  logo: `${BASE}/royal-logo-small-192x192.png`,
  campus: `${BASE}/whatsapp-image-2024-10-28-at-18.13.54-cb80dc6a-705x999.jpg`,
  admissions: `${BASE}/2025-admissions-815x815.png`,
  classroom: `${BASE}/332419888-1246340212647102-4730361110570400332-n-1101x734.jpeg`,
  students: `${BASE}/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg`,
  family1: `${BASE}/family-grandparents-parents-kids-cartoons-18591-52188-160x160.jpg`,
  family2: `${BASE}/happy-family-portrait-vectorized-character-design-23-2148163542-160x160.jpg`,
};

export const GALLERY_PHOTOS = [
  { src: LEGACY_IMAGES.campus, title: "Our Beautiful Campus", caption: "Royal Kings Premier School, Wangige" },
  { src: LEGACY_IMAGES.classroom, title: "Learning in Action", caption: "Engaged learners in our classrooms" },
  { src: LEGACY_IMAGES.students, title: "Happy Students", caption: "Joyful moments on campus" },
  { src: LEGACY_IMAGES.admissions, title: "2025 Admissions Open", caption: "Enroll your child today" },
  { src: LEGACY_IMAGES.campus, title: "Campus Life", caption: "Where learning is fun" },
  { src: LEGACY_IMAGES.students, title: "Growing Together", caption: "Christian family-centered education" },
];

export const STATS = [
  { value: "2006", label: "Founded" },
  { value: "355+", label: "Learners" },
  { value: "Creche–G9", label: "Levels" },
  { value: "20 yrs", label: "Excellence" },
];

export const INTRO = {
  legacy:
    "Welcome to Royal Kings Premier School, where education is our legacy and our passion. We have proudly served as a leading child-centered institution for nearly two decades, shaping young minds and nurturing future leaders since 2006. Our commitment to excellence in education is unwavering, and our track record speaks volumes.",
  empowering:
    "Welcome to the realm of Royal Kings Premier School, where education meets excellence in a whirlwind of Christian values and boundless potential. Our institution in Wangige, Kenya, is not just a school — it is a vibrant ecosystem where learners thrive under the guidance of dedicated professionals.",
  holistic:
    "Join us on a journey of holistic education that sparks curiosity, ignites passion, and shapes tomorrow's leaders. We are dedicated to nurturing well-rounded individuals, emphasizing academic achievement alongside emotional, spiritual, and social development.",
  aboutFull: `Royal Kings Premier School LTD is a Christian-centered institution serving families across Wangige, Lower Kabete, Kikuyu, Gitaru, Uthiru and surrounding communities. Since 2006, we have built a reputation for nurturing learners from Creche through Grade 9 in an environment where faith, family, and academic excellence go hand in hand.

We believe every child is fearfully and wonderfully made. Our teachers know learners by name, our parents are partners in the journey, and our campus feels like an extension of home. From morning devotions to afternoon sports, from CBC classrooms to November Talent Camp — Royal Kings is where little steps grow into great futures.`,
};

export const MISSION = {
  title: "Our Mission",
  body: "At Royal Kings Premier School, we ignite a passion for learning and growth by providing a holistic educational foundation that nurtures Christian values and moral character. Our friendly learning environment is where dedicated professionals guide students to reach their full potential. Join us on a journey of discovery and empowerment, where every learner is valued and supported to shine bright.",
};

export const VISION = {
  title: "Empowering Minds, Shaping Futures",
  body: "To be the leading Christian school in the region — recognised for academic excellence, character formation, and a warm family culture that produces confident, compassionate leaders.",
};

export const PILLARS = [
  { title: "Holistic Learning", description: "We go beyond academic success to develop well-rounded individuals, preparing our learners for today and a future where adaptability and innovation are key.", icon: "🌱" },
  { title: "Nurturing Environment", description: "Discover a nurturing environment where every child's potential is unlocked and dreams are realized.", icon: "🏡" },
  { title: "Collaborative Community", description: "We believe in the power of collaboration, inviting parents and professionals to join our school family.", icon: "🤝" },
  { title: "Core Christian Values", description: "Kindness, respect, truth, and love form the bedrock of our educational journey.", icon: "✝️" },
  { title: "Character Building", description: "Foster integrity and compassion through intentional character development programmes.", icon: "💎" },
  { title: "Future Leaders", description: "Prepare for success in a rapidly changing world with skills, confidence, and faith.", icon: "👑" },
];

export const LEGACY_TESTIMONIALS = [
  { quote: "Royal Kings School transformed my child's life, fostering a love for learning and instilling strong values.", role: "Parent" },
  { quote: "I've never seen my daughter happier since she started at Royal Kings School. It's truly a special place.", role: "Parent" },
  { quote: "The teachers go above and beyond to ensure every student thrives academically and personally.", role: "Parent" },
  { quote: "Choosing Royal Kings for my child was the best decision I ever made. The growth is remarkable!", role: "Parent" },
  { quote: "My son's confidence has soared since joining. The supportive community is truly exceptional.", role: "Parent" },
  { quote: "Royal Kings is not just a school; it's a family that nurtures, inspires, and empowers every student.", role: "Parent" },
];

export const HIGHLIGHTS = [
  { title: "November Talent Camp", subtitle: "Holiday enrichment & creative discovery for all ages", image: LEGACY_IMAGES.students },
  { title: "2025 Admissions Open", subtitle: "Limited spaces — enroll your child today", image: LEGACY_IMAGES.admissions },
  { title: "Nearly 20 Years", subtitle: "Shaping leaders since 2006 in Wangige", image: LEGACY_IMAGES.campus },
];

export const FEES_NOTE = {
  title: "School Fees & Value",
  intro: "Royal Kings Premier School offers premium Christian education structured for families seeking lasting value. We believe every child deserves excellence without compromise.",
  points: [
    "Transparent fee structure shared during school tour or admissions consultation",
    "Flexible payment plans discussed with our finance office",
    "Sibling discounts may apply — enquire with admissions",
    "Investment covers tuition, co-curricular access, and pastoral care",
    "Transport routes available for surrounding areas",
  ],
  cta: "Contact admissions or WhatsApp us for the current fee structure.",
};

export const ACADEMICS_CONTENT = {
  intro: "Our CBC-aligned curriculum guides learners from Creche through Grade 9 with competency-based assessment, dedicated teachers, and a clear pathway from early years to junior secondary.",
  stages: [
    { name: "Creche (Baby–Top Class)", ages: "3–5", focus: "Play-based learning, Bible stories, phonics introduction, school readiness", image: LEGACY_IMAGES.family2 },
    { name: "Foundation & Pre-Primary", ages: "5–6", focus: "Literacy foundations, numeracy, creative arts, confidence building", image: LEGACY_IMAGES.classroom },
    { name: "Lower Primary (Grades 1–3)", ages: "6–9", focus: "Core CBC competencies, creativity, Christian character formation", image: LEGACY_IMAGES.students },
    { name: "Upper Primary (Grades 4–6)", ages: "9–12", focus: "STEM integration, research projects, leadership opportunities", image: LEGACY_IMAGES.classroom },
    { name: "Junior Secondary (Grades 7–9)", ages: "12–15", focus: "Exam readiness, mentorship, career awareness, co-curricular mastery", image: LEGACY_IMAGES.campus },
  ],
};

export const CAMPUS_LIFE = {
  intro: "Experience a warm, safe, and vibrant campus where worship, sports, arts, and community come together. Royal Kings Premier School is designed to feel like an extension of home.",
  features: [
    { title: "Daily Devotions", detail: "Morning assembly and Bible teaching rooted in Christian values" },
    { title: "Safe Playgrounds", detail: "Supervised outdoor spaces for physical development and fun" },
    { title: "Nutritious Meals", detail: "Caring kitchen staff serving balanced meals daily" },
    { title: "Family Events", detail: "Open days, sports days, graduation, and talent showcases" },
    { title: "School Transport", detail: "Routes serving Wangige, Lower Kabete, Kikuyu, Gitaru & Uthiru" },
    { title: "Modern Classrooms", detail: "Bright, well-equipped CBC learning environments" },
  ],
};

export const CO_CURRICULAR = {
  intro: "Beyond the classroom, Royal Kings unlocks every child's gifts through world-class co-curricular programmes.",
  programs: [
    { name: "Coding & STEM", detail: "Digital literacy and problem-solving from early primary", icon: "💻" },
    { name: "Music & Worship", detail: "Choir, instruments, and school worship teams", icon: "🎵" },
    { name: "Ballet & Skating", detail: "Grace, discipline, and physical confidence", icon: "🩰" },
    { name: "Archery & Sports", detail: "Football, athletics, and competitive excellence", icon: "🏹" },
    { name: "Arts & Drama", detail: "Creative expression and public speaking", icon: "🎭" },
    { name: "November Talent Camp", detail: "Annual holiday enrichment programme", icon: "⭐" },
  ],
};
