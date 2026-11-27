import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np
from load import load_raw
from render import render
OUT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/pregled/'
for n in sys.argv[1:]:
    V,F,P=load_raw(n)
    red=P=='8'
    parts=[(V,F[~red],1),(V,F[red],2)]
    print(n,render(parts,OUT+f'{n}-ravna.png'))
