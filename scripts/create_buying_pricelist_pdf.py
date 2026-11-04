"""
Term 1 2027 Learning Requirements — Buying Price List PDF

Buying Price = selling total (unit sell × qty).
Item names follow the original requirements PDF; workbook/storybook
titles use Text Book Centre product names where they differ.
Book unit prices are rounded UP to the nearest KES 5.
"""
import math
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    HRFlowable,
    Image,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

OUT = Path(__file__).resolve().parents[1] / "Learning_Requirements_Buying_Price_List_Term1_2027.pdf"


def r5(x):
    """Round UP to nearest 5 (534→535, 536→540)."""
    if x is None:
        return None
    return int(math.ceil(x / 5) * 5)


# Stationery unit sell prices
U = {
    "tissue": 45,
    "wet_wipes": 160,
    "manilla": 40,
    "luminous": 40,
    "jumbo_crayons": 300,
    "clay": 220,
    "pencil_pkt": 140,
    "pencil_pouch": 380,
    "spring_file": 50,
    "masking_half": 25,
    "ruler": 60,
    "scissors": 60,
    "pritt": 200,
    "drawing_book": 35,
    "scrapbook": 150,
    "colour_pencils": 150,
    "a5_120": 50,
    "a5_200": 70,
    "a4_200": 150,
    "a4_graph": 90,
    "geom_set": 200,
    "story_any": 260,
    "jk_ream": 600,
    "building_blocks": 700,
    "water_paint": 280,
    "queenex_colouring": 300,
}

# Book unit sell prices (TBC / Kenya suppliers), then round up to nearest 5
_RAW = {
    "sound_read_1": 375,
    "sound_read_2": 405,
    "queenex": 450,
    "ladybird_low": 290,
    "ladybird_high": 250,
    "math_g1": 487,
    "math_g2": 534,
    "math_g3": 586,
    "math_g4": 621,
    "math_g5": 632,
    "math_g6": 696,
    "eng_g1": 487,
    "eng_g2": 534,
    "eng_g3": 586,
    "eng_g4": 632,
    "eng_g5": 696,
    "eng_g6": 731,
    "kis_g1": 487,
    "kis_g2": 534,
    "kis_g3": 586,
    "kis_g4": 586,
    "kis_g5": 632,
    "kis_g6": 731,
    "env_g1": 534,
    "env_g2": 586,
    "env_g3": 621,
    "sst_g4": 586,
    "sst_g5": 632,
    "sst_g6": 731,
    "sci_g4": 586,
    "sci_g5": 632,
    "sci_g6": 696,
    "agri_g4": 545,
    "agri_g5": 696,
    "agri_g6": 731,
    "cre_g1": 487,
    "cre_g2": 534,
    "cre_g3": 534,
    "cre_g4": 586,
    "cre_g5": 632,
    "cre_g6": 696,
    "diary_l1_eng": 480,
    "diary_l1_kis": 380,
    "diary_l2_eng": 500,
    "diary_l2_kis": 480,
    "diary_l3_eng": 580,
    "diary_l3_kis": 580,
    "diary_l4_eng": 650,
    "diary_l4_kis": 650,
    "bridges": 450,
    "melodies": 450,
    "daughter_nature": 455,
    "mshale": 450,
    "mji_matarajio": 300,
    "uteuzi_chale": 300,
    "wema_hauozi": 450,
    "pioneer_eng": 1200,
    "pioneer_kis": 1200,
    "pioneer_math": 1200,
    "math_tables": 400,
    "casio": 2530,
    "atlas_access": 1820,
    "atlas_mentor": 1450,
    "kamusi": 1400,
    "dict": 1335,
    "bible": 1102,
    "targeter": 1250,
}
B = {k: r5(v) for k, v in _RAW.items()}

STORY_BRANDS = "Oxford, Longhorn, Access, Pace setter, Storymoja, KLB, Moran"
LOGO = Path(__file__).resolve().parent / "royal_kings_logo.png"
BRAND_PURPLE = colors.HexColor("#6B2D8B")
SCHOOL_NAME = "Royal Kings Premier School LTD"
SCHOOL_ADDR = "Riverside, Lower Kabete, Nairobi"
SCHOOL_EMAIL = "info@royalkingsschools.sc.ke"
SCHOOL_TEL = "+254 719 396 233"
NOTICE = (
    "<b>PURCHASE &amp; PAYMENT INFORMATION</b><br/>"
    "Parents may purchase the listed items through the school admin Brian, "
    "contact <b>0708 225 397</b> for the quoted prices.<br/>"
    "Physical items may be purchased independently and presented to the school "
    "for verification on the opening day. Please do not make payments using "
    "school payment methods — liaise with Brian for the same."
)
DISCLAIMER_G4_9 = (
    "<b>IMPORTANT:</b> Dictionaries, atlases, set books, geometrical sets, "
    "calculator, kamusi and bible should only be purchased if missing or unusable. "
    "Do not replace items that are already available and in good condition."
)


def money(v):
    if v is None:
        return ""
    return f"{v:,.0f}"


def tot(unit, qty):
    if unit is None:
        return None
    return unit * qty


# (class_title, [(section, [(item, qty, unit, brand), ...]), ...])
CLASSES = [
    (
        "PP1 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("Wet Wipes (80 pcs)", 1, U["wet_wipes"], "Soft care, Bella, Hannan"),
                    ("A4 JK Photocopy Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Jumbo Crayons", 1, U["jumbo_crayons"], "MCG, Veda, Crayola, Doms"),
                    ("Modelling clay", 1, U["clay"], "Veda, MCG"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                    ("Pencils", 1, U["pencil_pkt"], "Officepoint, Pelican, Staedtler, Nataraj"),
                ],
            ),
            (
                "B. Story books",
                [
                    (
                        "Ladybird Peter and Jane (1a, 1b, 2a, 2b)",
                        4,
                        B["ladybird_low"],
                        "Ladybird Series",
                    ),
                ],
            ),
            (
                "C. Workbooks",
                [
                    ("Sound and Read Book 1 Revised Edition", 1, B["sound_read_1"], ""),
                    ("Queenex Mathematics Activities Practice Book PP1", 1, B["queenex"], "Queenex"),
                    ("Queenex Language Activities Practice Book PP1", 1, B["queenex"], "Queenex"),
                    ("Queenex Environmental Activities Practice Book PP1", 1, B["queenex"], "Queenex"),
                    ("Queenex CRE Activities Practice Book PP1", 1, B["queenex"], "Queenex"),
                    ("Queenex Creative Activities Practice Book PP1", 1, B["queenex"], "Queenex"),
                ],
            ),
        ],
    ),
    (
        "PP2 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("Wet Wipes", 1, U["wet_wipes"], "Soft care, Bella"),
                    ("A4 Photocopy Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Jumbo Crayons", 1, U["jumbo_crayons"], "MCG, Veda, Crayola, Doms"),
                    ("Pencils", 1, U["pencil_pkt"], "Officepoint, Pelican, Staedtler, Nataraj"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Story books",
                [
                    (
                        "Ladybird Peter and Jane (3a, 3b, 4a, 4b)",
                        4,
                        B["ladybird_low"],
                        "Ladybird Series",
                    ),
                ],
            ),
            (
                "C. Workbooks",
                [
                    ("Sound and Read Book 2 Revised Edition", 1, B["sound_read_2"], ""),
                    ("Queenex Mathematics Activities Practice Book PP2", 1, B["queenex"], "Queenex"),
                    ("Queenex Language Activities Practice Book PP2", 1, B["queenex"], "Queenex"),
                    ("Queenex Environmental Activities Practice Book PP2", 1, B["queenex"], "Queenex"),
                    ("Queenex CRE Activities Practice Book PP2", 1, B["queenex"], "Queenex"),
                    ("Queenex Creative Activities Practice Book PP2", 1, B["queenex"], "Queenex"),
                    ("Queenex Blossom Kiswahili Waridi kitabu cha kwanza", 1, B["queenex"], "Queenex"),
                ],
            ),
        ],
    ),
    (
        "Foundation – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("Wet Wipes", 1, U["wet_wipes"], "Softcare"),
                    ("A4 Photocopy Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Jumbo Crayons", 1, U["jumbo_crayons"], "Jumbo"),
                    ("Pencils", 1, U["pencil_pkt"], "Nataraj, Pelican"),
                    ("Pritt glue 22g", 1, U["pritt"], "Pritt"),
                    ("Masking Tape half inch", 1, U["masking_half"], ""),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                    ("Building Blocks", 1, U["building_blocks"], ""),
                    ("Water Paint", 1, U["water_paint"], "M&G"),
                    ("Modelling Clay (500 grams)", 1, U["clay"], "MG"),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Workbooks",
                [
                    ("Queenex Sound Workbook Foundation", 1, B["queenex"], "Queenex"),
                    ("Queenex Mathematical Activities Foundation", 1, B["queenex"], "Queenex"),
                    ("Queenex Environmental Activities Foundation", 1, B["queenex"], "Queenex"),
                    ("Queenex EKAS Colouring Book", 1, U["queenex_colouring"], "Queenex"),
                ],
            ),
        ],
    ),
    (
        "Grade 1 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("Pencils", 1, U["pencil_pkt"], "Officepoint, Pelican, Staedtler, Nataraj"),
                    ("JK Ream of photocopy Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], "A1"),
                    ("Luminous Paper", 1, U["luminous"], "A1"),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("Ruled books - A5 120 pages", 6, U["a5_120"], ""),
                    ("Squared books - A5 120 pages", 1, U["a5_120"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books",
                [
                    (
                        "Ladybird Peter and Jane (6a, 6b, 7a, 7b, 8a, 8b)",
                        6,
                        B["ladybird_high"],
                        "Ladybird Series",
                    ),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD1", 1, B["math_g1"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD1", 1, B["eng_g1"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD1", 1, B["kis_g1"], "Oxford"),
                    ("Oxford Our Lives Today Environmental Workbook GD1", 1, B["env_g1"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD1", 1, B["cre_g1"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 2 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("Pencils", 1, U["pencil_pkt"], "Officepoint, Pelican, Staedtler, Nataraj"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("Ruled books - A5 120 pages", 6, U["a5_120"], ""),
                    ("Squared books - A5 120 pages", 1, U["a5_120"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books",
                [
                    (
                        "Ladybird Peter and Jane (6a, 6b, 7a, 7b, 8a, 8b)",
                        6,
                        B["ladybird_high"],
                        "Ladybird Series",
                    ),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD2", 1, B["math_g2"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD2", 1, B["eng_g2"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD2", 1, B["kis_g2"], "Oxford"),
                    ("Oxford Our Lives Today Environmental Workbook GD2", 1, B["env_g2"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD2", 1, B["cre_g2"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 3 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("Colour pencils", 1, U["colour_pencils"], "Veda"),
                    ("Drawing book", 1, U["drawing_book"], ""),
                    ("Scrap book", 1, U["scrapbook"], ""),
                    ("Pritt glue 22g", 1, U["pritt"], "Pritt"),
                    ("Ruled books - A5 120 pages", 6, U["a5_120"], ""),
                    ("Squared books - A5 120 pages", 1, U["a5_120"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books",
                [
                    (
                        "Ladybird Peter and Jane (9a, 9b, 10a, 10b, 11a, 11b, 12a, 12b)",
                        8,
                        B["ladybird_high"],
                        "Ladybird Series",
                    ),
                    ("My Reading Diary Lvl 1 (Storymoja)", 1, B["diary_l1_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 1 (Storymoja)", 1, B["diary_l1_kis"], "Storymoja"),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD3", 1, B["math_g3"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD3", 1, B["eng_g3"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD3", 1, B["kis_g3"], "Oxford"),
                    ("Oxford Our Lives Today Environmental Workbook GD3", 1, B["env_g3"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD3", 1, B["cre_g3"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 4 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("Colour pencils", 1, U["colour_pencils"], "Veda"),
                    ("Drawing book", 1, U["drawing_book"], ""),
                    ("Scrap book", 1, U["scrapbook"], ""),
                    ("Pritt glue 22g", 1, U["pritt"], "Pritt"),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Ruled books - A5 200 pages", 9, U["a5_200"], ""),
                    ("Squared books - A5 200 pages", 1, U["a5_200"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    (
                        "Ladybird Peter and Jane (12a, 12b)",
                        2,
                        B["ladybird_high"],
                        "Ladybird Series",
                    ),
                    ("My Reading Diary Lvl 2 (Storymoja)", 1, B["diary_l2_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 2 (Storymoja)", 1, B["diary_l2_kis"], "Storymoja"),
                    ("Access Atlas", 1, B["atlas_access"], "Access"),
                    ("Story books - English (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Story books - Kiswahili (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD4", 1, B["math_g4"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD4", 1, B["eng_g4"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD4", 1, B["kis_g4"], "Oxford"),
                    ("Oxford Our Lives Today Social Studies Workbook GD4", 1, B["sst_g4"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD4", 1, B["cre_g4"], "Oxford"),
                    ("Oxford Everyday Science and Technology Workbook GD4", 1, B["sci_g4"], "Oxford"),
                    ("Oxford Modern Agriculture Workbook GD4", 1, B["agri_g4"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 5 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 1, U["manilla"], "Nataraj, Pelican"),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Geometrical set", 1, U["geom_set"], ""),
                    ("Ruled books - A5 200 pages", 9, U["a5_200"], ""),
                    ("Squared books - A5 200 pages", 1, U["a5_200"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    ("My Reading Diary Lvl 2 (Storymoja)", 1, B["diary_l2_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 2 (Storymoja)", 1, B["diary_l2_kis"], "Storymoja"),
                    ("Story books - English (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Story books - Kiswahili (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Access Atlas", 1, B["atlas_access"], "Access"),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD5", 1, B["math_g5"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD5", 1, B["eng_g5"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD5", 1, B["kis_g5"], "Oxford"),
                    ("Oxford Our Lives Today Social Studies Workbook GD5", 1, B["sst_g5"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD5", 1, B["cre_g5"], "Oxford"),
                    ("Oxford Everyday Science and Technology Workbook GD5", 1, B["sci_g5"], "Oxford"),
                    ("Oxford Modern Agriculture Workbook GD5", 1, B["agri_g5"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 6 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 1, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Geometrical set", 1, U["geom_set"], ""),
                    ("Ruled books - A5 200 pages", 9, U["a5_200"], ""),
                    ("Squared books - A5 200 pages", 1, U["a5_200"], ""),
                    ("Pencil Colours", 1, U["colour_pencils"], "Veda"),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    ("My Reading Diary Lvl 3 (Storymoja)", 1, B["diary_l3_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 3 (Storymoja)", 1, B["diary_l3_kis"], "Storymoja"),
                    ("Story books - English (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Story books - Kiswahili (any 2)", 2, U["story_any"], STORY_BRANDS),
                    ("Access Atlas", 1, B["atlas_access"], "Access"),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Oxford Let's do Maths Workbook GD6", 1, B["math_g6"], "Oxford"),
                    ("Oxford New Progressive English Workbook GD6", 1, B["eng_g6"], "Oxford"),
                    ("Oxford Kiswahili Dadisi Workbook GD6", 1, B["kis_g6"], "Oxford"),
                    ("Oxford Everyday Science and Technology Workbook GD6", 1, B["sci_g6"], "Oxford"),
                    ("Oxford Our Lives Today Social Studies Workbook GD6", 1, B["sst_g6"], "Oxford"),
                    ("Oxford Growing in Christ CRE Workbook GD6", 1, B["cre_g6"], "Oxford"),
                    ("Oxford Modern Agriculture Workbook GD6", 1, B["agri_g6"], "Oxford"),
                ],
            ),
        ],
    ),
    (
        "Grade 7 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], "Nataraj, Pelican"),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("Masking Tape half inch", 2, U["masking_half"], ""),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Geometrical set", 1, U["geom_set"], ""),
                    ("Ruled books - A4 200 pages", 10, U["a4_200"], ""),
                    ("Squared books - A4 200 pages", 1, U["a4_200"], ""),
                    ("A4 graph book", 1, U["a4_graph"], ""),
                    ("Scientific Calculator", 1, B["casio"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    ("My Reading Diary Lvl 3 (Storymoja)", 1, B["diary_l3_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 3 (Storymoja)", 1, B["diary_l3_kis"], "Storymoja"),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                    ("Mentor Atlas", 1, B["atlas_mentor"], "Mentor"),
                    ("Bridges Without Rivers and Other Stories (Access)", 1, B["bridges"], "Access"),
                    ("Melodies of Africa and Other Poems (Access)", 1, B["melodies"], "Access"),
                    ("Daughter of Nature and Other Poems (Access)", 1, B["daughter_nature"], "Access"),
                    ("Mshale wa Matumaini", 1, B["mshale"], ""),
                    ("Mji wa Matarajio (One Planet)", 1, B["mji_matarajio"], "One Planet"),
                    ("Uteuzi wa Chale Lvl 7 (One Planet)", 1, B["uteuzi_chale"], "One Planet"),
                    ("Shara yangu ya Kusoma", 1, None, "Storymoja"),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Storymoja Pioneer English Revision GD7-9", 1, B["pioneer_eng"], "Storymoja"),
                    ("Storymoja Pioneer Kiswahili Revision GD7-9", 1, B["pioneer_kis"], "Storymoja"),
                    ("Storymoja Pioneer Mathematics Revision GD7-9", 1, B["pioneer_math"], "Storymoja"),
                ],
            ),
        ],
    ),
    (
        "Grade 8 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Geometrical set", 1, U["geom_set"], ""),
                    ("Ruled books - A4 200 pages", 10, U["a4_200"], ""),
                    ("Squared books - A4 200 pages", 1, U["a4_200"], ""),
                    ("A4 graph book", 1, U["a4_graph"], ""),
                    ("Mathematical table", 1, B["math_tables"], ""),
                    ("Scientific Calculator", 1, B["casio"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    ("My Reading Diary Lvl 3 (Storymoja)", 1, B["diary_l3_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 3 (Storymoja)", 1, B["diary_l3_kis"], "Storymoja"),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                    ("Mentor Atlas", 1, B["atlas_mentor"], "Mentor"),
                    ("Bridges Without Rivers and Other Stories (Access)", 1, B["bridges"], "Access"),
                    ("Melodies of Africa and Other Poems (Access)", 1, B["melodies"], "Access"),
                    ("Daughter of Nature and Other Poems (Access)", 1, B["daughter_nature"], "Access"),
                    ("Mshale wa Matumaini", 1, B["mshale"], ""),
                    ("Mji wa Matarajio (One Planet)", 1, B["mji_matarajio"], "One Planet"),
                    ("Uteuzi wa Chale Lvl 7 (One Planet)", 1, B["uteuzi_chale"], "One Planet"),
                    ("Wema Hauozi", 1, B["wema_hauozi"], ""),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Storymoja Pioneer English Revision GD7-9", 1, B["pioneer_eng"], "Storymoja"),
                    ("Storymoja Pioneer Kiswahili Revision GD7-9", 1, B["pioneer_kis"], "Storymoja"),
                    ("Storymoja Pioneer Mathematics Revision GD7-9", 1, B["pioneer_math"], "Storymoja"),
                ],
            ),
        ],
    ),
    (
        "Grade 9 – Term 1 2027",
        [
            (
                "A. Items managed at school",
                [
                    ("Tissue Rolls", 4, U["tissue"], "Hannan, Velvex, Bella, Toilex, Neptune"),
                    ("JK Ream Paper", 1, U["jk_ream"], "JK"),
                    ("Manila Paper", 2, U["manilla"], ""),
                    ("Luminous Paper", 2, U["luminous"], ""),
                ],
            ),
            (
                "B. Personal Items (managed by learners)",
                [
                    (
                        "Pencils pouch with pencils, rubber, sharpeners",
                        1,
                        U["pencil_pouch"],
                        "",
                    ),
                    ("30cm ruler", 1, U["ruler"], "Haco"),
                    ("A pair of scissors", 1, U["scissors"], ""),
                    ("Geometrical set", 1, U["geom_set"], ""),
                    ("Ruled books - A4 200 pages", 10, U["a4_200"], ""),
                    ("Squared books - A4 200 pages", 1, U["a4_200"], ""),
                    ("A4 graph book", 1, U["a4_graph"], ""),
                    ("Mathematical table", 1, B["math_tables"], ""),
                    ("Scientific Calculator", 1, B["casio"], ""),
                    ("Spring File", 1, U["spring_file"], ""),
                ],
            ),
            (
                "C. Story books / supplementary books",
                [
                    ("My Reading Diary Lvl 4 (Storymoja)", 1, B["diary_l4_eng"], "Storymoja"),
                    ("Shajara Yangu ya Usomaji Kiwango cha 4 (Storymoja)", 1, B["diary_l4_kis"], "Storymoja"),
                    ("Kamusi ya Karne ya 21", 1, B["kamusi"], "Longhorn"),
                    ("Students Dictionary", 1, B["dict"], ""),
                    ("Good News Bible", 1, B["bible"], ""),
                    ("Mentor Atlas", 1, B["atlas_mentor"], "Mentor"),
                    ("Bridges Without Rivers and Other Stories (Access)", 1, B["bridges"], "Access"),
                    ("Melodies of Africa and Other Poems (Access)", 1, B["melodies"], "Access"),
                    ("Daughter of Nature and Other Poems (Access)", 1, B["daughter_nature"], "Access"),
                    ("Mshale wa Matumaini", 1, B["mshale"], ""),
                    ("Mji wa Matarajio (One Planet)", 1, B["mji_matarajio"], "One Planet"),
                    ("Uteuzi wa Chale Lvl 7 (One Planet)", 1, B["uteuzi_chale"], "One Planet"),
                    ("Wema Hauozi", 1, B["wema_hauozi"], ""),
                ],
            ),
            (
                "D. Workbooks",
                [
                    ("Storymoja Pioneer English Revision GD7-9", 1, B["pioneer_eng"], "Storymoja"),
                    ("Storymoja Pioneer Kiswahili Revision GD7-9", 1, B["pioneer_kis"], "Storymoja"),
                    ("Storymoja Pioneer Mathematics Revision GD7-9", 1, B["pioneer_math"], "Storymoja"),
                ],
            ),
        ],
    ),
]


def branded_header(class_title, styles, content_width):
    """Logo + school details sized for A4 print margins."""
    name_style = ParagraphStyle(
        "SchoolName",
        parent=styles["Normal"],
        fontSize=11,
        textColor=BRAND_PURPLE,
        fontName="Helvetica-Bold",
        leading=13,
        alignment=TA_LEFT,
    )
    detail_style = ParagraphStyle(
        "SchoolDetail",
        parent=styles["Normal"],
        fontSize=8,
        textColor=colors.HexColor("#444444"),
        leading=10,
        alignment=TA_LEFT,
    )
    class_style = ParagraphStyle(
        "ClassHead",
        parent=styles["Normal"],
        fontSize=11,
        textColor=BRAND_PURPLE,
        fontName="Helvetica-Bold",
        spaceBefore=1,
        spaceAfter=2,
        alignment=TA_CENTER,
    )
    doc_style = ParagraphStyle(
        "DocLabel",
        parent=styles["Normal"],
        fontSize=8,
        textColor=colors.HexColor("#555555"),
        alignment=TA_CENTER,
        spaceAfter=2,
    )

    logo = Image(str(LOGO), width=16 * mm, height=14 * mm) if LOGO.exists() else Spacer(1, 14 * mm)
    info = [
        Paragraph(SCHOOL_NAME, name_style),
        Paragraph(f"{SCHOOL_ADDR}<br/>{SCHOOL_EMAIL} | Tel: {SCHOOL_TEL}", detail_style),
        Paragraph("Kindergarten | Primary | Junior Secondary · A Sure Foundation", detail_style),
    ]
    header = Table([[logo, info]], colWidths=[20 * mm, content_width - 20 * mm])
    header.setStyle(
        TableStyle(
            [
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                ("LEFTPADDING", (0, 0), (-1, -1), 0),
                ("RIGHTPADDING", (0, 0), (-1, -1), 0),
                ("TOPPADDING", (0, 0), (-1, -1), 0),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
            ]
        )
    )
    return [
        header,
        Spacer(1, 1.5 * mm),
        HRFlowable(width="100%", thickness=1.2, color=BRAND_PURPLE, spaceBefore=0, spaceAfter=2),
        Paragraph("LEARNING REQUIREMENTS — BUYING PRICE LIST", doc_style),
        Paragraph(class_title, class_style),
    ]


def make_section_table(sec_name, items, styles_pack, col_widths):
    """Build one requirements section table; returns (table, section_total)."""
    section_style, cell_style, brand_style, sec_colors = styles_pack
    prefix = sec_name[:2]
    banner = sec_colors.get(prefix, colors.HexColor("#455A64"))

    data = [
        [
            Paragraph(f"<b>{sec_name}</b>", section_style),
            Paragraph("<b>Qty</b>", section_style),
            Paragraph("<b>Price<br/>(KES)</b>", section_style),
            Paragraph("<b>Brand</b>", section_style),
        ]
    ]
    sec_total = 0
    for item, qty, unit, brand in items:
        t = tot(unit, qty)
        if t is not None:
            sec_total += t
        data.append(
            [
                Paragraph(item, cell_style),
                Paragraph(str(qty), cell_style),
                Paragraph(money(t), cell_style),
                Paragraph(brand or "", brand_style),
            ]
        )
    data.append(
        [
            Paragraph("<b>Section total</b>", cell_style),
            "",
            Paragraph(f"<b>{money(sec_total)}</b>", cell_style),
            "",
        ]
    )

    table = Table(data, colWidths=col_widths, repeatRows=1)
    cmds = [
        ("BACKGROUND", (0, 0), (-1, 0), banner),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("BACKGROUND", (0, -1), (-1, -1), colors.HexColor("#F0F0F0")),
        ("ALIGN", (1, 0), (2, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("GRID", (0, 0), (-1, -1), 0.35, colors.HexColor("#CCCCCC")),
        ("LEFTPADDING", (0, 0), (-1, -1), 2.5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 2.5),
        ("TOPPADDING", (0, 0), (-1, -1), 2),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2),
    ]
    for r in range(1, len(data) - 1):
        if r % 2 == 0:
            cmds.append(("BACKGROUND", (0, r), (-1, r), colors.HexColor("#FAFAFA")))
        if data[r][2].text == "":
            cmds.append(("BACKGROUND", (2, r), (2, r), colors.HexColor("#FFF8E1")))
    if prefix == "B." and "Personal" in sec_name:
        for r in range(1, len(data) - 1):
            cmds.append(("BACKGROUND", (0, r), (0, r), colors.HexColor("#E3F2FD")))
    table.setStyle(TableStyle(cmds))
    return table, sec_total


def build():
    styles = getSampleStyleSheet()
    section_style = ParagraphStyle(
        "SecHead",
        parent=styles["Normal"],
        fontSize=8,
        textColor=colors.HexColor("#FFFFFF"),
        leading=10,
    )
    cell_style = ParagraphStyle(
        "Cell",
        parent=styles["Normal"],
        fontSize=7.5,
        leading=9,
    )
    brand_style = ParagraphStyle(
        "Brand",
        parent=styles["Normal"],
        fontSize=6.5,
        textColor=colors.HexColor("#333333"),
        leading=8,
    )
    notice_style = ParagraphStyle(
        "Notice",
        parent=styles["Normal"],
        fontSize=7.5,
        textColor=colors.HexColor("#333333"),
        leading=9.5,
        spaceBefore=2,
        alignment=TA_LEFT,
    )
    disclaimer_style = ParagraphStyle(
        "Disclaimer",
        parent=styles["Normal"],
        fontSize=7.5,
        textColor=BRAND_PURPLE,
        leading=9.5,
        spaceBefore=2,
        alignment=TA_LEFT,
    )
    total_style = ParagraphStyle(
        "Total",
        parent=styles["Normal"],
        fontSize=9,
        textColor=BRAND_PURPLE,
        fontName="Helvetica-Bold",
        spaceBefore=2,
        spaceAfter=2,
    )

    sec_colors = {
        "A.": colors.HexColor("#2E7D32"),
        "B.": colors.HexColor("#1565C0"),
        "C.": colors.HexColor("#6A1B9A"),
        "D.": colors.HexColor("#E65100"),
    }
    styles_pack = (section_style, cell_style, brand_style, sec_colors)

    # Standard A4 print margins (~15 mm / ~0.6")
    margin = 15 * mm

    # PP1=0, PP2=1, Foundation=2, Grade 1=3 … Grade 4=6 … Grade 9=11
    G4_9_INDEXES = set(range(6, 12))
    # Use 2 columns when a class has many line items
    TWO_COL_MIN_ITEMS = 22

    doc = SimpleDocTemplate(
        str(OUT),
        pagesize=A4,
        leftMargin=margin,
        rightMargin=margin,
        topMargin=12 * mm,
        bottomMargin=12 * mm,
    )
    content_w = doc.width
    story = []

    full_cols = [content_w * 0.50, content_w * 0.08, content_w * 0.14, content_w * 0.28]
    gap = 3 * mm
    half_w = (content_w - gap) / 2
    half_cols = [half_w * 0.48, half_w * 0.10, half_w * 0.16, half_w * 0.26]

    def stack_column(tables, width):
        """One outer table per column — avoids KeepTogether wrap bugs."""
        rows = [[t] for t in tables]
        col = Table(rows, colWidths=[width])
        col.setStyle(
            TableStyle(
                [
                    ("VALIGN", (0, 0), (-1, -1), "TOP"),
                    ("LEFTPADDING", (0, 0), (-1, -1), 0),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 0),
                    ("TOPPADDING", (0, 0), (-1, -1), 0),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 1.2 * mm),
                ]
            )
        )
        return col

    for idx, (class_title, sections) in enumerate(CLASSES):
        item_count = sum(len(items) for _, items in sections)
        use_two_cols = item_count >= TWO_COL_MIN_ITEMS

        blocks = list(branded_header(class_title, styles, content_w))
        class_total = 0
        built = []  # (table, item_count)

        for sec_name, items in sections:
            widths = half_cols if use_two_cols else full_cols
            table, sec_total = make_section_table(sec_name, items, styles_pack, widths)
            class_total += sec_total
            built.append((table, len(items)))

        if use_two_cols:
            # Balance by item count so both columns stay similar height
            left_tables, right_tables = [], []
            left_n = right_n = 0
            for tbl, n in built:
                if left_n <= right_n:
                    left_tables.append(tbl)
                    left_n += n
                else:
                    right_tables.append(tbl)
                    right_n += n
            if not right_tables and len(left_tables) > 1:
                right_tables.append(left_tables.pop())
            twin = Table(
                [
                    [
                        stack_column(left_tables, half_w),
                        "",
                        stack_column(right_tables, half_w),
                    ]
                ],
                colWidths=[half_w, gap, half_w],
            )
            twin.setStyle(
                TableStyle(
                    [
                        ("VALIGN", (0, 0), (-1, -1), "TOP"),
                        ("LEFTPADDING", (0, 0), (-1, -1), 0),
                        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
                        ("TOPPADDING", (0, 0), (-1, -1), 0),
                        ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
                    ]
                )
            )
            blocks.append(twin)
        else:
            for tbl, _ in built:
                blocks.append(tbl)
                blocks.append(Spacer(1, 2 * mm))

        blocks.append(Spacer(1, 1.5 * mm))
        blocks.append(Paragraph(f"Class total: KES {money(class_total)}", total_style))
        blocks.append(HRFlowable(width="100%", thickness=1, color=BRAND_PURPLE, spaceBefore=1, spaceAfter=3))
        blocks.append(Paragraph(NOTICE, notice_style))
        if idx in G4_9_INDEXES:
            blocks.append(Paragraph(DISCLAIMER_G4_9, disclaimer_style))

        story.extend(blocks)
        if idx != len(CLASSES) - 1:
            story.append(PageBreak())

    doc.build(story)
    print(OUT)


if __name__ == "__main__":
    build()
