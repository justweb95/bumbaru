import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np
from load import load
from zbuf import zbuffer
from PIL import Image
for n in sys.argv[1:]:
    m=load(n); h=zbuffer(m.vertices,m.faces,0.25)
    np.save(f'../{n}_hm025.npy',h)
    mx=np.nanmax(h); img=np.where(np.isnan(h),0,40+215*h/mx).astype(np.uint8)
    Image.fromarray(img).save(f'../{n}_hm.png')
    vals,c=np.unique(np.round(h[~np.isnan(h)],2),return_counts=True)
    k=c/c.sum()>0.005
    print(n,h.shape,list(zip(vals[k],np.round(c[k]/c.sum(),3))))
