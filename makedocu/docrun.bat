rem *** Express-Tool: Einfach den ganzen Ordner makedocu in das Verzeichnis der *.MD-Datei kopieren
rem *** Evtl. aus _light oder _medium die Default-CSS in diese Verzeichnis kopieren und 
rem *** USAGE: makedocu/docrun <infile> (*.md, with or without extension)
rem *** erzeugt dann PDF und HTML 


set MDBase=%~n1
set SD=%~dp0

pandoc %MDBase%.md --from gfm+alerts --css=flavoured.css --standalone -o %MDBase%.html

pandoc %MDBase%.md -f gfm+alerts --lua-filter="%SD%github-alerts.lua" --pdf-engine=lualatex -H "%SD%preamble.tex" --metadata-file="%SD%commonpdf.yml" -o %MDBase%.pdf

copy "%SD%flavoured.css" .

