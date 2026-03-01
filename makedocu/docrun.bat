@echo off
echo "USAGE: makedocu/docrun <infile> (*.md, with or without extension) [-h] [-p]"
echo "       Args: -h : HTML outfile, -p : PDF outfile"

set MDBase=%~dpn1
set SD=%~dp0
set DO_HTML=0
set DO_PDF=0

for %%A in (%*) do (
    if /i "%%A"=="-h" set DO_HTML=1
    if /i "%%A"=="-p" set DO_PDF=1
)

if %DO_HTML%==1 (
    echo "Generate HTML documentation from %MDBase%.md"
    pandoc %MDBase%.md --from gfm+alerts --resource-path="%~dp1." --css=flavoured.css --standalone -o %MDBase%.html
    copy "%SD%flavoured.css" "%~dp1."
)

if %DO_PDF%==1 (
    echo "Generate PDF documentation from %MDBase%.md"
    pandoc %MDBase%.md -f gfm+alerts --resource-path="%~dp1." --lua-filter="%SD%github-alerts.lua" --pdf-engine=lualatex -H "%SD%preamble.tex" --metadata-file="%SD%commonpdf.yml" -o %MDBase%.pdf
)
echo "Done."

