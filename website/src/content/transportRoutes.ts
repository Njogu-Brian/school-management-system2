export type TransportRoute = {
  name: string;
  stops: string[];
  /** Endpoint on the service map, in a 800×560 viewBox. */
  x: number;
  y: number;
  /** Curve the road so routes do not stack on one line. */
  bend: number;
};

export const TRANSPORT_HUB = { x: 400, y: 270 };

export const TRANSPORT_ROUTES: TransportRoute[] = [
  {
    name: "Lower Kabete",
    stops: ["Uon Campus", "Childrens Home", "Kanyongo", "Kmautiini", "Kingeero"],
    x: 600,
    y: 420,
    bend: 40,
  },
  {
    name: "Wangige",
    stops: ["Riverside", "Bluevally", "Kangora", "Muthumu", "Kiawanugu", "Mararo", "Whitehouse"],
    x: 470,
    y: 120,
    bend: -30,
  },
  {
    name: "Cuura",
    stops: ["Gatumumu", "Kahuho", "Kiangotho", "Gakinduri"],
    x: 210,
    y: 110,
    bend: -50,
  },
  {
    name: "Muthure",
    stops: ["Greenbelt", "Rukubi", "Kanyariri", "Gitaru", "Kwa Magu"],
    x: 190,
    y: 410,
    bend: 36,
  },
  {
    name: "Kibichiku",
    stops: ["Mwimuto", "Gathiga", "Kwa Kahawa", "Kwa Rhoda", "Njathaini"],
    x: 400,
    y: 450,
    bend: 28,
  },
  {
    name: "Karura",
    stops: ["Kibiku", "Kirangari", "Karura-Kanyungu", "Kamuremo", "Munyaka", "Kihara", "Ndederu", "Ruaka"],
    x: 630,
    y: 150,
    bend: -24,
  },
  {
    name: "Ngecha",
    stops: ["Ruku", "Kabocha", "Ngecha", "Nyathuna", "Gikuni", "Kagira", "Mukui"],
    x: 150,
    y: 240,
    bend: -20,
  },
];

export function routePath(route: TransportRoute): string {
  const midX = (TRANSPORT_HUB.x + route.x) / 2;
  const midY = (TRANSPORT_HUB.y + route.y) / 2 + route.bend;
  return `M ${TRANSPORT_HUB.x} ${TRANSPORT_HUB.y} Q ${midX} ${midY} ${route.x} ${route.y}`;
}
