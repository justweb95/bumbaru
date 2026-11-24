"""Iseca figure za pregled seta (rembg, lokalno). Pokretanje:
~/.venvs/rembg/bin/python scripts/scena/iseci.py <izlazni folder>
Ulaz: originalne fotografije iz E:/BUMBARU 3D Stampa/sajt-slike (1200x1200, bez loga)."""
import sys, os
from rembg import remove, new_session
from PIL import Image
SRC = '/mnt/e/BUMBARU 3D Stampa/sajt-slike/'
FOTO = {
    'deda-mraz': 'deda-mraz/bumbaru-deda-mraz-2.jpg',
    'snesko': 'snesko/bumbaru-snesko-1.jpg',
    'irvas': 'irvas/bumbaru-irvas-4.jpg',
    'spiralna': 'spiralna-jelka/bumbaru-spiralna-jelka-3.jpg',
    'jelka': 'jelka-sa-postoljem/bumbaru-jelka-sa-postoljem-2.jpg',
    'tacna': 'tacna/bumbaru-tacna-1.jpg',
}
out = sys.argv[1]
model = sys.argv[2] if len(sys.argv) > 2 else 'isnet-general-use'
os.makedirs(out, exist_ok=True)
s = new_session(model)
for k, f in FOTO.items():
    im = Image.open(SRC + f).convert('RGB')
    r = remove(im, session=s, post_process_mask=True)
    r.save(os.path.join(out, k + '.png'))
    print(k, r.size)
