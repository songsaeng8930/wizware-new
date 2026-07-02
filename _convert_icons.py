import re, os, glob, sys
sys.stdout.reconfigure(encoding='utf-8')

# FA icon name → Lucide icon name
ICON_MAP = {
    'gauge-high': 'gauge',
    'users': 'users',
    'fingerprint': 'fingerprint',
    'file-signature': 'file-pen-line',
    'credit-card': 'credit-card',
    'calendar-days': 'calendar-days',
    'briefcase': 'briefcase',
    'building': 'building-2',
    'clipboard-list': 'clipboard-list',
    'layer-group': 'layers',
    'scale-balanced': 'scale',
    'folder-open': 'folder-open',
    'puzzle-piece': 'puzzle',
    'user': 'user',
    'user-plus': 'user-plus',
    'user-tie': 'user-cog',
    'chevron-right': 'chevron-right',
    'chevron-left': 'chevron-left',
    'chevron-down': 'chevron-down',
    'plus': 'plus',
    'magnifying-glass': 'search',
    'search': 'search',
    'rotate-right': 'rotate-cw',
    'circle-question': 'circle-help',
    'circle-info': 'info',
    'angles-left': 'chevrons-left',
    'angles-right': 'chevrons-right',
    'xmark': 'x',
    'pen': 'pencil',
    'trash': 'trash-2',
    'trash-can': 'trash-2',
    'check': 'check',
    'check-double': 'check-check',
    'check-circle': 'circle-check',
    'spinner': 'loader',
    'download': 'download',
    'file-excel': 'file-spreadsheet',
    'floppy-disk': 'save',
    'print': 'printer',
    'clock': 'clock',
    'won-sign': 'banknote',
    'flag': 'flag',
    'coins': 'coins',
    'paper-plane': 'send',
    'sitemap': 'network',
    'list': 'list',
    'expand': 'maximize-2',
    'compress': 'minimize-2',
    'envelope': 'mail',
    'phone': 'phone',
    'grip-lines': 'grip-horizontal',
    'circle-minus': 'circle-minus',
    'utensils': 'utensils',
    'bus': 'bus',
    'handshake': 'handshake',
    'cart-shopping': 'shopping-cart',
}

# FA text size → Lucide w/h classes
SIZE_MAP = {
    'text-[10px]': ('w-2.5', 'h-2.5'),
    'text-xs': ('w-3', 'h-3'),
    'text-sm': ('w-3.5', 'h-3.5'),
    'text-base': ('w-4', 'h-4'),
    'text-lg': ('w-5', 'h-5'),
    'text-xl': ('w-5', 'h-5'),
    'text-2xl': ('w-6', 'h-6'),
    'text-3xl': ('w-8', 'h-8'),
    'text-4xl': ('w-9', 'h-9'),
}

def convert_icon_tag(match):
    """Convert <i class="fa-solid fa-ICON ...classes..."> to <i data-lucide="LUCIDE" class="...">"""
    full_match = match.group(0)
    classes_str = match.group(1)  # everything in class=""
    after_class = match.group(2)  # anything after class="..." but before >
    
    # Extract FA icon name
    fa_match = re.search(r'fa-([\w-]+)', classes_str.replace('fa-solid', '').strip())
    if not fa_match:
        return full_match
    
    fa_name = fa_match.group(1)
    lucide_name = ICON_MAP.get(fa_name, fa_name)  # fallback to same name
    
    # Remove fa-solid and fa-ICON from classes
    remaining = classes_str
    remaining = re.sub(r'\bfa-solid\b', '', remaining)
    remaining = re.sub(r'\bfa-' + re.escape(fa_name) + r'\b', '', remaining)
    
    # Convert size classes
    found_size = False
    for fa_size, (w, h) in SIZE_MAP.items():
        if fa_size in remaining:
            remaining = remaining.replace(fa_size, f'{w} {h}')
            found_size = True
            break
    
    # Default size if none specified
    if not found_size:
        remaining = remaining.strip() + ' w-4 h-4'
    
    # Remove w-4 text-center pattern (FA centering hack)
    remaining = re.sub(r'\bw-4\b(?=.*\btext-center\b)', '', remaining)
    remaining = remaining.replace('text-center', '')
    
    # Clean up extra spaces
    remaining = ' '.join(remaining.split())
    
    # Build new tag
    class_attr = f' class="{remaining}"' if remaining else ''
    after = after_class.strip()
    after_attr = f' {after}' if after else ''
    
    return f'<i data-lucide="{lucide_name}"{class_attr}{after_attr}></i>'

def convert_js_icon_tag(match):
    """Same but for JS template strings using backticks"""
    return convert_icon_tag(match)

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # Pattern: <i class="fa-solid fa-ICON ...">  (with possible other attrs after class)
    # Handle both HTML and JS template literals
    content = re.sub(
        r'<i\s+class="(fa-solid\s+fa-[\w-]+[^"]*)"(\s*[^>]*)></i>',
        convert_icon_tag,
        content
    )
    
    # Handle: <i class="fa-solid fa-ICON ..."></i> (self-closing style sometimes)
    content = re.sub(
        r'<i\s+class="(fa-solid\s+fa-[\w-]+[^"]*)"(\s*[^>]*)>\s*</i>',
        convert_icon_tag,
        content
    )
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

# Process all PHP files
base = 'd:/www/zaemit_plugin'
files = glob.glob(os.path.join(base, '**/*.php'), recursive=True)
changed = []
for f in sorted(files):
    # Skip sidebar.php (handle separately due to PHP variable icons)
    if 'sidebar.php' in f:
        continue
    if process_file(f):
        changed.append(os.path.relpath(f, base))
        print(f'  ✓ {os.path.relpath(f, base)}')

print(f'\nConverted {len(changed)} files')
