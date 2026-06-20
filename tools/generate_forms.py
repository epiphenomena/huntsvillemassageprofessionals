#!/usr/bin/env python3
"""
Generate branded, modernized DOCX forms for Huntsville Massage Professionals:
  - Client Intake Form
  - Consent & Service Agreement

Run with python-docx installed (see tools/README or the repo README):
    python tools/generate_forms.py

Design notes:
  * Simplified per request: physical address, employer and home phone removed.
  * Email kept and an explicit email-marketing opt-in checkbox added.
  * Consent wording modernized in tone while preserving the original legal
    intent. THIS LANGUAGE SHOULD BE REVIEWED BY A LICENSED PROFESSIONAL.
"""

import os
from docx import Document
from docx.shared import Pt, RGBColor, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.section import WD_SECTION
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

GREEN = RGBColor(0x2F, 0x4A, 0x3C)
GREEN_DK = RGBColor(0x24, 0x3B, 0x30)
GOLD = RGBColor(0xC9, 0xA8, 0x5C)
GOLD_HEX = "C9A85C"
GREEN_HEX = "2F4A3C"
INK = RGBColor(0x2B, 0x2E, 0x2B)
GREY = RGBColor(0x70, 0x78, 0x72)

PHONE = "256-738-3469"
EMAIL = "huntsvillemassageprofessional@gmail.com"
NAME = "Huntsville Massage Professionals"
TAGLINE = "Refresh your mind, body & soul."


def base_styles(doc):
    normal = doc.styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = INK
    pf = normal.paragraph_format
    pf.space_after = Pt(4)
    pf.line_spacing = 1.06
    for sec in doc.sections:
        sec.top_margin = Inches(0.6)
        sec.bottom_margin = Inches(0.6)
        sec.left_margin = Inches(0.7)
        sec.right_margin = Inches(0.7)


def p_border_bottom(paragraph, color=GOLD_HEX, sz="6", space="1"):
    pPr = paragraph._p.get_or_add_pPr()
    pBdr = OxmlElement("w:pBdr")
    bottom = OxmlElement("w:bottom")
    bottom.set(qn("w:val"), "single")
    bottom.set(qn("w:sz"), sz)
    bottom.set(qn("w:space"), space)
    bottom.set(qn("w:color"), color)
    pBdr.append(bottom)
    pPr.append(pBdr)


def shade(cell, fill):
    tcPr = cell._tc.get_or_add_tcPr()
    sh = OxmlElement("w:shd")
    sh.set(qn("w:val"), "clear")
    sh.set(qn("w:fill"), fill)
    tcPr.append(sh)


def no_table_borders(table):
    tbl = table._tbl
    tblPr = tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        el = OxmlElement(f"w:{edge}")
        el.set(qn("w:val"), "none")
        borders.append(el)
    tblPr.append(borders)


def title_block(doc):
    t = doc.add_paragraph()
    t.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = t.add_run(NAME)
    r.font.size = Pt(20)
    r.font.bold = True
    r.font.color.rgb = GREEN_DK
    t.paragraph_format.space_after = Pt(0)

    s = doc.add_paragraph()
    s.alignment = WD_ALIGN_PARAGRAPH.CENTER
    rs = s.add_run(TAGLINE)
    rs.italic = True
    rs.font.size = Pt(10.5)
    rs.font.color.rgb = GOLD
    s.paragraph_format.space_after = Pt(1)

    c = doc.add_paragraph()
    c.alignment = WD_ALIGN_PARAGRAPH.CENTER
    rc = c.add_run(f"{PHONE} • {EMAIL}")
    rc.font.size = Pt(9)
    rc.font.color.rgb = GREY
    p_border_bottom(c, color=GOLD_HEX, sz="12")
    c.paragraph_format.space_after = Pt(10)


def doc_title(doc, text):
    h = doc.add_paragraph()
    h.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = h.add_run(text)
    r.font.size = Pt(14)
    r.font.bold = True
    r.font.color.rgb = GREEN
    h.paragraph_format.space_after = Pt(8)


def section(doc, text):
    h = doc.add_paragraph()
    h.paragraph_format.space_before = Pt(8)
    h.paragraph_format.space_after = Pt(3)
    r = h.add_run(text.upper())
    r.font.size = Pt(10.5)
    r.font.bold = True
    r.font.color.rgb = GREEN
    rPr = r._element.get_or_add_rPr()
    spc = OxmlElement("w:spacing")
    spc.set(qn("w:val"), "20")
    rPr.append(spc)
    p_border_bottom(h, color=GREEN_HEX, sz="4")


def field_line(doc, label, gap_before=0):
    """A write-in field: bold label, then an underline space for the answer."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(gap_before)
    p.paragraph_format.space_after = Pt(7)
    r = p.add_run(label + "  ")
    r.font.bold = True
    r.font.color.rgb = GREEN_DK
    r.font.size = Pt(9.5)
    # Tab-filled underline to the right margin.
    line = p.add_run("\t")
    pPr = p._p.get_or_add_pPr()
    tabs = OxmlElement("w:tabs")
    tab = OxmlElement("w:tab")
    tab.set(qn("w:val"), "right")
    tab.set(qn("w:leader"), "underscore")
    tab.set(qn("w:pos"), "10440")  # ~7.25in within margins
    tabs.append(tab)
    pPr.append(tabs)
    return p


def fixed_layout(table):
    tblPr = table._tbl.tblPr
    layout = OxmlElement("w:tblLayout")
    layout.set(qn("w:type"), "fixed")
    tblPr.append(layout)


def two_fields(doc, label1, label2):
    """Two write-in fields on one row using a borderless, fixed-width table."""
    table = doc.add_table(rows=1, cols=2)
    no_table_borders(table)
    table.autofit = False
    table.allow_autofit = False
    fixed_layout(table)
    table.columns[0].width = Inches(3.55)
    table.columns[1].width = Inches(3.55)
    for cell, label in ((table.rows[0].cells[0], label1), (table.rows[0].cells[1], label2)):
        cell.width = Inches(3.55)
        # Label line (tight), then a bordered write-in line beneath it.
        cell.paragraphs[0].text = ""
        lp = cell.paragraphs[0]
        lp.paragraph_format.space_after = Pt(1)
        r = lp.add_run(label)
        r.font.bold = True
        r.font.color.rgb = GREEN_DK
        r.font.size = Pt(9.5)
        line = cell.add_paragraph()
        line.paragraph_format.space_after = Pt(8)
        line.add_run(" ")  # give the line height
        p_border_bottom(line, color=GOLD_HEX, sz="6")
    return table


def checkbox_grid(doc, items, cols=3):
    rows = (len(items) + cols - 1) // cols
    table = doc.add_table(rows=rows, cols=cols)
    no_table_borders(table)
    for i, item in enumerate(items):
        cell = table.rows[i // cols].cells[i % cols]
        p = cell.paragraphs[0]
        p.paragraph_format.space_after = Pt(2)
        rb = p.add_run("☐  ")  # ballot box
        rb.font.size = Pt(11)
        rt = p.add_run(item)
        rt.font.size = Pt(9.5)
    return table


def checkbox_line(doc, text, options=("No", "Yes"), trailing=None):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(7)
    r = p.add_run(text + "   ")
    r.font.bold = True
    r.font.color.rgb = GREEN_DK
    r.font.size = Pt(9.5)
    for opt in options:
        rb = p.add_run("☐ ")
        rb.font.size = Pt(11)
        ro = p.add_run(opt + "    ")
        ro.font.size = Pt(9.5)
    if trailing:
        rt = p.add_run("    " + trailing + "  ")
        rt.font.bold = True
        rt.font.color.rgb = GREEN_DK
        rt.font.size = Pt(9.5)
        p.add_run("__________________________")
    return p


def consent_checkbox(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.left_indent = Inches(0.1)
    p.paragraph_format.space_after = Pt(5)
    rb = p.add_run("☐  ")
    rb.font.size = Pt(11)
    rb.font.color.rgb = GREEN
    rt = p.add_run(text)
    rt.font.size = Pt(9.5)
    return p


def body(doc, text, size=9.5, italic=False, color=INK, space_after=5):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(space_after)
    r = p.add_run(text)
    r.font.size = Pt(size)
    r.italic = italic
    r.font.color.rgb = color
    return p


# --------------------------------------------------------------------------
def build_intake(path):
    doc = Document()
    base_styles(doc)
    title_block(doc)
    doc_title(doc, "Client Intake Form")
    body(doc, "Welcome! Please complete this form before your first visit. Your information is "
              "kept private and confidential, and helps your therapist give you the safest, most "
              "effective session. Fields marked * are required.",
         size=9, italic=True, color=GREY, space_after=8)

    section(doc, "Your Information")
    two_fields(doc, "Full name *", "Date of birth")
    two_fields(doc, "Email *", "Cell phone *")
    field_line(doc, "Emergency contact & phone")
    field_line(doc, "How did you hear about us?")
    checkbox_line(doc, "May we email you occasional news, wellness tips & special offers?",
                  options=("Yes, please", "No thank you"))

    section(doc, "Health History")
    field_line(doc, "Allergies (oils, lotions, scents, latex, etc.)")
    field_line(doc, "Current medications")
    body(doc, "Please check anything you have now or have had in the past:", size=9.5, space_after=3)
    checkbox_grid(doc, [
        "Skin problems / rashes", "Frequent headaches", "Asthma",
        "Circulatory problems", "Respiratory problems", "Bruise easily",
        "Varicose veins", "Epilepsy / seizures", "Athlete's foot",
        "HIV/AIDS", "Osteoporosis", "Hepatitis",
        "Cardiac problems", "Pancreatitis", "Cancer",
        "High blood pressure", "Depression / anxiety", "Diabetes",
    ], cols=3)
    field_line(doc, "Other notable medical conditions", gap_before=4)
    field_line(doc, "Recent illnesses, injuries or accidents")
    field_line(doc, "Recent surgeries or broken bones")
    checkbox_line(doc, "Are you currently pregnant?", options=("No", "Yes"), trailing="If yes, due date:")
    field_line(doc, "Reason for today's visit")
    checkbox_line(doc, "Have you had a professional massage before?", options=("Yes", "No"))

    body(doc, "Please review and sign the Consent & Service Agreement (provided separately) before "
              "your session.", size=9, italic=True, color=GREY, space_after=2)

    doc.save(path)


def build_consent(path):
    doc = Document()
    base_styles(doc)
    title_block(doc)
    doc_title(doc, "Consent & Service Agreement")

    body(doc, "Massage therapy is provided for relaxation and relief from muscular tension and "
              "stress. Your therapist does not diagnose illness, perform skeletal adjustments, or "
              "prescribe medication, and massage is not a substitute for medical care.")
    body(doc, "I confirm that I have shared all of my known medical conditions and agree to keep my "
              "therapist updated on any changes to my health. If I feel any pain or discomfort during "
              "a session, I will tell my therapist right away so that pressure and technique can be "
              "adjusted to my comfort.")
    body(doc, "I understand that the therapist assumes no liability beyond the professional standard "
              "of care, and I agree to hold the therapist and establishment harmless from claims "
              "arising from information I have withheld or misrepresented.")

    section(doc, "I understand and agree that")
    consent_checkbox(doc, "The relationship between client and therapist is professional, and all "
                          "information I provide is kept confidential.")
    consent_checkbox(doc, "I will be properly draped at all times for my comfort, security and warmth.")
    consent_checkbox(doc, "Massage is provided solely for therapeutic purposes. Any inappropriate, "
                          "illicit or sexually suggestive remarks or behavior will end the session "
                          "immediately, and the session will be charged in full.")
    consent_checkbox(doc, "My therapist also has the right to a safe, respectful environment, free "
                          "from unwanted, harmful or offensive contact or behavior.")
    consent_checkbox(doc, "I may request that any technique be modified, changed, stopped, or simply "
                          "not performed at any time.")

    section(doc, "Consent")
    body(doc, "By signing below, I confirm that the information I have provided is accurate to the "
              "best of my knowledge. I have read and understand this agreement and freely give my "
              "consent to receive massage therapy, including future sessions.")
    body(doc, "If the client is a minor, I confirm I am the parent or legal guardian and give my "
              "consent on their behalf, having been present for this discussion.",
         size=9, italic=True, color=GREY, space_after=10)

    # Signature lines
    two_fields(doc, "Client (or guardian) signature", "Date")
    two_fields(doc, "Printed name", "Date of birth")
    two_fields(doc, "Therapist signature", "Date")

    body(doc, "This consent and service agreement is provided as a starting point and should be "
              "reviewed by a licensed professional before use.",
         size=8, italic=True, color=GREY, space_after=0)

    doc.save(path)


if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "forms")
    os.makedirs(out_dir, exist_ok=True)
    intake_path = os.path.join(out_dir, "Client-Intake-Form.docx")
    consent_path = os.path.join(out_dir, "Consent-and-Service-Agreement.docx")
    build_intake(intake_path)
    build_consent(consent_path)
    print("Wrote:", intake_path)
    print("Wrote:", consent_path)
