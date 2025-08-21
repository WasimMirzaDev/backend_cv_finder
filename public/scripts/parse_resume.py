import sys
import os
import pytesseract
import json
from pdf2image import convert_from_path

# Function: Extract text from PDF (OCR per page)
def extract_text_from_pdf(file_path):
    text_output = ""
    try:
        pages = convert_from_path(file_path)
        for i, page in enumerate(pages):
            text = pytesseract.image_to_string(page)
            text_output += f"\n--- Page {i+1} ---\n{text}"
        return text_output
    except Exception as e:
        return f"Error during OCR: {str(e)}"

# 🧪 MAIN ENTRY POINT
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({
            "error": "Usage: python parse_resume.py <file_path>"
        }))
        sys.exit(1)

    file_path = sys.argv[1]

    if not os.path.exists(file_path):
        print(json.dumps({
            "error": "File not found"
        }))
        sys.exit(1)

    extracted_text = extract_text_from_pdf(file_path)

    # Print valid JSON so Laravel can parse it
    safe_text = extracted_text.encode('utf-8', 'replace').decode('utf-8', 'ignore')

    print(json.dumps({
        "raw_text": safe_text
    }, ensure_ascii=False))

