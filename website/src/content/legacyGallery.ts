/**
 * Curated photos extracted from https://royalkingsschools.sc.ke/Gallery.html
 * and other legacy pages. Hot-linked from the live legacy CDN until migrated to CMS.
 */

const BASE = "https://royalkingsschools.sc.ke/assets/images";

export function legacyImg(path: string): string {
  return `${BASE}/${path}`;
}

/** Page-specific hero & editorial images — each section gets a different photo */
export const LEGACY_HEROES = {
  homeMural: legacyImg("whatsapp-image-2024-10-28-at-18.13.54-cb80dc6a-705x999.jpg"),
  empowering: legacyImg("325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg"),
  mission: legacyImg("fb-img-1713928866746-1036x691.jpg"),
  aboutWelcome: legacyImg("326278193-5858633464182480-7349085187052583899-n-2048x1365.jpg"),
  aboutFacilities: legacyImg("screenshot-2024-04-16-162903-1101x619.png"),
  fees: legacyImg("332419888-1246340212647102-4730361110570400332-n-1101x734.jpeg"),
  admissions: legacyImg("326278193-5858633464182480-7349085187052583899-n-1695x1130.jpg"),
  admissionsPromo: legacyImg("2025-admissions-815x815.png"),
  novemberCamp: legacyImg("349297852-270039605413050-4816736476140741163-n-2048x1365.jpg"),
  classroom: legacyImg("418895251-888303299961455-2787170897480080371-n-1920x1280.jpg"),
  playground: legacyImg("353656422-744351687689951-6059922830462771828-n-1-2048x1365.jpg"),
  sports: legacyImg("424586410-911309330994185-5108768823401027837-n-2048x1365.jpg"),
  arts: legacyImg("441539411-1116959156184234-4545625582975216690-n-1020x765.jpg"),
  transport: legacyImg("355824845-750731427051977-8166312862093221759-n-2048x1365.jpg"),
  devotions: legacyImg("333831088-591139956404203-1995073253231790586-n-2048x1365.jpg"),
  earlyYears: legacyImg("339086991-515149154156100-4607853730272201698-n-960x720.jpg"),
  primary: legacyImg("421837351-897799755678476-4674695205872222493-n-1920x1280.jpg"),
  junior: legacyImg("434473409-945757164216068-4716698907512283835-n-2048x1365.jpg"),
  talent: legacyImg("20241024-img-1319-5184x3456.jpg"),
  community: legacyImg("377541946-804798248311961-8642644574650503623-n-2048x1365.jpg"),
};

export type LegacyGalleryPhoto = {
  src: string;
  title: string;
  caption: string;
  category?: string;
};

/** Full gallery — 36 unique activity photos from the legacy site */
export const LEGACY_GALLERY: LegacyGalleryPhoto[] = [
  { src: legacyImg("20241024-img-1132-5184x3456.jpg"), title: "Learning is Fun", caption: "Hands-on discovery in our Wangige classrooms", category: "classroom" },
  { src: legacyImg("20241024-img-1194-5184x3456.jpg"), title: "Focused Learners", caption: "CBC-aligned lessons that spark curiosity", category: "classroom" },
  { src: legacyImg("20241024-img-1196-5184x3456.jpg"), title: "Collaborative Work", caption: "Teamwork and cooperation every day", category: "classroom" },
  { src: legacyImg("20241024-img-1225-5184x3456.jpg"), title: "Creative Expression", caption: "Arts and imagination in action", category: "arts" },
  { src: legacyImg("20241024-img-1236-5184x3456.jpg"), title: "Outdoor Play", caption: "Safe, supervised play for healthy development", category: "playground" },
  { src: legacyImg("20241024-img-1243-5184x3456.jpg"), title: "School Community", caption: "A warm family atmosphere since 2006", category: "community" },
  { src: legacyImg("20241024-img-1254-5184x3456.jpg"), title: "Active Minds", caption: "Building confidence through participation", category: "classroom" },
  { src: legacyImg("20241024-img-1276-5184x3456.jpg"), title: "Joyful Learning", caption: "Where every child is valued and supported", category: "classroom" },
  { src: legacyImg("20241024-img-1286-5184x3456.jpg"), title: "Growing Together", caption: "Peer learning and friendship", category: "community" },
  { src: legacyImg("20241024-img-1308-5184x3456.jpg"), title: "Campus Life", caption: "Vibrant days at Royal Kings Wangige", category: "campus" },
  { src: legacyImg("20241024-img-1314-5184x3456.jpg"), title: "Exploration", caption: "Discovery beyond the textbook", category: "classroom" },
  { src: legacyImg("20241024-img-1319-5184x3456.jpg"), title: "Talent & Performance", caption: "November Camp and school showcases", category: "events" },
  { src: legacyImg("20241024-img-1335-5184x3456.jpg"), title: "Young Achievers", caption: "Celebrating effort and excellence", category: "events" },
  { src: legacyImg("20241024-img-1584-5184x3456.jpg"), title: "Classroom Moments", caption: "Dedicated teachers guiding every learner", category: "classroom" },
  { src: legacyImg("img-0094-2-5184x3456.jpg"), title: "Morning Energy", caption: "Ready to learn and grow", category: "campus" },
  { src: legacyImg("img-0151-2-5184x3456.jpg"), title: "Group Activities", caption: "Cooperation and teamwork", category: "classroom" },
  { src: legacyImg("img-0215-2-5184x3456.jpg"), title: "School Spirit", caption: "Proud to be Royal Kings", category: "events" },
  { src: legacyImg("img-0280-2-5184x3456.jpg"), title: "Play & Learn", caption: "Our “Learning is Fun” philosophy in action", category: "playground" },
  { src: legacyImg("314461291-573632808095174-1243437736262671815-n-2048x1365.jpg"), title: "Sports Day", caption: "Athletics, teamwork, and healthy competition", category: "sports" },
  { src: legacyImg("317373843-590549593070162-3939650561048064921-n-2048x1365.jpg"), title: "On the Field", caption: "Football, volleyball, and netball facilities", category: "sports" },
  { src: legacyImg("327046865-657484502789913-536948905286816978-n-2048x1365.jpg"), title: "School Events", caption: "Memorable moments with families", category: "events" },
  { src: legacyImg("331398421-753079789300033-1390116872041811629-n-2048x1365.jpg"), title: "Cultural Activities", caption: "Music, drama, and creative arts", category: "arts" },
  { src: legacyImg("333600012-179542658157119-8596816425554427038-n-2048x1365.jpg"), title: "Assembly Time", caption: "Christian values and morning devotions", category: "devotions" },
  { src: legacyImg("349158830-629255139084772-2533209689668914796-n-2048x1365.jpg"), title: "Early Years", caption: "Nurturing the littlest learners", category: "early-years" },
  { src: legacyImg("353064933-742741094517677-2469003071930098490-n-2048x1365.jpg"), title: "Outdoor Learning", caption: "Fresh air and active play", category: "playground" },
  { src: legacyImg("355281298-750019000456553-4799360971237326078-n-2048x1365.jpg"), title: "Friendship", caption: "A nurturing environment for every child", category: "community" },
  { src: legacyImg("356643154-752649590193494-6543682608398721980-n-2048x1365.jpg"), title: "School Pride", caption: "Uniforms, discipline, and belonging", category: "campus" },
  { src: legacyImg("418932972-888303923294726-3552956428978987932-n-1920x1280.jpg"), title: "STEM & ICT", caption: "Computer rooms and digital literacy", category: "classroom" },
  { src: legacyImg("419207795-888304753294643-7756641471110685063-n-1920x1280.jpg"), title: "Laboratory Learning", caption: "Science, technology, engineering & mathematics", category: "classroom" },
  { src: legacyImg("421894241-897798622345256-6974103216678133088-n-1920x1280.jpg"), title: "Primary Excellence", caption: "Strong foundations for CBC success", category: "primary" },
  { src: legacyImg("424742648-911309887660796-3797725180477908359-n-2048x1365.jpg"), title: "Team Sports", caption: "Building character through sport", category: "sports" },
  { src: legacyImg("425284149-917825643675887-7508335264303464100-n-2048x1365.jpg"), title: "Celebrations", caption: "Graduation, talent shows, and open days", category: "events" },
  { src: legacyImg("430848822-931267018998416-7719776982542194845-n-960x640.jpg"), title: "Happy Learners", caption: "Confidence grows here every day", category: "classroom" },
  { src: legacyImg("441936196-1116957666184383-9222729562541643395-n-1020x765.jpg"), title: "Creative Arts", caption: "Dance, drama, music, and art studios", category: "arts" },
  { src: legacyImg("442510792-1116957376184412-8949845446620167530-n-1020x765.jpg"), title: "Performance", caption: "Stage confidence and public speaking", category: "arts" },
  { src: legacyImg("398048073-839445201513932-2539398949688195547-n-1365x2048.jpg"), title: "Campus Walk", caption: "Riverside Wangige, along the Western Bypass", category: "campus" },
];
