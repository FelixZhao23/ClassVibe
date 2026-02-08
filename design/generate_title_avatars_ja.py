#!/usr/bin/env python3
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent
CFG = ROOT / "title_system_ja.json"
OUT = ROOT / "assets" / "avatars_ja"

STYLE = {
    "understand": {
        "bg": ("#79B8FF", "#316BDE"),
        "cape": "#1E3A8A",
        "cloth": "#2563EB",
        "hair": "#F5D08A",
        "eye": "#3B82F6",
        "accent": "#93C5FD",
        "weapon": "book",
        "label": "理解"
    },
    "question": {
        "bg": ("#7EE7D8", "#129486"),
        "cape": "#0F766E",
        "cloth": "#14B8A6",
        "hair": "#F6F1D5",
        "eye": "#0EA5A4",
        "accent": "#99F6E4",
        "weapon": "orb",
        "label": "質問"
    },
    "collab": {
        "bg": ("#B79CFF", "#6C42D6"),
        "cape": "#4C1D95",
        "cloth": "#8B5CF6",
        "hair": "#B46A3D",
        "eye": "#6D28D9",
        "accent": "#DDD6FE",
        "weapon": "sword",
        "label": "協力"
    },
    "engagement": {
        "bg": ("#FFC27D", "#E16A21"),
        "cape": "#9A3412",
        "cloth": "#F97316",
        "hair": "#F2B632",
        "eye": "#F59E0B",
        "accent": "#FCD34D",
        "weapon": "flame",
        "label": "参加"
    },
    "stability": {
        "bg": ("#8BE7B4", "#249B62"),
        "cape": "#166534",
        "cloth": "#22C55E",
        "hair": "#DDE5F0",
        "eye": "#16A34A",
        "accent": "#A7F3D0",
        "weapon": "shield",
        "label": "安定"
    },
    "hybrid": {
        "bg": ("#FFE486", "#D88A00"),
        "cape": "#7C4A00",
        "cloth": "#F59E0B",
        "hair": "#F5E7C4",
        "eye": "#CA8A04",
        "accent": "#FDE68A",
        "weapon": "spear",
        "label": "複合"
    },
    "common": {
        "bg": ("#CCD5E3", "#64748B"),
        "cape": "#334155",
        "cloth": "#64748B",
        "hair": "#ECEFF4",
        "eye": "#334155",
        "accent": "#E2E8F0",
        "weapon": "star",
        "label": "共通"
    },
}


def branch_of(avatar_id: str) -> str:
    if avatar_id.startswith("av_u_"):
        return "understand"
    if avatar_id.startswith("av_q_"):
        return "question"
    if avatar_id.startswith("av_c_"):
        return "collab"
    if avatar_id.startswith("av_e_"):
        return "engagement"
    if avatar_id.startswith("av_s_"):
        return "stability"
    if avatar_id.startswith("av_hy_"):
        return "hybrid"
    return "common"


def tier_of(avatar_id: str) -> int:
    if "_10" in avatar_id:
        return 1
    if "_15" in avatar_id:
        return 2
    if "_20" in avatar_id:
        return 3
    if "_25" in avatar_id:
        return 4
    if "_30" in avatar_id:
        return 5
    if "common_01" in avatar_id:
        return 1
    if "common_02" in avatar_id:
        return 2
    if "common_03" in avatar_id:
        return 3
    if "common_04" in avatar_id:
        return 4
    return 5


def hair_shape(tier: int, hair: str) -> str:
    tuft = 4 + tier
    return f'''
<path d="M46 66 C52 46 70 40 80 42 C92 38 108 46 114 66 L114 73 L46 73 Z" fill="{hair}" stroke="#1B1B1B" stroke-width="1.6"/>
<path d="M74 49 C77 {44-tuft} 84 {44-tuft} 88 49" fill="none" stroke="{hair}" stroke-width="8" stroke-linecap="round"/>
'''


def weapon_svg(kind: str, tier: int, accent: str) -> str:
    if kind == "sword":
        return f'<g><rect x="30" y="86" width="5" height="26" rx="2" fill="#9CA3AF"/><polygon points="27,86 35,86 33,62 29,62" fill="#E5E7EB"/><rect x="26" y="84" width="10" height="3" rx="1" fill="#D97706"/></g>'
    if kind == "shield":
        return '<g><path d="M31 73 L45 80 L43 98 C41 106 36 111 31 114 C26 111 21 106 19 98 L17 80 Z" fill="#E2E8F0" stroke="#1B1B1B" stroke-width="1.5"/></g>'
    if kind == "book":
        return '<g><rect x="20" y="82" width="18" height="14" rx="2" fill="#EFF6FF" stroke="#1B1B1B" stroke-width="1.4"/><line x1="29" y1="82" x2="29" y2="96" stroke="#1E3A8A" stroke-width="1.1"/></g>'
    if kind == "orb":
        return f'<g><circle cx="30" cy="88" r="8" fill="{accent}" stroke="#1B1B1B" stroke-width="1.4"/><circle cx="27" cy="85" r="2" fill="#FFFFFF"/></g>'
    if kind == "flame":
        return '<g><path d="M30 98 C22 94 24 82 30 76 C36 82 38 94 30 98 Z" fill="#FDE047" stroke="#1B1B1B" stroke-width="1.3"/></g>'
    if kind == "spear":
        return '<g><rect x="29" y="70" width="3.5" height="38" rx="1" fill="#7C2D12"/><polygon points="25,70 36,70 30.5,59" fill="#E5E7EB" stroke="#1B1B1B" stroke-width="1.2"/></g>'
    return '<g><polygon points="30,78 33,85 40,86 35,91 36,98 30,94 24,98 25,91 20,86 27,85" fill="#FDE68A" stroke="#1B1B1B" stroke-width="1.2"/></g>'


def gear_svg(branch: str, tier: int, accent: str) -> str:
    shoulder = '<path d="M58 90 L49 96 L56 102 L66 95 Z" fill="#B45309" stroke="#1B1B1B" stroke-width="1.2"/><path d="M102 90 L111 96 L104 102 L94 95 Z" fill="#B45309" stroke="#1B1B1B" stroke-width="1.2"/>'
    belt = '<rect x="62" y="104" width="36" height="6" rx="2" fill="#7C3AED" stroke="#1B1B1B" stroke-width="1.1"/><circle cx="80" cy="107" r="2.4" fill="#FDE68A"/>'
    cloak_pin = f'<circle cx="80" cy="88" r="3.6" fill="{accent}" stroke="#1B1B1B" stroke-width="1.1"/>'
    sash = '<path d="M68 90 L84 112 L88 112 L72 90 Z" fill="#1F2937" opacity="0.45"/>'
    t3 = shoulder if tier >= 3 else ""
    t4 = belt if tier >= 4 else ""
    t5 = f'<path d="M57 86 Q80 70 103 86" stroke="{accent}" stroke-width="2" fill="none" opacity="0.9"/>' if tier >= 5 else ""

    # branch-specific emblem
    if branch == "understand":
        emblem = '<rect x="76.8" y="86.8" width="6.4" height="4.4" rx="1" fill="#DBEAFE" stroke="#1B1B1B" stroke-width="0.8"/>'
    elif branch == "question":
        emblem = '<text x="80" y="91.2" text-anchor="middle" font-size="5.2" font-family="Arial" fill="#0F172A">?</text>'
    elif branch == "collab":
        emblem = '<path d="M76 87 L79 90 L82 87 L85 90 L82 93 L79 90 L76 93 L73 90 Z" fill="#E9D5FF" stroke="#1B1B1B" stroke-width="0.8"/>'
    elif branch == "engagement":
        emblem = '<path d="M80 85 L82 89 L86 90 L83 93 L84 97 L80 95 L76 97 L77 93 L74 90 L78 89 Z" fill="#FDE68A" stroke="#1B1B1B" stroke-width="0.8"/>'
    elif branch == "stability":
        emblem = '<path d="M80 85 L84 87 L83.4 92 C82.8 94 81.5 95 80 95.6 C78.5 95 77.2 94 76.6 92 L76 87 Z" fill="#BBF7D0" stroke="#1B1B1B" stroke-width="0.8"/>'
    else:
        emblem = '<polygon points="80,85 82,89 86,90 83,93 84,97 80,95 76,97 77,93 74,90 78,89" fill="#FDE68A" stroke="#1B1B1B" stroke-width="0.8"/>'

    return f'<g>{sash}{cloak_pin}{emblem}{t3}{t4}{t5}</g>'


def tier_marks(tier: int, accent: str) -> str:
    parts = []
    for i in range(tier):
        x = 49 + i * 12
        parts.append(f'<polygon points="{x},18 {x+2},23 {x+7},24 {x+3},27.5 {x+4},32.5 {x},30 {x-4},32.5 {x-3},27.5 {x-7},24 {x-2},23" fill="#FDE68A"/>')
    if tier >= 4:
        parts.append(f'<rect x="132" y="14" width="16" height="9" rx="3" fill="#111827" opacity="0.82"/><text x="140" y="20.6" text-anchor="middle" font-size="5.2" font-family="Arial" fill="{accent}">SSR</text>')
    if tier == 5:
        parts.append('<rect x="132" y="26" width="16" height="9" rx="3" fill="#7C2D12" opacity="0.86"/><text x="140" y="32.6" text-anchor="middle" font-size="5.1" font-family="Arial" fill="#FDE68A">UR</text>')
    return "".join(parts)


def title_font_size(text: str) -> float:
    n = len(text)
    if n <= 6:
        return 9.8
    if n <= 9:
        return 9.0
    if n <= 12:
        return 8.2
    return 7.6


def render(avatar_id: str, title_ja: str) -> str:
    branch = branch_of(avatar_id)
    tier = tier_of(avatar_id)
    s = STYLE[branch]
    is_legend = tier >= 5
    title_size = title_font_size(title_ja)
    title_fill = "url(#goldText)" if is_legend else "#F8FAFC"

    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="768" height="768" viewBox="0 0 160 160">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{s['bg'][0]}"/>
      <stop offset="100%" stop-color="{s['bg'][1]}"/>
    </linearGradient>
    <radialGradient id="spot" cx="50%" cy="36%" r="70%">
      <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.36"/>
      <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="goldText" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#FEF9C3"/>
      <stop offset="55%" stop-color="#FDE68A"/>
      <stop offset="100%" stop-color="#F59E0B"/>
    </linearGradient>
    <filter id="shadow" x="-30%" y="-30%" width="160%" height="160%">
      <feDropShadow dx="0" dy="1.2" stdDeviation="1.0" flood-color="#000" flood-opacity="0.35"/>
    </filter>
    <path id="titleArc" d="M 20 149 Q 80 141 140 149"/>
  </defs>

  <rect width="160" height="160" rx="24" fill="url(#bg)"/>
  <rect x="6" y="6" width="148" height="148" rx="20" fill="none" stroke="#FFFFFF" stroke-opacity="0.30" stroke-width="2.8"/>
  {tier_marks(tier, s['accent'])}
  <ellipse cx="80" cy="82" rx="55" ry="46" fill="url(#spot)"/>

  <!-- character: fantasy chibi hero -->
  <g filter="url(#shadow)">
    <path d="M49 112 L111 112 L107 84 Q80 64 53 84 Z" fill="{s['cape']}" stroke="#1B1B1B" stroke-width="1.8"/>
    <path d="M56 112 L104 112 L101 87 Q80 74 59 87 Z" fill="{s['cloth']}" stroke="#1B1B1B" stroke-width="1.7"/>
    <circle cx="80" cy="86" r="25" fill="#F8D8B0" stroke="#1B1B1B" stroke-width="1.8"/>
    <polygon points="57,86 47,80 57,76" fill="#F8D8B0" stroke="#1B1B1B" stroke-width="1.6"/>
    <polygon points="103,86 113,80 103,76" fill="#F8D8B0" stroke="#1B1B1B" stroke-width="1.6"/>
    {hair_shape(tier, s['hair'])}
    <ellipse cx="71" cy="87" rx="4.4" ry="6" fill="#1F2937"/>
    <ellipse cx="89" cy="87" rx="4.4" ry="6" fill="#1F2937"/>
    <circle cx="70" cy="85" r="1.4" fill="#FFFFFF"/>
    <circle cx="88" cy="85" r="1.4" fill="#FFFFFF"/>
    <circle cx="72" cy="89" r="1" fill="{s['eye']}"/>
    <circle cx="90" cy="89" r="1" fill="{s['eye']}"/>
    <path d="M72 97 Q80 102 88 97" stroke="#1B1B1B" stroke-width="2.2" fill="none" stroke-linecap="round"/>
    <circle cx="80" cy="80" r="2.2" fill="#EAB308"/>
    <path d="M77 80 L80 77 L83 80" stroke="#CA8A04" stroke-width="1.2" fill="none"/>
    {gear_svg(branch, tier, s['accent'])}
    {weapon_svg(s['weapon'], tier, s['accent'])}
  </g>

  <rect x="10" y="126" width="140" height="24" rx="10" fill="#0F172A" opacity="0.92"/>
  <text x="80" y="132" text-anchor="middle" font-size="5.8" letter-spacing="0.7" font-family="'Noto Sans JP','Yu Gothic',Arial" fill="{s['accent']}">{s['label']} ルート</text>
  <text font-size="{title_size}" letter-spacing="0.22" font-weight="700" font-family="'Noto Serif JP','Yu Mincho',serif" fill="{title_fill}" stroke="#FFFFFF" stroke-opacity="0.26" stroke-width="0.22">
    <textPath href="#titleArc" startOffset="50%" text-anchor="middle">{title_ja}</textPath>
  </text>
</svg>
'''


def rows(cfg):
    out = []
    for i in cfg.get("common_track", []):
        out.append((i["avatar_id"], i["name_ja"]))
    for b in cfg.get("branches", []):
        for t in b.get("titles", []):
            out.append((t["avatar_id"], t["name_ja"]))
    for h in cfg.get("hybrid_titles", []):
        out.append((h["avatar_id"], h["name_ja"]))
    return out


def main():
    cfg = json.loads(CFG.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)
    manifest = []

    for avatar_id, title_ja in rows(cfg):
        p = OUT / f"{avatar_id}.svg"
        p.write_text(render(avatar_id, title_ja), encoding="utf-8")
        manifest.append({
            "avatar_id": avatar_id,
            "title_ja": title_ja,
            "branch": branch_of(avatar_id),
            "tier": tier_of(avatar_id),
            "file": str(p.relative_to(ROOT)),
        })

    (OUT / "manifest_ja.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"generated {len(manifest)} fantasy-chibi avatars -> {OUT}")


if __name__ == "__main__":
    main()
