-- Lua-Filter für GitHub-Alerts in LaTeX/PDF
-- Pandoc mit gfm+alerts konvertiert automatisch zu Div-Elementen
function Div(el)
  -- Erkenne Alert-Typ anhand der Klasse
  local latex_env = nil
  
  if el.classes:includes("note") then
    latex_env = "notebox"
  elseif el.classes:includes("tip") then
    latex_env = "tipbox"
  elseif el.classes:includes("important") then
    latex_env = "importantbox"
  elseif el.classes:includes("warning") then
    latex_env = "warningbox"
  elseif el.classes:includes("caution") then
    latex_env = "cautionbox"
  end
  
  if latex_env then
    -- Extrahiere den Inhalt (ohne den Titel-Div)
    local new_content = pandoc.List()
    for i, block in ipairs(el.content) do
      -- Überspringe den title-Div (normalerweise das erste Element)
      if not (block.t == "Div" and block.classes:includes("title")) then
        new_content:insert(block)
      end
    end
    
    -- Erstelle LaTeX RawBlock
    local latex_begin = pandoc.RawBlock('latex', '\\begin{' .. latex_env .. '}')
    local latex_end = pandoc.RawBlock('latex', '\\end{' .. latex_env .. '}')
    
    -- Kombiniere alles
    local result = pandoc.List()
    result:insert(latex_begin)
    result:extend(new_content)
    result:insert(latex_end)
    
    return result
  end
  
  return el
end
