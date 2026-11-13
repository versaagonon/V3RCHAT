from fontTools.ttLib import TTFont
import glob, os

for f in sorted(glob.glob('frontend/src/assets/fonts/*.woff2')):
    t = TTFont(f)
    name = t['name']
    family = name.getDebugName(1) or ''
    subfamily = name.getDebugName(2) or ''
    full = name.getDebugName(4) or ''
    version = name.getDebugName(5) or ''
    cmap = t.getBestCmap()
    print(os.path.basename(f))
    print('  family:', family, '| subfamily:', subfamily, '| full:', full, '| version:', version)
    print('  weightClass:', t['OS/2'].usWeightClass, '| glyphs:', len(t.getGlyphOrder()), '| codepoints:', len(cmap))
    print()
